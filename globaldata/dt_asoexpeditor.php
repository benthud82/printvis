<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}
if ($_GET) {
    $sel_lsecse = ($_GET['sel_lsecse']);
    $dl_data = ($_GET['dldata']); //if 1, dl data, else display 
}
if ($_POST) {

    $sel_lsecse = ($_POST['sel_lsecse']);

    $dl_data = ($_POST['dldata']); //if 1, dl data, else display 
}






switch ($sel_lsecse) {
    case '*':
        $sql_lsecse = ' ';

        break;
    case 'C':
        $sql_lsecse = ' and dropzone_tozone in (7,8)';

        break;
    case 'L':
        $sql_lsecse = ' and dropzone_tozone not in (7,8)';

        break;

    default:
        break;
}

$today = date('Y-m-d');

if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = 'desc';
}

$orderbyvalue = $_POST['orderby'];
switch ($orderbyvalue) {
    case 'item':
        $orderbysql = " ORDER BY dropzone_item $asc_desc";
        break;
    case 'totalhold':
        $orderbysql = " ORDER BY COUNT_TOTAL $asc_desc";
        break;
    case 'lpnum':
        $orderbysql = " ORDER BY dropzone_lpnum $asc_desc";
        break;
    case 'fromloc':
        $orderbysql = " ORDER BY dropzone_fromloc $asc_desc";
        break;
    case 'toloc':
        $orderbysql = " ORDER BY dropzone_toloc $asc_desc";
        break;
    case 'boxhold':
        $orderbysql = " ORDER BY COUNT_HOLD $asc_desc";
        break;
    case 'shorthold':
        $orderbysql = " ORDER BY COUNT_SHORTS $asc_desc";
        break;
    case 'boxlines':
        $orderbysql = " ORDER BY COUNT_LINES $asc_desc";
        break;
    default :
        $orderbysql = " ORDER BY COUNT_TOTAL $asc_desc";
        break;
}





$dt_sql = $conn1->prepare("SELECT DISTINCT
                                dropzone_item,
                                dropzone_fromzone,
                                dropzone_tozone,
                                dropzone_fromloc,
                                dropzone_toloc,
                                dropzone_reqdate,
                                dropzone_lpnum,
                                @COUNT_HOLD:=(SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.asoboxholds
                                    WHERE
                                        dropzone_whse = asohold_whse
                                            AND dropzone_toloc = asohold_location
                                            AND dropzone_item = asohold_item) AS COUNT_HOLD,
                                @COUNT_LINES:=(SELECT 
                                        sum(asohold_boxlines)
                                    FROM
                                        printvis.asoboxholds
                                    WHERE
                                        dropzone_whse = asohold_whse
                                            AND dropzone_toloc = asohold_location
                                            AND dropzone_item = asohold_item) AS COUNT_LINES,
                                @COUNT_SHORTS:=(SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.shorts_daily_item
                                    WHERE
                                        dropzone_whse = shorts_item_whse
                                            AND dropzone_toloc = shorts_item_loc
                                            AND dropzone_item = shorts_item_item
                                            AND shorts_item_date = '$today') AS COUNT_SHORTS,
                                (@COUNT_HOLD + @COUNT_SHORTS) AS COUNT_TOTAL
                            FROM
                                printvis.dropzone_replen
                                    LEFT JOIN
                                printvis.delete_shortsexp ON delete_whse = dropzone_whse
                                    AND delete_loc = dropzone_toloc
                            WHERE
                                dropzone_whse = $var_whse AND delete_loc IS NULL
                                    AND ((SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.shorts_daily_item
                                    WHERE
                                        dropzone_whse = shorts_item_whse
                                            AND dropzone_toloc = shorts_item_loc
                                            AND dropzone_item = shorts_item_item
                                            AND shorts_item_date = '$today') + (SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.asoboxholds
                                    WHERE
                                        dropzone_whse = asohold_whse
                                            AND dropzone_toloc = asohold_location
                                            AND dropzone_item = asohold_item)) > 0
                                            $sql_lsecse
                                            $orderbysql");
$dt_sql->execute();
$dt_array = $dt_sql->fetchAll(pdo::FETCH_ASSOC);

if ($dl_data == 1) {



    $now = date('Y_m_d');

//The name of the CSV file that will be downloaded by the user.
    $fileName = 'Shorts_Expeditor_' . $now . '.csv';

//Set the Content-Type and Content-Disposition headers.
    header('Content-Type: application/excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $csvdata = array();

//header
    $csvdata[] = array("Item", "FromZone", "ToZone", "FromLoc", "ToLoc", "ReqDate", "LPNumber", "BoxHolds", "BinReports", "Total");

//A multi-dimensional array containing our CSV data.
    foreach ($dt_array as $key => $value) {
        $csvdata[] = array_values($dt_array[$key]);
    }

//Open up a PHP output stream using the function fopen.
    $fp = fopen('php://output', 'w');

//Loop through the array containing our CSV data.
    foreach ($csvdata as $row) {
        //fputcsv formats the array into a CSV format.
        //It then writes the result to our output stream.
        fputcsv($fp, $row);
    }

    fclose($fp);
} else {
    ?>
    <div class="row">
        <div class="pull-left  col-lg-3" >
            <div id="container_deletebtn">
                <button id="btn_delete_batch" class="btn btn-danger">Delete Selected Batches</button>
            </div>
        </div>
    </div>
    <!--start of div table-->
    <div class="" id="divtable_priorities" style="padding-bottom: 51px">
        <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">


            <div class='widget-content widget-table'  style="position: relative;">
                <div class='divtable'>
                    <div id="sticky-anchor"></div>
                    <div style="padding-top: 51px;"></div>
                    <div id="sticky" class='divtableheader' style="padding-top">
                        <div class='divtabletitle width5' style="cursor: default">Delete?</div>
                        <div class='divtabletitle width8_33 click_sort' name="item" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Item</div>
                        <div class='divtabletitle width8_33 click_sort' name="lpnum" data-sort="<?php echo $asc_desc ?>" style="cursor: default">LPNumber</div>
                        <div class='divtabletitle width8_33 click_sort' name="fromloc" data-sort="<?php echo $asc_desc ?>" style="cursor: default">From Loc / Drop Zone</div>
                        <div class='divtabletitle width8_33 click_sort' name="toloc" data-sort="<?php echo $asc_desc ?>" style="cursor: default">To Location</div>
                        <div class='divtabletitle width8_33 click_sort' name="boxhold" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Box Hold Count</div>
                        <div class='divtabletitle width8_33 click_sort' name="boxlines" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Box Hold Lines</div>
                        <div class='divtabletitle width8_33 click_sort' name="shorthold" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Shorts Count</div>
                        <div class='divtabletitle width8_33 click_sort' name="totalhold" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Total Count</div>


                    </div>
    <?php foreach ($dt_array as $key => $value) { ?>
                        <div id="<?php echo $dt_array[$key]['dropzone_toloc']; ?>"class='divtablerow itemdetailexpand' style="cursor: pointer">
                            <div class='divtabledata width5' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch noclick" name="checkbox" id="<?php echo $dt_array[$key]['dropzone_toloc']; ?>"  /></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_item']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_lpnum']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_fromloc']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_toloc']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_HOLD']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_LINES']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_SHORTS']; ?></div>
                            <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_TOTAL']; ?></div>

                        </div>
    <?php } ?>

                </div>
            </div>





        </div>
    </div>    
<?php
}

