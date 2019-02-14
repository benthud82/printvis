<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
include '../globalvariables/packtimes.php';

$batch = intval($_POST['batch']);
if (isset($_POST['sort'])) {
    $sortby = $_POST['sort'];
    switch ($sortby) {
        case 'endtime':
            $sort = "ORDER BY IF (TIME(tote_end_endtime) <> '', 0, 1), TIME(tote_end_endtime)  ASC, totetimes_bin ASC";
            break;
        default:
            $sort = 'ORDER BY totetimes_bin ASC';
            break;
    }
} else {
    $sort = 'ORDER BY totetimes_bin ASC';
}

$totedata = $conn1->prepare("SELECT 
                                                                    totelp,
                                                                    tote_end_tsm,
                                                                    totetimes_cart,
                                                                    totetimes_bin,
                                                                    totetimes_boxsize,
                                                                    totetimes_shipzone,
                                                                    totetimes_linecount,
                                                                    totetimes_unitcount,
                                                                    totetimes_totalPFD,
                                                                    TIME(tote_end_endtime) as tote_end_endtime
                                                                FROM
                                                                    printvis.totetimes
                                                                        LEFT JOIN
                                                                    printvis.tote_end ON totetimes_whse = tote_end_whse
                                                                        AND totetimes_cart = tote_end_batch
                                                                        AND tote_end_tote = totetimes_bin
                                                                WHERE
                                                                    totetimes_cart = $batch
                                                                        and totetimes_whse = $whsesel
                                                                $sort;");
$totedata->execute();
$totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);

$totedata_summary = $conn1->prepare("SELECT 
                                                                                batch_start_time,
                                                                                case when batch_start_speedpack = 'Y' then 'YES' else 'NO' end as batch_start_speedpack,
                                                                                batch_start_packstation
                                                                            FROM
                                                                                printvis.batch_start
                                                                            WHERE
                                                                                batch_start_batch = $batch
                                                                                    AND batch_start_whse = $whsesel
                                                                            ORDER BY batch_start_time DESC;");
$totedata_summary->execute();
$totedata_summary_array = $totedata_summary->fetchAll(pdo::FETCH_ASSOC);
?>

<!--Header summary info-->
<div class="col-xs-12">
    <div class="portlet light portlet-fit ">
        <div class="portlet-body">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="mt-element-ribbon bg-grey-steel">
                        <div class="ribbon ribbon-color-default uppercase">Cart Start Time</div>
                        <p class="ribbon-content"><?php echo $totedata_summary_array[0]['batch_start_time']; ?></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="mt-element-ribbon bg-grey-steel">
                        <div class="ribbon ribbon-color-default uppercase">Speed Pack</div>
                        <p class="ribbon-content"><?php echo $totedata_summary_array[0]['batch_start_speedpack']; ?></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="mt-element-ribbon bg-grey-steel">
                        <div class="ribbon ribbon-color-default uppercase">Pack Station ID</div>
                        <p class="ribbon-content"><?php echo $totedata_summary_array[0]['batch_start_packstation']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<button id="modal_btn_delete" class="btn btn-danger">Delete Batch</button>
<!--start of div table-->
<div class="" id="divtablecontainer">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width10' >LP</div>
                    <div class='divtabletitle width10' >TSM</div>
                    <div class='divtabletitle width10' >Batch</div>
                    <div class='divtabletitle width10 binsort' style="cursor: pointer;" >Bin</div>
                    <div class='divtabletitle width10' >Box Size</div>
                    <div class='divtabletitle width10' >Ship Zone</div>
                    <div class='divtabletitle width10' >Lines</div>
                    <div class='divtabletitle width10' >Units</div>
                    <div class='divtabletitle width10' >Est. Time</div>
                    <div class='divtabletitle width10 endtimesort' style="cursor: pointer;" >Completion Time</div>

                </div>
                <?php foreach ($totedata_modal as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totelp']; ?> </div>
                        <div class='divtabledata width10' id="activetsm"> <?php echo $totedata_modal[$key]['tote_end_tsm']; ?> </div>
                        <div class='divtabledata width10'  id="activebatch"> <?php echo $totedata_modal[$key]['totetimes_cart']; ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_bin']; ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_boxsize']; ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_shipzone']; ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_linecount']; ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_unitcount']; ?> </div>
                        <div class='divtabledata width10'> <?php echo number_format($totedata_modal[$key]['totetimes_totalPFD'], 1); ?> </div>
                        <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['tote_end_endtime']; ?> </div>
                    </div>

                <?php } ?>
            </div>
        </div>

    </div>    
</div>    

