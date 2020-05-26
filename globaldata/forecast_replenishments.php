<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$building = $_POST['building'];


// calulate total lines by equip type

$sql_replensummary = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building");
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
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building AND OPENMOVETIMES_TYPE = 'AUTO'");
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
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building AND OPENMOVETIMES_TYPE = 'ASO'");
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
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building AND OPENMOVETIMES_TYPE = 'SPEC'");
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
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building AND OPENMOVETIMES_TYPE = 'CONSOL'");
$sql_CONSOLLINE->execute();
$array_CONSOLLINE = $sql_CONSOLLINE->fetchAll(pdo::FETCH_ASSOC);

$CONSOLLINE = $array_CONSOLLINE[0]['CONSOLLINE'];





// total times by move type
$sql_totalreplen = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_TOTAL
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building");
$sql_totalreplen->execute();
$array_totalreplen = $sql_totalreplen->fetchAll(pdo::FETCH_ASSOC);

$totalreplen = $array_totalreplen[0]['TIME_TOTAL'];

// total auto time
$sql_totalauto = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TOTAL_AUTO
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building and openmovetimes_type = 'auto'");
$sql_totalauto->execute();
$array_totalauto = $sql_totalauto->fetchAll(pdo::FETCH_ASSOC);

$totalauto = $array_totalauto[0]['TOTAL_AUTO'];


// total aso time
$sql_totalaso = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_ASO
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building and openmovetimes_type = 'aso'");
$sql_totalaso->execute();
$array_totalaso = $sql_totalaso->fetchAll(pdo::FETCH_ASSOC);

$totalaso = $array_totalaso[0]['TIME_ASO'];


// total special times
$sql_totalspec = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_SPEC
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building and openmovetimes_type = 'spec'");
$sql_totalspec->execute();
$array_totalspec = $sql_totalspec->fetchAll(pdo::FETCH_ASSOC);

$totalspec = $array_totalspec[0]['TIME_SPEC'];


// total consol time
$sql_totalconsol = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_CONSOL
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = $whsesel and openmovetimes_tobldg = $building and openmovetimes_type = 'consol'");
$sql_totalconsol->execute();
$array_totalconsol = $sql_totalconsol->fetchAll(pdo::FETCH_ASSOC);

$totalconsol = $array_totalconsol[0]['TIME_CONSOL'];


// switch to forecasted moves totals..


$sql_forecastsummary = $conn1->prepare("SELECT 
                                SUM(forecastmovetimes_totlines) AS FORECAST_SUMMARY
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel");
$sql_forecastsummary->execute();
$array_forecastsummary = $sql_forecastsummary->fetchAll(pdo::FETCH_ASSOC);

$forecastsummary = $array_forecastsummary[0]['FORECAST_SUMMARY'];

// foecasted bin moves

$sql_forecastbinline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_BINLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_equip = 'MVEBIN'");
$sql_forecastbinline->execute();
$array_forecastbinline = $sql_forecastbinline->fetchAll(pdo::FETCH_ASSOC);

$forecastbinline = $array_forecastbinline[0]['FORECAST_BINLINE'];


// foecasted flow moves

$sql_forecastflowline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_FLOWLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_equip = 'MVEFLW'");
$sql_forecastflowline->execute();
$array_forecastflowline = $sql_forecastflowline->fetchAll(pdo::FETCH_ASSOC);

$forecastflowline = $array_forecastflowline[0]['FORECAST_FLOWLINE'];


// foecasted L01  moves

$sql_forecastFPline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_FPLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_equip = 'MVEL01'");
$sql_forecastFPline->execute();
$array_forecastFPline = $sql_forecastFPline->fetchAll(pdo::FETCH_ASSOC);

$forecastFPline = $array_forecastFPline[0]['FORECAST_FPLINE'];

// forecasted dog pound

$sql_forecastdogline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_DOGLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_equip = 'MVEDOG'");
$sql_forecastdogline->execute();
$array_forecastdogline = $sql_forecastdogline->fetchAll(pdo::FETCH_ASSOC);

$forecastdogline = $array_forecastdogline[0]['FORECAST_DOGLINE'];


// forecasted pkr pound

$sql_forecastpkrline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_PKRLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_building = $building and forecastmovetimes_equip = 'MVEPKR'");
$sql_forecastpkrline->execute();
$array_forecastpkrline = $sql_forecastpkrline->fetchAll(pdo::FETCH_ASSOC);

$forecastpkrline = $array_forecastpkrline[0]['FORECAST_PKRLINE'];


// forecasted TUR

$sql_forecastturline = $conn1->prepare("SELECT
                                forecastmovetimes_whse as whse,
                                forecastmovetimes_equip as equip,
                                forecastmovetimes_totlines AS FORECAST_TURLINE
                            FROM
                                printvis.forecastmoves_opentimes
                            WHERE
                                forecastmovetimes_whse = $whsesel and forecastmovetimes_building = $building and forecastmovetimes_equip = 'MVETUR'");
$sql_forecastturline->execute();
$array_forecastturline = $sql_forecastturline->fetchAll(pdo::FETCH_ASSOC);

$forecastturline = $array_forecastturline[0]['FORECAST_TURLINE'];

?> 


<?php if ($whsesel == 3){?>

<div class="col-sm-12">
                    <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Open Moves</header>
                        <div id="openmoves" class="panel-body" style="background: #efefef">
                     
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
                <div class="desc">Total Replen Hours</div>
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
                <div class="desc">Total Auto Hours</div>
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
                <div class="desc">Total ASO Hours</div>
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
                <div class="desc">Total Special Hours</div>
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
                <div class="desc">Total Consol Hours</div>
            </div>
        </div>
    </div>
    </div>
</div>
</div>
    



<div class="row">
<div class="col-sm-12">
                    <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Forecasted Moves by Destination Location Type</header>
                        <div id="forecastedmoves" class="panel-body" style="background: #efefef">

<div class="row">
    <div class="col-lg-2" id="stat_FORECAST_SUMMARY">
        <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastsummary, 0) ?></span>
                </div>
                <div class="desc">Forecast Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_FORECAST_BINLINE">
        <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastbinline, 0) ?></span>
                </div>
                <div class="desc">Forecast Bin Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_FORECAST_FPLINE">
            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                <div class="visual">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastFPline, 0) ?></span>
                    </div>
                    <div class="desc">Forecast Full Pallet Lines</div>
                </div>
            </div>
        </div>
    <div class="col-lg-2" id="stat_FORECAST_DOGLINE">
            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                <div class="visual">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastdogline, 0) ?></span>
                    </div>
                    <div class="desc">Forecast Dogpound Lines</div>
                </div>
            </div>
        </div>
        <div class="col-lg-2" id="stat_FORECAST_FLOWLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastflowline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast Flow Lines</div>
                    </div>
                </div>
            </div>
        <div class="col-lg-2" id="stat_FORECAST_PKRLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastpkrline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast PKR Lines</div>
                    </div>
                </div>
            </div>
        <div class="col-lg-2" id="stat_FORECAST_TURLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastturline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast Turret Lines</div>
                    </div>
                </div>
            </div>
    </div>
     
                        

   
                            
<?php} else{ ?>
    
<div class="col-sm-12">
                    <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Open Moves</header>
                        <div id="openmoves" class="panel-body" style="background: #efefef">
                     
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
                <div class="desc">Total Replen Hours</div>
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
                <div class="desc">Total Auto Hours</div>
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
                <div class="desc">Total ASO Hours</div>
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
                <div class="desc">Total Special Hours</div>
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
                <div class="desc">Total Consol Hours</div>
            </div>
        </div>
    </div>
    </div>
</div>
</div>
    



<div class="row">
<div class="col-sm-12">
                    <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Forecasted Moves by Destination Location Type</header>
                        <div id="forecastedmoves" class="panel-body" style="background: #efefef">

<div class="row">
    <div class="col-lg-2" id="stat_FORECAST_SUMMARY">
        <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastsummary, 0) ?></span>
                </div>
                <div class="desc">Forecast Moves</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_FORECAST_BINLINE">
        <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
            <div class="visual">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastbinline, 0) ?></span>
                </div>
                <div class="desc">Forecast Bin Lines</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2" id="stat_FORECAST_FPLINE">
            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                <div class="visual">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastFPline, 0) ?></span>
                    </div>
                    <div class="desc">Forecast Full Pallet Lines</div>
                </div>
            </div>
        </div>
    <div class="col-lg-2" id="stat_FORECAST_DOGLINE">
            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                <div class="visual">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastdogline, 0) ?></span>
                    </div>
                    <div class="desc">Forecast Dogpound Lines</div>
                </div>
            </div>
        </div>
        <div class="col-lg-2" id="stat_FORECAST_FLOWLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastflowline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast Flow Lines</div>
                    </div>
                </div>
            </div>
        <div class="col-lg-2" id="stat_FORECAST_PKRLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastpkrline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast PKR Lines</div>
                    </div>
                </div>
            </div>
        <div class="col-lg-2" id="stat_FORECAST_TURLINE">
                <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($forecastturline, 0) ?></span>
                        </div>
                        <div class="desc">Forecast Turret Lines</div>
                    </div>
                </div>
            </div>
    </div>
     
                            
                           
<?php } ?>

