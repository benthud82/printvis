
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>High Put Times</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->

        <?php
        include_once '../Off_System_Slotting/headerincludes.php';
        $var_userid = $_SESSION['MYUSER'];
        $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
        $whssql->execute();
        $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

        $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


        $datesqlall = $conn1->prepare("SELECT DISTINCT
                                                                DATE(etcomb_curtime) as dates
                                                            FROM
                                                                printvis.elapsedtime_comb
                                                            WHERE
                                                                etcomb_whse = $var_whse");
        $datesqlall->execute();
        $datesqlallarray = $datesqlall->fetchAll(pdo::FETCH_ASSOC);
        $ids = array_column($datesqlallarray, 'dates');
        $includedates = '["' . implode('" , "', $ids) . '"]';


        $datesql = $conn1->prepare("SELECT 
                                max(DATE(etcomb_curtime)) as recentdate
                            FROM
                                printvis.elapsedtime_comb
                            WHERE
                                etcomb_whse = $var_whse;");
        $datesql->execute();
        $datesqlarary = $datesql->fetchAll(pdo::FETCH_ASSOC);
        $today = $datesqlarary[0]['recentdate'];
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
                    <!--Button to load data-->
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                    <!--Container for high put times data-->
                </div>
                <div class="hidewrapper hidden" id="wrapper_puttimes">
                        <section class="panel portlet-item" style="opacity: 1; z-index: 0;"> 
                            <header class="panel-heading bg-inverse"> High Put Times<i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_highputtimes"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                            <div class="panel-body">
                                <div id="container_highputtimes" class="">
                                    <table id="dt_highputtimes" class="table table-bordered table-striped " cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                                        <thead>
                                            <tr>
                                                <th>TSM#</th>
                                                <th>TSM</th>
                                                <th>Equipment</th>
                                                <th>Current Log</th>
                                                <th>Current Location</th>
                                                <th>Put Quantity</th>
                                                <th>Put Each</th>
                                                <th>Put Case</th>
                                                <th>Current Put Time</th>
                                                <th>Previous Put Time</th>
                                                <th>Previous Location</th>
                                                <th>Previous Log</th>
                                                <th>Minute Difference</th>
                                                <th>Break/Lunch</th>
                                                <th>User/System</th>
                                                <th data-toggle='tooltip' title='Was elapsed time between end of one log and start of a different log?' data-placement='top' data-container='body'>New Log?</th>
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
                oTable4 = $('#dt_highputtimes').DataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    //                    dom: 'frltip',
                    destroy: true,
                    "order": [[10, "desc"]],
                    "scrollX": true,
                    'sAjaxSource': "globaldata/data_highputtimes.php?startdatesel=" + startdatesel + "&enddatesel=" + enddatesel,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5'
                    ]
                });
               
            }

            //datepicker initialization and function to only show available dates from mysql table
            var availableDates = <?php echo $includedates; ?>;
            function available(date) {
                ymd = date.getFullYear() + "-" + ('0' + (date.getMonth() + 1)).slice(-2) + "-" + ('0' + date.getDate()).slice(-2);
                if ($.inArray(ymd, availableDates) !== -1) {
                    return [true, "", "Available"];
                } else {
                    return [false, "", "Not Available"];
                }
            }
            $('#startfiscal').datepicker({beforeShowDay: available});
            $('#endfiscal').datepicker({beforeShowDay: available});

        </script>
    </body>
</html>
