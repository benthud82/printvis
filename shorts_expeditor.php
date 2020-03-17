
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


                <div id="ajaxloadergif" class=""> Data Loading, please wait...<img src="../ajax-loader-big.gif" alt=""/></div>
                <div class="row">
                    <div class="pull-left  col-lg-3" >
                        <label>Loose or Case:</label>
                        <select class="selectstyle" id="sel_cselse" name="sel_position" style="width: 175px;padding: 5px; margin-right: 10px;"onChange="refreshall()">
                            <option value="*"> -All- </option>
                            <option value="L">Loose</option>
                            <option value="C">Case</option>
                        </select>
                    </div>
                </div>
                <div id="container_asoexpedite" >

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
                refreshall();
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
                $.ajax({
                    url: 'globaldata/dropzone_replen.php',
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

            function _get_display(sel_lsecse) {
                $('#ajaxloadergif').removeClass('hidden');
                $.ajax({
                    url: 'globaldata/dt_asoexpeditor.php',
                    data: {sel_lsecse: sel_lsecse},
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

            function refreshall() {
                debugger;
//                $("#divtable_priorities").hide();
                $('#container_asoexpedite').addClass('hidden');
                var sel_lsecse = $('#sel_cselse').val();
                _get_aso_holds();
                _get_dropzone_replen();
                _get_deleteold_deletes();
                _get_display(sel_lsecse);
//                $("#divtable_priorities").show();
            }


        </script>
    </body>
</html>
