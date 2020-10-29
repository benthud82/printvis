<?php

include '../../globalincludes/usa_asys.php';
include_once '../../connections/conn_printvis.php';
include_once '../../heatmap_logic/functions/funct.php';
$caselp = intval($_POST['caselp']);

$verify_sql = $as400_conn->prepare("SELECT PBWHSE AS shorts_whse,
                                PBLP9D AS shorts_lp,
                                PBWCS# AS shorts_wcsnum,
                                PBWKNO AS shorts_wcsworkorder,
                                PBCART AS shorts_batch,
                                PBSHPZ AS shorts_shipzone,
                                PBLOC# AS shorts_location
                                
                                FROM HSIPCORDTA.NOTWPB
                                
                                WHERE PBLP9D = $caselp");
                                
$verify_sql->execute();
$verify_array = $verify_sql->fetchAll(pdo::FETCH_ASSOC);

$mysqltable = 'case_lpdata';
$schema = 'nahsi';
$arraychunk = 1;
pdoMultiInsert($mysqltable, $schema, $verify_array, $conn1, $arraychunk);
