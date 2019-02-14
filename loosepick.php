
<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once '../Off_System_Slotting/connection/connection_details.php';
    ?>
    <head>
        <title>Loose Pick</title>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>
         <script src="../easytimer.js" type="text/javascript"></script>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder" style="padding-bottom: 75px; padding-top: 75px;"> 

                <!--Header stats at top of page-->
                <div id="headerstats" class="hidden"></div>
                <div id="ajaxloadergif" class=""> Data Loading, please wait...<img src="../ajax-loader-big.gif" alt=""/></div>

                <div class="row"> 
                    <!--data for loose pick boxes not yet printed goes here-->
                    <div id="ctn_notprintedloose" class="hidden">
                        <div id="ctn_divtablenotprinted"></div>
                    </div>
                </div>

            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});

            $(document).ready(function () {
                //call function to refresh loosepick header times
                refreshloosepickheadertimes();

                $(window).resize();
            });

            function refreshloosepickheadertimes() {
                $.ajax({
                    url: 'globaldata/data_loosepicktimes.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#headerstats").html(ajaxresult);
                        $('#ajaxloadergif').addClass('hidden');
                        $('#headerstats').removeClass('hidden');
                        $(window).resize();
                    }
                });
            }

        </script>

        <script>
            $("#loosepick").addClass('active');

        </script>
    </body>
</html>
