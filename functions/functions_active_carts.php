<?php

/**
 * Return whether a PrintVis table exists in the current MySQL instance.
 */
function printvis_table_exists(PDO $conn, $tableName) {
    $query = $conn->prepare("SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'printvis'
          AND table_name = :table_name
        LIMIT 1");
    $query->execute([':table_name' => $tableName]);

    return (bool) $query->fetchColumn();
}

/**
 * Use MySQL's clock for the operational-day cutoff. PHP and MySQL may be in
 * different timezones on the scheduler host.
 */
function printvis_current_print_cutoff(PDO $conn) {
    return $conn->query("SELECT CONCAT(
            DATE_FORMAT(
                CASE
                    WHEN DAYOFWEEK(CURDATE()) = 2 THEN DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                    ELSE DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                END,
                '%Y-%m-%d'
            ),
            ' 17:20:59'
        )")->fetchColumn();
}

/**
 * Rebuild the carts-currently-picking table from the batch-level Lucas start
 * feed. Line-level Lucas data is optional enrichment, not the activation gate.
 */
function printvis_refresh_active_carts(PDO $conn, $printCutoff) {
    foreach (['looselines_batchtime', 'voice_batchespicked', 'batch_start', 'looselines_cartsinprocess'] as $requiredTable) {
        if (!printvis_table_exists($conn, $requiredTable)) {
            throw new RuntimeException("Required PrintVis table is missing: printvis.$requiredTable");
        }
    }

    // The shared connection may not select a default schema; multi-table DELETE requires one.
    $conn->exec("USE printvis");
    $conn->exec("CREATE TABLE IF NOT EXISTS printvis.looselines_cartsinprocess_temp
        LIKE printvis.looselines_cartsinprocess");
    $conn->exec("TRUNCATE TABLE printvis.looselines_cartsinprocess_temp");

    $insertBase = $conn->prepare("INSERT INTO printvis.looselines_cartsinprocess_temp (
            cartpick_whse,
            cartpick_cart,
            cartpick_shipzone,
            cartpick_dateadd,
            cartpick_printdate,
            cartpick_recdate,
            cartpick_totaltime,
            cartpick_currentaisle,
            cartpick_picktime,
            cartpick_remaintime,
            cartpick_elaptime,
            cartpick_status
        )
        SELECT
            B.batchtime_whse,
            B.batchtime_cart,
            B.batchtime_shipzone,
            B.batchtime_datetimeadded,
            B.batchtime_printdatetime,
            B.batchtime_recdate,
            CEIL(COALESCE(B.batchtime_time_totaltime, 0)),
            NULL,
            0,
            CEIL(COALESCE(B.batchtime_time_totaltime, 0)),
            GREATEST(0, TIMESTAMPDIFF(
                MINUTE,
                V.voice_startdatetime,
                CASE
                    WHEN B.batchtime_whse = 3 THEN DATE_SUB(NOW(), INTERVAL 3 HOUR)
                    WHEN B.batchtime_whse = 7 THEN DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ELSE NOW()
                END
            )),
            CASE
                WHEN TIMESTAMPDIFF(
                    MINUTE,
                    V.voice_startdatetime,
                    CASE
                        WHEN B.batchtime_whse = 3 THEN DATE_SUB(NOW(), INTERVAL 3 HOUR)
                        WHEN B.batchtime_whse = 7 THEN DATE_SUB(NOW(), INTERVAL 1 HOUR)
                        ELSE NOW()
                    END
                ) > 2 THEN 'LATE'
                ELSE 'ONTIME'
            END
        FROM printvis.looselines_batchtime B
        INNER JOIN printvis.voice_batchespicked V
            ON V.voice_whse = B.batchtime_whse
            AND V.voice_batch = B.batchtime_cart
        WHERE V.voice_userid > 0
          AND V.voice_startdatetime >= :print_cutoff
          AND B.batchtime_printdatetime >= CASE
              WHEN B.batchtime_whse = 3 THEN DATE_SUB(NOW(), INTERVAL 6 HOUR)
              WHEN B.batchtime_whse = 7 THEN DATE_SUB(NOW(), INTERVAL 4 HOUR)
              ELSE DATE_SUB(NOW(), INTERVAL 3 HOUR)
          END
          AND V.voice_startdatetime >= CASE
              WHEN B.batchtime_whse = 3 THEN DATE_SUB(NOW(), INTERVAL 6 HOUR)
              WHEN B.batchtime_whse = 7 THEN DATE_SUB(NOW(), INTERVAL 4 HOUR)
              ELSE DATE_SUB(NOW(), INTERVAL 3 HOUR)
          END
          AND B.batchtime_count_line > 5
          AND B.batchtime_colgcount / B.batchtime_count_line <= 0.95
          AND B.batchtime_count_ice / B.batchtime_count_line <> 1
          AND NOT EXISTS (
              SELECT 1
              FROM printvis.batch_start P
              WHERE P.batch_start_whse = B.batchtime_whse
                AND P.batch_start_batch = B.batchtime_cart
          )");
    $insertBase->execute([':print_cutoff' => $printCutoff]);

    $hasVoicePicks = printvis_table_exists($conn, 'voicepicks');
    $hasAisleTimes = printvis_table_exists($conn, 'looselines_aisletime');

    if ($hasVoicePicks) {
        $currentAisle = $conn->prepare("UPDATE printvis.looselines_cartsinprocess_temp C
            INNER JOIN (
                SELECT
                    Whse,
                    Batch_Num,
                    MAX(SUBSTR(Location, 1, 3)) AS current_aisle
                FROM printvis.voicepicks
                WHERE ReserveUSerID > 0
                  AND DateTimeFirstPick >= :print_cutoff
                GROUP BY Whse, Batch_Num
            ) P
                ON P.Whse = C.cartpick_whse
                AND P.Batch_Num = C.cartpick_cart
            SET C.cartpick_currentaisle = P.current_aisle");
        $currentAisle->execute([':print_cutoff' => $printCutoff]);
    }

    if ($hasVoicePicks && $hasAisleTimes) {
        $timeExpression = "A.aisletime_time_pickline
            + A.aisletime_time_wcard
            + A.aisletime_time_fridge
            + A.aisletime_time_pullbin
            + A.aisletime_time_unit
            + A.aisletime_time_ladder
            + A.aisletime_time_putline
            + A.aisletime_time_putunit
            + A.aisletime_time_aisletravel";

        $conn->exec("UPDATE printvis.looselines_cartsinprocess_temp C
            SET C.cartpick_picktime = LEAST(
                    C.cartpick_totaltime,
                    COALESCE((
                        SELECT CEIL(SUM($timeExpression))
                        FROM printvis.looselines_aisletime A
                        WHERE A.aisletime_whse = C.cartpick_whse
                          AND A.aisletime_cart = C.cartpick_cart
                          AND A.aisletime_aisle <= C.cartpick_currentaisle
                    ), 0)
                ),
                C.cartpick_remaintime = GREATEST(
                    C.cartpick_totaltime - LEAST(
                        C.cartpick_totaltime,
                        COALESCE((
                            SELECT CEIL(SUM($timeExpression))
                            FROM printvis.looselines_aisletime A
                            WHERE A.aisletime_whse = C.cartpick_whse
                              AND A.aisletime_cart = C.cartpick_cart
                              AND A.aisletime_aisle <= C.cartpick_currentaisle
                        ), 0)
                    ),
                    0
                )
            WHERE C.cartpick_currentaisle IS NOT NULL");

        $conn->exec("DELETE FROM printvis.looselines_cartsinprocess_temp
            WHERE cartpick_remaintime <= 0");
    }

    $conn->exec("DELETE C
        FROM printvis.looselines_cartsinprocess C
        LEFT JOIN printvis.looselines_cartsinprocess_temp T
            ON T.cartpick_whse = C.cartpick_whse
            AND T.cartpick_cart = C.cartpick_cart
        WHERE T.cartpick_cart IS NULL");

    $conn->exec("INSERT INTO printvis.looselines_cartsinprocess
        SELECT *
        FROM printvis.looselines_cartsinprocess_temp
        ON DUPLICATE KEY UPDATE
            cartpick_shipzone = VALUES(cartpick_shipzone),
            cartpick_dateadd = VALUES(cartpick_dateadd),
            cartpick_printdate = VALUES(cartpick_printdate),
            cartpick_recdate = VALUES(cartpick_recdate),
            cartpick_totaltime = VALUES(cartpick_totaltime),
            cartpick_currentaisle = VALUES(cartpick_currentaisle),
            cartpick_picktime = VALUES(cartpick_picktime),
            cartpick_remaintime = VALUES(cartpick_remaintime),
            cartpick_elaptime = VALUES(cartpick_elaptime),
            cartpick_status = VALUES(cartpick_status)");

    $counts = $conn->query("SELECT cartpick_whse AS whse, COUNT(*) AS cart_count
        FROM printvis.looselines_cartsinprocess
        GROUP BY cartpick_whse
        ORDER BY cartpick_whse")->fetchAll(PDO::FETCH_ASSOC);

    return [
        'counts' => $counts,
        'used_voicepicks' => $hasVoicePicks,
        'used_aisletimes' => $hasAisleTimes,
    ];
}
