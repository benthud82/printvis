
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../Off_System_Slotting/connection/connection_details.php';
    ?>
    <head>
        <title>Customer Complaints</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>

    <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php
        include_once '../Off_System_Slotting/headerincludes.php';
        ?>
        <script src="../app.min.js" type="text/javascript"></script>
        <script src="../jquery.counterup.min.js" type="text/javascript"></script>
        <script src="../jquery.waypoints.min.js" type="text/javascript"></script>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder" style="padding-top: 75px;"> 

                <!--Header stats-->
                <div id="newcomplaints"></div>

                <div class="row" id="chartsrow">
                    <!--Historical Actual Replens graph-->
                    <div class="col-lg-9">
                        <section class="panel hidewrapper" id="graph_week_complaints" style="margin-bottom: 50px; margin-top: 20px;"> 
                            <header class="panel-heading bg bg-inverse h2">Customer Complaints by Week</header>
                            <div id="week_complaints" class="panel-body" style="background: #efefef">
                                <div id="chartpage_week_complaints"  class="page-break" style="width: 100%">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="alert alert-info " style="font-size: 100%;"> <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button> <i class="fa fa-info-circle fa-lg"></i><span> Most recent week does not include data from today. </span></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="alert alert-success" style="font-size: 100%;"> <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button> <i class="fa fa-arrow-down fa-lg"></i><span> Positive improvement indicated by <strong>downward</strong> trending graph. </span></div>
                                        </div>
                                    </div>
                                    <div id="container_week_complaints" class="dashboardstyle printrotate"></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!--Pie charts showing distribution of error types-->
                    <div class="col-lg-3">
                        <section class="panel hidewrapper" id="graph_pie_week" style="margin-bottom: 50px; margin-top: 20px;"> 
                            <header class="panel-heading bg bg-inverse h2">Complaints by Type</header>
                            <div id="week_pie" class="panel-body" style="background: #efefef">
                                <div id="chartpage_week_pie"  class="page-break" style="width: 100%">
                                    <div id="container_week_pie" class=" printrotate"></div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row" id="row_topimpacts">
                    <div id="cnt_topimpacts"></div>
                </div>

                <!--Modal Includes-->
                <?php
                include_once 'modals/modal_yest_complaints.php ';
                ?>
            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#custcomplaints").addClass('active');

            $(document).ready(function () {
                headerdata();
                weeklypiechart();
                returncode_topimpacts();
                weeklyhighchart();
                $(window).resize();
            });

            //Data pull to refresh header case data
            function headerdata() {
                $.ajax({
                    url: 'globaldata/complaints_headerdata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#newcomplaints").html(ajaxresult);
                        $('.yestreturns').counterUp({
                            delay: 100,
                            time: 1200
                        });
                    }
                });
            }

            //Data pull to top impacts by return code
            function returncode_topimpacts() {
                $.ajax({
                    url: 'globaldata/returncode_topimpacts.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#cnt_topimpacts").html(ajaxresult);
                    }
                });
            }

            //options for weekly complaints
            function weeklyhighchart() {
                var options3 = {
                    chart: {
                        marginTop: 50,
                        marginBottom: 135,
                        renderTo: 'container_week_complaints',
                        type: 'spline'
                    }, credits: {
                        enabled: false
                    },
                    plotOptions: {
                        spline: {
                            marker: {
                                enabled: false
                            }
                        },
                        series: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        window.open('custcomplaintdata.php?startdate=' + this.category + '&enddate=' + this.category + '&movetype=' + this.series.name + '&formSubmit=Submit');
                                    }
                                }
                            }
                        }
                    },
                    title: {
                        text: ' '
                    },
                    xAxis: {
                        categories: [], labels: {
                            rotation: -90,
                            y: 25,
                            align: 'right',
                            step: 1,
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Verdana, sans-serif'
                            }
                        },
                        legend: {
                            y: "10",
                            x: "5"
                        }

                    },

                    yAxis: {
                        title: {
                            text: 'Complaints by Week'
                        },
                        plotLines: [{
                                value: 0,
                                width: 1,
                                color: '#808080'
                            }],
                        opposite: true,
                        min: 0
                    }, tooltip: {
                        formatter: function () {
                            return '<b>' + this.series.name + '</b><br/>' +
                                    this.x + ': ' + Highcharts.numberFormat(this.y, 0) + '<br/>' +
                                    'Click me for detail!';
                        }
                    },
                    series: []
                };
                $.ajax({
                    url: 'globaldata/graph_complaints_week.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'true',
                    success: function (json) {
                        options3.xAxis.categories = json[0]['data'];
                        options3.series[0] = json[1];
                        options3.series[1] = json[2];
                        options3.series[2] = json[3];
                        options3.series[3] = json[4];
                        options3.series[0].visible = false;
                        options3.series[1].visible = false;
                        options3.series[2].visible = false;

                        chart = new Highcharts.Chart(options3);
                        series = chart.series;
                        $(window).resize();
                    }
                });
            }

            //open modal to display relevant data for yesterdays DC complaints
            $(document).on("click touchstart", "#stat_totalcomplaints_dc", function (e) {
                $('#modal_yestreturns').modal('toggle');
            });

            //open modal to display relevant data for yesterdays DC complaints
            $(document).on("click touchstart", ".clickable", function (e) {
                debugger;
                var post_desc = $(this).attr('data-postdesc');
                var post_val = $(this).attr('data-postval');
//                var post_val = $(this).text();
                post_val = post_val.trim();
                document.cookie = "post-desc=" + post_desc;
                document.cookie = "post-val=" + post_val;
//                document.cookie = "max-age=10";
                var date = new Date();
                date.setTime(date.getTime() + (30 * 1000));
                var expires = "expires=" + date;
                document.cookie = expires;
                window.open("custcomplaintdata.php");
            });

            function weeklypiechart() {
                $.ajax({
                    url: 'globaldata/graph_complaints_week_pie.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'true',
                    success: function (weeklyjsondata) {

                        var d = weeklyjsondata;
                        var name = Array();
                        var data = Array();
                        var dataArrayFinal = Array();
                        for (i = 0; i < d.length; i++) {
                            name[i] = d[i].name;
                            data[i] = d[i].data;
                        }

                        for (j = 0; j < name.length; j++) {
                            var temp = new Array(name[j], data[j]);
                            dataArrayFinal[j] = temp;
                        }

                        renderChart(dataArrayFinal);
                    }
                });
            }

            function renderChart(seriesData) {
                chart = new Highcharts.Chart({
                    chart: {
                        renderTo: "container_week_pie"
                    },
                    title: {
                        text: 'Complaints this Week'
                    },
                    credits: {
                        enabled: false
                    },
                    series: [{
                            type: 'pie',
                            name: 'Complaint Count',
                            data: seriesData
                        }]
                });
            }


        </script>

    </body>
</html>
