<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}



//include '../timezoneset.php';
$now = date('Y-m-d H.i.s');
$localtime = date('H:i');
$printcutoff = '17:07';
$today = date('Y-m-d');



//Not printed time
if ($localtime > $printcutoff) {
    $loose_notprintedtime = 0;
    $loose_notprintedtime_formatted = _convertToHoursMins($loose_notprintedtime);
} else {  //not after printcutoff time.  Proceed.
    $hdr_loosenotprinted = $conn1->prepare("SELECT 
                                                                                        SUM(batchtime_time_totaltime) as NOTPRINTEDTIME
                                                                                    FROM
                                                                                        printvis.looselines_batchtime_open
                                                                                    WHERE
                                                                                        batchtime_whse = $var_whse");
    $hdr_loosenotprinted->execute();
    $hdr_loosenotprinted_array = $hdr_loosenotprinted->fetchAll(pdo::FETCH_ASSOC);
    $loose_notprintedtime = $hdr_loosenotprinted_array[0]['NOTPRINTEDTIME'];
    $loose_notprintedtime_formatted = _convertToHoursMins($loose_notprintedtime);
}

//printed, not picked, time
$hdr_looseprinted = $conn1->prepare("SELECT 
                                                                            sum(batchtime_time_totaltime) AS PRINTEDTIME
                                                                        FROM
                                                                            printvis.looselines_batchtime
                                                                                LEFT JOIN
                                                                            voice_batchespicked ON voice_whse = batchtime_whse
                                                                                AND voice_batch = batchtime_cart
                                                                        WHERE
                                                                                 batchtime_whse = $var_whse
                                                                                AND voice_userid = 0
                                                                                AND batchtime_colgcount / batchtime_count_line <= .95
                                                                                AND voice_cartconfig <> ' '
                                                                                AND batchtime_count_ice / batchtime_count_line <> 1
                                                                        ORDER BY batchtime_cart");
$hdr_looseprinted->execute();
$hdr_looseprinted_array = $hdr_looseprinted->fetchAll(pdo::FETCH_ASSOC);
$loose_printedtime = $hdr_looseprinted_array[0]['PRINTEDTIME'];
$loose_printedtime_formatted = _convertToHoursMins($loose_printedtime);

//curently in picking
$hdr_loosepicking = $conn1->prepare("SELECT 
                                                                    SUM(cartpick_remaintime) as PICKINGTIME
                                                                FROM
                                                                    printvis.looselines_cartsinprocess
                                                                WHERE
                                                                    cartpick_whse = $var_whse");
$hdr_loosepicking->execute();
$hdr_loosepicking_array = $hdr_loosepicking->fetchAll(pdo::FETCH_ASSOC);
$loose_pickingtime = $hdr_loosepicking_array[0]['PICKINGTIME'];
$loose_pickingtime_formatted = _convertToHoursMins($loose_pickingtime);
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
                    <span data-counter="counterup" data-value="1349"><?php // echo $case_total_time  ?></span>
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
                    <span data-counter="counterup" data-value="1349"><?php echo $loose_notprintedtime_formatted ?></span>
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
                    <span data-counter="counterup" data-value="1349"><?php  echo $loose_printedtime_formatted  ?></span>
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
                    <span data-counter="counterup" data-value="1349"><?php  echo $loose_pickingtime_formatted  ?></span>
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
