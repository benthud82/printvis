
<?php
include '../../CustomerAudit/connection/connection_details.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}
$sel_lsecse = ($_POST['sel_lsecse']);

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

$dt_sql = $conn1->prepare("SELECT DISTINCT
                                dropzone_item,
                                dropzone_fromzone,
                                dropzone_tozone,
                                dropzone_fromloc,
                                dropzone_toloc,
                                dropzone_reqdate,
                                @COUNT_HOLD:=(SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.asoboxholds
                                    WHERE
                                        dropzone_whse = asohold_whse
                                            AND dropzone_toloc = asohold_location
                                            AND dropzone_item = asohold_item) AS COUNT_HOLD,
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
                            ORDER BY COUNT_TOTAL DESC");
$dt_sql->execute();
$dt_array = $dt_sql->fetchAll(pdo::FETCH_ASSOC);
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
                    <div class='divtabletitle width8_33' style="cursor: default">Item</div>
                    <div class='divtabletitle width8_33' style="cursor: default">From Loc / Drop Zone</div>
                    <div class='divtabletitle width8_33' style="cursor: default">To Location</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Box Hold Count</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Shorts Count</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Total Count</div>


                </div>
                <?php foreach ($dt_array as $key => $value) { ?>
                    <div id="<?php echo $dt_array[$key]['dropzone_toloc']; ?>"class='divtablerow itemdetailexpand' style="cursor: pointer">
                        <div class='divtabledata width5' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch noclick" name="checkbox" id="<?php echo $dt_array[$key]['dropzone_toloc']; ?>"  /></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_item']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_fromloc']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['dropzone_toloc']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_HOLD']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_SHORTS']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $dt_array[$key]['COUNT_TOTAL']; ?></div>

                    </div>
                <?php } ?>

            </div>
        </div>





    </div>
</div>    



