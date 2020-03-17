
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

                <!--Datatable-->
                <div id="tablecontainer" class="" style="cursor: pointer">
                    <table id="tbl_expedite" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>From Loc / Drop Zone</th>
                                <th>To Location</th>
                                <th>Box Hold Count</th>
                                <th>Shorts Count</th>
                                <th>Total Count</th>
                            </tr>
                        </thead>
                    </table>
                </div>



            </section>
        </section>
        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#loosepick").addClass('active');

            $(document).ready(function () {
                _get_aso_holds();
                _get_dropzone_replen();
                _get_display();
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

            function _get_display() {
                oTable = $('#tbl_expedite').DataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    select: true,
                    "scrollX": true,

//                    "columnDefs": [
//                        {
//                            "targets": [13],
//                            "visible": false,
//                            "searchable": false
//                        },
//                        {
//                            "targets": 15,
//                            "orderDataType": "dom-checkbox"
//                        }
//                    ],
//                    "fnCreatedRow": function (nRow, aData, iDataIndex) {
//                        if (aData[13] === 1) {
//                            $('td:eq(14)', nRow).append("<input  id='" + aData[0] + "' type='checkbox'  class='input_checkbox' checked='checked'  onchange='active_tsm(this)'/>");
//                        } else {
//                            $('td:eq(14)', nRow).append("<input  id='" + aData[0] + "' type='checkbox'  class='input_checkbox'   onchange='active_tsm(this)'/> ");
//                        }
//                    },
                    'sAjaxSource': 'globaldata/dt_asoexpeditor.php'
//                    buttons: [
//                        {
//                            text: 'Add New TSM',
//                            className: 'bg-success separatedbutton',
//                            action: function () {
//                                $('#modal_addnewtsm').modal('toggle');
//                            }
//                        },
//                        {
//                            text: 'Modify TSM',
//                            className: 'bg-info separatedbutton',
//                            action: function () {
//                                debugger;
//                                $('#modal_modifytsm').modal('toggle');
//                                var modifytsm_tsmid = oTable.cell('.selected', 0).data();
//                                var modifytsm_firstname = oTable.cell('.selected', 1).data();
//                                var modifytsm_lastname = oTable.cell('.selected', 2).data();
//                                var modifytsm_whse = oTable.cell('.selected', 3).data();
//                                var modifytsm_building = oTable.cell('.selected', 4).data();
//                                var modifytsm_position = oTable.cell('.selected', 5).data();
//                                var modifytsm_stdhours = oTable.cell('.selected', 6).data();
//                                var modifytsm_starttime = oTable.cell('.selected', 7).data();
//                                var modifytsm_endtime = oTable.cell('.selected', 8).data();
//                                var modifytsm_breaktime1 = oTable.cell('.selected', 9).data();
//                                var modifytsm_breaktime2 = oTable.cell('.selected', 10).data();
//                                var modifytsm_lunchtime = oTable.cell('.selected', 11).data();
//                                var modifytsm_othours = oTable.cell('.selected', 12).data();
//                                var modifytsm_includehours = oTable.cell('.selected', 13).data();
//                                var modifytsm_dept = oTable.cell('.selected', 14).data();
//                                $('#modifytsm_tsmid').val(modifytsm_tsmid);
//                                $('#modifytsm_firstname').val(modifytsm_firstname);
//                                $('#modifytsm_lastname').val(modifytsm_lastname);
//                                $('#modifytsm_whse').val(modifytsm_whse);
//                                $('#modifytsm_building').val(modifytsm_building);
//                                $('#modifytsm_position').val(modifytsm_position);
//                                $('#modifytsm_stdhours').val(modifytsm_stdhours);
//                                $('#modifytsm_starttime').val(modifytsm_starttime);
//                                $('#modifytsm_endtime').val(modifytsm_endtime);
//                                $('#modifytsm_breaktime1').val(modifytsm_breaktime1);
//                                $('#modifytsm_breaktime2').val(modifytsm_breaktime2);
//                                $('#modifytsm_lunchtime').val(modifytsm_lunchtime);
//                                $('#modifytsm_othours').val(modifytsm_othours);
//                                $('#modifytsm_includehours').val(modifytsm_includehours);
//                                $('#modifytsm_dept').val(modifytsm_dept);
//                            }
//                        },
//                        {
//                            text: 'Delete TSM',
//                            className: 'bg-danger separatedbutton',
//                            action: function () {
//                                var selectedtsm = oTable.cell('.selected', 0).data();
//                                $('#modal_deletetsm').modal('toggle');
//                                $('#delete_tsmid').val(selectedtsm);
//                            }
//                        }
//                    ]

                });

            }

        </script>
    </body>
</html>
