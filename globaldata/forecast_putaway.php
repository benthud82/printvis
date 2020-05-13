<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];



// calulate total lines by equip type

$sql_lineput = $conn1->prepare("SELECT 
                                SUM(logequipopentimes_totlines) AS LINE_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel");
$sql_lineput->execute();
$array_lineput = $sql_lineput->fetchAll(pdo::FETCH_ASSOC);

$lineput = $array_lineput[0]['LINE_TOTAL'];

// cart lines
$sql_linecrt = $conn1->prepare("SELECT 
                                logequipopentimes_totlines AS LINE_CART
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTCRT'");
$sql_linecrt->execute();
$array_linecrt = $sql_linecrt->fetchAll(pdo::FETCH_ASSOC);


$linecrt = $array_linecrt[0]['LINE_CART'];



// flow lines
$sql_lineflw = $conn1->prepare("SELECT 
                                logequipopentimes_totlines AS LINE_FLOW
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTFLW'");
$sql_lineflw->execute();
$array_lineflw = $sql_lineflw->fetchAll(pdo::FETCH_ASSOC);

$lineflw = $array_lineflw[0]['LINE_FLOW'];

//dogpound lines
$sql_linedog = $conn1->prepare("SELECT 
                                logequipopentimes_totlines AS LINE_DOG
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTDOG'");
$sql_linedog->execute();
$array_linedog = $sql_linedog->fetchAll(pdo::FETCH_ASSOC);

$linedog = $array_linedog[0]['LINE_DOG'];


// PKR lines

$sql_linepkr = $conn1->prepare("SELECT 
                                logequipopentimes_totlines AS LINE_PKR
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTPKR'");
$sql_linepkr->execute();
$array_linepkr = $sql_linepkr->fetchAll(pdo::FETCH_ASSOC);

$linepkr = $array_linepkr[0]['LINE_PKR'];



// TUR TOTAL

$sql_linetur = $conn1->prepare("SELECT 
                                logequipopentimes_totlines AS LINE_TUR
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTTUR'");
$sql_linetur->execute();
$array_linetur = $sql_linetur->fetchAll(pdo::FETCH_ASSOC);

$linetur = $array_linetur[0]['LINE_TUR'];







// total times by equip



$sql_totalput = $conn1->prepare("SELECT 
                                SUM(logequipopentimes_tottime) AS TIME_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel");
$sql_totalput->execute();
$array_totalput = $sql_totalput->fetchAll(pdo::FETCH_ASSOC);

$totalput = $array_totalput[0]['TIME_TOTAL'];

// cart total
$sql_putcrt = $conn1->prepare("SELECT 
                                logequipopentimes_tottime AS CART_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTCRT'");
$sql_putcrt->execute();
$array_putcrt = $sql_putcrt->fetchAll(pdo::FETCH_ASSOC);


$putcrt = $array_putcrt[0]['CART_TOTAL'];



// flow total
$sql_putflw = $conn1->prepare("SELECT 
                                logequipopentimes_tottime AS FLOW_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTFLW'");
$sql_putflw->execute();
$array_putflw = $sql_putflw->fetchAll(pdo::FETCH_ASSOC);

$putflw = $array_putflw[0]['FLOW_TOTAL'];

//dogpound total
$sql_putdog = $conn1->prepare("SELECT 
                                logequipopentimes_tottime AS DOG_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTDOG'");
$sql_putdog->execute();
$array_putdog = $sql_putdog->fetchAll(pdo::FETCH_ASSOC);

$putdog = $array_putdog[0]['DOG_TOTAL'];


// PKR TOTAL

$sql_putpkr = $conn1->prepare("SELECT 
                                logequipopentimes_tottime AS PKR_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTPKR'");
$sql_putpkr->execute();
$array_putpkr = $sql_putpkr->fetchAll(pdo::FETCH_ASSOC);

$putpkr = $array_putpkr[0]['PKR_TOTAL'];



// TUR TOTAL

$sql_puttur = $conn1->prepare("SELECT 
                                logequipopentimes_tottime AS TUR_TOTAL
                            FROM
                                printvis.log_equipopentimes
                            WHERE
                                logequipopentimes_whse = $whsesel and logequipopentimes_equip = 'PUTTUR'");
$sql_puttur->execute();
$array_puttur = $sql_puttur->fetchAll(pdo::FETCH_ASSOC);

$puttur = $array_puttur[0]['TUR_TOTAL'];






?>

<!--Line Count-->
<div class="row">
    <div class="col-lg-2" id="stat_LINETOTAL">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($lineput, 0) ?></span>
                </div>
                <div class="desc">Total Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_LINECRT">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($linecrt, 0) ?></span>
                </div>
                <div class="desc">Cart Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_LINEFLW">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($lineflw, 0) ?></span>
                </div>
                <div class="desc">Flow Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_LINEDOG">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($linedog, 0) ?></span>
                </div>
                <div class="desc">Dogpound Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_LINEPKR">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($linepkr, 0) ?></span>
                </div>
                <div class="desc">OP Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_LINETUR">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($linetur, 0) ?></span>
                </div>
                <div class="desc">Turret Lines</div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-2" id="stat_TOTALPUT">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
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
    <div class="col-lg-2" id="stat_TOTALCRT">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($putcrt, 1) ?></span>
                </div>
                <div class="desc">Total Cart Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_TOTALFLW">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($putflw, 1) ?></span>
                </div>
                <div class="desc">Total Flow Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_TOTALDOG">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($putdog, 1) ?></span>
                </div>
                <div class="desc">Total DogPound Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_TOTALPKR">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($putpkr, 1) ?></span>
                </div>
                <div class="desc">Total OP Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_TOTALTUR">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($puttur, 1) ?></span>
                </div>
                <div class="desc">Total Turret Time</div>
            </div>
        </div>
    </div>
</div>

