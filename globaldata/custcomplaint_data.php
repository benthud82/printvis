<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include '../../globalfunctions/custdbfunctions.php';
//include '../../globalincludes/usa_asys.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}

$var_sqldata = $_POST['sqldata'];
$var_reporttype = $_POST['reporttype'];

switch ($var_reporttype) {
    case 'lpnum':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_lpnum_data.php';

        //data stored in $lparray
        ?>

        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-bubble font-green-sharp"></i>
                    <span class="caption-subject font-green-sharp bold uppercase">Data for LP#: <span style="background-color: black; color: #dddada"><?php echo $var_sqldata; ?></span></span>
                </div>
            </div>
            <?php
            if (sizeof($lparray) == 0) {
                echo ' <div class="h4">No Data Available for this LP</div>';
            } else {
                ?>
                <div class="portlet-body">
                    <ul class="nav nav-pills">
                        <li class="active">
                            <a href="#lptab_2_5" data-toggle="tab" aria-expanded="false"> Timeline </a>
                        </li>
                        <li class="">
                            <a href="#lptab_2_1" data-toggle="tab" aria-expanded="true"> Customer Data </a>
                        </li>
                        <li class="">
                            <a href="#lptab_2_2" data-toggle="tab" aria-expanded="false"> Box Level Data </a>
                        </li>
                        <li class="">
                            <a href="#lptab_2_3" data-toggle="tab" aria-expanded="false"> Complaint Data </a>
                        </li>
                        <li class="">
                            <a href="#lptab_2_4" data-toggle="tab" aria-expanded="false"> Picking/Packing Data </a>
                        </li>

                    </ul>

                    <div class="tab-content">
                        <!--Timeline data-->
                        <div class="tab-pane fade  active in" id="lptab_2_5">
                            <div class="frst-container">
                                <div class="frst-timeline frst-timeline-style-1 frst-alternate frst-date-opposite">
                                    <div class="frst-timeline-block frst-timeline-label-block" data-animation="slideInUp">
                                        <div class="frst-labels frst-start-label"> <span style="width: 150px;">Order Timeline</span> </div>
                                    </div>
                                    <!-- Order recieved block -->
                                    <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                        <div class="frst-timeline-img"> <span><i class="fa fa-stack-overflow" aria-hidden="true"></i></span> </div>
                                        <!-- frst-timeline-img -->
                                        <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                            <div class="frst-timeline-content-inner">
                                                <h2>Order Received Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBRCJD']))) ?></span>
                                                <p>Order received on <strong><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBRCJD']))) ?></strong> at <strong><?php echo _int_mktime($lparray[0]['PBRCHM']) ?></strong> </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Order printed block -->
                                    <div class="frst-timeline-block frst-even-item" data-animation="slideInUp">
                                        <div class="frst-timeline-img"> <span><i class="fa fa-print" aria-hidden="true"></i></span> </div>
                                        <!-- frst-timeline-img -->
                                        <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                            <div class="frst-timeline-content-inner">
                                                <h2>Order Print Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBPTJD']))) ?></span>
                                                <p>Order printed on <strong><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBPTJD']))) ?></strong> at <strong><?php echo _int_mktime($lparray[0]['PBPTHM']) ?></strong> </p>
                                                <p><strong><?php echo _datediff_text($lparray[0]['PBRCJD'], $lparray[0]['PBRCHM'], $lparray[0]['PBPTJD'], $lparray[0]['PBPTHM']); ?> </strong> after it was received.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Order picked block -->

                                    <?php
                                    //determine loose or case pick
                                    if (!empty($lparray[0]['PICK_TSMNUM'])) {
                                        ?>
                                        <!--//loose pick batch-->
                                        <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-hand-grab-o" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">
                                                    <h2>Order Picked Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['PICK_DATE'])) ?></span>
                                                    <p>Order picked on <strong><?php echo date('Y-m-d', strtotime($lparray[0]['PICK_DATE'])) ?></strong> at <strong><?php echo date('H:i A', strtotime($lparray[0]['PICK_DATE'])) ?></strong> </p>
                                                    <p>by <strong><?php echo $lparray[0]['PICK_TSM'] ?></strong></p>
                                                    <p><strong><?php echo _datediff_textanddate($lparray[0]['PBPTJD'], $lparray[0]['PBPTHM'], $lparray[0]['PICK_DATE']); ?> </strong> after it was printed.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Order packed block -->
                                        <div class="frst-timeline-block frst-even-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-stack-overflow" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">
                                                    <h2>Order Packed Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['PACK_DATE'])) ?></span>
                                                    <p>Order packed on <strong><?php echo date('Y-m-d', strtotime($lparray[0]['PACK_DATE'])) ?></strong> at <strong><?php echo date('H:i A', strtotime($lparray[0]['PACK_DATE'])) ?></strong> </p>
                                                    <p>by <strong><?php echo $lparray[0]['PACK_TSMNAME'] ?></strong></p>
                                                    <p><strong><?php echo _datediff_date($lparray[0]['PICK_DATE'], $lparray[0]['PACK_DATE']); ?> </strong> after it was picked.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- EOL block -->
                                        <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-check-square-o" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">

                                                    <?php if (!empty($lparray[0]['EOLLOOSE_TSM'])) { ?>
                                                        <!--EOL case data is present-->
                                                        <h2>EOL</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['PICK_DATE'])) ?></span>
                                                        <p>This box was audited by <strong><?php echo $lparray[0]['EOLLOOSE_TSM'] ?></strong></p>
                                                        <p>
                                                            <!--Create loose error string-->
                                                            <?php
                                                            $loose_error_string = 'No errors found at EOL';
                                                            if (!empty($lparray[0]['EOLLOOSE_WI'])) {
                                                                $loose_error_string = 'The following error was found: <strong>Wrong Item</strong>';
                                                            } elseif (!empty($lparray[0]['EOLLOOSE_CE'])) {
                                                                $loose_error_string = 'The following error was found: <strong>Count Error</strong>';
                                                            } elseif (!empty($lparray[0]['EOLLOOSE_MI'])) {
                                                                $loose_error_string = 'The following error was found: <strong>Missing Item</strong>';
                                                            } elseif (!empty($lparray[0]['EOLLOOSE_AI'])) {
                                                                $loose_error_string = 'The following error was found: <strong>Added Item</strong>';
                                                            } elseif (!empty($lparray[0]['EOLLOOSE_PE'])) {
                                                                $loose_error_string = 'The following error was found: <strong>PE?</strong>';
                                                            }
                                                            echo $loose_error_string;
                                                            ?>
                                                        </p>
                                                    <?php } else { ?>
                                                        <!--EOL loose data is NOT present-->
                                                        <h2>EOL</h2> <span class="frst-date">N/A</span>
                                                        <p>There is no EOL data available.</p>
                                                    <?php }
                                                    ?>

                                                </div>
                                            </div>
                                        </div>

                                    <?php } else {
                                        ?>
                                        <!--//case pick batch-->
                                        <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-hand-grab-o" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">
                                                    <h2>Order Picked Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['CASEPICK_DATETIME'])) ?></span>
                                                    <p>Order picked on <strong><?php echo date('Y-m-d', strtotime($lparray[0]['CASEPICK_DATETIME'])) ?></strong> at <strong><?php echo date('H:i A', strtotime($lparray[0]['CASEPICK_DATETIME'])) ?></strong> </p>
                                                    <p>by <strong><?php echo $lparray[0]['CASEPICK_TSM'] ?></strong></p>
                                                    <p><strong><?php echo _datediff_textanddate($lparray[0]['PBPTJD'], $lparray[0]['PBPTHM'], $lparray[0]['CASEPICK_DATETIME']); ?> </strong> after it was printed.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Order packed block -->
                                        <div class="frst-timeline-block frst-even-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-stack-overflow" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">
                                                    <h2>Order Packed Date/Time</h2> <span class="frst-date">N/A</span>
                                                    <p>Case pick box.  No packing data available. </p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- EOL block -->
                                        <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                            <div class="frst-timeline-img"> <span><i class="fa fa-check-square-o" aria-hidden="true"></i></span> </div>
                                            <!-- frst-timeline-img -->
                                            <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                                <div class="frst-timeline-content-inner">

                                                    <?php if (!empty($lparray[0]['EOLCASE_TSM'])) { ?>
                                                        <!--EOL case data is present-->
                                                        <h2>EOL</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['CASEPICK_DATETIME'])) ?></span>
                                                        <p>This box was audited by <strong><?php echo $lparray[0]['EOLCASE_TSM'] ?></strong></p>
                                                        <p>
                                                            <!--Create case error string-->
                                                            <?php
                                                            $case_error_string = 'No errors found at EOL';
                                                            if (!empty($lparray[0]['EOLCASE_OT'])) {
                                                                $case_error_string = 'The following error was found: <strong>Out of Tolerance</strong>';
                                                            } elseif (!empty($lparray[0]['EOLCASE_OT'])) {
                                                                $case_error_string = 'The following error was found: <strong>RA?</strong>';
                                                            }
                                                            echo $case_error_string;
                                                            ?>
                                                        </p>
                                                    <?php } else { ?>
                                                        <!--EOL case data is NOT present-->
                                                        <h2>EOL</h2> <span class="frst-date">N/A</span>
                                                        <p>There is no EOL data available.</p>
                                                    <?php }
                                                    ?>

                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>

                                    <!-- Order manifested block -->
                                    <div class="frst-timeline-block frst-even-item" data-animation="slideInUp">
                                        <div class="frst-timeline-img"> <span><i class="fa fa-truck" aria-hidden="true"></i></span> </div>
                                        <!-- frst-timeline-img -->
                                        <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                            <div class="frst-timeline-content-inner">
                                                <h2>Order Manifest Date/Time</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBRLJD']))) ?></span>
                                                <p>Order printed on <strong><?php echo date('Y-m-d', strtotime(_yydddtogregdate($lparray[0]['PBRLJD']))) ?></strong> at <strong><?php echo _int_mktime($lparray[0]['PBRLHM']) ?></strong> </p>
                                                <p><strong><?php echo _datediff_text($lparray[0]['PBPTJD'], $lparray[0]['PBPTHM'], $lparray[0]['PBRLJD'], $lparray[0]['PBRLHM']); ?> </strong> after it was packed.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- complaint date block -->
                                    <div class="frst-timeline-block frst-odd-item" data-animation="slideInUp">
                                        <div class="frst-timeline-img"> <span><i class="fa fa-comment" aria-hidden="true"></i></span> </div>
                                        <!-- frst-timeline-img -->
                                        <div class="frst-timeline-content animated hingeTop" style="position: relative;">
                                            <div class="frst-timeline-content-inner">
                                                <h2>Order Complaint Date</h2> <span class="frst-date"><?php echo date('Y-m-d', strtotime($lparray[0]['ORD_RETURNDATE'])) ?></span>
                                                <p>Customer complaint entered on <strong><?php echo date('Y-m-d', strtotime($lparray[0]['ORD_RETURNDATE'])) ?></strong> </p>
                                                <p><strong><?php echo _datediff_textanddate($lparray[0]['PBRLJD'], $lparray[0]['PBRLHM'], $lparray[0]['ORD_RETURNDATE']); ?> </strong> after it was packed.</p>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <!-- frst-timeline -->
                            </div>
                        </div>
                        <!--Customer Data-->
                        <div class="tab-pane fade" id="lptab_2_1">
                            <div class="row" style="padding-top: 50px;">
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Sales Plan</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['SALESPLAN'] . ' | ' . $lparray[0]['SALESPLAN_DESC']; ?></p>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Bill To</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['BILLTONUM'] . ' | ' . $lparray[0]['BILLTONAME']; ?></p>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Ship To</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['SHIPTONUM'] . ' | ' . $lparray[0]['SHIPTONAME']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Box Level Data Content-->
                        <div class="tab-pane fade" id="lptab_2_2">
                            <div class="row" style="padding-top: 50px;">
                                <div class="col-lg-3 col-md-6">
                                    <section class="panel"> 
                                        <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                            <div class="widget-content-blue-wrapper changed-up">
                                                <div class="widget-content-blue-inner padded">
                                                    <div class="h4"><i class="fa fa-file-o"></i> Invoice Data</div>

                                                </div>
                                            </div>
                                        </div>
                                        <!-- List group -->
                                        <div class="list-group">
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['WCSNUM']; ?></strong></span> 
                                                WCS #
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['WONUM']; ?></strong></span> 
                                                Work Order #
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['BOXNUM']; ?></strong></span> 
                                                Box Number
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['JDENUM']; ?></strong></span> 
                                                JDE #
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['LPNUM']; ?></strong></span> 
                                                LP #
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $lparray[0]['ITEMCODE']; ?></strong></span> 
                                                Item #
                                            </div>

                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <!--Complaint Data Content-->
                        <div class="tab-pane fade" id="lptab_2_3">
                            <div class="row" style="padding-top: 50px;">
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Complaint Code</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['RETURNCODE']; ?></p>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Complaint Date</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['ORD_RETURNDATE']; ?></p>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-xs-12">
                                    <div class="mt-element-ribbon bg-grey-steel">
                                        <div class="ribbon ribbon-color-default uppercase">Item Code</div>
                                        <p class="ribbon-content ribbon-content-medium"><?php echo $lparray[0]['ITEMCODE']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Picking/Packing Data-->
                        <div class="tab-pane fade" id="lptab_2_4">
                            <div class="row" style="padding-top: 50px;">
                                <?php
                                //determine loose or case pick
                                if (!empty($lparray[0]['PICK_TSMNUM'])) {
                                    ?>
                                    <!--//loose pick batch-->
                                    <!--Picking Data-->
                                    <div class="col-lg-3 col-md-6">
                                        <section class="panel"> 
                                            <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                                <div class="widget-content-blue-wrapper changed-up">
                                                    <div class="widget-content-blue-inner padded">
                                                        <div class="h4"><i class="fa fa-shopping-basket"></i> Picking Data - Loose Pick</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- List group -->
                                            <div class="list-group">
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PICK_WHSE']; ?></strong></span> 
                                                    Pick Whse
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['BATCH_NUM']; ?></strong></span> 
                                                    Batch Num
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PICK_LOCATION']; ?></strong></span> 
                                                    Pick Location
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PICK_DATE']; ?></strong></span> 
                                                    Pick Date
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PICK_TSM']; ?></strong></span> 
                                                    Pick TSM
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                    <!--Packing Data-->
                                    <div class="col-lg-3 col-md-6">
                                        <section class="panel"> 
                                            <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                                <div class="widget-content-blue-wrapper changed-up">
                                                    <div class="widget-content-blue-inner padded">
                                                        <div class="h4"><i class="fa fa-stack-overflow "></i> Packing Data</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- List group -->
                                            <div class="list-group">
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PACK_TSM']; ?></strong></span> 
                                                    Pack TSM
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PACK_DATE']; ?></strong></span> 
                                                    Pack Date
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PACK_STATION']; ?></strong></span> 
                                                    Pack Station
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['PACK_TYPE']; ?></strong></span> 
                                                    Pack Type
                                                </div>
                                            </div>
                                        </section>
                                    </div>

                                <?php } else {
                                    ?>
                                    <!--//case pick batch-->
                                    <!--Picking Data-->
                                    <div class="col-lg-3 col-md-6">
                                        <section class="panel"> 
                                            <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                                <div class="widget-content-blue-wrapper changed-up">
                                                    <div class="widget-content-blue-inner padded">
                                                        <div class="h4"><i class="fa fa-shopping-basket"></i> Picking Data - Case Pick</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- List group -->
                                            <div class="list-group">
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['CASEPICK_TSM']; ?></strong></span> 
                                                    Case Pick TSM
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['CASEPICK_DATETIME']; ?></strong></span> 
                                                    Pick Date
                                                </div>
                                            </div>
                                        </section>
                                    </div> 
                                    <?php
                                }
                                ?>


                                <!--EOL Data-->
                                <div class="col-lg-3 col-md-6">
                                    <section class="panel"> 
                                        <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                            <div class="widget-content-blue-wrapper changed-up">
                                                <div class="widget-content-blue-inner padded">
                                                    <div class="h4"><i class="fa fa-check-square-o"></i> EOL Data</div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($lparray[0]['EOLLOOSE_TSM'])) { ?>
                                            <!-- List group -->
                                            <div class="list-group">
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_TSM']; ?></strong></span> 
                                                    EOL TSM
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_WI']; ?></strong></span> 
                                                    Error Code - WI
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_CE']; ?></strong></span> 
                                                    Error Code - CE
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_MI']; ?></strong></span> 
                                                    Error Code - MI
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_AI']; ?></strong></span> 
                                                    Error Code - AI
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLLOOSE_PE']; ?></strong></span> 
                                                    Error Code - PE
                                                </div>
                                            </div>
                                        <?php } elseif (!empty($lparray[0]['EOLCASE_TSM'])) {
                                            ?>
                                            <!-- List group -->
                                            <div class="list-group">
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLCASE_TSM']; ?></strong></span> 
                                                    EOL TSM
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLCASE_OT']; ?></strong></span> 
                                                    Error Code - OT
                                                </div>
                                                <div class="list-group-item"> 
                                                    <span class="pull-right"><strong><?php echo $lparray[0]['EOLCASE_RA']; ?></strong></span> 
                                                    Error Code - RA
                                                </div>
                                            </div>
                                        <?php } else {
                                            ?>
                                            <div class="h4" style="padding: 25px;">No EOL Data</div>
                                        <?php }
                                        ?>

                                    </section>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <?php
        }
        break;

    case 'picktsm':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_picktsm_data.php';
        break;
    case 'packtsm':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_packtsm_data.php';
        break;
    case 'billto':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_billto_data.php';
        break;
    case 'shipto':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_shipto_data.php';
        break;
    case 'itemcode':
        //include the data file.  This is where the sql statement resides as well as the html markdown that is displayed in the datareturn div on the main page custcomplaintdata.php
        include 'custcomp_itemcode_data.php';
        break;

    default:
        break;
}
?>

