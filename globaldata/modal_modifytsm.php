<!-- Modify TSM Modal -->
<div id="modal_modifytsm" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modify TSM</h4>
            </div>
            <form class="form-horizontal" id="post_modifynewtsm">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">TSM ID#</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_tsmid" id="modifytsm_tsmid" class="selectstylecondendsed" placeholder="" tabindex="1" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">First Name</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_firstname" id="modifytsm_firstname" class="selectstylecondendsed" placeholder="" tabindex="2" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Last Name</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_lastname" id="modifytsm_lastname" class="selectstylecondendsed" placeholder="" tabindex="3" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Whse</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_whse" id="modifytsm_whse" class="selectstylecondendsed" placeholder="" tabindex="4" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Building</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_building" id="modifytsm_building" class="selectstylecondendsed" placeholder="" tabindex="5" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Select Position</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="modifytsm_position" name="modifytsm_position"  tabindex="6">
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
                            <input type="text" name="modifytsm_stdhours" id="modifytsm_stdhours" class="selectstylecondendsed" placeholder="" tabindex="7" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Start Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_starttime" id="modifytsm_starttime" class="selectstylecondendsed" placeholder="" tabindex="8" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">End Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_endtime" id="modifytsm_endtime" class="selectstylecondendsed" placeholder="" tabindex="9" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Break Time 1</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_breaktime1" id="modifytsm_breaktime1" class="selectstylecondendsed" placeholder="" tabindex="10" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Break Time 2</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_breaktime2" id="modifytsm_breaktime2" class="selectstylecondendsed" placeholder="" tabindex="11" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Lunch Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_lunchtime" id="modifytsm_lunchtime" class="selectstylecondendsed" placeholder="" tabindex="12" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">OT Hours</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modifytsm_othours" id="modifytsm_othours" class="selectstylecondendsed" placeholder="" tabindex="13" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Include Hours?</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="modifytsm_includehours" name="modifytsm_includehours"  tabindex="14">
                                <option value=""></option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Department</label>
                        <div class="col-sm-3">
                            <select class="selectstylecondendsed" id="modifytsm_dept" name="modifytsm_dept"  tabindex="15">
                                <option value="0"></option>
                                <option value="CASE">Case</option>
                                <option value="LOOSE">Loose</option>
                            </select>
                        </div>
                    </div>



                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg pull-left" name="btn_modifytsm" id="btn_modifytsm"  tabindex="16">Modify TSM</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>






    //post modify tsm to table
    $(document).on("click", "#btn_modifytsm", function (event) {
        event.preventDefault();
        debugger;
        var modifytsm_tsmid = $('#modifytsm_tsmid').val();
        var modifytsm_firstname = $('#modifytsm_firstname').val();
        var modifytsm_lastname = $('#modifytsm_lastname').val();
        var modifytsm_whse = $('#modifytsm_whse').val();
        var modifytsm_building = $('#modifytsm_building').val();
        var modifytsm_position = $('#modifytsm_position').val();
        var modifytsm_stdhours = $('#modifytsm_stdhours').val();
        var modifytsm_starttime = $('#modifytsm_starttime').val();
        var modifytsm_endtime = $('#modifytsm_endtime').val();
        var modifytsm_breaktime1 = $('#modifytsm_breaktime1').val();
        var modifytsm_breaktime2 = $('#modifytsm_breaktime2').val();
        var modifytsm_lunchtime = $('#modifytsm_lunchtime').val();
        var modifytsm_othours = $('#modifytsm_othours').val();
        var modifytsm_includehours = $('#modifytsm_includehours').val();
        var modifytsm_dept = $('#modifytsm_dept').val();

        var formData = 'modifytsm_tsmid=' + modifytsm_tsmid + '&modifytsm_firstname=' + modifytsm_firstname + '&modifytsm_lastname=' + modifytsm_lastname + '&modifytsm_whse=' + modifytsm_whse + '&modifytsm_building=' + modifytsm_building + '&modifytsm_position=' + modifytsm_position
                + '&modifytsm_stdhours=' + modifytsm_stdhours + '&modifytsm_starttime=' + modifytsm_starttime + '&modifytsm_endtime=' + modifytsm_endtime + '&modifytsm_breaktime1=' + modifytsm_breaktime1 + '&modifytsm_breaktime2=' + modifytsm_breaktime2 + '&modifytsm_lunchtime=' + modifytsm_lunchtime
                + '&modifytsm_othours=' + modifytsm_othours + '&modifytsm_includehours=' + modifytsm_includehours + '&modifytsm_dept=' + modifytsm_dept;
        $.ajax({
            url: 'formpost/postmodifytsm.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_modifytsm').modal('hide');
                $('#shifttable').DataTable().ajax.reload();
            }
        });
    });


    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>

