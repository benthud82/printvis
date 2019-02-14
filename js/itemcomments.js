
//show addcomment modal through id
$(document).on("click", "#addcomment", function (e) {
    $('#addcommentmodal').modal('toggle');
});

//show addcomment modal through class
$(document).on("click", ".addcomment", function (e) {
    $('#addcommentmodal').modal('toggle');
    $('#itemmodal').val($(this).attr("id")); //if clicked from class of add comment there will not be an item value.  Must take from id of clicked comment
});



//submit item comment to post to mysql
$(document).on("click", "#additemcomment", function (event) {
    event.preventDefault();
    var descriptionmodal = $('#descriptionmodal').val();
    var commentmodal = $('#commentmodal').val();
    debugger;
    var itemmodal = $('#itemnum').val();
    if (itemmodal === undefined) {
        var itemmodal = $('#itemmodal').val();  //pull from item modal id if undefined.  Probably came from .addcomment class rather than ID of #addcomment
    }
    var formData = 'descriptionmodal=' + descriptionmodal + '&commentmodal=' + commentmodal + '&itemmodal=' + itemmodal;
    $.ajax({
        url: 'formpost/postadditemcomment.php',
        type: 'POST',
        data: formData,
        success: function (result) {
            $('#addcommentmodal').modal('hide');
            gettable(itemmodal);
        }
    });
});


