<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';
    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Replenishment Dashboard</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--body content-->
        <section id="content"> 
            <section class="main padder" style="padding-top: 75px"> 
                <?php
                if (isset($_SESSION['MYUSER'])) {
                    $var_userid = $_SESSION['MYUSER'];
                    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
                    $whssql->execute();
                    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
                    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
                }
                if ($var_whse == 3) {
                    $buidlingclass = '';
                    $selected = "selected='selected'";
                } else {
                    $buidlingclass = 'hidden';
                    $selected = '';
                }
                ?>
                <div id="buildingcontainer" class="<?php echo $buidlingclass ?>">
                    <label>Select Building: </label>
                    <select class="selectstyle" id="building" name="building" style="width: 75px;" onChange="refreshall()">
                        <!--<option value="both">Pick and Replen Map</option>-->
                        <option value="1">1</option>
                        <option value="2" <?php echo $selected ?>>2</option>
                    </select>
                </div>
                
                
        <!--Main Content-->
        <section id="content"> 
            <section class="main padder" style="padding-top: 75px;"> 

                <div id="replensummary"></div>
                
                <!--Picking Equipment Analysis-->
                
            </section>
        </section>
        <script>
            $(document).ready(function () {
                load_data();
                replensummary();
                
            });

function refreshall() {
                load_data();
                replensummary();
                
}



function load_data() {
                //PHP, AJAX, JAVASCIPT
                var building = $('#building').val();
                $.ajax({
                    url: 'datapull/logequip_open.php',
               data: {building: building},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                    }
                });
            }



            function replensummary() {
                //PHP, AJAX, JAVASCIPT
                
                var building = $('#building').val();
                $.ajax({
                    url: 'globaldata/forecast_replenishments.php',
                    data: {building: building},
//                data: {sel_lsecse: sel_lsecse},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#replensummary").html(ajaxresult);
                    }
                });
            }

           
            
// just in case
//            function old_putaway() {
//            debugger;    
//                //call headerstats function
//              var building = $('#building').val();
//
//                oTable = $('#table_dated_putaway').dataTable({
////                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
//                    destroy: true,
//                    "order": [[0, "desc"]],
//                    "scrollX": true,
////                    "aoColumnDefs": [
////                        {"sClass": "lightgray", "aTargets": [1, 2, 5, 6]}
////                    ],
//
//                    ajax: {
//                        url: 'globaldata/DataTable_OldPut.php'
//                         data: {building: building},
//                                           }
//                });
//            }

        </script>
    </body>
</html>       