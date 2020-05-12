<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


// calulate total lines by equip type

$sql_replensummary = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel");
$sql_replensummary->execute();
$array_replensummary = $sql_replensummary->fetchAll(pdo::FETCH_ASSOC);

$replensummary = $array_replensummary[0]['REPLEN_SUMMARY'];

// count of auto moves

$sql_AUTOLINE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS AUTOLINE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel AND OPENMOVETIMES_TYPE = 'AUTO'");
$sql_AUTOLINE->execute();
$array_AUTOLINE = $sql_AUTOLINE->fetchAll(pdo::FETCH_ASSOC);

$AUTOLINE = $array_AUTOLINE[0]['AUTOLINE'];

// count of aso moves

$sql_ASOLINE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS ASOLINE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel AND OPENMOVETIMES_TYPE = 'ASO'");
$sql_ASOLINE->execute();
$array_ASOLINE = $sql_ASOLINE->fetchAll(pdo::FETCH_ASSOC);

$ASOLINE = $array_ASOLINE[0]['ASOLINE'];

// count of special moves

$sql_SPECLINE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS SPECLINE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel AND OPENMOVETIMES_TYPE = 'SPEC'");
$sql_SPECLINE->execute();
$array_SPECLINE = $sql_SPECLINE->fetchAll(pdo::FETCH_ASSOC);

$SPECLINE = $array_SPECLINE[0]['SPECLINE'];

// count of consolidation moves

$sql_CONSOLLINE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS CONSOLLINE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel AND OPENMOVETIMES_TYPE = 'CONSOL'");
$sql_CONSOLLINE->execute();
$array_CONSOLLINE = $sql_CONSOLLINE->fetchAll(pdo::FETCH_ASSOC);

$CONSOLLINE = $array_CONSOLLINE[0]['CONSOLLINE'];





// total times by move type
$sql_totalreplen = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_TOTAL
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel");
$sql_totalreplen->execute();
$array_totalreplen = $sql_totalreplen->fetchAll(pdo::FETCH_ASSOC);

$totalreplen = $array_totalreplen[0]['TIME_TOTAL'];

// total auto time
$sql_totalauto = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TOTAL_AUTO
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_type = 'auto'");
$sql_totalauto->execute();
$array_totalauto = $sql_totalauto->fetchAll(pdo::FETCH_ASSOC);

$totalauto = $array_totalauto[0]['TOTAL_AUTO'];


// total aso time
$sql_totalaso = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_ASO
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_type = 'aso'");
$sql_totalaso->execute();
$array_totalaso = $sql_totalaso->fetchAll(pdo::FETCH_ASSOC);

$totalaso = $array_totalaso[0]['TIME_ASO'];


// total special times
$sql_totalspec = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_SPEC
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_type = 'spec'");
$sql_totalspec->execute();
$array_totalspec = $sql_totalspec->fetchAll(pdo::FETCH_ASSOC);

$totalspec = $array_totalspec[0]['TIME_SPEC'];


// total consol time
$sql_totalconsol = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_CONSOL
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_type = 'consol'");
$sql_totalconsol->execute();
$array_totalconsol = $sql_totalconsol->fetchAll(pdo::FETCH_ASSOC);

$totalconsol = $array_totalconsol[0]['TIME_CONSOL'];





?>

<!--Line Count-->
<div class="row">
    <div class="col-lg-2" id="stat_REPLEN_SUMMARY">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($replensummary, 0) ?></span>
                </div>
                <div class="desc">Total Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_AUTOLINE">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($AUTOLINE, 0) ?></span>
                </div>
                <div class="desc">AUTO Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_ASOLINE">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($ASOLINE, 0) ?></span>
                </div>
                <div class="desc">ASO Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_SPECLINE">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($SPECLINE, 0) ?></span>
                </div>
                <div class="desc">SPEC Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_CONSOLLINE">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($CONSOLLINE, 0) ?></span>
                </div>
                <div class="desc">CONSOL Moves</div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-2" id="stat_totalreplen">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalreplen, 1) ?></span>
                </div>
                <div class="desc">Total Replen Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_totalauto">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalauto, 1) ?></span>
                </div>
                <div class="desc">Total Auto Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_totalaso">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalaso, 1) ?></span>
                </div>
                <div class="desc">Total ASO Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_totalspec">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalspec, 1) ?></span>
                </div>
                <div class="desc">Total Special Time</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_totalconsol">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalconsol, 1) ?></span>
                </div>
                <div class="desc">Total Consol Time</div>
            </div>
        </div>
    </div>
    </div>




/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

