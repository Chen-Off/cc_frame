'use strict';


ngApp.controller('CI_plan_one', ['$scope', '$http', function ($scope, $http) {

    //废弃计划
    $scope.diePlan = function (vpID, holdID) {
        if (!window.confirm('你确定要废弃该回访计划吗？\n请慎重选择')) {
            return false;
        }

        var showBox = 'dieShow_' + vpID;
        var vpBox = '#vp_list_' + vpID;
        ajax_loading('show', showBox);
        $http.post('app/CustomerMange/visit_plan/vp_result/quick', {
            vp_id: vpID,
            hold_id: holdID,
            type: 'die'
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    alert('计划废弃成功');
                    $('#'+showBox).hide();
                } else {
                    alert(response.data.msg);
                }
            }, function (x) {
                alert('服务器错误');
            });
        ajax_loading('hide', showBox);
    };

}]);

function ajax_loading($type, $id) {
    if ($id == '') $id = 'ajaxResult';
    if ($type == 'show') {
        $('#' + $id).addClass('ajax_loading');
    } else {
        setTimeout(function () {
            $('#' + $id).removeClass('ajax_loading');
        }, 1000);//延时半秒消除
    }

}