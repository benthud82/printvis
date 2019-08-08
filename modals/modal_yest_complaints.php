<?php
include_once '../connections/conn_printvis.php';
include_once '../globalfunctions/custdbfunctions.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}


//Basic returns detail
$YESTRETURNS = $conn1->prepare("SELECT 
                                                                ORD_RETURNDATE,
                                                                SHIPDATEJ,
                                                                RETURNCODE,
                                                                ITEMCODE,
                                                                WCSNUM,
                                                                WONUM,
                                                                LPNUM
                                                            FROM
                                                                custaudit.custreturns
                                                            WHERE
                                                                WHSE = $whsesel
                                                                    AND RETURNCODE IN ('IBNS' , 'WISP', 'WQSP')
                                                                    AND ORD_RETURNDATE = '$yesterday'
                                                            ORDER BY SHIPDATEJ");
$YESTRETURNS->execute();
$YESTRETURNS_array = $YESTRETURNS->fetchAll(pdo::FETCH_ASSOC);
?>
<!--Modal to show batch level detail-->
<div id="modal_yestreturns" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Yesterday's DC Complaints</h4>
            </div>
            <div class="modal-body">
                <!--body header stats-->
                <div class="portlet light portlet-fit ">
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-xs-12">
                                <div class="mt-element-ribbon bg-grey-steel">
                                    <div class="ribbon ribbon-color-default uppercase">Complaint Date</div>
                                    <p class="ribbon-content ribbon-content-large"><?php echo $YESTRETURNS_array[0]['ORD_RETURNDATE']; ?></p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-xs-12">
                                <div class="mt-element-ribbon bg-grey-steel">
                                    <div class="ribbon ribbon-color-default uppercase">DC Complaints</div>
                                    <p class="ribbon-content ribbon-content-large"><?php echo count($YESTRETURNS_array); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--start of div table-->
                <div class="" id="divtablecontainer">
                    <div class='widget-content widget-table'  style="position: relative;">
                        <div class='divtable'>
                            <div class='divtableheader'>
                                <div class='divtabletitle width14_28' >Complaint Date</div>
                                <div class='divtabletitle width14_28' >Ship Date</div>
                                <div class='divtabletitle width14_28' >Complaint Code</div>
                                <div class='divtabletitle width14_28' >Item</div>
                                <div class='divtabletitle width14_28' >WCS #</div>
                                <div class='divtabletitle width14_28' >WO #</div>
                                <div class='divtabletitle width14_28' >LP #</div>
                            </div>
                            <?php foreach ($YESTRETURNS_array as $key => $value) { ?>
                                <div class='divtablerow itemdetailexpand'>
                                    <div class='divtabledata width14_28 clickable custcomplaint' data-postdesc="ORD_RETURNDATE" data-postval="<?php echo $YESTRETURNS_array[$key]['ORD_RETURNDATE']; ?>"> <?php echo $YESTRETURNS_array[$key]['ORD_RETURNDATE']; ?> </div>
                                    <div class='divtabledata width14_28 ' data-postdesc="SHIPDATEJ" data-postval="<?php echo date('Y-m-d', strtotime(_yydddtogregdate($YESTRETURNS_array[$key]['SHIPDATEJ']))); ?>"> <?php echo date('Y-m-d', strtotime(_yydddtogregdate($YESTRETURNS_array[$key]['SHIPDATEJ']))); ?> </div>
                                    <div class='divtabledata width14_28 clickable custcomplaint' data-postdesc="RETURNCODE" data-postval="<?php echo $YESTRETURNS_array[$key]['RETURNCODE']; ?>"> <?php echo $YESTRETURNS_array[$key]['RETURNCODE']; ?> </div>
                                    <div class='divtabledata width14_28 clickable custcomplaint' data-postdesc="ITEMCODE" data-postval="<?php echo $YESTRETURNS_array[$key]['ITEMCODE']; ?>" > <?php echo $YESTRETURNS_array[$key]['ITEMCODE']; ?> </div>
                                    <div class='divtabledata width14_28 clickable custcomplaint' data-postdesc="WCSNUM" data-postval="<?php echo $YESTRETURNS_array[$key]['WCSNUM']; ?>"> <?php echo $YESTRETURNS_array[$key]['WCSNUM']; ?> </div>
                                    <div class='divtabledata width14_28' data-postdesc=""> <?php echo $YESTRETURNS_array[$key]['WONUM']; ?> </div>
                                    <div class='divtabledata width14_28 clickable custcomplaint' data-postdesc="LPNUM" data-postval="<?php echo $YESTRETURNS_array[$key]['LPNUM']; ?>"> <?php echo $YESTRETURNS_array[$key]['LPNUM']; ?> </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>    
            </div>
        </div>

    </div>
</div>

