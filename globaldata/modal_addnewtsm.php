<!-- Add New TSM Modal -->
<div id="modal_addnewtsm" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New TSM</h4>
            </div>
            <form class="form-horizontal" id="post_addnewtsm">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">TSM ID#</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_tsmid" id="addtsm_tsmid" class="selectstylecondendsed" placeholder="" tabindex="1" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">First Name</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_firstname" id="addtsm_firstname" class="selectstylecondendsed" placeholder="" tabindex="2" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Last Name</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_lastname" id="addtsm_lastname" class="selectstylecondendsed" placeholder="" tabindex="3" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Whse</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_whse" id="addtsm_whse" class="selectstylecondendsed" placeholder="" tabindex="4" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Building</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_building" id="addtsm_building" class="selectstylecondendsed" placeholder="" tabindex="5" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Select Position</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="addtsm_position" name="addtsm_position"  tabindex="6">
                                <option value="0"></option>
                                <option value="Case Picker II">Case Picker II</option>
                                <option value="Shipping Representative">Shipping Representative</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Standard Hours</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_stdhours" id="addtsm_stdhours" class="selectstylecondendsed" placeholder="" tabindex="7" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Start Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_starttime" id="addtsm_starttime" class="selectstylecondendsed" placeholder="" tabindex="8" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">End Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_endtime" id="addtsm_endtime" class="selectstylecondendsed" placeholder="" tabindex="9" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Break Time 1</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_breaktime1" id="addtsm_breaktime1" class="selectstylecondendsed" placeholder="" tabindex="10" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Break Time 2</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_breaktime2" id="addtsm_breaktime2" class="selectstylecondendsed" placeholder="" tabindex="11" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Lunch Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_lunchtime" id="addtsm_lunchtime" class="selectstylecondendsed" placeholder="" tabindex="12" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">OT Hours</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addtsm_othours" id="addtsm_othours" class="selectstylecondendsed" placeholder="" tabindex="13" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Include Hours?</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="addtsm_includehours" name="addtsm_includehours"  tabindex="14">
                                <option value="0"></option>
                                <option value="1">Yes</option>
                                <option value="2">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Department</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="addtsm_dept" name="addtsm_dept"  tabindex="15">
                                <option value="0"></option>
                                <option value="CASE">Case</option>
                                <option value="LOOSE">Loose</option>
                            </select>
                        </div>
                    </div>



                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg pull-left" name="btn_addtsm" id="btn_addtsm"  tabindex="16">Add TSM</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>






    //post add wager to table
    $(document).on("click", "#btn_addtsm", function (event) {
        event.preventDefault();
        debugger;
        var addtsm_tsmid = $('#addtsm_tsmid').val();
        var addtsm_firstname = $('#addtsm_firstname').val();
        var addtsm_lastname = $('#addtsm_lastname').val();
        var addtsm_whse = $('#addtsm_whse').val();
        var addtsm_building = $('#addtsm_building').val();
        var addtsm_position = $('#addtsm_position').val();
        var addtsm_stdhours = $('#addtsm_stdhours').val();
        var addtsm_starttime = $('#addtsm_starttime').val();
        var addtsm_endtime = $('#addtsm_endtime').val();
        var addtsm_breaktime1 = $('#addtsm_breaktime1').val();
        var addtsm_breaktime2 = $('#addtsm_breaktime2').val();
        var addtsm_lunchtime = $('#addtsm_lunchtime').val();
        var addtsm_othours = $('#addtsm_othours').val();
        var addtsm_includehours = $('#addtsm_includehours').val();
        var addtsm_dept = $('#addtsm_dept').val();

        var formData = 'addtsm_tsmid=' + addtsm_tsmid + '&addtsm_firstname=' + addtsm_firstname + '&addtsm_lastname=' + addtsm_lastname + '&addtsm_whse=' + addtsm_whse + '&addtsm_building=' + addtsm_building + '&addtsm_position=' + addtsm_position
                + '&addtsm_stdhours=' + addtsm_stdhours + '&addtsm_starttime=' + addtsm_starttime + '&addtsm_endtime=' + addtsm_endtime + '&addtsm_breaktime1=' + addtsm_breaktime1 + '&addtsm_breaktime2=' + addtsm_breaktime2 + '&addtsm_lunchtime=' + addtsm_lunchtime
                + '&addtsm_othours=' + addtsm_othours + '&addtsm_includehours=' + addtsm_includehours + '&addtsm_dept=' + addtsm_dept;
        $.ajax({
            url: 'formpost/postaddnewtsm.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_addnewtsm').modal('hide');
                $('#shifttable').DataTable().ajax.reload();
            }
        });
    });
    $('#startfiscal').datepicker();

    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>

