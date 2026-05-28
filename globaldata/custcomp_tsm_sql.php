<?php

$complaint_tsm_joins = "
    LEFT JOIN printvis.tsm pick_tsm ON pick_tsm.tsm_num = cd.PICK_TSMNUM
    LEFT JOIN printvis.tsm pack_tsm ON pack_tsm.tsm_num = cd.PACK_TSM
    LEFT JOIN (
        SELECT bs1.batch_start_whse, bs1.batch_start_batch, bs1.batch_start_TSM
        FROM printvis.batch_start bs1
        INNER JOIN (
            SELECT batch_start_whse, batch_start_batch, MAX(batch_start_time) AS max_time
            FROM printvis.batch_start
            GROUP BY batch_start_whse, batch_start_batch
        ) bs2 ON bs1.batch_start_whse = bs2.batch_start_whse
            AND bs1.batch_start_batch = bs2.batch_start_batch
            AND bs1.batch_start_time = bs2.max_time
    ) bs_latest ON bs_latest.batch_start_whse = cd.PICK_WHSE
        AND bs_latest.batch_start_batch = cd.BATCH_NUM
    LEFT JOIN printvis.tsm pack_bs_tsm ON pack_bs_tsm.tsm_num = bs_latest.batch_start_TSM
";

$complaint_pick_tsm_sql = "UPPER(COALESCE(pick_tsm.tsm_name, cd.PICK_TSM, cd.CASEPICK_TSMNAME))";
$complaint_pack_tsm_sql = "UPPER(COALESCE(pack_tsm.tsm_name, pack_bs_tsm.tsm_name, cd.PACK_TSMNAME))";
