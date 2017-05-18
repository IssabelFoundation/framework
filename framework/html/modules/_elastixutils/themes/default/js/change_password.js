$(document).ready(function() {
    $('#sendChanPass').click(function() {
        var arrAction = new Array();
        var oldPass   = $('#curr_pass').val();
        var newPass   = $('#curr_pass_new').val();
        var newPassRe = $('#curr_pass_renew').val();

        if (oldPass == "")
            return alert($('#lblCurrentPassAlert').val());
        if (newPass == "" || newPassRe == "")
            return alert($('#lblNewRetypePassAlert').val());
        if (newPass != newPassRe)
            return alert($('#lblPassNoTMatchAlert').val());

        request('index.php', {
            menu:           '_elastixutils',
            action:         'changePasswordElastix',
            oldPassword:    oldPass,
            newPassword:    newPass,
            newRePassword:  newPassRe
        }, false, function(arrData,statusResponse,error) {
            alert(error);
            if (statusResponse != "false") {
                hideModalPopUP();
            }
        });
    });
});

