
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Batch Pack Times</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <script src="../svg-pan-zoom.js" type="text/javascript"></script>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="padding-bottom: 75px; padding-top: 75px;"> 
                    <div  class="col-sm-12"id="batchdisplay" style="font-size: 16px; text-align: center"></div>
                </div>



            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');

            $(document).ready(function () {
//                $('#modal_datarefreshing').modal('toggle'); //show data refreshing modal
                updatedata();
            });

            function updatedata() {
                $.ajax({
                    url: 'globaldata/batchpack.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#batchdisplay").html(ajaxresult);
                    }
                });
            }

            //Set the interval function to refresh a set time period
            setInterval(function () {
                updatedata();
                $(window).resize();
            }, 60000);

        </script>

    </body>
</html>
