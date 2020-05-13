<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';
    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Forecast Inbound</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder" style="padding-top: 75px;"> 

                <div id="totalput"></div>
                
                <!--Picking Equipment Analysis-->
                <div class="col-sm-12">
                    <!--datatable for equipment by day-->
                    <section class="panel hidewrapper " id="section_dt_equipment" style="margin-bottom: 50px; margin-top: 20px;"> 
                        <header class="panel-heading bg bg-inverse h2">Dated Putaway Transactions</header>
                        <div id="tablepanel_dt_equipment" class="panel-body" style="background: #efefef">
                            <div id="tablecontainer_dt_equipment" class="col-sm-12 ">
                                <table id="table_dated_putaway" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;  background-color:  white;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Transaction</th>
                                            <th>To Location</th>
                                            <th>Log Number</th>
                                            
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

            </section>
        </section>
        <script>
            $(document).ready(function () {
                load_data();
                totalput();
                old_putaway();
            });

function load_data() {
                //PHP, AJAX, JAVASCIPT
                $.ajax({
                    url: 'datapull/logequip_open.php',
//                data: {sel_lsecse: sel_lsecse},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                    }
                });
            }



            function totalput() {
                //PHP, AJAX, JAVASCIPT
                $.ajax({
                    url: 'globaldata/forecast_putaway.php',
//                data: {sel_lsecse: sel_lsecse},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#totalput").html(ajaxresult);
                    }
                });
            }

           
            

            function old_putaway() {
            debugger;    
                //call headerstats function
              

                oTable = $('#table_dated_putaway').dataTable({
//                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "order": [[0, "desc"]],
                    "scrollX": true,
//                    "aoColumnDefs": [
//                        {"sClass": "lightgray", "aTargets": [1, 2, 5, 6]}
//                    ],

                    ajax: {
                        url: 'globaldata/DataTable_OldPut.php'
                                           }
                });
            }

        </script>
    </body>
</html>



