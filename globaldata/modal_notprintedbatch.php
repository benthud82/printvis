<?php
include '../sessioninclude.php';
include '../../CustomerAudit/connection/connection_details.php';
include '../functions/functions_totetimes.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$batch = ($_POST['batch']);

$totedata = $conn1->prepare("SELECT 
                                                    casetote_time_cart,
                                                    casetote_time_aisle,
                                                    casetote_time_lines,
                                                    casetote_time_firstloc,
                                                    casetote_time_lastloc,
                                                    casetote_time_totaltime
                                                FROM
                                                    printvis.notprintedcasetote_time
                                                WHERE casetote_time_cart = '$batch' and casetote_time_whse = $whsesel;");
$totedata->execute();
$totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);

?>

<!--start of div table-->
<div class="" id="divtablecontainer">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width16_66' >Batch</div>
                    <div class='divtabletitle width16_66' >Aisle</div>
                    <div class='divtabletitle width16_66' >Lines</div>
                    <div class='divtabletitle width16_66' >First Location</div>
                    <div class='divtabletitle width16_66' >Last Location</div>
                    <div class='divtabletitle width16_66' >Total Time</div>


                </div>
                <?php foreach ($totedata_modal as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>
                        <div class='divtabledata width16_66'> <?php echo $totedata_modal[$key]['casetote_time_cart']; ?> </div>
                        <div class='divtabledata width16_66'> <?php echo $totedata_modal[$key]['casetote_time_aisle']; ?> </div>
                        <div class='divtabledata width16_66'> <?php echo $totedata_modal[$key]['casetote_time_lines']; ?> </div>
                        <div class='divtabledata width16_66'> <?php echo $totedata_modal[$key]['casetote_time_firstloc']; ?> </div>
                        <div class='divtabledata width16_66'> <?php echo $totedata_modal[$key]['casetote_time_lastloc']; ?> </div>
                        <div class='divtabledata width16_66'> <?php echo _convertToHoursMins($totedata_modal[$key]['casetote_time_totaltime']); ?> </div>

                    </div>

                <?php } ?>
            </div>
        </div>

    </div>    
</div>    

