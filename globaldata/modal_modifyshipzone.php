<!-- Modify TSM Modal -->
<div id="modal_modifyshipzone" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modify Ship Zone</h4>
            </div>
            <form class="form-horizontal" id="post_modifynewshipzone">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Ship Zone</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modify_shipzone" id="modify_shipzone" class="selectstylecondendsed" placeholder="" tabindex="1" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Print Cutoff Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modify_print" id="modify_print" class="selectstylecondendsed" placeholder="" tabindex="2" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Truck Pull Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="modify_truck" id="modify_truck" class="selectstylecondendsed" placeholder="" tabindex="3" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg pull-left" name="btn_modifyshipzone" id="btn_modifyshipzone"  tabindex="4">Modify Ship Zone</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>






    //post modify tsm to table
    $(document).on("click", "#btn_modifyshipzone", function (event) {
        event.preventDefault();
        debugger;
        var modify_shipzone = $('#modify_shipzone').val();
        var modify_print = $('#modify_print').val();
        var modify_truck = $('#modify_truck').val();

        var formData = 'modify_shipzone=' + modify_shipzone + '&modify_print=' + modify_print + '&modify_truck=' + modify_truck;
        $.ajax({
            url: 'formpost/postmodifyshipzone.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_modifyshipzone').modal('hide');
                err_shipzone();
                shipzone_ul_list();
            }
        });
    });


    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>

