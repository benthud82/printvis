<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$prepickcutoff = 1820;



include '../timezoneset.php';

$batches = $conn1->prepare("SELECT
                                    casebatches_cart            as BATCHCART ,
                                    sum(casebatches_time_final) as BATCHTIME ,
                                    MIN(B.cutoff_rank)                       ,
                                    (
                                            SELECT
                                                    X.cutoff_zone
                                            FROM
                                                    printvis.printcutoff X
                                            WHERE
                                                    X.cutoff_rank = MIN(B.cutoff_rank)
                                            AND     X.cutoff_DC   = B.cutoff_DC) AS SHIPZONE
                            FROM
                                    printvis.casebatches_time
                            LEFT JOIN
                                    printvis.casebatchstarttime A
                            ON
                                    starttime_whse  = casebatches_whse
                            AND     starttime_build = casebatches_build
                            AND     starttime_batch = casebatches_cart
                            LEFT JOIN
                                    printvis.case_boxesreleased
                            ON
                                    boxrel_batch = casebatches_cart
                            LEFT JOIN
                                    printvis.casebatchdelete
                            on
                                    casebatches_cart = casedelete_batch
                            LEFT JOIN
                                    printvis.printcutoff B
                            ON
                                    B.cutoff_DC                    = casebatches_whse
                            AND     SUBSTRING(B.cutoff_zone, 1, 2) = SUBSTR(casebatches_time_shipzone, 1, 2)
                            WHERE
                                    starttime_tsm IS NULL
                            and     casedelete_batch IS NULL
                            AND     casebatches_whse  = $whsesel
                            GROUP BY
                                    casebatches_cart");
$batches->execute();
$batches_array = $batches->fetchAll(pdo::FETCH_ASSOC);
?>

<div class="row">
    <div id="tablecontainer" class="col-sm-4" style="cursor: default">
        <table id="shifttable" class="table table-bordered table-striped" cellspacing="0" >
            <thead>
                <tr>
                    <th style="font-size: 20px; font-family: Calibri; text-align: center">BATCH</th>
                    <th style="font-size: 20px; font-family: Calibri; text-align: center">MINUTES</th>
                    <th style="font-size: 20px; font-family: Calibri; text-align: center">SHIP ZONE</th>
                </tr>
            </thead>
            <?php
            foreach ($batches_array as $key => $value) {

//                if (intval(date('H:i', strtotime($batches_array[$key]['PRINTTIME']))) >= $prepickcutoff) {
//                    $rowclass = 'greenbackground';
//                } else {
//                    $rowclass = '';
//                }

                //close out table and create new table every 30 batches
                $mod = $key % 30;
                if ($mod == 0 && $key !== 0) {
                    ?>
                </table>
            </div>
            <div id="tablecontainer" class="col-sm-4" style="cursor: default">
                <table id="shifttable" class="table table-bordered table-striped" cellspacing="0" >
                    <thead>
                        <tr>
                            <th style="font-size: 20px; font-family: Calibri; text-align: center">BATCH</th>
                            <th style="font-size: 20px; font-family: Calibri; text-align: center">MINUTES</th>
                            <th style="font-size: 20px; font-family: Calibri; text-align: center">SHIP ZONE</th>
                        </tr>
                    </thead>
                    <?php
                }
                echo'<tr>';
                echo '<td style="font-size: 20px; font-family: Calibri; text-align: center"><b>' . $batches_array[$key]['BATCHCART'] . "</b></td>";
                echo '<td style="font-size: 20px; font-family: Calibri; text-align: center"><b>' . intval($batches_array[$key]['BATCHTIME']) . "</b></td>";
                echo '<td style="font-size: 20px; font-family: Calibri; text-align: center"><b>' . ($batches_array[$key]['SHIPZONE']) . "</b></td>";
                echo'</tr>';
            }
            ?>

        </table>
    </div>
</div>

