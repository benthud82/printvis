<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Receiving Transactions</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->

        <?php
        include_once 'headerincludes.php';
        $var_userid = $_SESSION['MYUSER'];
        $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
        $whssql->execute();
        $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

        $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


        $datesqlall = $conn1->prepare("SELECT DISTINCT
                                                                DATE(comp_rec_datetime) as dates
                                                            FROM
                                                                printvis.completed_receipts
                                                            WHERE
                                                                comp_rec_whse = $var_whse");
        $datesqlall->execute();
        $datesqlallarray = $datesqlall->fetchAll(pdo::FETCH_ASSOC);
        $ids = array_column($datesqlallarray, 'dates');
        $includedates = '["' . implode('" , "', $ids) . '"]';


        $datesql = $conn1->prepare("SELECT 
                                max(DATE(comp_rec_datetime)) as recentdate
                            FROM
                                printvis.completed_receipts
                            WHERE
                                comp_rec_whse = $var_whse;");
        $datesql->execute();
        $datesqlarary = $datesql->fetchAll(pdo::FETCH_ASSOC);
        $today = $datesqlarary[0]['recentdate'];
        
        /////  new
        $tsmnumber = $conn1->prepare("SELECT DISTINCT
                                comp_rec_tsm as tsmnumber
                            FROM
                                printvis.completed_receipts
                            WHERE
                                comp_rec_whse = $var_whse;");
        $tsmnumber->execute();
        $tsmnumberarray = $tsmnumber->fetchAll(pdo::FETCH_ASSOC);
        $tsm = $tsmnumberarray[0]['tsmnumber'];
        
        ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="padding-bottom: 75px; padding-top: 75px;"> 
                    <!--Start date date picker-->
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>Start Date:</label>
                            <input name="startfiscal" id="startfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <!--End date date picker-->
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>End Date:</label>
                            <input name="endfiscal" id="endfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <!--TSM Selection picker-->
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>TSM Number:</label>
                            <input name="sel_tsm" id="sel_tsm" class="selectstyle"/>
                        </div>
                    </div>
                    <!--Button to load data-->
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                    <!--Container for high put times data-->
                </div>
                <div class="hidewrapper hidden" id="wrapper_puttimes">
                        <section class="panel portlet-item" style="opacity: 1; z-index: 0;"> 
                            <header class="panel-heading bg-inverse"> Receiving Transactions<i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_rectransreport"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                            <div class="panel-body">
                                <div id="container_rectransreport" class="">
                                    <table id="dt_rectransreport" class="table table-bordered table-striped " cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                                        <thead>
                                            <tr>
                                                <th>TSM#</th>
                                                <th>TSM</th>
                                                <th>DCI</th>
                                                <th>Label</th>
                                                <th>Location</th>
                                                <th>Quantity</th>
                                                <th>Case Qty</th>
                                                <th>Each Qty</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>

      
            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');

            function gettable() {
                 $('#wrapper_puttimes').removeClass('hidden');
                var startdatesel = $('#startfiscal').val();
                var enddatesel = $('#endfiscal').val();
                var tsm = $('#sel_tsm').val();
                oTable4 = $('#dt_rectransreport').DataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    //                    dom: 'frltip',
                    destroy: true,
                    "order": [[1, "desc"]],
                    "scrollX": true,
                    'sAjaxSource': "globaldata/data_rectransreport.php?startdatesel=" + startdatesel + "&enddatesel=" + enddatesel + "&sel_tsm=" + tsm,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5'
                    ]
                });
               
            }

            //datepicker initialization and function to only show available dates from mysql table
//            var availableDates = <?php //echo $includedates; ?>;
//            function available(date) {
//                ymd = date.getFullYear() + "-" + ('0' + (date.getMonth() + 1)).slice(-2) + "-" + ('0' + date.getDate()).slice(-2);
//                if ($.inArray(ymd, availableDates) !== -1) {
//                    return [true, "", "Available"];
//                } else {
//                    return [false, "", "Not Available"];
//                }
//            }
            $('#startfiscal').datepicker();
            $('#endfiscal').datepicker();

        </script>
    </body>
</html>

