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

            </section>
        </section>
        <script>
            $(document).ready(function () {
                totalput();
            });


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

            function cartput() {
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

        </script>
    </body>
</html>
