<!--Delete TSM-->


<div id="modal_deleteshipzone" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Delete Ship Zone</h4>
            </div>
            <form class="form-horizontal" id="post_deleteshipzone">
                <div class="modal-body">

                    <div class="form-group hidden">
                        <label class="col-sm-3 control-label">TSM ID</label>
                        <div class="col-sm-3">
                            <input type="text" name="delete_shipzone" id="delete_shipzone" class="selectstyle" placeholder="" tabindex="" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-danger btn-lg pull-left" name="btn_deleteshipzone" id="btn_deleteshipzone">Delete Ship Zone?</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script>

    //post complete wager to table
    $(document).on("click", "#btn_deleteshipzone", function (event) {
        event.preventDefault();
        debugger;
        var currank = $('#delete_shipzone').val();

        var formData = 'currank=' + currank;
        $.ajax({
            url: 'formpost/deleteshipzone.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_deleteshipzone').modal('hide');
                shipzone_ul_list();
            }
        });
    });

    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>
