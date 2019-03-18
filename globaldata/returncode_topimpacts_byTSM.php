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


$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}

//complaint count for yesterday for WQSP
$top_wqsp = $conn1->prepare("SELECT 
                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                    FROM
                                                        custaudit.custreturns A
                                                            LEFT JOIN
                                                        slotting.itemdesignation B ON A.WHSE = B.WHSE AND A.ITEMCODE = B.ITEM
                                                    WHERE
                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                            AND A.WHSE = $var_whse
                                                            AND RETURNCODE IN ('WQSP')
                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                    ORDER BY TRENDCOUNT DESC
                                                    LIMIT 10");
$top_wqsp->execute();
$top_wqsp_array = $top_wqsp->fetchAll(pdo::FETCH_ASSOC);

//complaint count for yesterday for WISP
$top_wisp = $conn1->prepare("SELECT 
                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                    FROM
                                                        custaudit.custreturns A
                                                            LEFT JOIN
                                                        slotting.itemdesignation B ON A.WHSE = B.WHSE AND A.ITEMCODE = B.ITEM
                                                    WHERE
                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                            AND A.WHSE = $var_whse
                                                            AND RETURNCODE IN ('WISP')
                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                    HAVING TRENDCOUNT > 1
                                                    ORDER BY TRENDCOUNT DESC
                                                    LIMIT 10");
$top_wisp->execute();
$top_wisp_array = $top_wisp->fetchAll(pdo::FETCH_ASSOC);

//complaint count for yesterday for IBNS
$top_ibns = $conn1->prepare("SELECT 
                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                    FROM
                                                        custaudit.custreturns A
                                                            LEFT JOIN
                                                        slotting.itemdesignation B ON A.WHSE = B.WHSE AND A.ITEMCODE = B.ITEM
                                                    WHERE
                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                            AND A.WHSE = $var_whse
                                                            AND RETURNCODE IN ('IBNS')
                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                    HAVING TRENDCOUNT > 1
                                                    ORDER BY TRENDCOUNT DESC
                                                    LIMIT 10");
$top_ibns->execute();
$top_ibns_array = $top_ibns->fetchAll(pdo::FETCH_ASSOC);
?>

<!--TOP WQSP-->
<div class="col-md-4 ">
    <!-- BEGIN Portlet PORTLET-->
    <div class="portlet box blue-hoki">
        <div class="portlet-title">
            <div class="caption">
                <i class="fa fa-exclamation-circle"></i>Top WQSP Impacts </div>
        </div>
        <div class="portlet-body">
            <!--start of div table-->
            <div class="" id="divtable_top_wqsp" style="padding-bottom: 51px">
                <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
                    <div class='widget-content widget-table'  style="position: relative;">
                        <div class='divtable'>
                            <div id="" class='divtableheader' style="padding-top">
                                <div class='divtabletitle width20' >Item</div>
                                <div class='divtabletitle width60' >Description</div>
                                <div class='divtabletitle width20' >Total Complaints</div>
                            </div>
                            <?php
                            foreach ($top_wqsp_array as $key => $value) {
                                ?>
                                <div id="<?php echo $top_wqsp_array[$key]['ITEMCODE']; ?>"class='divtablerow itemdetailexpand greyhover batchclick' data-date="<?php echo $top_wqsp_array[$key]['ITEMCODE']; ?>">
                                    <div class='divtabledata width20' ><?php echo $top_wqsp_array[$key]['ITEMCODE']; ?></div>
                                    <div class='divtabledata width60' style="text-align: left;"><?php echo $top_wqsp_array[$key]['ITEM_DESC']; ?></div>
                                    <div class='divtabledata width20' ><?php echo $top_wqsp_array[$key]['TRENDCOUNT']; ?></div>

                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>    
    </div>    
</div>    

<!--TOP WISP-->
<div class="col-md-4 ">
    <!-- BEGIN Portlet PORTLET-->
    <div class="portlet box blue-hoki">
        <div class="portlet-title">
            <div class="caption">
                <i class="fa fa-exclamation-circle"></i>Top WISP Impacts </div>
        </div>
        <div class="portlet-body">
            <!--start of div table-->
            <div class="" id="divtable_top_wisp" style="padding-bottom: 51px">
                <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
                    <div class='widget-content widget-table'  style="position: relative;">
                        <div class='divtable'>
                            <div id="" class='divtableheader' style="padding-top">
                                <div class='divtabletitle width20' >Item</div>
                                <div class='divtabletitle width60' >Description</div>
                                <div class='divtabletitle width20' >Total Complaints</div>
                            </div>
                            <?php
                            foreach ($top_wisp_array as $key => $value) {
                                ?>
                                <div id="<?php echo $top_wisp_array[$key]['ITEMCODE']; ?>"class='divtablerow itemdetailexpand greyhover batchclick' data-date="<?php echo $top_wisp_array[$key]['ITEMCODE']; ?>">
                                    <div class='divtabledata width20' ><?php echo $top_wisp_array[$key]['ITEMCODE']; ?></div>
                                    <div class='divtabledata width60' style="text-align: left;"><?php echo $top_wisp_array[$key]['ITEM_DESC']; ?></div>
                                    <div class='divtabledata width20' ><?php echo $top_wisp_array[$key]['TRENDCOUNT']; ?></div>

                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>    
    </div>    
</div>    

<!--TOP IBNS-->
<div class="col-md-4 ">
    <!-- BEGIN Portlet PORTLET-->
    <div class="portlet box blue-hoki">
        <div class="portlet-title">
            <div class="caption">
                <i class="fa fa-exclamation-circle"></i>Top IBNS Impacts </div>
        </div>
        <div class="portlet-body">
            <!--start of div table-->
            <div class="" id="divtable_top_ibns" style="padding-bottom: 51px">
                <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
                    <div class='widget-content widget-table'  style="position: relative;">
                        <div class='divtable'>
                            <div id="" class='divtableheader' style="padding-top">
                                <div class='divtabletitle width20' >Item</div>
                                <div class='divtabletitle width60' >Description</div>
                                <div class='divtabletitle width20' >Total Complaints</div>
                            </div>
                            <?php
                            foreach ($top_ibns_array as $key => $value) {
                                ?>
                                <div id="<?php echo $top_ibns_array[$key]['ITEMCODE']; ?>"class='divtablerow itemdetailexpand greyhover batchclick' data-date="<?php echo $top_ibns_array[$key]['ITEMCODE']; ?>">
                                    <div class='divtabledata width20' ><?php echo $top_ibns_array[$key]['ITEMCODE']; ?></div>
                                    <div class='divtabledata width60' style="text-align: left;"><?php echo $top_ibns_array[$key]['ITEM_DESC']; ?></div>
                                    <div class='divtabledata width20' ><?php echo $top_ibns_array[$key]['TRENDCOUNT']; ?></div>

                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>    
    </div>    
</div>    
