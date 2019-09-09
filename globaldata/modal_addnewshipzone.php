<!-- Add New TSM Modal -->
<div id="modal_addnewshipzone" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Ship Zone</h4>
            </div>
            <form class="form-horizontal" id="post_addnewshipzone">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Ship Zone</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addshipzone_shipzone" id="addshipzone_shipzone" class="selectstylecondendsed" placeholder="First 2 Characters" tabindex="1" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Print Cutoff Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addshipzone_print" id="addshipzone_print" class="selectstylecondendsed" placeholder="Use 24 hour" tabindex="2" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Truck Pull Time</label>
                        <div class="col-sm-3" >
                            <input type="text" name="addshipzone_truck" id="addshipzone_truck" class="selectstylecondendsed" placeholder="Use 24 hour" tabindex="3" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg pull-left" name="btn_addshipzone" id="btn_addshipzone"  tabindex="4">Add Ship Zone</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

    $(document).on("click", "#btn_addshipzone", function (event) {
        event.preventDefault();
        debugger;
        var addshipzone_shipzone = $('#addshipzone_shipzone').val();
        var addshipzone_print = $('#addshipzone_print').val();
        var addshipzone_truck = $('#addshipzone_truck').val();


        var formData = 'addshipzone_shipzone=' + addshipzone_shipzone + '&addshipzone_print=' + addshipzone_print + '&addshipzone_truck=' + addshipzone_truck;
        $.ajax({
            url: 'formpost/postaddnewshipzone.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_addnewshipzone').modal('hide');
                err_shipzone();
                shipzone_ul_list();
            }
        });
    });


    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>

