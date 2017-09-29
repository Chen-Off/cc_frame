'use strict';

function quick_check_in(contact) {
    var url = 'app/CustomerMange/invitePlan/quick_invite_check/' + contact;
    $.get(url, function (msg) {
        alert(msg);
        $('#qci_msg').html(msg);
    });
}

$(document).on('click', '.qip_btn', function () {
    if (window.confirm('您确定该客户进行签到吗？')) {
        //alert("确定");
        return true;
    } else {
        //alert("取消");
        return false;
    }
});