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

    $startdatesel = date("Y-m-d", strtotime($_POST['startdatesel']));  //pulled from heatmap.php
    $enddatesel = date("Y-m-d", strtotime($_POST['enddatesel']));  //pulled from heatmap.php

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}


if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = ' asc';
}



if (isset($_POST['orderby'])) {
    $orderbyvalue = $_POST['orderby'];
} else {
    $orderbyvalue = 'batch';
}

switch ($orderbyvalue) {
    case 'batch':
        $orderbysql = " ORDER BY case_batch $asc_desc ";
        break;
    case 'date':
        $orderbysql = " ORDER BY case_date $asc_desc ";
        break;
    case 'equip':
        $orderbysql = " ORDER BY case_equip $asc_desc ";
        break;
    case 'unscannedtime':
        $orderbysql = " ORDER BY case_time $asc_desc ";
        break;
    case 'lines':
        $orderbysql = " ORDER BY case_lines $asc_desc ";
        break;
    default:
        $orderbysql = " ORDER BY case_batch $asc_desc ";
        break;
}

$unscannedsql = $conn1->prepare("SELECT 
                                                                            case_batch, case_date, case_equip, case_lines, case_time
                                                                        FROM
                                                                            printvis.unscannedcases
                                                                        WHERE
                                                                            DATE(case_date) >= '$startdatesel' and DATE(case_date) <= '$enddatesel' 
                                                                                AND case_whse = $var_whse"
        . " $orderbysql  ");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);



$unscannedheader = $conn1->prepare("SELECT 
                                                            sum(case_time) as USCANNED_TIME, sum(case_lines) as USCANNED_LINES, count(*) as TOTE_NOTSCANNED
                                                        FROM
                                                            printvis.unscannedcases
                                                        WHERE
                                                            DATE(case_date) >= '$startdatesel'
                                                                AND DATE(case_date) <= '$enddatesel'
                                                                AND case_whse = $var_whse");
$unscannedheader->execute();
$unscannedheader_array = $unscannedheader->fetchAll(pdo::FETCH_ASSOC);
?>





<!--Header Stats-->
<div class="col-lg-12">
    <div class="row" style="padding-top: 25px">
        <div class="col-lg-4 " id="stat_losttime">
            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                <div class="visual">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo _convertToHoursMins($unscannedheader_array[0]['USCANNED_TIME']) ?></span>
                    </div>
                    <div class="desc"> Total Hours Unscanned </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4" id="stat_notscanned">
            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                <div class="visual">
                    <i class="fa fa-times"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo ($unscannedheader_array[0]['TOTE_NOTSCANNED']) ?></span>
                    </div>
                    <div class="desc"> Total Case Batches not Scanned </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4" id="stat_notscannedlines">
            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                <div class="visual">
                    <i class="fa fa-list-ul"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo ($unscannedheader_array[0]['USCANNED_LINES']) ?></span>
                    </div>
                    <div class="desc"> Total Case Lines Not Scanned </div>
                </div>
            </div>
        </div>

    </div>
</div>



<!--start of div table-->
<div class="" id="divtable_notprinted" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-9 print-1wide'  style="float: none;">
        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width20 click_sort  ' data-pull="unscanned" name="batch" data-sort="<?php echo $asc_desc ?>">Batch</div>
                    <div class='divtabletitle width20 click_sort ' data-pull="unscanned" name="date" data-sort="<?php echo $asc_desc ?>">Print Date</div>
                    <div class='divtabletitle width20 click_sort ' data-pull="unscanned" name="equip" data-sort="<?php echo $asc_desc ?>">Equipment</div>
                    <div class='divtabletitle width20 click_sort ' data-pull="unscanned" name="lines" data-sort="<?php echo $asc_desc ?>">Lines</div>
                    <div class='divtabletitle width20 click_sort ' data-pull="unscanned" name="unscannedtime" data-sort="<?php echo $asc_desc ?>" >Unscanned Time</div>
                </div>
                <?php
                foreach ($unscannedsql_array as $key => $value) {
                    ?>
                    <div id="<?php echo $unscannedsql_array[$key]['case_batch']; ?>"class='divtablerow itemdetailexpand greyhover batchclick' data-date="<?php echo date('Y-m-d', strtotime($unscannedsql_array[$key]['case_date'])); ?>">
                        <div id=""class='divtabledata width20 '><?php echo $unscannedsql_array[$key]['case_batch']; ?></div>
                        <div class='divtabledata width20' ><?php echo $unscannedsql_array[$key]['case_date']; ?></div>
                        <div class='divtabledata width20' ><?php echo $unscannedsql_array[$key]['case_equip']; ?></div>
                        <div class='divtabledata width20' ><?php echo $unscannedsql_array[$key]['case_lines']; ?></div>
                        <div class='divtabledata width20' ><?php echo _convertToHoursMins($unscannedsql_array[$key]['case_time']); ?></div>

                    </div>
                <?php } ?>
            </div>
        </div>
    </div>    
</div>    
