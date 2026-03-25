<?php

include_once '../connections/connection.php';
require_once '../sessioninclude.php';

header('Content-Type: application/json');

$whse = isset($_SESSION['WHSE']) ? (int) $_SESSION['WHSE'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$loc = isset($_POST['loc']) ? strtoupper(trim($_POST['loc'])) : '';

if ($whse <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Warehouse session is unavailable.'
    ]);
    exit;
}

$cleanup = $conn1->prepare(
    "DELETE FROM printvis.delete_shortsexp
     WHERE delete_whse = :whse
       AND delete_datetime < DATE_SUB(NOW(), INTERVAL 60 MINUTE)"
);
$cleanup->execute([':whse' => $whse]);

try {
    switch ($action) {
        case 'omit':
            if ($loc === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Location is required.'
                ]);
                exit;
            }

            $conn1->beginTransaction();

            $deleteExisting = $conn1->prepare(
                "DELETE FROM printvis.delete_shortsexp
                 WHERE delete_whse = :whse
                   AND delete_loc = :loc"
            );
            $deleteExisting->execute([
                ':whse' => $whse,
                ':loc' => $loc
            ]);

            $insert = $conn1->prepare(
                "INSERT INTO printvis.delete_shortsexp
                    (iddelete_shortsexp, delete_whse, delete_loc, delete_datetime)
                 VALUES
                    (0, :whse, :loc, NOW())"
            );
            $insert->execute([
                ':whse' => $whse,
                ':loc' => $loc
            ]);

            $conn1->commit();
            $message = 'Location ' . $loc . ' omitted for 1 hour.';
            break;

        case 'restore':
            if ($loc === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Location is required.'
                ]);
                exit;
            }

            $restore = $conn1->prepare(
                "DELETE FROM printvis.delete_shortsexp
                 WHERE delete_whse = :whse
                   AND delete_loc = :loc"
            );
            $restore->execute([
                ':whse' => $whse,
                ':loc' => $loc
            ]);

            $message = $restore->rowCount() > 0
                ? 'Location ' . $loc . ' added back.'
                : 'Location ' . $loc . ' was not currently omitted.';
            break;

        case 'clear_all':
            $clear = $conn1->prepare(
                "DELETE FROM printvis.delete_shortsexp
                 WHERE delete_whse = :whse"
            );
            $clear->execute([':whse' => $whse]);

            $message = $clear->rowCount() > 0
                ? 'Cleared all omitted locations.'
                : 'No omitted locations were active.';
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Unsupported action.'
            ]);
            exit;
    }
} catch (Exception $e) {
    if ($conn1->inTransaction()) {
        $conn1->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update omitted locations.'
    ]);
    exit;
}

$countQuery = $conn1->prepare(
    "SELECT COUNT(DISTINCT delete_loc) AS active_count
     FROM printvis.delete_shortsexp
     WHERE delete_whse = :whse"
);
$countQuery->execute([':whse' => $whse]);
$count = (int) $countQuery->fetchColumn();

echo json_encode([
    'success' => true,
    'message' => $message,
    'count' => $count
]);
