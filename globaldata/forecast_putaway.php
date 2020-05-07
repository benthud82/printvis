<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


$sql_totalput = $conn1->prepare("SELECT 
                                SUM(logequipopentimes_tottime) AS TIME_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel");
$sql_totalput->execute();
$array_totalput = $sql_totalput->fetchAll(pdo::FETCH_ASSOC);

$totalput = $array_totalput[0]['TIME_TOTAL'];
?>
<div class="row">
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Put Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Cart Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Put Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Put Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Put Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_hourred">
        <div class="dashboard-stat dashboard-stat-v2 yellow-casablanca">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalput, 1) ?></span>
                </div>
                <div class="desc">Total Put Time</div>
            </div>
        </div>
    </div>
</div>