
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Case Volume by Hour</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php
        include_once 'headerincludes.php';

        if (!function_exists('array_column')) {

            function array_column(array $input, $columnKey, $indexKey = null) {
                $array = array();
                foreach ($input as $value) {
                    if (!isset($value[$columnKey])) {
                        trigger_error("Key \"$columnKey\" does not exist in array");
                        return false;
                    }
                    if (is_null($indexKey)) {
                        $array[] = $value[$columnKey];
                    } else {
                        if (!isset($value[$indexKey])) {
                            trigger_error("Key \"$indexKey\" does not exist in array");
                            return false;
                        }
                        if (!is_scalar($value[$indexKey])) {
                            trigger_error("Key \"$indexKey\" does not contain scalar value");
                            return false;
                        }
                        $array[$value[$indexKey]] = $value[$columnKey];
                    }
                }
                return $array;
            }

        }

        $var_userid = $_SESSION['MYUSER'];
        $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
        $whssql->execute();
        $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

        $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


        $datesqlall = $conn1->prepare("SELECT DISTINCT
                                                                DATE(casevol_availdate) as dates
                                                            FROM
                                                                printvis.hist_casevol_summary
                                                            WHERE
                                                                casevol_whse = $var_whse");
        $datesqlall->execute();
        $datesqlallarray = $datesqlall->fetchAll(pdo::FETCH_ASSOC);
        $ids = array_column($datesqlallarray, 'dates');
        $includedates = '["' . implode('" , "', $ids) . '"]';


        $datesql = $conn1->prepare("SELECT 
                                max(DATE(casevol_availdate)) as recentdate
                            FROM
                                printvis.hist_casevol_summary
                            WHERE
                                casevol_whse = $var_whse;");
        $datesql->execute();
        $datesqlarary = $datesql->fetchAll(pdo::FETCH_ASSOC);
        $today = $datesqlarary[0]['recentdate'];

//        include '../newEmptyPHP.php';
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
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>Start Date:</label>
                            <input name="startfiscal" id="startfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>End Date:</label>
                            <input name="endfiscal" id="endfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="btn_download" type="button" class="btn btn-danger hidden" onclick="">Download Data</button>
                    </div>
                </div>

                    <div id="itemdetailcontainerloading" class="loading col-sm-12 text-center hidden" >
                        Data Loading <img src="../ajax-loader-big.gif"/>
                    </div>

                    <!--Datatable-->
                    <div id="tablecontainer" class="hidden" style="cursor: pointer">
                        <table id="dt_casevol" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                            <thead>
                                <tr>
                                    <th>Available Date</th>
                                    <th>Available Hour</th>
                                    <th>Equipment</th>
                                    <th>Lines Received</th>
                                    <th>Cubic Vol Received</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#insights").addClass('active');

            function gettable() {
                ajaxpull_unscanned('batch', ' asc');

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




            // Load Datable on click

            function gettable() {
                $('#tablecontainer').removeClass('hidden');
                var startdatesel = $('#startfiscal').val();
                var enddatesel = $('#endfiscal').val();
                oTable4 = $('#dt_casevol').DataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    //                    dom: 'frltip',
                    destroy: true,
                    "scrollX": true,
                    'sAjaxSource': "globaldata/data_casevol.php?startdatesel=" + startdatesel + "&enddatesel=" + enddatesel,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5'
                    ]
                });

            }



        </script>

    </body>
</html>
