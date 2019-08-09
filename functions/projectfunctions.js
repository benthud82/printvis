
function checkCookie_custcomplaint() {
    debugger;
    var clickeddata = getCookie("post-desc");
    var clickedval = getCookie("post-val");
    if (clickeddata !== "" && clickedval !== "") {
        //cookies have been passed from custcomplaints.php
        //based off clickeddata value, call approriate ajax function
        switch (clickeddata) {
            case 'ORD_RETURNDATE':
                // code block
                break;
            case 'SHIPDATEJ':
                // code block
                break;
            case 'RETURNCODE':
                // code block
                break;
            case 'ITEMCODE':
                // code block
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
    debugger;
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

//data when searched from common report
function getmodaldata(idval, modal) {
    debugger;
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

//data when searched by cookie from custcomplaints.php
function getmodaldata_cookie(idval, modal) {

    $('#datareturn').addClass('hidden');
    $('#datareturn').removeClass('hidden');
    debugger;
    var sqldata = idval;
    var reporttype = modal;
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