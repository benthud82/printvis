<script src="../../EasyAutocomplete-1.3.4/dist/jquery.easy-autocomplete.js" type="text/javascript"></script>
<link href="../../EasyAutocomplete-1.3.4/dist/easy-autocomplete.css" rel="stylesheet" type="text/css"/>
<link href="../../EasyAutocomplete-1.3.4/dist/easy-autocomplete.themes.css" rel="stylesheet" type="text/css"/>


<!--Modal to search by LP#-->
<div id="modal_lp" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by LP#</h4>
            </div>
            <div class="modal-body">
                <div class="" style="margin-left: 15px" >
                    <label>Enter Box LP</label>
                    <input  required minlength="9" maxlength="9" name='lpnum' class='datainput selectstyle' id='lpnum' onKeyDown="if (event.keyCode === 13)
                                getmodaldata('lpnum', 'modal_lp');"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left" onclick="getmodaldata('lpnum', 'modal_lp');" style="margin-bottom: 5px;">Load Data</button>
            </div>
        </div>
    </div>
</div>

<!--Modal to search by PICK TSM-->
<div id="modal_pick" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Pick TSM</h4>
            </div>
            <div class="modal-body">
                <div class="" style="margin-left: 15px" >
                    <label>Enter Pick TSM Name</label>
                    <input name='picktsm' class='datainput selectstyle' id='picktsm' onKeyDown="if (event.keyCode === 13)
                                getmodaldata('picktsm', 'modal_pick');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onselect="getmodaldata('picktsm', 'modal_pick');" onclick="getmodaldata('picktsm', 'modal_pick');" style="margin-bottom: 5px;">Load Data</button>
            </div>
        </div>
    </div>
</div>

<!--Modal to search by PACK TSM-->
<div id="modal_pack" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Pack TSM</h4>
            </div>
            <div class="modal-body">
                <div class="" style="margin-left: 15px" >
                    <label>Enter Pack TSM Name</label>
                    <input name='packtsm' class='datainput selectstyle' id='packtsm' onKeyDown="if (event.keyCode === 13)
                                getmodaldata('packtsm', 'modal_pack');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onselect="getmodaldata('packtsm', 'modal_pack');" onclick="getmodaldata('packtsm', 'modal_pack');" style="margin-bottom: 5px;">Load Data</button>
            </div>
        </div>
    </div>
</div>

<!--Modal to search by BILL TO-->
<div id="modal_billto" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Bill To #</h4>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

<!--Modal to search by SHIP TO-->
<div id="modal_shipto" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Ship To #</h4>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

<!--Modal to search by complaint code-->
<div id="modal_code" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Complaint Reason Code</h4>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

<script>
    //autocomplete when searched for pick TSM
    var options_picktsm = {
        url: "globaldata/dropdown_picktsm.php",
        getValue: "name",
        template: {
            type: "description",
            fields: {
                description: "number"
            }
        },
        list: {
            match: {
                enabled: true
            },
            onClickEvent: function () {
                //call getmodaldata function
                getmodaldata('picktsm', 'modal_pick');
            }
        },
        theme: "plate-dark"
    };

    //populate the options for picktsm
    $("#picktsm").easyAutocomplete(options_picktsm);
    
    //autocomplete when searched for pack TSM
    var options_packtsm = {
        url: "globaldata/dropdown_picktsm.php",
        getValue: "name",
        template: {
            type: "description",
            fields: {
                description: "number"
            }
        },
        list: {
            match: {
                enabled: true
            },
            onClickEvent: function () {
                //call getmodaldata function
                getmodaldata('packtsm', 'modal_pack');
            }
        },
        theme: "plate-dark"
    };

    //populate the options for packtsm
    $("#packtsm").easyAutocomplete(options_packtsm);

    //data when searched by LP#
    function getmodaldata(idval, modal) {
        $('#' + modal).modal('toggle');
        $('#datareturn').addClass('hidden');
        $('#datareturn').removeClass('hidden');
        debugger;
        var sqldata = $('#' + idval).val();
        var reporttype = idval;
        //ajax to pull data by lpnum
        $.ajax({
            url: 'globaldata/custcomplaint_data.php',
            type: 'POST',
            data: {sqldata: sqldata, reporttype: reporttype},
            dataType: 'html',
            success: function (ajaxresult) {
                $("#datareturn").html(ajaxresult);
            }
        });
    }


    //clear modal input on hide
    $('.modal').on('hidden.bs.modal', function (e) {
        $(this)
                .find("input,textarea,select")
                .val('')
                .end()
                .find("input[type=checkbox], input[type=radio]")
                .prop("checked", "")
                .end();
    });
</script>



