<script src="../../EasyAutocomplete-1.3.4/dist/jquery.easy-autocomplete.js" type="text/javascript"></script>
<link href="../../EasyAutocomplete-1.3.4/dist/easy-autocomplete.css" rel="stylesheet" type="text/css"/>
<link href="../../EasyAutocomplete-1.3.4/dist/easy-autocomplete.themes.css" rel="stylesheet" type="text/css"/>
<link href="../../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>


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
                                loaddata('lpnum', 'modal_lp');"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left" onclick="loaddata('lpnum', 'modal_lp');" style="margin-bottom: 5px;">Load Data</button>
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
                                loaddata('picktsm', 'modal_pick');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onselect="loaddata('picktsm', 'modal_pick');" onclick="loaddata('picktsm', 'modal_pick');" style="margin-bottom: 5px;">Load Data</button>
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
                                loaddata('packtsm', 'modal_pack');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onselect="loaddata('packtsm', 'modal_pack');" onclick="loaddata('packtsm', 'modal_pack');" style="margin-bottom: 5px;">Load Data</button>
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
                <div class="" style="margin-left: 15px" >
                    <label>Enter Bill To #</label>
                    <input name='billto' class='datainput selectstyle' id='billto' onKeyDown="if (event.keyCode === 13)
                                loaddata('billto', 'modal_billto');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer">
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onclick="loaddata('billto', 'modal_billto');" style="margin-bottom: 5px;">Load Data</button>
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
                <div class="" style="margin-left: 15px" >
                    <label>Enter Ship To #</label>
                    <input name='shipto' class='datainput selectstyle' id='shipto' onKeyDown="if (event.keyCode === 13)
                                loaddata('shipto', 'modal_shipto');" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer" >
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onclick="loaddata('shipto', 'modal_shipto');" style="margin-bottom: 5px;">Load Data</button>
            </div>
        </div>
    </div>
</div>

<!--Modal to search by ITEM CODE-->
<div id="modal_item" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Item Code</h4>
            </div>
            <div class="modal-body">
                <div class="" style="margin-left: 15px" >
                    <label>Enter Item #</label>
                    <input  name='itemcode' class='datainput selectstyle' id='itemcode' onKeyDown="if (event.keyCode === 13)
                                _dt_itemcode();" style="min-width: 300px;"/>
                </div>
            </div>
            <div class="modal-footer" >
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onclick="_dt_itemcode();" style="margin-bottom: 5px;">Load Data</button>
            </div>
        </div>
    </div>
</div>

<!--Modal to search by DATE-->
<div id="modal_date" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close_visible" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Search by Date</h4>
            </div>
            <div class="modal-body">
                <div class="" style="margin-left: 15px" >
                    <label>Enter Date</label>
                    <input autocomplete="off"  name="startfiscal" id="startfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;"onKeyDown="if (event.keyCode === 13)
                                _dt_date();"  />
                </div>
            </div>
            <div class="modal-footer" >
                <button id="loaddata" type="button" class="btn btn-primary pull-left"  onclick="_dt_date();" style="margin-bottom: 5px;">Load Data</button>
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
                //call loaddata function
                loaddata('picktsm', 'modal_pick');
            }
        },
        theme: "plate-dark"
    };

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
                //call loaddata function
                loaddata('packtsm', 'modal_pack');
            }
        },
        theme: "plate-dark"
    };

    //populate the options for packtsm
    $("#packtsm").easyAutocomplete(options_packtsm);

    //populate the options for picktsm
    $("#picktsm").easyAutocomplete(options_picktsm);


    $('.modal').on('shown.bs.modal', function () {
        setTimeout(function () {
            $('.datainput').focus();
        }, 100);

    });
</script>


<script>
    $('#startfiscal').datepicker();
</script>