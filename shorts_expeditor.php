
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';
//    include_once 'globaldata/asoboxhold.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Shorts Expeditor</title>
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

                <div class="row">
                    <div class="pull-left  col-lg-3" >
                        <label>Loose or Case:</label>
                        <select class="selectstyle" id="sel_cselse" name="sel_position" style="width: 175px;padding: 5px; margin-right: 10px;" onChange="refreshall()">
                            <option value="*"> -All- </option>
                            <option value="L">Loose</option>
                            <option value="C">Case</option>
                        </select>
                    </div>
                    <div class="pull-left  col-lg-3" >
                        <label>Only in Drop Zone <i onclick="showhelpmodal();" class="fa fa-question-circle" style="cursor: pointer"></i></label>
                        <input onclick="refreshall()" type="checkbox" class="chkbox_deletebatch noclick" name="checkbox" id="dropzone" style="width: 20px; height: 20px;"></input>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="btn_download" type="button" class="btn btn-info" onclick="">Download Data</button>
                    </div>
                </div>

                <div id="container_asoexpedite" ></div>

                <div id="ajaxloadergif" class=""> Data Loading, please wait...<img src="../ajax-loader-big.gif" alt=""/></div>

                <div id="container_helpmodal" class="modal fade " role="dialog">
                    <div class="modal-dialog modal-lg">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Only Include Replens in Drop Zone?</h4>
                            </div>

                            <div class="modal-body" id="" style="margin: 25px;">
                                <h4 style="font-family: calibri">
                                    Checking this box will only show replenenishments that are currently in a drop zone waiting to be put to location.
                                </h4>
                            </div>

                        </div>
                    </div>
                </div>


            </section>
        </section>
        <script>
            setInterval(function () {
                //set header data to refresh every 120 seconds (120,000 ms)
                refreshall();
            }, 245000);


            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#aso").addClass('active');

            $(document).ready(function () {
                var dldata = 0;
                refreshall(dldata);
            });

            function _get_aso_holds() {

                $.ajax({
                    url: 'globaldata/asoboxhold.php',
                    type: 'post',
                    success: function () {
                    }
                });
            }

            function _get_dropzone_replen() {
                // Dropzone check box
                if ($("#dropzone").is(':checked'))
                    var onlydrop = 1; // checked
                else
                    var onlydrop = 0; // checked

                $.ajax({
                    url: 'globaldata/dropzone_replen.php',
                    data: {onlydrop: onlydrop},
                    type: 'post',
                    success: function () {
                    }
                });
            }

            function _get_deleteold_deletes() {
                $.ajax({
                    url: 'formpost/deleteold_deletes.php',
                    type: 'post',
                    success: function () {
                    }
                });
            }

            function _get_display(sel_lsecse, dldata) {
                $('#ajaxloadergif').removeClass('hidden');
                $.ajax({
                    url: 'globaldata/dt_asoexpeditor.php',
                    data: {sel_lsecse: sel_lsecse, dldata: dldata},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $('#ajaxloadergif').addClass('hidden');
                        $('#container_asoexpedite').removeClass('hidden');
                        $("#container_asoexpedite").html(ajaxresult);
                    }
                });
            }

            //function to delete selected batches
            $(document).on("click", "#btn_delete_batch", function (e) {
//                $('#container_asoexpedite').addClass('hidden');
                var deletearray = [];
                var arraycount = 0;
                $('input.chkbox_deletebatch').each(function () {
                    if ($(this).is(':checked')) {
                        var deletekey = $.trim($(this).attr('id'));
                        deletearray[arraycount] = [deletekey];
                        arraycount += 1;
                    }
                });
                var deletearraycount = (deletearray.length);
                $.ajax({
                    url: 'formpost/delete_shortsexp.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });

            function showhelpmodal() {
                $('#container_helpmodal').modal('toggle');
            }

            function refreshall() {
//                debugger;
                $('#container_asoexpedite').addClass('hidden');
                var sel_lsecse = $('#sel_cselse').val();
                var dldata = 0;  //set default to NOT download data.
                _get_aso_holds();
                _get_dropzone_replen();
                _get_deleteold_deletes();
                _get_display(sel_lsecse, dldata);
            }


            $(document).on("click touchstart", "#btn_download", function (e) {
                var dldata = 1;
                var sel_lsecse = $('#sel_cselse').val();
                window.location = "globaldata/dt_asoexpeditor.php?dldata=" + dldata + "&sel_lsecse=" + sel_lsecse;
                refreshall();
            });


        </script>
    </body>
</html>
