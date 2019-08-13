
function checkCookie_custcomplaint() {
    var clickeddata = getCookie("post-desc");
    var clickedval = getCookie("post-val");
    if (clickeddata !== "" && clickedval !== "") {
        //cookies have been passed from custcomplaints.php
        //based off clickeddata value, call approriate ajax function
        switch (clickeddata) {
            case 'ORD_RETURNDATE':
                // code block
                break;
            case 'COMPDATE':
                _dt_date(clickedval);
                break;
            case 'RETURNCODE':
                // code block
                break;
            case 'ITEMCODE':
                _dt_itemcode(clickedval);
                break;
            case 'WCSNUM':
                // code block
                break;
            case 'LPNUM':
                getmodaldata_cookie(clickedval, 'lpnum');
                break;
            default:
                break
        }
    }
}

function getCookie(cname) {

    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function loaddata(idval, modal) {
    getmodaldata(idval, modal);
}

//data when searched from common report
function getmodaldata(idval, modal) {
    $('#' + modal).modal('toggle');
    $('#datareturn').addClass('hidden');
    $('#datareturn').removeClass('hidden');
    $('.hidewrapper').addClass('hidden');
    var sqldata = $('#' + idval).val();
    var reporttype = idval;

    $.ajax({
        url: 'globaldata/custcomplaint_data.php',
        type: 'POST',
        data: {sqldata: sqldata, reporttype: reporttype},
        dataType: 'html',
        success: function (ajaxresult) {
//             $('#datareturn').html($('#datareturn',ajaxresult).html());
            $("#datareturn").html(ajaxresult);
            //    datatables
            switch (reporttype) {
                case 'packtsm':
                    _dt_packtsm(sqldata);
                    break;
                case 'picktsm':
                    _dt_picktsm(sqldata);
                    break;
                default:
                    break
            }
        }
    });
}

//data when searched by cookie from custcomplaints.php
function getmodaldata_cookie(idval, modal) {
    $('#datareturn').addClass('hidden');
    $('#datareturn').removeClass('hidden');
    $('.hidewrapper').addClass('hidden');
    var sqldata = idval;
    var reporttype = modal;
    //    datatables
    switch (reporttype) {
        case 'packtsm':
            _dt_packtsm(sqldata);
            break;
        case 'picktsm':
            _dt_picktsm(sqldata);
            break;
        default:
            break
    }
    //ajax to pull data by lpnum
    $.ajax({
        url: 'globaldata/custcomplaint_data.php',
        type: 'POST',
        data: {sqldata: sqldata, reporttype: reporttype},
        dataType: 'html',
        success: function (ajaxresult) {
            $("#datareturn").html(ajaxresult);
            deleteAllCookies();
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

// Load Datable on click for item code
function _dt_itemcode(clickedval) {
    $('#datareturn').addClass('hidden');
    $('.hidewrapper').addClass('hidden');
    if (clickedval == null) {
        var itemcode = $('#itemcode').val();
    } else {
        var itemcode = clickedval;
    }
    oTable = $('#table_itemcode').DataTable({
        dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
        destroy: true,
        "scrollX": true,
        "aoColumnDefs": [
            {
                "aTargets": [6], // Column to target
                "mRender": function (data, type, full) {
                    // 'full' is the row's data object, and 'data' is this column's data
                    // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                    return '<a href="https://www.ups.com/track?loc=en_US&tracknum=' + data + '&requester=WT/trackdetails" target="_blank">' + data + '</a>';
                }
            }
        ],
        'sAjaxSource': "globaldata/custcomp_itemcode_data.php?itemcode=" + itemcode,
        buttons: [
            'copyHtml5',
            'excelHtml5'
        ]
    });
    $('#modal_item').modal('hide');
    $('#section_itemcode').removeClass('hidden');
    deleteAllCookies();
}

// Load Datable on click for pack TSM
function _dt_packtsm(clickedval) {
    $('.hidewrapper').addClass('hidden');
    if (clickedval == null) {
        var packtsm = $('#packtsm').val();
    } else {
        var packtsm = clickedval;
    }
    oTable = $('#table_packtsm').DataTable({
        dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
        destroy: true,
        "scrollX": true,
        "aoColumnDefs": [
            {
                "aTargets": [6], // Column to target
                "mRender": function (data, type, full) {
                    // 'full' is the row's data object, and 'data' is this column's data
                    // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                    return '<a href="https://www.ups.com/track?loc=en_US&tracknum=' + data + '&requester=WT/trackdetails" target="_blank">' + data + '</a>';
                }
            }
        ],
        'sAjaxSource': "globaldata/custcomp_dtpacktsm_data.php?packtsm=" + packtsm,
        buttons: [
            'copyHtml5',
            'excelHtml5'
        ]
    });

    $('#section_packtsm').removeClass('hidden');
    deleteAllCookies();
}

// Load Datable on click for pack TSM
function _dt_picktsm(clickedval) {
    if (clickedval == null) {
        var picktsm = $('#picktsm').val();
    } else {
        var picktsm = clickedval;
    }
    oTable = $('#table_picktsm').DataTable({
        dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
        destroy: true,
        "scrollX": true,
        "aoColumnDefs": [
            {
                "aTargets": [6], // Column to target
                "mRender": function (data, type, full) {
                    // 'full' is the row's data object, and 'data' is this column's data
                    // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                    return '<a href="https://www.ups.com/track?loc=en_US&tracknum=' + data + '&requester=WT/trackdetails" target="_blank">' + data + '</a>';
                }
            }
        ],
        'sAjaxSource': "globaldata/custcomp_dtpicktsm_data.php?picktsm=" + picktsm,
        buttons: [
            'copyHtml5',
            'excelHtml5'
        ]
    });

    $('#section_picktsm').removeClass('hidden');
    deleteAllCookies();
}

// Load Datable on click for date
function _dt_date(clickedval) {
        $('#datareturn').addClass('hidden');
    $('.hidewrapper').addClass('hidden');
    if (clickedval == null) {
        var startdate = $('#startfiscal').val();
    } else {
        var startdate = clickedval;
    }
    oTable = $('#table_date').DataTable({
        dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
        destroy: true,
        "scrollX": true,
        "aoColumnDefs": [
            {
                "aTargets": [6], // Column to target
                "mRender": function (data, type, full) {
                    // 'full' is the row's data object, and 'data' is this column's data
                    // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                    return '<a href="https://www.ups.com/track?loc=en_US&tracknum=' + data + '&requester=WT/trackdetails" target="_blank">' + data + '</a>';
                }
            }
        ],
        'sAjaxSource': "globaldata/custcomp_dtdate_data.php?startdate=" + startdate,
        buttons: [
            'copyHtml5',
            'excelHtml5'
        ]
    });
    $('#modal_date').modal('hide');
    $('#section_date').removeClass('hidden');
    deleteAllCookies();
}

function deleteAllCookies() {
    var cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
}

 