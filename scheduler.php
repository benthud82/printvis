
<!DOCTYPE html>
<html>
    <?php
    include_once 'sessioninclude.php';

    include_once '../connections/conn_printvis.php';
    ?>
    <head>
        <title>Shift Scheduler</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <!--<script src="../svg-pan-zoom.js" type="text/javascript"></script>-->
        <?php
        include_once 'headerincludes.php';
        ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <!--Main Content-->
        <section id="content"> 
            <section class="main padder"> 

                <!--TSM selection filters-->
                <div class="row" style="padding-bottom: 75px; padding-top: 75px;"> 
                    <div class="pull-left  col-lg-3" >
                        <label>Building:</label>
                        <select class="selectstyle" id="sel_building" name="sel_building" style="width: 175px;padding: 5px; margin-right: 10px;">
                            <option value="1">Building 1</option>
                            <option value="2">Building 2</option>
                        </select>
                    </div>
                    <div class="pull-left  col-lg-3" >
                        <label>Position:</label>
                        <select class="selectstyle" id="sel_position" name="sel_position" style="width: 175px;padding: 5px; margin-right: 10px;">
                            <option value="*"> -All- </option>
                            <option value="Case Picker II">Case Picker II</option>
                            <option value="Shipping Representative">Shipping Representative</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                </div>

                <!--Datatable-->
                <div id="tablecontainer" class="hidden" style="cursor: pointer">
                    <table id="shifttable" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                        <thead>
                            <tr>
                                <th>TSM#</th>
                                <th>TSM First Name</th>
                                <th>TSM Last Name</th>
                                <th>Warehouse</th>
                                <th>Building</th>
                                <th>Position</th>
                                <th>Scheduled Hours</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Break 1</th>
                                <th>Break 2</th>
                                <th>Lunch</th>
                                <th>Scheduled OT</th>
                                <th>Include Hours?</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                    </table>
                </div>



            </section>
        </section>

        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#scheduler").addClass('active');

            // Load Datable on click
            function gettable() {
                $('#tablecontainer').addClass('hidden');
                var sel_position = $('#sel_position').val();
                var sel_building = $('#sel_building').val();

                oTable = $('#shifttable').DataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    select: true,
                    "order": [[0, "asc"]],
                    "scrollX": true,
                    "fnCreatedRow": function (nRow, aData, iDataIndex) {
                        if (aData[13] === 1) {
                            $('td:eq(13)', nRow).append("<div class='text-center'><input  id='" + aData[0] + "' type='checkbox'  class='input_checkbox' checked='checked' /> </div>");
                        } else {
                            $('td:eq(13)', nRow).append("<div class='text-center'><input  id='" + aData[0] + "' type='checkbox'  class='input_checkbox'  /> </div>");
                        }
                    },
                    'sAjaxSource': "globaldata/dt_shift.php?sel_position=" + sel_position + "&sel_building=" + sel_building,
                    buttons: [
                        {
                            text: 'Add New TSM',
                            className: 'bg-success separatedbutton',
                            action: function () {
                                $('#modal_addnewtsm').modal('toggle');
                            }
                        },
                        {
                            text: 'Modify TSM',
                            className: 'bg-info separatedbutton',
                            action: function () {
                                debugger;
                                $('#modal_modifytsm').modal('toggle');
                                var modifytsm_tsmid = oTable.cell('.selected', 0).data();
                                var modifytsm_firstname = oTable.cell('.selected', 1).data();
                                var modifytsm_lastname = oTable.cell('.selected', 2).data();
                                var modifytsm_whse = oTable.cell('.selected', 3).data();
                                var modifytsm_building = oTable.cell('.selected', 4).data();
                                var modifytsm_position = oTable.cell('.selected', 5).data();
                                var modifytsm_stdhours = oTable.cell('.selected', 6).data();
                                var modifytsm_starttime = oTable.cell('.selected', 7).data();
                                var modifytsm_endtime = oTable.cell('.selected', 8).data();
                                var modifytsm_breaktime1 = oTable.cell('.selected', 9).data();
                                var modifytsm_breaktime2 = oTable.cell('.selected', 10).data();
                                var modifytsm_lunchtime = oTable.cell('.selected', 11).data();
                                var modifytsm_othours = oTable.cell('.selected', 12).data();
                                var modifytsm_includehours = oTable.cell('.selected', 13).data();
                                if (modifytsm_includehours === 'YES') {
                                    modifytsm_includehours = 1;
                                } else {
                                    modifytsm_includehours = 0;
                                }
                                var modifytsm_dept = oTable.cell('.selected', 14).data();


                                $('#modifytsm_tsmid').val(modifytsm_tsmid);
                                $('#modifytsm_firstname').val(modifytsm_firstname);
                                $('#modifytsm_lastname').val(modifytsm_lastname);
                                $('#modifytsm_whse').val(modifytsm_whse);
                                $('#modifytsm_building').val(modifytsm_building);
                                $('#modifytsm_position').val(modifytsm_position);
                                $('#modifytsm_stdhours').val(modifytsm_stdhours);
                                $('#modifytsm_starttime').val(modifytsm_starttime);
                                $('#modifytsm_endtime').val(modifytsm_endtime);
                                $('#modifytsm_breaktime1').val(modifytsm_breaktime1);
                                $('#modifytsm_breaktime2').val(modifytsm_breaktime2);
                                $('#modifytsm_lunchtime').val(modifytsm_lunchtime);
                                $('#modifytsm_othours').val(modifytsm_othours);
                                $('#modifytsm_includehours').val(modifytsm_includehours);
                                $('#modifytsm_dept').val(modifytsm_dept);


                            }
                        },
                        {
                            text: 'Delete TSM',
                            className: 'bg-danger separatedbutton',
                            action: function () {
                                var selectedtsm = oTable.cell('.selected', 0).data();
                                $('#modal_deletetsm').modal('toggle');
                                $('#delete_tsmid').val(selectedtsm);
                            }
                        }
                    ]
                });
                $('#tablecontainer').removeClass('hidden');

            }
        </script>
        <?php include_once 'globaldata/modal_addnewtsm.php'; ?>
        <?php include_once 'globaldata/modal_modifytsm.php'; ?>
        <?php include_once 'globaldata/modal_deletetsm.php'; ?>
    </body>
</html>
