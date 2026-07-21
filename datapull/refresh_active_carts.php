<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../connections/conn_printvis.php';
require_once __DIR__ . '/../functions/functions_active_carts.php';

$printCutoff = printvis_current_print_cutoff($conn1);
$result = printvis_refresh_active_carts($conn1, $printCutoff);

echo json_encode([
    'ok' => true,
    'print_cutoff' => $printCutoff,
    'counts' => $result['counts'],
    'used_voicepicks' => $result['used_voicepicks'],
    'used_aisletimes' => $result['used_aisletimes'],
], JSON_PRETTY_PRINT) . PHP_EOL;
