<?php

include_once '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';


$tsmsql = $conn1->prepare("SELECT 
                                                                        tsm_num as number, UPPER(tsm_name) as name
                                                                    FROM
                                                                        printvis.tsm;");
$tsmsql->execute();
$tsm_array = $tsmsql->fetchAll(pdo::FETCH_ASSOC);

$tsmjson = json_encode($tsm_array);
echo $tsmjson;