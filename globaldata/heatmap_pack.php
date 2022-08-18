
<?php
   include '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

include '../globalvariables/packtimes.php';
include '../functions/functions_totetimes.php';
include '../timezoneset.php';


$baycolor = $conn1->prepare("SELECT 
                                                                totetimes_cart AS BATCH,
                                                                batch_start_time AS BATCH_START,
                                                                batch_start_packstation AS PACK_STATION,
                                                                batch_start_TSM AS TSM,
                                                                batch_start_speedpack AS SPEED,
                                                                TIMESTAMPDIFF(MINUTE,
                                                                    batch_start_time,
                                                                    NOW()) - $mintosubtract AS MINUTES_ELAPSED,
                                                                SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete AS CART_TIME,
                                                                SUM(CASE
                                                                    WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                    ELSE 0
                                                                END) + loosepm_cartcomplete AS CART_TIME_REMAINING,
                                                                TIMESTAMPDIFF(MINUTE,
                                                                    batch_start_time,
                                                                    NOW()) - $mintosubtract + SUM(CASE
                                                                    WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                    ELSE 0
                                                                END) + loosepm_cartcomplete AS PROJ_COMPLETE_MIN,
                                                                SUM(totetimes_linecount) AS LINE_COUNT,
                                                                SUM(totetimes_unitcount) AS UNIT_COUNT,
                                                                COUNT(*) AS TOTE_COUNT,
                                                                SUM(CASE
                                                                    WHEN tote_end_endtime > 0 THEN 1
                                                                    ELSE 0
                                                                END) AS COMPLETED_TOTES
                                                            FROM
                                                                printvis.totetimes
                                                                    LEFT JOIN
                                                                printvis.tote_end ON tote_end_whse = totetimes_whse
                                                                    AND totetimes_cart = tote_end_batch
                                                                    AND tote_end_tote = totetimes_bin
                                                                    LEFT JOIN
                                                                printvis.batch_start A ON batch_start_whse = totetimes_whse
                                                                    AND batch_start_batch = totetimes_cart
                                                                    LEFT JOIN
                                                                printvis.packbatchdelete ON CONCAT(totetimes_cart, batch_start_TSM) = idpackbatchdelete
                                                                    JOIN
                                                                printvis.pm_packtimes ON loosepm_function = totetimes_packfunction
                                                                    AND totetimes_whse = loosepm_whse
                                                            WHERE
                                                                totetimes_whse = $whsesel
                                                                    AND batch_start_time IS NOT NULL
                                                                    AND idpackbatchdelete IS NULL
                                                                    AND (A.batch_start_time) IN (SELECT 
                                                                        MAX((B.batch_start_time))
                                                                    FROM
                                                                        printvis.batch_start B
                                                                    WHERE
                                                                        A.batch_start_batch = B.batch_start_batch)
                                                                    AND (A.batch_start_time) IN (SELECT 
                                                                        MAX((C.batch_start_time))
                                                                    FROM
                                                                        printvis.batch_start C
                                                                    WHERE
                                                                        A.batch_start_TSM = C.batch_start_TSM)
                                                            GROUP BY totetimes_cart , batch_start_time , batch_start_TSM
                                                            HAVING COUNT(*) <> SUM(CASE
                                                                WHEN tote_end_endtime > 0 THEN 1
                                                                ELSE 0
                                                            END)");
$baycolor->execute();
$baycolorarray = $baycolor->fetchAll(pdo::FETCH_ASSOC);


$loosetext = $conn1->prepare("SELECT 
                                                        *
                                                    FROM
                                                        printvis.heatmap_packstation_text
                                                    WHERE
                                                        WHSE = $whsesel;");
$loosetext->execute();
$loosetextarray = $loosetext->fetchAll(pdo::FETCH_ASSOC);


$packstation = $conn1->prepare("SELECT 
                                                                        *
                                                                    FROM
                                                                        printvis.heatmap_packstation
                                                                    WHERE
                                                                        packstation_whse = $whsesel;");
$packstation->execute();
$packstation_array = $packstation->fetchAll(pdo::FETCH_ASSOC);

$screenfactor = 1;
$posfactor = 1;
$heiwidfactor = 5;
?>

<div class="borderedcontainer">
    <svg id="svg2" width="100%" height="100%" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" >
        <?php
//Populate text
        foreach ($loosetextarray as $key => $value) {
            ?>
            <text transform = "translate(<?php echo $loosetextarray[$key]['XTRANS'] . ', ' . $loosetextarray[$key]['YTRANS'] . ') rotate(' . $loosetextarray[$key]['ROTATE'] . ')' ?>" font-family="'Open Sans', sans-serif" font-size="<?php echo $loosetextarray[$key]['FONTSIZE'] ?>" ><?php echo $loosetextarray[$key]['TEXT'] ?></text>
        <?php } ?>

        <?php
        foreach ($packstation_array as $key => $value) {
            $packstatus = 'No Cart';
            $cart = NULL;
            $transform = '';
            $packstation_id = $packstation_array[$key]['packstation_id'];
            //is cart currently at station?
            $packstationkey = array_search($packstation_id, array_column($baycolorarray, 'PACK_STATION')); //Find 'L04' associated key
            if ($packstationkey !== FALSE) {
                //calc the cell color
                $projected_compl_min = $baycolorarray[$packstationkey]['PROJ_COMPLETE_MIN'];
                $CART_TIME = $baycolorarray[$packstationkey]['CART_TIME'];
                $minutecalc = number_format(abs($projected_compl_min - $CART_TIME),2);
                $cart = $baycolorarray[$packstationkey]['BATCH'];
                //call the cell color calculation function
                $cellcolor = _cellcolor($projected_compl_min, $CART_TIME);
                if ($cellcolor == 'RED') {
                    $packstatus = $minutecalc . ' minutes behind schedule.';
                } else if ($cellcolor == 'GREEN') {
                    $packstatus = $minutecalc . ' minutes ahead of schedule.';
                } else if ($cellcolor == 'WHITE') {
                    $packstatus = 'No Cart';
                }
            } else {
                $cellcolor = 'WHITE';
            }


            //if any of the shapes need to be rotated
            switch ($packstation_id) {
                case 'TXGPPACK99':
                    $transform = 'transform="translate(800) rotate(45)"';
                    break;

                default:
                    $transform = '';
                    break;
            }
            ?>
            <rect id="<?php echo $cart ?>" class="clickablesvg" x="<?php echo $packstation_array[$key]['packstation_xcor'] * $screenfactor ?>" y="<?php echo $packstation_array[$key]['packstation_ycor'] * $screenfactor ?>" width="<?php echo $packstation_array[$key]['packstation_width'] * $screenfactor ?>" height="<?php echo $packstation_array[$key]['packstation_height'] * $screenfactor ?>" style="stroke:#464646; fill: <?php echo $cellcolor ?> " <?php echo $transform ?>><title><?php echo $packstatus ?></title></rect>
                <?php } ?>    


    </svg>
</div>
