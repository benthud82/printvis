
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Loose Pick Priorities</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>

    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder" style="padding-bottom: 75px; padding-top: 75px;"> 

                <!--User input variables  This should be done through a modal since data is constantly refreshing-->
                <!--                <div class="row">
                                    <div class="col-md-3 col-xl-2" style="">
                                        <label>Buffer Time:</label>
                                        <input type="number" min="0" max="60" id="input_buffer" class="selectstyle" step="5" value="30"/>
                                    </div>
                                    <div class="col-md-3 col-lg-2">
                                        <div class="" style="margin-left: 15px" >
                                            <button id="loaddata" type="button" class="btn btn-primary" onclick="loadprioritydata();" style="margin-bottom: 5px;">Load Data</button>
                                        </div>
                                    </div>
                                </div>-->

                <div id="ajaxloadergif" class=""> Data Loading, please wait...<img src="../ajax-loader-big.gif" alt=""/></div>

                <!--Container to hold table of batches after ajax run-->
                <div id="container_priorities"></div>

                <!--Modal to show batch level detail-->
                <div id="modal_batchdetail" class="modal fade " role="dialog">
                    <div class="modal-dialog modal-lg">

                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Batch Detail</h4>
                            </div>
                            <div id="data_modalreturn"></div>
                        </div>
                    </div>
                </div>

            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');

            function loadprioritydata() {
                $('#ajaxloadergif').removeClass('hidden');
                $.ajax({
//                    data: {orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/loosepriorities.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $('#ajaxloadergif').addClass('hidden');
                        $("#container_priorities").html(ajaxresult);
                    }
                });
            }

            $(document).ready(function () {
                loadprioritydata();

            });

            $(document).on("click touchstart", ".noclick", function (e) {
                e.stopPropagation();
            });


            //open modal to display relevant data when batch is clicked
            $(document).on("click touchstart", ".itemdetailexpand", function (e) {
                debugger;
                var batch = (this.id);
                $('#modal_batchdetail').modal('toggle');

                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_batchdata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_modalreturn").html(ajaxresult);
                    }
                });
            });


            //Set the interval function to refresh a set time period
            setInterval(function () {
                loadprioritydata();
                $(window).resize();
            }, 60000);

            //function to delete selected batches
            $(document).on("click", "#btn_delete_batch", function (e) {
                debugger;
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
                    url: 'formpost/deletepickbatch_loosepriority.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });


        </script>

    </body>
</html>
