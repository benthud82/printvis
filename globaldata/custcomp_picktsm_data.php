<?php
include_once '../sessioninclude.php';

if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}


$tsmpick = $conn1->prepare("SELECT 
    *
FROM
    custaudit.complaint_detail
        LEFT JOIN
    printvis.tsm ON PICK_TSMNUM = tsm_num
WHERE
    UPPER(tsm_name) = '$var_sqldata' and RETURNCODE in ('IBNS','WISP','WQSP')"
        . "AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK) ");
$tsmpick->execute();
$tsmpick_array = $tsmpick->fetchAll(pdo::FETCH_ASSOC);

//total number of customer complaints
$picktsm_itemcount = count($tsmpick_array);





//complaint count for yesterday for WQSP
$top_wqsp = $conn1->prepare("SELECT 
                                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                                    FROM
                                                                        custaudit.complaint_detail A
                                                                            LEFT JOIN
                                                                        slotting.itemdesignation B ON A.ITEMCODE = B.ITEM
                                                                            LEFT JOIN
                                                                        printvis.tsm C ON C.tsm_num = A.PICK_TSMNUM
                                                                    WHERE
                                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                                            AND B.WHSE = $var_whse
                                                                            AND RETURNCODE IN ('WQSP')
                                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                                            AND UPPER(PICK_TSM) = '$var_sqldata'
                                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                                    ORDER BY TRENDCOUNT DESC");
$top_wqsp->execute();
$top_wqsp_array = $top_wqsp->fetchAll(pdo::FETCH_ASSOC);

//complaint count for yesterday for WISP
$top_wisp = $conn1->prepare("SELECT 
                                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                                    FROM
                                                                        custaudit.complaint_detail A
                                                                            LEFT JOIN
                                                                        slotting.itemdesignation B ON A.ITEMCODE = B.ITEM
                                                                            LEFT JOIN
                                                                        printvis.tsm C ON C.tsm_num = A.PICK_TSMNUM
                                                                    WHERE
                                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                                            AND B.WHSE = $var_whse
                                                                            AND RETURNCODE IN ('WISP')
                                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                                            AND UPPER(PICK_TSM) = '$var_sqldata'
                                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                                    ORDER BY TRENDCOUNT DESC");
$top_wisp->execute();
$top_wisp_array = $top_wisp->fetchAll(pdo::FETCH_ASSOC);

//complaint count for yesterday for IBNS
$top_ibns = $conn1->prepare("SELECT 
                                                                        ITEMCODE, ITEM_DESC, COUNT(*) AS TRENDCOUNT
                                                                    FROM
                                                                        custaudit.complaint_detail A
                                                                            LEFT JOIN
                                                                        slotting.itemdesignation B ON A.ITEMCODE = B.ITEM
                                                                            LEFT JOIN
                                                                        printvis.tsm C ON C.tsm_num = A.PICK_TSMNUM
                                                                    WHERE
                                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                                            AND B.WHSE = $var_whse
                                                                            AND RETURNCODE IN ('IBNS')
                                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 13 WEEK)
                                                                            AND UPPER(PICK_TSM) = '$var_sqldata'
                                                                    GROUP BY ITEMCODE , ITEM_DESC , RETURNCODE
                                                                    ORDER BY TRENDCOUNT DESC");
$top_ibns->execute();
$top_ibns_array = $top_ibns->fetchAll(pdo::FETCH_ASSOC);
?>

<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption">
            <i class="icon-bubble font-green-sharp"></i>
            <span class="caption-subject font-green-sharp bold uppercase">Data for Picking TSM: <span style="background-color: black; color: #dddada"><?php echo $var_sqldata; ?></span></span>
        </div>
    </div>
    <div class="portlet-body">
        <!--TOP WQSP-->
        <div class="col-md-4 ">
            <!-- BEGIN Portlet PORTLET-->
            <div class="portlet box blue-hoki">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-exclamation-circle"></i>Top WQSP Impacts </div>
                </div>
                <?php if (count($top_wqsp_array) === 0) { ?>
                    <div class="portlet-body">
                        <!--No records-->
                        <div class="h4">No WQSP complaints in last quarter!</div>
                    </div>  <?php
                } else {
                    ?>
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
                <?php } ?>
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
                <?php if (count($top_wisp_array) === 0) { ?>
                    <div class="portlet-body">
                        <!--No records-->
                        <div class="h4">No WISP complaints in last quarter!</div>
                    </div>  <?php
                } else {
                    ?>
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
                <?php } ?>
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
                <?php if (count($top_ibns_array) === 0) { ?>
                    <div class="portlet-body">
                        <!--No records-->
                        <div class="h4">No IBNS complaints in last quarter!</div>
                    </div>  <?php
                } else {
                    ?>
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
                <?php } ?>
            </div>    
        </div>    
    </div>    
</div>    



