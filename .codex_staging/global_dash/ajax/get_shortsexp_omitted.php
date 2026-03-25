<?php

include_once '../connections/connection.php';
require_once '../sessioninclude.php';

header('Content-Type: application/json');

$whse = isset($_SESSION['WHSE']) ? (int) $_SESSION['WHSE'] : 0;

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

$query = $conn1->prepare(
    "SELECT delete_loc, MAX(delete_datetime) AS omitted_at
     FROM printvis.delete_shortsexp
     WHERE delete_whse = :whse
     GROUP BY delete_loc
     ORDER BY omitted_at DESC, delete_loc ASC"
);
$query->execute([':whse' => $whse]);

$rows = $query->fetchAll(PDO::FETCH_ASSOC);
$now = new DateTime('now');
$items = [];

foreach ($rows as $row) {
    if (empty($row['omitted_at'])) {
        continue;
    }

    $omittedAt = new DateTime($row['omitted_at']);
    $expiresAt = clone $omittedAt;
    $expiresAt->modify('+60 minutes');

    if ($expiresAt <= $now) {
        continue;
    }

    $secondsRemaining = $expiresAt->getTimestamp() - $now->getTimestamp();
    $minutesRemaining = max(1, (int) ceil($secondsRemaining / 60));

    $items[] = [
        'loc' => $row['delete_loc'],
        'omitted_at' => $omittedAt->format('Y-m-d H:i:s'),
        'omitted_at_display' => $omittedAt->format('M j, g:i A'),
        'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        'expires_at_display' => $expiresAt->format('M j, g:i A'),
        'minutes_remaining' => $minutesRemaining,
        'status_text' => $minutesRemaining === 1
            ? 'returns in 1 min'
            : 'returns in ' . $minutesRemaining . ' min'
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($items),
    'items' => $items
]);
