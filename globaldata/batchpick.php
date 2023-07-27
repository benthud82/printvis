
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
                                batchtime_cart AS BATCHCART,
                                CAST(batchtime_time_totaltime AS UNSIGNED) AS BATCHTIME,
                                batchtime_printdatetime AS PRINTTIME,
                                MIN(cutoff_zone) AS cutoff_zone,
                                cutoff_color,
                                cutoff_color_text
                            FROM
                                printvis.looselines_batchtime
                                    LEFT JOIN
                                printvis.voice_batchespicked ON voice_whse = batchtime_whse
                                    AND voice_batch = batchtime_cart
                                    LEFT JOIN
                                printvis.printcutoff ON cutoff_DC = batchtime_whse
                                    AND cutoff_rank = batchtime_shipzone
                            WHERE
                                batchtime_printdatetime >= CASE
                                    WHEN $whsesel = 3 THEN DATE_SUB(NOW(), INTERVAL 6 HOUR)
                                    WHEN $whsesel = 7 THEN DATE_SUB(NOW(), INTERVAL 4 HOUR)
                                    ELSE DATE_SUB(NOW(), INTERVAL 3 HOUR)
                                END
                                    AND batchtime_whse = $whsesel
                                    AND voice_userid = 0
                                    AND batchtime_count_line > 5
                                    AND batchtime_colgcount / batchtime_count_line <= .95
                                   -- AND voice_cartconfig <> ' '
                                    AND batchtime_count_ice / batchtime_count_line <> 1
                            GROUP BY batchtime_cart , CAST(batchtime_time_totaltime AS UNSIGNED) , batchtime_printdatetime , cutoff_color
                            ORDER BY batchtime_cart");
$batches->execute();
$batches_array = $batches->fetchAll(pdo::FETCH_ASSOC);
?>

<div class="row">
    <div id="tablecontainer" class="col-sm-4" style="cursor: default">
        <table id="shifttable" class="table table-bordered" cellspacing="0" >
            <thead>
                <tr>
                    <th style="font-size: 20px; font-family: Calibri; text-align: center">BATCH</th>
                    <th style="font-size: 20px; font-family: Calibri; text-align: center">MINUTES</th>
                </tr>
            </thead>
            <?php
            foreach ($batches_array as $key => $value) {
                $style_backcolor = ''; //reset style color
                $style_textcolor = ''; //reset style color
                if (intval(date('H:i', strtotime($batches_array[$key]['PRINTTIME']))) >= $prepickcutoff) {
                    $style_backcolor = 'background-color: #0000ff80 !important;';
                    $style_textcolor = 'color: #000000 !important;';
                    $rowclass = '';
                } else {
                    $rowclass = '';
                }

                //set the color for printcutoff
                $backcolor = $batches_array[$key]['cutoff_color'];
                $textcolor = $batches_array[$key]['cutoff_color_text'];
                if (!is_null($backcolor)) {
                    $style_backcolor = 'background-color: ' . $backcolor . ' !important;';
                    $style_textcolor = 'color:' . $textcolor . '!important;';
                }

                //close out table and create new table every 30 batches
                $mod = $key % 30;
                if ($mod == 0 && $key !== 0) {
                    ?>
                </table>
            </div>
            <div id="tablecontainer" class="col-sm-4" style="cursor: default">
                <table id="shifttable" class="table table-bordered" cellspacing="0" >
                    <thead>
                        <tr>
                            <th style="font-size: 20px; font-family: Calibri; text-align: center">BATCH</th>
                            <th style="font-size: 20px; font-family: Calibri; text-align: center">MINUTES</th>
                        </tr>
                    </thead>
                    <?php
                }
                echo'<tr>';
                echo '<td class="' . $rowclass . '" style="font-size: 20px; font-family: Calibri; text-align: center;' . $style_backcolor . ';' . $style_textcolor . ' "><b>' . $batches_array[$key]['BATCHCART'] . "</b></td>";
                echo '<td class="' . $rowclass . '" style="font-size: 20px; font-family: Calibri; text-align: center;' . $style_backcolor . ';' . $style_textcolor . ' "><b>' . $batches_array[$key]['BATCHTIME'] . "</b></td>";

                echo'</tr>';
            }
            ?>

        </table>
    </div>
</div>

