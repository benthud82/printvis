<?php

include '../../globalincludes/usa_asys.php';
include_once '../../connections/conn_printvis.php';
include_once '../../heatmap_logic/functions/funct.php';
$caselp = intval($_POST['caselp']);
$schema = $_POST['schema'];

$now = date('Y-m-d H:i:s');

$verify_sql = $as400_conn->prepare("SELECT A.PBWHSE AS shorts_item_whse,
                                A.PBCART AS shorts_batch,
                                A.PBBIN# as shorts_item_tote,
                                B.PDITEM as shorts_item_item,
                                A.PBLOC# AS shorts_item_loc,
                                A.PBBOXL AS shorts_item_qty,
                                A.PBWCS# AS shorts_item_wcsnumber,
                                A.PBWKNO AS shorts_item_workorder,
                                A.PBBOX# AS shorts_item_boxnumber,
                                '$now' as shorts_item_date,
                                '999999' AS shorts_item_tsm,
                                '999' as shorts_item_cart,
                                A.PBLP9D AS shorts_item_lp,
                                Case when A.PBBXSZ = 'CSE' then 'CSE' else 'LSE' END as shorts_item_type
                                
                                FROM HSIPCORDTA.NOTWPB A
                                
                                LEFT JOIN HSIPCORDTA.NOTWPD B ON A.PBWHSE = B.PDWHSE AND A.PBWCS# = B.PDWCS# AND A.PBWKNO = B.PDWKNO AND A.PBBOX# = B.PDBOX# 

                                WHERE PBLP9D = $caselp");
                                
$verify_sql->execute();
$result = $verify_sql->fetchAll(pdo::FETCH_ASSOC);

//loop through result set to determine if 
foreach ($result as $key => $value) {
    $whse = $result[$key]['SHORTS_ITEM_WHSE'];
    $loc = $result[$key]['SHORTS_ITEM_LOC'];

    //select data from loc_oh table to determine if onhand is greater than current allocations
    $sql_locoh = $conn1->prepare("SELECT 
                                        CASE
                                            WHEN locoh_onhand > (locoh_openalloc + locoh_printalloc) THEN 1
                                            ELSE 0
                                        END AS issue_ic,
                                        locoh_onhand,
                                        (locoh_openalloc + locoh_printalloc) as tot_alloc
                                    FROM
                                        nahsi.loc_oh
                                    WHERE
                                        locoh_whse = $whse AND locoh_loc = '$loc'");
    $sql_locoh->execute();
    $array_locoh = $sql_locoh->fetchAll(pdo::FETCH_ASSOC);
    if ($array_locoh) {
        $result[$key]['shorts_item_icissue'] = $array_locoh[0]['issue_ic'];
        $result[$key]['shorts_item_oh'] = $array_locoh[0]['locoh_onhand'];
        $result[$key]['shorts_item_alloc'] = $array_locoh[0]['tot_alloc'];
        $result[$key]['shorts_item_locempty'] = 0;
            } else {
        $result[$key]['shorts_item_icissue'] = 2;
        $result[$key]['shorts_item_oh'] = 0;
        $result[$key]['shorts_item_alloc'] = 0;
        $result[$key]['shorts_item_locempty'] = 0;
            }
     //defaults to 0.  User must manually update through short_trim tool
}

$arraychunk = 10000;
$mysqltable = 'shorts_daily_item';

$updatecols = array('shorts_item_qty', 'shorts_item_wcsnumber');
//insert into table
pdoMultiInsert_duplicate($mysqltable, $schema, $result, $conn1, $arraychunk, $updatecols);
$hist_mysqltable = 'shorts_daily_itemhistory';
//update historical shorts table
//insert into table
pdoMultiInsert($hist_mysqltable, $schema, $result, $conn1, $arraychunk);

