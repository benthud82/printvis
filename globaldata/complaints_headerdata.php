<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include '../../globalfunctions/custdbfunctions.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}
switch ($var_whse) {
    case 2:
        $slottingtable = 'slotting.2invlinesshipped';
        break;
    case 3:
        $slottingtable = 'slotting.3invlinesshipped';
        break;
    case 6:
        $slottingtable = 'slotting.6invlinesshipped';
        break;
    case 7:
        $slottingtable = 'slotting.7invlinesshipped';
        break;
    case 9:
        $slottingtable = 'slotting.9invlinesshipped';
        break;

    default:
        break;
}



$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}

//complaint count for yesterday
$hdr_complaints = $conn1->prepare("SELECT 
                                                                COUNT(*) as YESTRETURNS
                                                            FROM
                                                                custaudit.custreturns
                                                            WHERE
                                                                WHSE = $var_whse
                                                                    AND ORD_RETURNDATE = '$yesterday'");
$hdr_complaints->execute();
$hdr_complaints_array = $hdr_complaints->fetchAll(pdo::FETCH_ASSOC);
$YESTRETURNS = $hdr_complaints_array[0]['YESTRETURNS'];

//this weeks percentage
$hdr_percent = $conn1->prepare("SELECT 
                                                                1 - (COUNT(*) / (SELECT 
                                                                            SUM(INVLINES)
                                                                        FROM
                                                                            $slottingtable
                                                                        WHERE
                                                                            INVDATE >= NOW() - INTERVAL 7 DAY)) AS PERCENTACC
                                                            FROM
                                                                custaudit.custreturns
                                                            WHERE
                                                                WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                                    AND WHSE = $var_whse
                                                                    AND RETURNCODE IN ('IBNS' , 'WISP', 'WQSP')
                                                                    AND ORD_RETURNDATE >= NOW() - INTERVAL 7 DAY
                                                            ORDER BY ORD_RETURNDATE");
$hdr_percent->execute();
$hdr_percent_array = $hdr_percent->fetchAll(pdo::FETCH_ASSOC);
$precent = ($hdr_percent_array[0]['PERCENTACC'] * 100);


//trend analysis
$hdr_trend = $conn1->prepare("SELECT 
                                    CONCAT(YEAR(ORD_RETURNDATE),
                                            CASE
                                                WHEN WEEK(ORD_RETURNDATE) < 10 THEN CONCAT(0, WEEK(ORD_RETURNDATE))
                                                ELSE WEEK(ORD_RETURNDATE)
                                            END) AS YEAR_WEEK,
                                    COUNT(*) AS TRENDCOUNT
                                FROM
                                    custaudit.custreturns
                                WHERE
                                    WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                        AND WHSE = $var_whse
                                        AND RETURNCODE IN ('IBNS' , 'WISP', 'WQSP')
                                        AND CONCAT(YEAR(ORD_RETURNDATE),
                                            WEEK(ORD_RETURNDATE)) <> CONCAT(YEAR(CURDATE()), WEEK(CURDATE()))
                                        AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                GROUP BY CONCAT(YEAR(ORD_RETURNDATE),
                                        WEEK(ORD_RETURNDATE))
                                ORDER BY CONCAT(YEAR(ORD_RETURNDATE),
                                        WEEK(ORD_RETURNDATE))");
$hdr_trend->execute();
$hdr_trend_array = $hdr_trend->fetchAll(pdo::FETCH_COLUMN);
$trendarraycount = count($hdr_trend_array);
for ($i = 1; $i <= $trendarraycount; ++$i) {
    $xcordarray[] = $i;
}
$linearregression_complaint = linear_regression($xcordarray, $hdr_trend_array);
$complaint_trend = number_format($linearregression_complaint['m'], 2);
if ($complaint_trend >= 0) {
    $trendsign = '+';
    $trendclass = 'red-intense';
} else {
    $trendsign = '';
    $trendclass = 'green-jungle';
}

//DC related issues
$hdr_complaints_dc = $conn1->prepare("SELECT 
                                                                COUNT(*) as YESTRETURNS
                                                            FROM
                                                                custaudit.custreturns
                                                            WHERE
                                                                WHSE = $var_whse
                                                                    AND RETURNCODE in ('IBNS','WISP','WQSP')
                                                                    AND ORD_RETURNDATE = '$yesterday'");
$hdr_complaints_dc->execute();
$hdr_complaints_dc_array = $hdr_complaints_dc->fetchAll(pdo::FETCH_ASSOC);
$YESTRETURNS_dc = $hdr_complaints_dc_array[0]['YESTRETURNS'];
?>


<div class="row" style="padding-top: 25px">
    <div class="col-lg-3 " id="stat_totalcomplaints_dc">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-frown-o"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value="<?php echo $YESTRETURNS_dc ?>"><?php echo $YESTRETURNS_dc ?></span>
                </div>
                <div class="desc"> Yesterday's DC Complaints </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_totaltime">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-percent"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value="<?php echo $precent ?>"><?php echo $precent ?></span> <?php echo '%' ?>
                </div>
                <div class="desc"> Rolling Week Percent </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_trend">
        <div class="dashboard-stat dashboard-stat-v2 <?php echo $trendclass; ?> ">  
            <div class="visual">
                <i class="fa fa-line-chart"></i>
            </div>
            <div class="details">
                <div class="number"><?php echo $trendsign . ' '; ?>
                    <span class="yestreturns" data-counter="counterup" data-value="<?php echo $complaint_trend ?>"><?php echo $complaint_trend ?></span>
                </div>
                <div class="desc"> Rolling Quarter Trend </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 " id="stat_totalcomplaints_total">
        <div class="dashboard-stat dashboard-stat-v2 red-intense">  
            <div class="visual">
                <i class="fa fa-frown-o"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span class="yestreturns" data-counter="counterup" data-value="<?php echo $YESTRETURNS ?>"><?php echo $YESTRETURNS_dc ?></span>
                </div>
                <div class="desc"> Yesterday's Total Complaints </div>
            </div>
        </div>
    </div>
</div>


