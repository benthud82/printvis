
<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once '../Off_System_Slotting/connection/connection_details.php';
    ?>
    <head>
        <title>Dashboard</title>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 
                <div class="" style="padding-bottom: 75px; padding-top: 75px;"> 

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
                </div>

            </section>
        </section>

        <script>
            $("#dash").addClass('active');
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});

            $(document).ready(function () {
                //call function to refresh casedata
                highchartoptions_forecast();

                $(window).resize();
            });

            setInterval(function () {
                //set header data to refresh every 120 seconds (120,000 ms)
                highchartoptions_forecast();
                $(window).resize();
            }, 245000);


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
                            text: 'Whse Lines Over Time'
                        },
                        labels: {
                            formatter: function () {
                                return this.value ;
                            }
                        }
                    },

                    tooltip: {
                        formatter: function () {
                            return '<b>' + this.series.name + '</b><br/>' +
                                    '<b>At time:  </b>' + this.x + '<br/> ' + this.y + ' lines received. ';
                        }
                    },
                    series: []
                };
                $.ajax({
                    url: 'globaldata/graphdata_foretoact_loose.php',
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


        </script>
    </body>
</html>
