<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
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
                                                    casetote_time_totaltime,
                                                    casetote_time_equipment
                                                FROM
                                                    printvis.casetote_time
                                                WHERE casetote_time_cart = '$batch';");
$totedata->execute();
$totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);
?>
<!--Modify equipment needed-->
<div class="row">
    <div class="col-lg-3">
        <select class="selectstyle updateequip" id="changeequip" name="changeequip" style="width: 160px;padding: 5px; margin-right: 10px;margin-bottom: 10px;">
            <option value="PALLETJACK" <?php
            if ($totedata_modal[0]['casetote_time_equipment'] === 'PALLETJACK') {
                echo 'selected';
            }
            ?>>PALLETJACK</option>
            <option value="BELTLINE" <?php
            if ($totedata_modal[0]['casetote_time_equipment'] === 'BELTLINE') {
                echo 'selected';
            }
            ?>>BELTLINE</option>
            <option value="ORDERPICKER" <?php
            if ($totedata_modal[0]['casetote_time_equipment'] === 'ORDERPICKER') {
                echo 'selected';
            }
            ?>>ORDERPICKER</option>
            <option value="REACH" <?php
            if ($totedata_modal[0]['casetote_time_equipment'] === 'REACH') {
                echo 'selected';
            }
            ?>>REACH</option>

        </select>
    </div>
    <div id="" class="btn btn-inverse btn_changeequip" batch-id="<?php echo $totedata_modal[0]['casetote_time_cart']; ?>">Change Equipment Type</div>
</div> 

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

