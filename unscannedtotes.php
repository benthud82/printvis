
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Unscanned Totes</title>
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
                                                                DATE(cartstart_starttime) as dates
                                                            FROM
                                                                printvis.allcart_history_hist
                                                            WHERE
                                                                cartstart_whse = $var_whse");
        $datesqlall->execute();
        $datesqlallarray = $datesqlall->fetchAll(pdo::FETCH_ASSOC);
        $ids = array_column($datesqlallarray, 'dates');
        $includedates = '["' . implode('" , "', $ids) . '"]';


        $datesql = $conn1->prepare("SELECT 
                                max(DATE(cartstart_starttime)) as recentdate
                            FROM
                                printvis.allcart_history_hist
                            WHERE
                                cartstart_whse = $var_whse;");
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


                    <div id="itemdetailcontainerloading" class="loading col-sm-12 text-center hidden" >
                        Data Loading <img src="../ajax-loader-big.gif"/>
                    </div>


                    <div class="row">
                        <div class="col-lg-12 hidden" id="container_unscannedtotes">

                        </div>
                    </div>


                </div>


                <!--Modal for tote level pack data-->
                <div id="modal_totelevelpack" class="modal fade " role="dialog" >
                    <div class="modal-dialog modal-lg">
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Tote Level Pack Data</h4>
                            </div>
                            <form class="form-horizontal" id="post_refreshpackdata">
                                <div class="modal-body">
                                    <div class="col-sm-3">
                                        <input type="text" name="batch_id" id="batch_id" class="form-control hidden" /> 
                                    </div>
                                    <div id="data_totelevelpack"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');

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

            //sort div table based on column heading 
            $(document).on("click touchstart", ".click_sort", function (e) {
                debugger;
                var sort_class = $(this).attr('data-sort');
                var datapull = $(this).attr('data-pull');
                if (sort_class === ' asc') {
                    $(this).attr('data-sort', ' desc');
                }
                if (sort_class === ' desc') {
                    $(this).attr('data-sort', ' asc');
                }
                var link_name = $(this).attr('name'); //name pulled from column header settings
                var sort_class2 = $(this).attr('data-sort');
                switch (datapull) {
                    case 'unscanned':
                        ajaxpull_unscanned(link_name, sort_class2);
                        break;
                }
            });

            //sort div table based on column heading for the tote level modal data
            $(document).on("click touchstart", ".click_sort_modal", function (e) {
                debugger;
                var sort_class = $(this).attr('data-sort');
                var datapull = $(this).attr('data-pull');
                if (sort_class === ' asc') {
                    $(this).attr('data-sort', ' desc');
                }
                if (sort_class === ' desc') {
                    $(this).attr('data-sort', ' asc');
                }
                var link_name = $(this).attr('name'); //name pulled from column header settings
                var sort_class2 = $(this).attr('data-sort');
              
                var batch = $('#batch_id').val();
                switch (datapull) {
                    case 'modal_unscanned':
                        ajaxpull_modaltotedata(link_name, sort_class2, batch);
                        break;
                }
            });

            //ajax function to pull data for printed batches
            function ajaxpull_unscanned(orderby, sort_class) {
                $('#itemdetailcontainerloading').removeClass('hidden');
                $('#btn_download').addClass('hidden');
                $('#container_unscannedtotes').addClass('hidden');
                if (typeof (orderby) !== "undefined" && orderby !== null) {
                    var orderby = orderby;
                } else {
                    var orderby = 'default';
                }
                var startdatesel = $('#startfiscal').val();
                var enddatesel = $('#endfiscal').val();
                $.ajax({
                    data: {startdatesel: startdatesel, enddatesel: enddatesel, orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/data_unscannedtotes.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $('#itemdetailcontainerloading').addClass('hidden');
                        $("#container_unscannedtotes").html(ajaxresult);
                        $('#btn_download').removeClass('hidden');
                        $('#container_unscannedtotes').removeClass('hidden');
                    }
                });
            }

            $(document).on("click touchstart", "#btn_download", function (e) {
                var startdatesel = $('#startfiscal').val();
                var enddatesel = $('#endfiscal').val();

                window.location = "globaldata/dl_unscannedtotes.php?startdatesel=" + startdatesel + "&enddatesel=" + enddatesel;
            });


            $(document).on("click touchstart", ".batchclick", function (e) {
             
                var batch = (this.id);
                $('#batch_id').val(batch);
                $('#modal_totelevelpack').modal('toggle');

                var sort_class = ' asc';
                var orderby = 'bin';
                var totedate = $(this).attr('data-date');
                ajaxpull_modaltotedata(orderby, sort_class, batch, totedate);

            });

            //ajax pull for modal data for tote level
            function ajaxpull_modaltotedata(orderby, sort_class, batchid, totedate) {
                if (typeof (orderby) !== "undefined" && orderby !== null) {
                    var orderby = orderby;
                } else {
                    var orderby = 'default';
                }
                var batch = batchid;
                var totedate = totedate;
                
                debugger;
                $.ajax({
                    data: {orderby: orderby, sort_class: sort_class, batch: batch, totedate:totedate},
                    url: 'globaldata/modal_totedata_unscanned.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_totelevelpack").html(ajaxresult);
                    }
                });
            }

        </script>

    </body>
</html>
