<!DOCTYPE html>
<html>
    
    <?php
    include 'sessioninclude.php';
    include_once '../Off_System_Slotting/connection/connection_details.php';
    ?>
    <!--Headers-->
    <head>
        <title>Case Pick</title>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>
        <script src="../easytimer.js" type="text/javascript"></script>

    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>

        <!--body content-->
        <section id="content"> 
            <section class="main padder" style="padding-top: 75px"> 
                <div class="btn btn-danger" id="btn_casedatarefresh">Refresh Data</div> 
                <!--Header stats at top of page-->
                <div id="headerstats" class="hidden"></div>
                <div id="ajaxloadergif" class=""> Data Loading, please wait...<img src="../ajax-loader-big.gif" alt=""/></div>

                <!--data for cases not yet printed goes here-->
                <div id="ctn_notprintedcases" class="hidden">
                    <div id="ctn_divtablenotprinted"></div>
                </div>

                <!--data for cases not yet printed goes here-->
                <div id="ctn_printednotpicked"class="hidden">
                </div>

                <!--data for cases not yet printed goes here-->
                <div id="ctn_picking"class="hidden">

                </div>

                <!--highchart for not printed cases goes here-->        
                <div class="hidewrapper">
                    <section class="panel portlet-item" style="opacity: 1; z-index: 0; margin-top: 15px;"> 
                        <header class="panel-heading bg-inverse"> Hours Needed over Time <i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_asgntasks"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                        <div class="panel-body">
                            <div id="chartpage"  class="page-break" style="width: 100%">
                                <div id="chart_hoursneeded" class="hidden">
                                    <div id="ctn_chartnotprinted" class="largecustchartstyle printrotate"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!--highchart for forecast to actual graph goes here-->        
                <div class="hidewrapper">
                    <section class="panel portlet-item" style="opacity: 1; z-index: 0; margin-top: 15px;"> 
                        <header class="panel-heading bg-inverse"> Hours needed - Forecast to Actual <i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_foretoact"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                        <!--Stats about forecast to actual go here.  From the php include globaldata/forecaststats.php-->
                        <div class="panel-body">
                            <!--forecasts stats data goes here-->
                            <div id="forecaststats"></div>
                            <?php // include 'globaldata/forecaststats.php'; ?>


                            <div id="foretoact"  class="page-break" style="width: 100%">
                                <div id="chart_foretoact" class="hidden">
                                    <div id="ctn_foretoact" class="largecustchartstyle printrotate"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>




            </section>
        </section>

        <!--Modal for batches NOT printed-->
        <div id="modal_notprintedbatch" class="modal fade " role="dialog" >
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Batch Level Data - NOT Printed</h4>
                    </div>
                    <form class="form-horizontal" id="post_refreshpackdata">
                        <div class="modal-body">
                            <div id="data_notprintedbatch"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Modal for batches printed but not yet picked-->
        <div id="modal_printedbatch" class="modal fade " role="dialog" >
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Batch Level Data - Printed but not yet picked</h4>
                    </div>
                    <form class="form-horizontal" id="">
                        <div class="modal-body">
                            <div id="data_printedbatch"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Modal for batches currently in picking-->
        <div id="modal_pickingbatch" class="modal fade " role="dialog" >
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Batch Level Data - Currently being picked</h4>
                    </div>
                    <form class="form-horizontal" id="">
                        <div class="modal-body">
                            <div id="data_pickingbatch"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Modal for batches currently in picking-->
        <div id="modal_equipmentchange" class="modal fade " role="dialog" >
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Change Equipment Allocation</h4>
                    </div>
                    <form class="form-horizontal" id="">
                        <div class="modal-body">
                            <div id="data_equipmentalloc"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Scripts-->
        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#casepick").addClass('active');

            //ajax detail data for cases not yet printed
            $(document).on("click touchstart", "#stat_notprintedtime", function (e) {
                $('#ctn_notprintedcases').addClass('hidden');
                $('#ctn_printednotpicked').addClass('hidden');
                $('#ctn_picking').addClass('hidden');
                ajaxpull_notprinted();
            });

            //ajax detail data for cases printed
            $(document).on("click touchstart", "#stat_printed", function (e) {
                $('#ctn_notprintedcases').addClass('hidden');
                $('#ctn_printednotpicked').addClass('hidden');
                $('#ctn_picking').addClass('hidden');
                ajaxpull_printed();
            });

            //ajax detail data for cases currently being picked
            $(document).on("click touchstart", "#stat_beingpicked", function (e) {
                $('#ctn_notprintedcases').addClass('hidden');
                $('#ctn_printednotpicked').addClass('hidden');
                $('#ctn_picking').addClass('hidden');
                ajaxpull_picking('batch', ' asc');
            });

            //sort div table based on column heading 
            $(document).on("click touchstart", ".click_sort", function (e) {
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
                    case 'printed':
                        ajaxpull_printed(link_name, sort_class2);
                        break;
                    case 'notprinted':
                        ajaxpull_notprinted(link_name, sort_class2);
                        break;
                    case 'picking':
                        ajaxpull_picking(link_name, sort_class2);
                        break;
                }
            });

            //ajax function to pull data for printed batches
            function ajaxpull_picking(orderby, sort_class) {
                if (typeof (orderby) !== "undefined" && orderby !== null) {
                    var orderby = orderby;
                } else {
                    var orderby = 'default';
                }
                $.ajax({
                    data: {orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/data_pickingcase.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#ctn_picking").html(ajaxresult);
                        $('#ctn_picking').removeClass('hidden');
                    }
                });
            }

            //ajax function to pull data for printed batches
            function ajaxpull_printed(orderby, sort_class) {
                if (typeof (orderby) !== "undefined" && orderby !== null) {
                    var orderby = orderby;
                } else {
                    var orderby = 'default';
                }
                $.ajax({
                    data: {orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/data_printedcase.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#ctn_printednotpicked").html(ajaxresult);
                        $('#ctn_printednotpicked').removeClass('hidden');
                    }
                });
            }

            //ajax function to pull data for printed batches
            function ajaxpull_notprinted(orderby, sort_class) {
                if (typeof (orderby) !== "undefined" && orderby !== null) {
                    var orderby = orderby;
                } else {
                    var orderby = 'default';
                }
                $.ajax({
                    data: {orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/data_notprintedcase.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#ctn_divtablenotprinted").html(ajaxresult);
                        $('#ctn_notprintedcases').removeClass('hidden');
                    }
                });

            }

            //ajax function to pull data for printed batches
            function ajaxpull_forecaststats() {

                $.ajax({
                    url: 'globaldata/forecaststats.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#forecaststats").html(ajaxresult);
                    }
                });

            }

            //ajax to change equipment type needed by batch
            $(document).on("click touchstart", ".btn_changeequip", function (e) {
                var newequip = $('.updateequip').val();
                var batchid = $(this).attr('batch-id');
                $.ajax({
                    data: {newequip: newequip, batchid: batchid},
                    url: 'formpost/changeequip.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        window.location.reload(true);
                    }
                });
            });

            //ajax detail data for cases currently being picked
            $(document).on("click touchstart", "#stat_beingpicked", function (e) {
                $('#ctn_notprintedcases').addClass('hidden');
                $('#ctn_printednotpicked').addClass('hidden');
                $('#ctn_picking').addClass('hidden');
                $.ajax({
                    url: 'globaldata/data_pickingcase.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#ctn_picking").html(ajaxresult);
                        $('#ctn_picking').removeClass('hidden');
                    }
                });
            });

            //if refresh button is clicked, call all refresh functions
            $(document).on("click touchstart", "#btn_casedatarefresh", function (e) {
                refreshprintedcasedata();
                refreshnotprintedcasedata();
                refreshheaderdata();
            });

            //Set the interval function to refresh a set time period
            setInterval(function () {
                //set header data to refresh every 120 seconds (120,000 ms)
                //refreshprintedcasedata();
                //refreshnotprintedcasedata();
                refreshheaderdata();
                highchartoptions();
                highchartoptions_forecast();
                ajaxpull_forecaststats();
                $(window).resize();
            }, 245000);

            //Data pull to refresh printed case data
            function refreshprintedcasedata() {
                $('#headerstats').addClass('hidden');
                $('#ctn_notprintedcases').addClass('hidden');
                $('#ctn_printednotpicked').addClass('hidden');
                $('#ctn_picking').addClass('hidden');
                $('#ajaxloadergif').removeClass('hidden');
                $.ajax({
                    url: 'datapull/casedata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                    }
                });
            }

            //Data pull to refresh NOT printed case data
            function refreshnotprintedcasedata() {
                $.ajax({
                    url: 'datapull/opencases.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                    }
                });
            }

            //Chart options and ajax for labor hours by hour
            function highchartoptions() {
                //Highchart variables for total hours not printed history
                var options = {
                    chart: {
                        marginTop: 50,
                        marginBottom: 130,
                        renderTo: 'ctn_chartnotprinted',
                        type: 'spline',
                        zoomType: 'x',
                        height: 600
                    },
                    credits: {
                        enabled: false
                    },
                    plotOptions: {
                        spline: {
                            marker: {
                                enabled: false
                            }
                        }
                    },
                    title: {
                        text: ' '
                    },
                    xAxis: {
                        categories: [],
                        labels: {
                            rotation: -90,
                            y: 25,
                            align: 'right',
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Verdana, sans-serif'
                            }
                        },
                        minTickInterval: 10,
                        legend: {
                            y: "10",
                            x: "5"
                        }

                    },
                    yAxis: {
                        opposite: true,
                        min: 0,
                        title: {
                            text: 'Hours Needed over Time'
                        },
                        labels: {
                            formatter: function () {
                                return Math.floor(this.value / 60);
                            }
                        }
                    },

                    tooltip: {
                        formatter: function () {
                            return '<b>' + this.series.name + '</b><br/>' +
                                    '<b>At time:  </b>' + this.x + '<br/> ' + Math.floor(this.y / 60) + ' hours, ' + Math.floor(this.y % 60) + ' minutes needed.';
                        }
                    },
                    series: []
                };
                $.ajax({
                    url: 'globaldata/graphdata_notprinted.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'true',
                    success: function (json) {
                        options.xAxis.categories = json[0]['data'];
                        options.series[0] = json[1];
                        options.series[1] = json[2];
                        options.series[2] = json[3];
                        options.series[3] = json[4];
                        options.series[4] = json[5];
                        options.series[4].visible = false;
                        chart = new Highcharts.Chart(options);
                        series = chart.series;
                        $('#chart_hoursneeded').removeClass('hidden');
                        $(window).resize();
                    }
                });
            }

            //Chart options and ajax for labor hours by hour
            function highchartoptions_forecast() {
                //Highchart variables for total hours not printed history
                var options2 = {
                    chart: {
                        marginTop: 50,
                        marginBottom: 130,
                        renderTo: 'ctn_foretoact',
                        type: 'spline',
                        zoomType: 'x',
                        height: 600
                    },
                    credits: {
                        enabled: false
                    },
                    plotOptions: {
                        spline: {
                            marker: {
                                enabled: false
                            }
                        }
                    },
                    title: {
                        text: ' '
                    },
                    xAxis: {
                        categories: [],
                        labels: {
                            rotation: -90,
                            y: 25,
                            align: 'right',
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Verdana, sans-serif'
                            }
                        },
                        minTickInterval: 1,
                        legend: {
                            y: "10",
                            x: "5"
                        }

                    },
                    yAxis: {
                        opposite: true,
                        min: 0,
                        title: {
                            text: 'Hours Needed over Time'
                        },
                        labels: {
                            formatter: function () {
                                return Math.floor(this.value / 60);
                            }
                        }
                    },

                    tooltip: {
                        formatter: function () {
                            return '<b>' + this.series.name + '</b><br/>' +
                                    '<b>At time:  </b>' + this.x + '<br/> ' + Math.floor(this.y / 60) + ' hours, ' + Math.floor(this.y % 60) + ' minutes needed.';
                        }
                    },
                    series: []
                };
                $.ajax({
                    url: 'globaldata/graphdata_foretoact.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'true',
                    success: function (json) {
                        options2.xAxis.categories = json[0]['data'];
                        options2.series[4] = json[5];
                        options2.series[5] = json[6];
                        options2.series[6] = json[7];
                        options2.series[7] = json[8];
                        options2.series[0] = json[1];
                        options2.series[1] = json[2];
                        options2.series[2] = json[3];
                        options2.series[3] = json[4];
                        options2.series[0].type = 'column';
                        options2.series[1].type = 'column';
                        options2.series[2].type = 'column';
                        options2.series[3].type = 'column';


                        options2.series[0].visible = false;
                        options2.series[1].visible = false;
                        options2.series[2].visible = false;
                        options2.series[4].visible = false;
                        options2.series[5].visible = false;
                        options2.series[6].visible = false;
                        chart = new Highcharts.Chart(options2);
                        series = chart.series;
                        $('#chart_foretoact').removeClass('hidden');
                        $(window).resize();
                    }
                });
            }

            //Data pull to refresh header case data
            function refreshheaderdata() {
                $.ajax({
                    url: 'globaldata/data_casepickheadertimes.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#headerstats").html(ajaxresult);
                        $('#ajaxloadergif').addClass('hidden');
                        $('#headerstats').removeClass('hidden');
                        $(window).resize();
                    }
                });
            }

            $(document).ready(function () {
                //call function to refresh casedata
                //refreshprintedcasedata();

                //call function to refresh open not printed cases
                //refreshnotprintedcasedata();

                //call function to refresh header data
                refreshheaderdata();

                //call highchart on load
                highchartoptions();

                //call highchart on load
                highchartoptions_forecast();
                
                //call forecast stats function
                ajaxpull_forecaststats();
                
                $(window).resize();
            });

            //show detail on click for not printed projected batches
            $(document).on("click touchstart", ".batchclick_notprinted", function (e) {
                debugger;
                var batch = (this.id);
                $('#modal_notprintedbatch').modal('toggle');
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_notprintedbatch.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_notprintedbatch").html(ajaxresult);
                    }
                });
            });

            //Show detail on click for printed but not yet picked batches
            $(document).on("click touchstart", ".batchclick_printed", function (e) {
                debugger;
                var batch = (this.id);
                var classclicked = $(this).attr('class');
                $('#modal_printedbatch').modal('toggle');
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_printedbatch.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_printedbatch").html(ajaxresult);
                    }
                });
            });

            //Show detail on click for printed but not yet picked batches
            $(document).on("click touchstart", ".batchclick_picking", function (e) {
                var batch = (this.id);
                $('#modal_pickingbatch').modal('toggle');
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_pickingbatch.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_pickingbatch").html(ajaxresult);
                    }
                });
            });

            //show detail on click for not printed projected batches
            $(document).on("click touchstart", "#btn_equipmodal", function (e) {

                $('#modal_equipmentchange').modal('toggle');
                $.ajax({
                    url: 'globaldata/modal_equipalloc.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_equipmentalloc").html(ajaxresult);
                    }
                });

            });

            //call ajax to post the change in equipment allocation from the equip allocation modal
            $(document).on("click touchstart", "#formpost_equipalloc", function (e) {
                e.preventDefault();
                var count_pj = $('#count_PALLETJACK').val();
                var count_belt = $('#count_BELTLINE').val();
                var count_op = $('#count_ORDERPICKER').val();
                $.ajax({
                    data: {count_pj: count_pj, count_belt: count_belt, count_op: count_op},
                    url: 'formpost/post_changeequip.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $('#modal_equipmentchange').modal('toggle');
                        //call function to refresh casedata
                        refreshprintedcasedata();

                        //call function to refresh open not printed cases
                        refreshnotprintedcasedata();

                        //call function to refresh header data
                        refreshheaderdata();

                        //call highchart on load
                        highchartoptions();
                    }
                });

            });

            //function to delete selected batches
            $(document).on("click", "#btn_delete_printed", function (e) {
                debugger;
                var deletearray = [];
                var arraycount = 0;
                var tableid = 'printed';
                $('input.chkbox_deletebatch').each(function () {
                    if ($(this).is(':checked')) {
                        var deletekey = $.trim($(this).attr('id'));
                        deletearray[arraycount] = [deletekey];
                        arraycount += 1;
                    }
                });
                var deletearraycount = (deletearray.length);
                $.ajax({
                    url: 'formpost/deletepackbatch_casepick.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount, tableid: tableid},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });

            //function to delete selected batches
            $(document).on("click", "#btn_delete_picking", function (e) {
                debugger;
                var deletearray = [];
                var arraycount = 0;
                var tableid = 'picking';
                $('input.chkbox_deletebatch').each(function () {
                    if ($(this).is(':checked')) {
                        var deletekey = $.trim($(this).attr('id'));
                        deletearray[arraycount] = [deletekey];
                        arraycount += 1;
                    }
                });
                var deletearraycount = (deletearray.length);
                $.ajax({
                    url: 'formpost/deletepackbatch_casepick.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount, tableid: tableid},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });

        </script>



    </body>
</html>