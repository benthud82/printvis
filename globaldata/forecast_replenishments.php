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


// RENO ONLY - special logic for moves bet buildings

//  SQL FOR REPLENS FROM CASE BUILDING TO MAIN BUILDING


$sql_replensummarycasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1");
$sql_replensummarycasetomain->execute();
$array_replensummarycasetomain = $sql_replensummarycasetomain->fetchAll(pdo::FETCH_ASSOC);

$replensummarycasetomain = $array_replensummarycasetomain[0]['REPLEN_SUMMARY_CASETOMAIN'];

// count of auto moves

$sql_AUTOLINECASETOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS AUTOLINE_CASETOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'AUTO'");
$sql_AUTOLINECASETOMAIN->execute();
$array_AUTOLINECASETOMAIN = $sql_AUTOLINECASETOMAIN->fetchAll(pdo::FETCH_ASSOC);

$AUTOLINECASETOMAIN = $array_AUTOLINECASETOMAIN[0]['AUTOLINE_CASETOMAIN'];

// count of aso moves

$sql_ASOLINECASETOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS ASOLINE_CASETOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'ASO'");
$sql_ASOLINECASETOMAIN->execute();
$array_ASOLINECASETOMAIN = $sql_ASOLINECASETOMAIN->fetchAll(pdo::FETCH_ASSOC);

$ASOLINECASETOMAIN = $array_ASOLINECASETOMAIN[0]['ASOLINE_CASETOMAIN'];

// count of special moves

$sql_SPECLINECASETOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS SPECLINE_CASETOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'SPEC'");
$sql_SPECLINECASETOMAIN->execute();
$array_SPECLINECASETOMAIN = $sql_SPECLINECASETOMAIN->fetchAll(pdo::FETCH_ASSOC);

$SPECLINECASETOMAIN = $array_SPECLINECASETOMAIN[0]['SPECLINE_CASETOMAIN'];

// count of consolidation moves

$sql_CONSOLLINECASETOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS CONSOLLINE_CASETOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'CONSOL'");
$sql_CONSOLLINECASETOMAIN->execute();
$array_CONSOLLINECASETOMAIN = $sql_CONSOLLINECASETOMAIN->fetchAll(pdo::FETCH_ASSOC);

$CONSOLLINECASETOMAIN = $array_CONSOLLINECASETOMAIN[0]['CONSOLLINE_CASETOMAIN'];

// total times by move type
$sql_totalreplencasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_TOTAL_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1");
$sql_totalreplencasetomain->execute();
$array_totalreplencasetomain = $sql_totalreplencasetomain->fetchAll(pdo::FETCH_ASSOC);

$totalreplencasetomain = $array_totalreplencasetomain[0]['TIME_TOTAL_CASETOMAIN'];

// total auto time
$sql_totalautocasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TOTAL_AUTO_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND openmovetimes_type = 'auto'");
$sql_totalautocasetomain->execute();
$array_totalautocasetomain = $sql_totalautocasetomain->fetchAll(pdo::FETCH_ASSOC);

$totalautocasetomain = $array_totalautocasetomain[0]['TOTAL_AUTO_CASETOMAIN'];


// total aso time
$sql_totalasocasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_ASO_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 and openmovetimes_type = 'aso'");
$sql_totalasocasetomain->execute();
$array_totalasocasetomain = $sql_totalasocasetomain->fetchAll(pdo::FETCH_ASSOC);

$totalasocasetomain = $array_totalasocasetomain[0]['TIME_ASO_CASETOMAIN'];


// total special times
$sql_totalspeccasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_SPEC_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 and openmovetimes_type = 'spec'");
$sql_totalspeccasetomain->execute();
$array_totalspeccasetomain = $sql_totalspeccasetomain->fetchAll(pdo::FETCH_ASSOC);

$totalspeccasetomain = $array_totalspeccasetomain[0]['TIME_SPEC_CASETOMAIN'];


// total consol time
$sql_totalconsolcasetomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_CONSOL_CASETOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 and openmovetimes_type = 'consol'");
$sql_totalconsolcasetomain->execute();
$array_totalconsolcasetomain = $sql_totalconsolcasetomain->fetchAll(pdo::FETCH_ASSOC);

$totalconsolcasetomain = $array_totalconsolcasetomain[0]['TIME_CONSOL_CASETOMAIN'];



///  SQL FOR REPLENS FROM MAIN TO MAIN

$sql_replensummarymaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1");
$sql_replensummarymaintomain->execute();
$array_replensummarymaintomain = $sql_replensummarymaintomain->fetchAll(pdo::FETCH_ASSOC);

$replensummarymaintomain = $array_replensummarymaintomain[0]['REPLEN_SUMMARY_MAINTOMAIN'];

// count of auto moves

$sql_AUTOLINEMAINTOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS AUTOLINE_MAINTOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'AUTO'");
$sql_AUTOLINEMAINTOMAIN->execute();
$array_AUTOLINEMAINTOMAIN = $sql_AUTOLINEMAINTOMAIN->fetchAll(pdo::FETCH_ASSOC);

$AUTOLINEMAINTOMAIN = $array_AUTOLINEMAINTOMAIN[0]['AUTOLINE_MAINTOMAIN'];

// count of aso moves

$sql_ASOLINEMAINTOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS ASOLINE_MAINTOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'ASO'");
$sql_ASOLINEMAINTOMAIN->execute();
$array_ASOLINEMAINTOMAIN = $sql_ASOLINEMAINTOMAIN->fetchAll(pdo::FETCH_ASSOC);

$ASOLINEMAINTOMAIN = $array_ASOLINEMAINTOMAIN[0]['ASOLINE_MAINTOMAIN'];

// count of special moves

$sql_SPECLINEMAINTOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS SPECLINE_MAINTOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'SPEC'");
$sql_SPECLINEMAINTOMAIN->execute();
$array_SPECLINEMAINTOMAIN = $sql_SPECLINEMAINTOMAIN->fetchAll(pdo::FETCH_ASSOC);

$SPECLINEMAINTOMAIN = $array_SPECLINEMAINTOMAIN[0]['SPECLINE_MAINTOMAIN'];

// count of consolidation moves

$sql_CONSOLLINEMAINTOMAIN = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS CONSOLLINE_MAINTOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_TYPE = 'CONSOL'");
$sql_CONSOLLINEMAINTOMAIN->execute();
$array_CONSOLLINEMAINTOMAIN = $sql_CONSOLLINEMAINTOMAIN->fetchAll(pdo::FETCH_ASSOC);

$CONSOLLINEMAINTOMAIN = $array_CONSOLLINEMAINTOMAIN[0]['CONSOLLINE_MAINTOMAIN'];


// total times by move type
$sql_totalreplenmaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_TOTAL_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1");
$sql_totalreplenmaintomain->execute();
$array_totalreplenmaintomain = $sql_totalreplenmaintomain->fetchAll(pdo::FETCH_ASSOC);

$totalreplenmaintomain = $array_totalreplenmaintomain[0]['TIME_TOTAL_MAINTOMAIN'];

// total auto time
$sql_totalautomaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TOTAL_AUTO_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 AND openmovetimes_type = 'auto'");
$sql_totalautomaintomain->execute();
$array_totalautomaintomain = $sql_totalautomaintomain->fetchAll(pdo::FETCH_ASSOC);

$totalautomaintomain = $array_totalautomaintomain[0]['TOTAL_AUTO_MAINTOMAIN'];


// total aso time
$sql_totalasomaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_ASO_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 and openmovetimes_type = 'aso'");
$sql_totalasomaintomain->execute();
$array_totalasomaintomain = $sql_totalasomaintomain->fetchAll(pdo::FETCH_ASSOC);

$totalasomaintomain = $array_totalasomaintomain[0]['TIME_ASO_MAINTOMAIN'];


// total special times
$sql_totalspecmaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_SPEC_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 and openmovetimes_type = 'spec'");
$sql_totalspecmaintomain->execute();
$array_totalspecmaintomain = $sql_totalspecmaintomain->fetchAll(pdo::FETCH_ASSOC);

$totalspecmaintomain = $array_totalspecmaintomain[0]['TIME_SPEC_MAINTOMAIN'];


// total consol time
$sql_totalconsolmaintomain = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_CONSOL_MAINTOMAIN
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 1 and openmovetimes_type = 'consol'");
$sql_totalconsolmaintomain->execute();
$array_totalconsolmaintomain = $sql_totalconsolmaintomain->fetchAll(pdo::FETCH_ASSOC);

$totalconsolmaintomain = $array_totalconsolmaintomain[0]['TIME_CONSOL_MAINTOMAIN'];






// SQL FOR REPLENS FROM MAIN BLDG TO CASE BLDG

$sql_totalreplenmaintocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY_B1TOB2
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 2");
$sql_totalreplenmaintocase->execute();
$array_totalreplenmaintocase = $sql_totalreplenmaintocase->fetchAll(pdo::FETCH_ASSOC);

$totalreplenmaintocase = $array_totalreplenmaintocase[0]['REPLEN_SUMMARY_B1TOB2'];



$sql_replensummarytocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_totlines) AS REPLEN_SUMMARY_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2");
$sql_replensummarytocase->execute();
$array_replensummarytocase = $sql_replensummarytocase->fetchAll(pdo::FETCH_ASSOC);

$replensummarytocase = $array_replensummarytocase[0]['REPLEN_SUMMARY_TOCASE'];

// count of auto moves

$sql_AUTOLINETOCASE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS AUTOLINE_TOCASE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                            openmovetimes_whse = 3 and openmovetimes_tobldg = 2 AND OPENMOVETIMES_TYPE = 'AUTO'");

$sql_AUTOLINETOCASE->execute();
$array_AUTOLINETOCASE = $sql_AUTOLINETOCASE->fetchAll(pdo::FETCH_ASSOC);

$AUTOLINETOCASE = $array_AUTOLINETOCASE[0]['AUTOLINE_TOCASE'];

// count of aso moves

$sql_ASOLINETOCASE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS ASOLINE_TOCASE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 AND OPENMOVETIMES_TYPE = 'ASO'");
$sql_ASOLINETOCASE->execute();
$array_ASOLINETOCASE = $sql_ASOLINETOCASE->fetchAll(pdo::FETCH_ASSOC);

$ASOLINETOCASE = $array_ASOLINETOCASE[0]['ASOLINE_TOCASE'];

// count of special moves

$sql_SPECLINETOCASE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS SPECLINE_TOCASE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 AND OPENMOVETIMES_TYPE = 'SPEC'");
$sql_SPECLINETOCASE->execute();
$array_SPECLINETOCASE = $sql_SPECLINETOCASE->fetchAll(pdo::FETCH_ASSOC);

$SPECLINETOCASE = $array_SPECLINETOCASE[0]['SPECLINE_TOCASE'];

// count of consolidation moves

$sql_CONSOLLINETOCASE = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                 openmovetimes_type as type,                                
                                SUM(openmovetimes_totlines) AS CONSOLLINE_TOCASE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 AND OPENMOVETIMES_TYPE = 'CONSOL'");
$sql_CONSOLLINETOCASE->execute();
$array_CONSOLLINETOCASE = $sql_CONSOLLINETOCASE->fetchAll(pdo::FETCH_ASSOC);

$CONSOLLINETOCASE = $array_CONSOLLINETOCASE[0]['CONSOLLINE_TOCASE'];


// total times by move type
$sql_totalreplentocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_TOTAL_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2");
$sql_totalreplentocase->execute();
$array_totalreplentocase = $sql_totalreplentocase->fetchAll(pdo::FETCH_ASSOC);

$totalreplentocase = $array_totalreplentocase[0]['TIME_TOTAL_TOCASE'];

// total auto time
$sql_totalautotocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TOTAL_AUTO_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 AND openmovetimes_type = 'auto'");
$sql_totalautotocase->execute();
$array_totalautotocase = $sql_totalautotocase->fetchAll(pdo::FETCH_ASSOC);

$totalautotocase = $array_totalautotocase[0]['TOTAL_AUTO_TOCASE'];


// total aso time
$sql_totalasotocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_ASO_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 and openmovetimes_type = 'aso'");
$sql_totalasotocase->execute();
$array_totalasotocase = $sql_totalasotocase->fetchAll(pdo::FETCH_ASSOC);

$totalasotocase = $array_totalasotocase[0]['TIME_ASO_TOCASE'];


// total special times
$sql_totalspectocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_SPEC_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 and openmovetimes_type = 'spec'");
$sql_totalspectocase->execute();
$array_totalspectocase = $sql_totalspectocase->fetchAll(pdo::FETCH_ASSOC);

$totalspectocase = $array_totalspectocase[0]['TIME_SPEC_TOCASE'];


// total consol time
$sql_totalconsoltocase = $conn1->prepare("SELECT 
                                SUM(openmovetimes_tottime) AS TIME_CONSOL_TOCASE
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_tobldg = 2 and openmovetimes_type = 'consol'");
$sql_totalconsoltocase->execute();
$array_totalconsoltocase = $sql_totalconsoltocase->fetchAll(pdo::FETCH_ASSOC);

$totalconsoltocase = $array_totalconsoltocase[0]['TIME_CONSOL_TOCASE'];


// Reno - On drop zone

$sql_dropzonecasetomain = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                SUM(openmovetimes_totlines) AS DROPZONE_CASETOMAIN
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 2 and openmovetimes_tobldg = 1 AND OPENMOVETIMES_EQUIP LIKE 'DRP%'
                                group by whse");
$sql_dropzonecasetomain->execute();
$array_dropzonecasetomain = $sql_dropzonecasetomain->fetchAll(pdo::FETCH_ASSOC);

$dropzonecasetomain = $array_dropzonecasetomain[0]['DROPZONE_CASETOMAIN'];





$sql_dropzonemaintocase = $conn1->prepare("SELECT 
                                openmovetimes_whse as whse,
                                SUM(openmovetimes_totlines) AS DROPZONE_MAINTOCASE
                                                              
                            FROM
                                printvis.openmoves_times
                            WHERE
                                openmovetimes_whse = 3 and openmovetimes_frombldg = 1 and openmovetimes_tobldg = 2 AND OPENMOVETIMES_EQUIP LIKE 'DRP%'
                                group by whse");
$sql_dropzonemaintocase->execute();
$array_dropzonemaintocase = $sql_dropzonemaintocase->fetchAll(pdo::FETCH_ASSOC);

$dropzonemaintocase = $array_dropzonemaintocase[0]['DROPZONE_MAINTOCASE'];


?> 


<?php if ($whsesel == 3) { ?>

    <!--Open Moves-->
    <div class="row">
        <div class="col-sm-12">
            <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                <header class="panel-heading bg bg-inverse h2">Total Open Moves</header>
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
                                    <div class="desc">Open Moves</div>
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
    </div>
<div class="row">
        <div class="col-sm-12">
            <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                <header class="panel-heading bg bg-inverse h2">Open Moves - Main Building to Main Building</header>
                <div id="openmoves" class="panel-body" style="background: #efefef">

                    <!--Line Count-->
                    <div class="row">
                        <div class="col-lg-2" id="stat_REPLEN_SUMMARY_MAINTOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($replensummarymaintomain, 0) ?></span>
                                    </div>
                                    <div class="desc">Open Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_AUTOLINE_MAINTOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($AUTOLINEMAINTOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">AUTO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_ASOLINE_MAINTOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($ASOLINEMAINTOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">ASO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_SPECLINE_MAINTOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($SPECLINEMAINTOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">SPEC Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_CONSOLLINE_MAINTOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($CONSOLLINEMAINTOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">CONSOL Moves</div>
                                </div>
                            </div>
                        </div>
                    </div>
                 <div class="row">
                        <div class="col-lg-2" id="stat_totalreplenmaintomain">
                            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalreplenmaintomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Replen Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalautomaintomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalautomaintomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Auto Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalasomaintomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalasomaintomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total ASO Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalspecmaintomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalspecmaintomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Special Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalconsolmaintomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalconsolmaintomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Consol Hours</div>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>
        </div>
    </div>
   
    
    
    
    <div class="row">
        <div class="col-sm-12">
            <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                <header class="panel-heading bg bg-inverse h2">Open Moves - Case Building to Main Building</header>
                <div id="openmoves" class="panel-body" style="background: #efefef">

                    <!--Line Count-->
                    <div class="row">
                        <div class="col-lg-2" id="stat_REPLEN_SUMMARY_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($replensummarycasetomain, 0) ?></span>
                                    </div>
                                    <div class="desc">Open Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_AUTOLINE_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($AUTOLINECASETOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">AUTO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_ASOLINE_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($ASOLINECASETOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">ASO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_SPECLINE_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($SPECLINECASETOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">SPEC Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_CONSOLLINE_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($CONSOLLINECASETOMAIN, 0) ?></span>
                                    </div>
                                    <div class="desc">CONSOL Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_DROPZONE_CASETOMAIN">
                            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($dropzonecasetomain, 0) ?></span>
                                    </div>
                                    <div class="desc">In Drop Zone</div>
                                </div>
                            </div>
                        </div>
                    </div>
                 <div class="row">
                        <div class="col-lg-2" id="stat_totalreplencasetomain">
                            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalreplencasetomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Replen Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalautocasetomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalautocasetomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Auto Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalasocasetomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalasocasetomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total ASO Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalspeccasetomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalspeccasetomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Special Hours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_totalconsolcasetomain">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalconsolcasetomain, 1) ?></span>
                                    </div>
                                    <div class="desc">Total Consol Hours</div>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <section class="panel hidewrapper" style="margin-bottom: 50px; margin-top: 20px;"> 
                <header class="panel-heading bg bg-inverse h2">Open Moves - To Case Building</header>
                <div id="openmoves" class="panel-body" style="background: #efefef">

                    <!--Line Count-->
                    <div class="row">
                        <div class="col-lg-2" id="stat_REPLEN_SUMMARY_TOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($replensummarytocase, 0) ?></span>
                                    </div>
                                    <div class="desc">Open Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_AUTOLINE_TOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($AUTOLINETOCASE, 0) ?></span>
                                    </div>
                                    <div class="desc">AUTO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_ASOLINE_TOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($ASOLINETOCASE, 0) ?></span>
                                    </div>
                                    <div class="desc">ASO Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_SPECLINE_TOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($SPECLINETOCASE, 0) ?></span>
                                    </div>
                                    <div class="desc">SPEC Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_CONSOLLINE_TOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($CONSOLLINETOCASE, 0) ?></span>
                                    </div>
                                    <div class="desc">CONSOL Moves</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2" id="stat_REPLEN_SUMMARY_B1TOB2">
                            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($totalreplenmaintocase, 0) ?></span>
                                    </div>
                                    <div class="desc">Moves from Main</div>
                                </div>
                            </div>
                        </div
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
                     <div class="col-lg-2" id="stat_DROPZONE_MAINTOCASE">
                            <div class="dashboard-stat dashboard-stat-v2 green-jungle">  
                                <div class="visual">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div class="details">
                                    <div class="number">
                                        <span class="yestreturns" data-counter="counterup" data-value=""><?php echo number_format($dropzonemaintocase, 0) ?></span>
                                    </div>
                                    <div class="desc">In Drop Zone</div>
                                </div>
                            </div>
                        </div
                    </div> 
                </div>
        </div>
    </div> 
    
<?php } else { ?>

    <div class="row">
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
            </section>
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

