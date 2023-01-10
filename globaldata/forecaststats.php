<?php
include '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';

if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}
$today = date('Y-m-d');
if (isset($_POST['building'])) {
    $building = intval($_POST['building']);
} else {
    echo 'bre';
}

//hours remaining in building
$forstat_hoursremain_sql = $conn1->prepare("SELECT 
                                                                                                    SUM(t.ESTIMATED_ONTASK_HOURS) AS TOTALHOURS
                                                                                                FROM
                                                                                                    (SELECT 
                                                                                                        @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
                                                                                                            @WORK_HOURS:=CASE
                                                                                                                WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
                                                                                                                WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
                                                                                                                WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
                                                                                                                ELSE @SCHEDULED_HOURS - 1.25
                                                                                                            END AS WORK_HOURS,
                                                                                                            @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
                                                                                                            @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
                                                                                                            @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS
                                                                                                    FROM
                                                                                                        printvis.tsmshift
                                                                                                    WHERE
                                                                                                        SHIFT_WHSE = $var_whse AND SHIFT_BUILD = $building
                                                                                                            AND SHIFT_INCLUDEHOURS = 1
                                                                                                            AND SHIFT_CORL = 'CASE') t");
$forstat_hoursremain_sql->execute();
$forstat_hoursremain_array = $forstat_hoursremain_sql->fetchAll(pdo::FETCH_ASSOC);

$forstat_hoursremain = round(($forstat_hoursremain_array[0]['TOTALHOURS']), 1);

//total forecasted hours needed
$forstat_forecasthours_sql = $conn1->prepare("SELECT 
                                                                                    SUM(fcase_minuteforecast) / 60 AS FORECAST_TOT
                                                                                FROM
                                                                                    printvis.forecast_case
                                                                                WHERE
                                                                                    fcase_date = '$today'
                                                                                        AND fcase_whse = $whsesel
                                                                                        AND fcase_build = $building");
$forstat_forecasthours_sql->execute();
$forstat_forecasthours_array = $forstat_forecasthours_sql->fetchAll(pdo::FETCH_ASSOC);

$forestat_forecasthoursneeded = round(($forstat_forecasthours_array[0]['FORECAST_TOT']), 1);

//Is volume trending above or below forecast and what are the extra/fewer hours needed
$forstat_trend_sql = $conn1->prepare("SELECT 
                                                                        t.fcase_hour,
                                                                       t.fcase_minuteforecast,
                                                                        SUM(caseactbyhour_minutes) AS ACTMINTOT,
                                                                        (SELECT 
                                                                                SUM(x.fcase_minuteforecast)
                                                                            FROM
                                                                                printvis.forecast_case x
                                                                            WHERE
                                                                                x.fcase_hour <= t.fcase_hour
                                                                                    AND x.fcase_whse = $whsesel
                                                                                    AND x.fcase_build = $building
                                                                                    AND x.fcase_date = '$today') AS cumulative_sum
                                                                    FROM
                                                                        printvis.forecast_case t
                                                                            LEFT JOIN
                                                                        printvis.case_actuallaborbyhour ON fcase_hour = caseactbyhour_hour
                                                                            AND caseactbyhour_whse = fcase_whse
                                                                            AND caseactbyhour_build = fcase_build
                                                                            AND caseactbyhour_date = fcase_date
                                                                            AND caseactbyhour_equip = fcase_equipment
                                                                    WHERE
                                                                        fcase_whse = $whsesel AND fcase_build = $building
                                                                            AND fcase_date = '$today'
                                                                            AND CASE
                                                                            WHEN $whsesel = 3 AND HOUR(NOW()) - 3 < 6 THEN 6
                                                                            WHEN $whsesel = 3 THEN HOUR(NOW()) - 3
                                                                            WHEN $whsesel = 7 AND HOUR(NOW()) - 1 < 6 THEN 6
                                                                            WHEN $whsesel = 7 THEN HOUR(NOW()) - 1
                                                                            ELSE HOUR(NOW())
                                                                        END = t.fcase_hour
                                                                    GROUP BY fcase_hour , CASE
                                                                        WHEN $whsesel = 3 AND HOUR(NOW()) - 3 < 6 THEN 6
                                                                        WHEN $whsesel = 3 THEN HOUR(NOW()) - 3
                                                                        WHEN $whsesel = 7 AND HOUR(NOW()) - 1 < 6 THEN 6
                                                                        WHEN $whsesel = 7 THEN HOUR(NOW()) - 1
                                                                        ELSE HOUR(NOW())
                                                                    END
                                                                    ORDER BY fcase_hour ASC");
$forstat_trend_sql->execute();
$forstat_trend_array = $forstat_trend_sql->fetchAll(pdo::FETCH_ASSOC);
$currentminpercent = (date('i') / 60);
if ($forstat_trend_array) {
    $actualforecastforhour = $forstat_trend_array[0]['cumulative_sum'] - ($forstat_trend_array[0]['fcase_minuteforecast'] * (1 - $currentminpercent));
    $actminreceived = $forstat_trend_array[0]['ACTMINTOT'];
} else {
    $actualforecastforhour = 1;
    $actminreceived = 1;
}
if ($actminreceived >= $actualforecastforhour) {
    $hours = round((($actminreceived - $actualforecastforhour) / 60), 1);
    $tsms = round($hours / 6.75, 1);

    $trendstatement = " you are trending ABOVE forecast by $hours hours (about $tsms TSMs).";
} else {
    $hours = round((($actminreceived - $actualforecastforhour) / 60), 1);
    $tsms = round($hours / 6.75, 1);

    $trendstatement = " you are trending BELOW forecast by " . ($hours * -1) . " hours (about " . $tsms * -1 . "  TSMs).";
}



if ($forestat_forecasthoursneeded == $forstat_hoursremain) {
    $forecasttoavail = ' available hours is equal to forecasted hours.';
} elseif ($forestat_forecasthoursneeded > $forstat_hoursremain) {
    $hoursshort = $forestat_forecasthoursneeded - $forstat_hoursremain;
    $forecasttoavail = " you are short $hoursshort hours (about " . round($hoursshort / 6.75, 1) . " TSMs).";
} else {
    $hourexcess = $forstat_hoursremain - $forestat_forecasthoursneeded;
    $forecasttoavail = " you have $hourexcess excess hours (about " . round($hourexcess / 7, 1) . " TSMs).";
}

//volme adjust to forecast calculation
$voladjusthoursneeded = $forestat_forecasthoursneeded + $hours;
if ($voladjusthoursneeded == $forstat_hoursremain) {
    $forecasttoavail_voladjust = ' available hours is equal to volume adjusted hours.';
} elseif ($voladjusthoursneeded > $forstat_hoursremain) {
    $hoursshort = $voladjusthoursneeded - $forstat_hoursremain;
    $forecasttoavail_voladjust = " you are short $hoursshort hours (about " . round($hoursshort / 6.75, 1) . " TSMs).";
} else {
    $hourexcess = $forstat_hoursremain - $voladjusthoursneeded;
    $forecasttoavail_voladjust = " you have $hourexcess excess hours (about " . round($hourexcess / 6.75, 1) . " TSMs).";
}
?>







<div class="portlet-body" style="padding-left: 15px;
     ">
    <h3></h3>
    <div class="row">
        <div class="col-md-6">
            <blockquote>
                <p> <?php echo "Today's available hours are $forstat_hoursremain on a predicted forecast of $forestat_forecasthoursneeded hours." ?> </p>
            </blockquote>
        </div>
        <div class="col-md-6">
            <blockquote>
                <p> <?php echo "Based off today's volume already received, $trendstatement" ?> </p>
            </blockquote>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <blockquote>
                <p> <?php echo "Based off today's forecast, $forecasttoavail" ?> </p>
            </blockquote>
        </div>
        <div class="col-md-6">
            <blockquote>
                <p> <?php echo "Taking today's volume adjust forecast into account, $forecasttoavail_voladjust" ?> </p>
            </blockquote>
        </div>
    </div>





</div>

