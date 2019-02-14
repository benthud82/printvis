
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

   include '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Pack Times</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <script src="../svg-pan-zoom.js" type="text/javascript"></script>
        <?php include_once '../printvis/headerincludes.php'; ?>
    </head>

    <style>
        rect {transition: .6s fill; opacity: 1 !important; cursor: pointer;opacity: .7 !important; }
        rect:hover {opacity: 1 !important;  transition: .4s !important}
        .borderedcontainer{
            border: 1px dashed black;
            height: 600px;
            padding: 0px;
            background: white;
        }
        text {cursor: default;}


    </style>


    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="padding-bottom: 75px; padding-top: 75px;"> 
                    <!--SVG container for pack stations-->
                    <div class="col-sm-12">
                        <div id="svgcontainer" class="" style="margin: 15px;"></div>
                    </div>
                </div>
                <div class="row">
                    <!--Table the shows pack times by batch-->

                    <div id="container_packtimes"></div>

            </section>
        </section>



        <!--Modal to indicate data is refreshing-->
        <div id="modal_datarefreshing" class="modal fade " role="dialog" data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Refreshing Pack Data</h4>
                    </div>
                    <form class="form-horizontal" id="post_refreshpackdata">
                        <div class="modal-body">
                            Data currently refreshing.  Please wait.  
                            <img src="../ajax-loader-big.gif" alt=""/>
                        </div>
                    </form>
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
                            <div id="data_totelevelpack"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Modal to refresh pack data-->
        <div id="modal_refreshpackdata" class="modal fade " role="dialog"data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Refresh Pack Data</h4>
                    </div>
                    <form class="form-horizontal" id="">
                        <div class="modal-body">
                            The data is out of date.  Please click button below to refresh data.
                        </div>
                        <div class="modal-footer">
                            <div class="text-center">
                                <button id="modal_btn_refreshdata" type="button" class="btn btn-danger" onclick="refreshdataclick();" style="margin-bottom: 5px;">Refresh Data</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');
            var myTimeout;

            function refreshdataclick() {
                window.location.reload(true);
            }

            function loadpackdata(link_name, sort_class2) {
                var orderby = link_name;
                var sort_class = sort_class2;

                $.ajax({
                    data: {orderby: orderby, sort_class: sort_class},
                    url: 'globaldata/totetimes.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#container_packtimes").html(ajaxresult);
                    }
                });
                clearTimeout(myTimeout);
                myTimeout = setTimeout(function () {
                    $('#modal_refreshpackdata').modal('toggle');
                }, 300000);
            }

            $(document).ready(function () {
                $('#modal_datarefreshing').modal('toggle'); //show data refreshing modal
                $.ajax({
                    url: 'globaldata/heatmap_pack.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#svgcontainer").html(ajaxresult);
                        window.zoomTiger = svgPanZoom('#svg2', {
                            zoomEnabled: true,
                            controlIconsEnabled: true,
                            fit: true,
                            center: true
                        });
                    }
                });
                loadpackdata('batch', ' asc');
                $('#modal_datarefreshing').modal('toggle'); //hide data refreshing modal



            });

            $(document).on("click touchstart", ".batchclick", function (e) {
                debugger;
                var batch = (this.id);
                $('#modal_totelevelpack').modal('toggle');
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_totedata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_totelevelpack").html(ajaxresult);
                    }
                });
            });

            //function to delete batch from tote detail modal
            $(document).on("click", "#modal_btn_delete", function (e) {
                debugger;
                var deletearray = [];
                var arraycount = 0;
                var deletekey = $.trim($("#activebatch").html()) + $.trim($("#activetsm").html());
                deletearray[arraycount] = [deletekey];
                var deletearraycount = 1;
                $.ajax({
                    url: 'formpost/deletepackbatch.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });

            //function to delete selected batches
            $(document).on("click", "#btn_delete", function (e) {
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
                    url: 'formpost/deletepackbatch.php',
                    type: 'post',
                    data: {deletearray: deletearray, deletearraycount: deletearraycount},
                    success: function () {
                        window.location.reload(true);
                    }
                });
            });

            //Sort tote detail modal by completion time.
            $(document).on("click touchstart", ".endtimesort", function (e) {
                var batch = $("#activebatch").html();
                var sort = 'endtime';
                $.ajax({
                    data: {batch: batch, sort: sort},
                    url: 'globaldata/modal_totedata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_totelevelpack").html(ajaxresult);
                    }
                });
            });

            //Sort tote detail modal by bin number.
            $(document).on("click touchstart", ".binsort", function (e) {
                var batch = $("#activebatch").html();
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_totedata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_totelevelpack").html(ajaxresult);
                    }
                });
            });

            //remove batch from pack table view
            $(document).on("click touchstart", ".packdelete", function (e) {
                // Show modal to delete batch from showing on packtimes
                var deleteid = (this.id);
                $('#modal_deletepackbatch').modal('toggle');
                $('#deletebatchid').val(deleteid);
            });

            //call tote data when pack station is clicked
            $(document).on("click touchstart", ".clickablesvg", function (e) {
                var batch = (this.id);
                $('#modal_totelevelpack').modal('toggle');
                $.ajax({
                    data: {batch: batch},
                    url: 'globaldata/modal_totedata.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#data_totelevelpack").html(ajaxresult);
                    }
                });
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
                        loadpackdata(link_name, sort_class2);
                        break;
                }
            });

        </script>

    </body>
</html>
