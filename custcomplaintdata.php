<!DOCTYPE html>
<html>

    <head>
        <title>Complaint Data</title>
        <link href="../timelinecss/animate.min.css" rel="stylesheet" type="text/css"/>
        <link href="../timelinecss/default.min.css" rel="stylesheet" type="text/css"/>
        <link href="../timelinecss/demo.css" rel="stylesheet" type="text/css"/>
        <link href="../timelinecss/frst-timeline-style-1.css" rel="stylesheet" type="text/css"/>
        <script src="../timelinejs/jquery-3.1.1.min.js" type="text/javascript"></script>
        <script src="../timelinejs/modernizr.js" type="text/javascript"></script>
        <link href="../timelinecss/reset.css" rel="stylesheet" type="text/css"/>

        <script src="../timelinejs/jquery.mousewheel-3.0.6.min.js" type="text/javascript"></script>
        <script src="../timelinejs/jquery.mousewheel.min.js" type="text/javascript"></script>
        <script src="../timelinejs/frst-timeline.js" type="text/javascript"></script>
        <script src="../timelinejs/jquery.mCustomScrollbar.min.js" type="text/javascript"></script>
        <link href="../timelinecss/jquery.mCustomScrollbar.min.css" rel="stylesheet" type="text/css"/>
        <link href="../EasyAutocomplete-1.3.4/dist/easy-autocomplete.css" rel="stylesheet" type="text/css"/>
        <link href="../EasyAutocomplete-1.3.4/dist/easy-autocomplete.themes.css" rel="stylesheet" type="text/css"/>
        <script src="../EasyAutocomplete-1.3.4/dist/jquery.easy-autocomplete.js" type="text/javascript"></script>
        <script src="../app.min.js" type="text/javascript"></script>
        <script src="../jquery.counterup.min.js" type="text/javascript"></script>
        <script src="../jquery.waypoints.min.js" type="text/javascript"></script>
        <style>
            td {
                white-space: nowrap;
            }
        </style>

        <?php
        include_once 'sessioninclude.php';
        include_once '../Off_System_Slotting/connection/connection_details.php';
        include_once '../Off_System_Slotting/headerincludes.php';
        ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>

        <section id="content"> 
            <section class="main padder" style="padding-top: 75px;"> 
                <div class="row">
                    <div class="col-lg-12">
                        <div id="commonreports"></div>
                    </div>
                </div>

                <!--Displayed data will go here-->
                <div id="mastercontainer">
                    <div id="datareturn" class="hidden">
                    </div>

                    <!--datatable for complaints by item-->
                    <section class="panel hidewrapper hidden" id="section_itemcode" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Customer Complaints by Item Code</header>
                        <div id="tablepanel_itemcode" class="panel-body" style="background: #efefef">
                            <div id="tablecontainer_itemcode" class="col-sm-12 ">
                                <table id="table_itemcode" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;  background-color:  white;">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Complaint Date</th>
                                            <th>Return Code</th>
                                            <th>Invoice#</th>
                                            <th>Ship Zone</th>
                                            <th>Box Size</th>
                                            <th>Tracer#</th>
                                            <th>Pick TSM</th>
                                            <th>Pack TSM</th>
                                            <th>Pick Location</th>
                                            <th>Pick Date</th>
                                            <th>EOL?</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!--datatable for complaints by pack TSM-->
                    <section class="panel hidewrapper hidden" id="section_packtsm" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Complaints Data for Pack TSM</header>
                        <div id="tablepanel_packtsm" class="panel-body" style="background: #efefef">
                            <div id="tablecontainer_packtsm" class="col-sm-12 ">
                                <table id="table_packtsm" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;  background-color:  white;">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Complaint Date</th>
                                            <th>Return Code</th>
                                            <th>Invoice#</th>
                                            <th>Ship Zone</th>
                                            <th>Box Size</th>
                                            <th>Tracer#</th>
                                            <th>Pick TSM</th>
                                            <th>Pack TSM</th>
                                            <th>Pack Station</th>
                                            <th>Pack Date</th>
                                            <th>EOL?</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!--datatable for complaints by pick TSM-->
                    <section class="panel hidewrapper hidden" id="section_picktsm" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Complaints Data for Pick TSM</header>
                        <div id="tablepanel_picktsm" class="panel-body" style="background: #efefef">
                            <div id="tablecontainer_picktsm" class="col-sm-12 ">
                                <table id="table_picktsm" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;  background-color:  white;">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Complaint Date</th>
                                            <th>Return Code</th>
                                            <th>Invoice#</th>
                                            <th>Ship Zone</th>
                                            <th>Box Size</th>
                                            <th>Tracer#</th>
                                            <th>Pick TSM</th>
                                            <th>Pack TSM</th>
                                            <th>Pick Location</th>
                                            <th>Pick Date</th>
                                            <th>EOL?</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!--datatable for complaints by Date-->
                    <section class="panel hidewrapper hidden" id="section_date" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Complaints Data by Date</header>
                        <div id="tablepanel_date" class="panel-body" style="background: #efefef">
                            <div id="tablecontainer_date" class="col-sm-12 ">
                                <table id="table_date" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;  background-color:  white;">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Complaint Date</th>
                                            <th>Return Code</th>
                                            <th>Invoice#</th>
                                            <th>Ship Zone</th>
                                            <th>Box Size</th>
                                            <th>Tracer#</th>
                                            <th>Pick TSM</th>
                                            <th>Pack TSM</th>
                                            <th>Pick Location</th>
                                            <th>Pick Date</th>
                                            <th>EOL?</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <!--Modal Includes-->
                <?php
                include_once 'modals/modal_commonreports.php ';
                ?>
            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $(document).ready(function () {
                commonreports();
                //if called from custcomplaints.php, check for cookies
                checkCookie_custcomplaint();
            });

            //function to pull for common reports for header
            function commonreports() {
                $.ajax({
                    url: 'globaldata/custcompl_commonreports.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#commonreports").html(ajaxresult);
                    }
                });
            }

            //function to display appropriate modal depending on tile clicked
            $(document).on("click touchstart", ".click_commreport", function (e) {
                var report_id = $(this).attr('id');
                var modal_id = $(this).attr('data-modalid');
                //concatenate modal id pulled from printvis.custcompl_commonreports table
                $('#' + modal_id).modal('toggle');
            });



        </script>
        <script>
//            (function ($) {
//                $(document).ready(function () {
//                    $("#side-menu-content").mCustomScrollbar({
//                        theme: "rounded-dots",
//                        scrollInertia: 800
//                    });
//                });
//            })(jQuery);
        </script>
    </body>
</html>


