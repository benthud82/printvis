<!--Delete TSM-->


<div id="modal_deletetsm" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Delete TSM</h4>
            </div>
            <form class="form-horizontal" id="postdeletewager">
                <div class="modal-body">

                    <div class="form-group hidden">
                        <label class="col-sm-3 control-label">TSM ID</label>
                        <div class="col-sm-3">
                            <input type="text" name="delete_tsmid" id="delete_tsmid" class="selectstyle" placeholder="" tabindex="" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-center">
                        <button type="submit" class="btn btn-danger btn-lg pull-left" name="btn_deletetsm" id="btn_deletetsm">Delete TSM?</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script>

    //post complete wager to table
    $(document).on("click", "#btn_deletetsm", function (event) {
        event.preventDefault();
        var delete_tsmid = $('#delete_tsmid').val();

        var formData = 'delete_tsmid=' + delete_tsmid;
        $.ajax({
            url: 'formpost/postdeletetsm.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#modal_deletetsm').modal('hide');
                $('#shifttable').DataTable().ajax.reload();
            }
        });
    });

    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
</script>
