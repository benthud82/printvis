
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';
    include_once '../Off_System_Slotting/connection/connection_details.php';
    ?>
    <head>
        <title>Ship Zone Priorities</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php
        include_once '../Off_System_Slotting/headerincludes.php';
        ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder"  style="padding-bottom: 75px; padding-top: 75px;"> 
                <?php include_once 'authority/auth_shipzone.php'; ?>
                <!--List any shipzones that are missing-->
                <div id="err_shipzones"></div>
                <!--List of ship zones-->
                <div id="shipzonelist"></div>
            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#shipzones").addClass('active');

            $(document).ready(function () {
                err_shipzone();
                //call function to refresh shipzone UL list
                shipzone_ul_list();
            });

            //Data pull 
            function shipzone_ul_list() {
                var mod_shipzone = <?php echo$auth_mod; ?>;
                $.ajax({
                    url: 'globaldata/data_shipzonelist.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#shipzonelist").html(ajaxresult);

                        if (mod_shipzone === 1) {
                            $('#sortable').sortable({
                                items: '.ul_shipzone',
                                start: function (event, ui) {
                                    // Create a temporary attribute on the element with the old index
                                    $(this).attr('data-currentindex', ui.item.index());
                                },
                                update: function (event, ui) {
                                    let current_position = $(this).attr('data-currentindex');
                                    let desired_position = ui.item.index();

                                    // Reset the current index
                                    $(this).removeAttr('data-currentindex');

                                    // Post to the server to handle the changes
                                    $.ajax({
                                        type: "POST",
                                        url: "formpost/updateshipzonerank.php",
                                        data: {
                                            desired_position: desired_position,
                                            current_position: current_position
                                        },
                                        beforeSend: function () {
                                            // Disable dragging
                                            $('#sortable').sortable('disable');
                                        },
                                        success: function (html) {
                                            // Re-enable dragging
                                            $('#sortable').sortable('enable');
                                            shipzone_ul_list(); //reload list
                                        }
                                    });
                                }
                            });

                        }

                        $("#sortable").disableSelection();

                    }
                });
            }

            function gettable(shipzone) {
                $('#modal_addnewshipzone').modal('toggle');
                if (typeof shipzone !== 'undefined') {
                    $('#addshipzone_shipzone').val(shipzone);
                }
            }

            function err_shipzone() {
                $.ajax({
                    url: 'globaldata/errors_shipzone.php',
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#err_shipzones").html(ajaxresult);
                    }
                });
            }

            $(document).on("click touchstart", ".del_shipzone", function (e) {
                var currank = $(this).attr('data-currentrank');
                $('#modal_deleteshipzone').modal('toggle');
                $('#delete_shipzone').val(currank);
            });

            $(document).on("click touchstart", ".mod_shipzone", function (e) {
                $('#modal_modifyshipzone').modal('toggle');
                debugger;
                var shipzone = $(this).attr('data-shipzone');
                var printcut = $(this).attr('data-printcut');
                var truckpull = $(this).attr('data-truckpull');

                $('#modify_shipzone').val(shipzone);
                $('#modify_print').val(printcut);
                $('#modify_truck').val(truckpull);

            });


        </script>

        <?php include_once 'globaldata/modal_deleteshipzone.php'; ?>
        <?php include_once 'globaldata/modal_addnewshipzone.php'; ?>
        <?php include_once 'globaldata/modal_modifyshipzone.php'; ?>

    </body>
</html>
