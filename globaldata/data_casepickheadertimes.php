<?php
include '../../connections/conn_printvis.php';
include '../functions/functions_totetimes.php';
include '../sessioninclude.php';



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

$caseheadersql = $conn1->prepare(" SELECT 
                                                                            hourbucket_datetime,
                                                                            hourbucket_notprinted,
                                                                            hourbucket_printed,
                                                                            hourbucket_picking,
                                                                            hourbucket_remaining
                                                                        FROM
                                                                            printvis.casevis_hourbuckets
                                                                        WHERE
                                                                            hourbucket_whse = $var_whse
                                                                                AND hourbucket_build = $building
                                                                                AND hourbucket_datetime = (SELECT 
                                                                                    MAX(hourbucket_datetime)
                                                                                FROM
                                                                                    printvis.casevis_hourbuckets
                                                                                WHERE
                                                                                    hourbucket_whse = $var_whse
                                                                                        AND hourbucket_build = $building)");
$caseheadersql->execute();
$caseheader_array = $caseheadersql->fetchAll(pdo::FETCH_ASSOC);

$case_notprinttime = $caseheader_array[0]['hourbucket_notprinted'];
$case_printtime = $caseheader_array[0]['hourbucket_printed'];
$case_picking = $caseheader_array[0]['hourbucket_picking'];

$case_printtime_formatted = _convertToHoursMins($case_printtime);
$case_notprinttime_formatted = _convertToHoursMins($case_notprinttime);
$case_picking_formatted = _convertToHoursMins($case_picking);

$case_total = $case_notprinttime + $case_printtime + $case_picking;
$case_total_formatted = _convertToHoursMins($case_total);
?>
<div class="row" style="padding: 10px;">
    Last refresh time: <?php echo date('H:i:s'); ?>
</div>
<div class="row" style="padding: 10px;     display: -webkit-inline-box;">
    <?php echo 'Time till next refresh:   ' ?>
    <div id="countdownExample" style="padding-left: 5px;">
        <div class="values"></div>
    </div>

</div>

<div class="row" style="padding-top: 25px">
    <div class="col-lg-3 " id="stat_totaltime">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-server"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $case_total_formatted ?></span>
                </div>
                <div class="desc"> Total Hours Needed </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_notprintedtime">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-server"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $case_notprinttime_formatted ?></span>
                </div>
                <div class="desc"> Total Hours not Printed </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_printed">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-print"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $case_printtime_formatted ?></span>
                </div>
                <div class="desc"> Total Hours Printed Waiting to be Picked </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_beingpicked">
        <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
            <div class="visual">
                <i class="fa fa-play-circle-o"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $case_picking_formatted ?></span>
                </div>
                <div class="desc"> Total Hours Remaining in Picking </div>
            </div>
        </div>
    </div>
</div>



<script>
    var timer = new Timer();
    timer.start({countdown: true, startValues: {seconds: 240}});
    $('#countdownExample .values').html(timer.getTimeValues().toString());
    timer.addEventListener('secondsUpdated', function (e) {
        $('#countdownExample .values').html(timer.getTimeValues().toString());
    });
</script>

