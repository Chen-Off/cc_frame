'use strict';

//生成二维码参数
ngApp.controller('CreateQrCodeController', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.cahtMsg = {};
    $scope.authError = null;
    $scope.gzh_id = '';      //公众号
    $scope.qrcode_type = ''; //二维码类型
    $scope.qrcode_action = '';  //二维码用途

    $scope.createQrCodeAjax = '';
    $scope.qrCodeImg = '';
    $scope.qrJSon = '';

    //获取消息
    $scope.createQrCode = function () {

        if ($scope.gzh_id == '' || $scope.qrcode_type == '' || $scope.qrcode_sdk == '') {
            alert('请先选择好参数');
            return false;
        }
        var $html = '';

        ajax_loading('createQrCodeAjax', 'show');

        // 尝试生成二维码
        $http.post('app/gzhManage/gzhList/create_qrCode', {
            gzh_id: $scope.gzh_id,
            qrcode_type: $scope.qrcode_type,
            qrcode_action: $scope.qrcode_action
        })
            .then(function (response) {
                $scope.createQrCodeAjax = response.data.msg;

                if (response.data.status == 'success' || response.data.status == 'warning') {
                    $html = '<img width="80%" src="' + response.data.qr_url + '"/>';
                    $scope.qrCodeImg = $sce.trustAsHtml($html);
                }

                if (response.data.status == 'warning') {
                    $scope.qrJSon = response.data.db_sql;
                }
            }, function (x) {
                alert('发送失败，请重试');
            });

        ajax_loading('createQrCodeAjax', 'hide');
    };

    //console.log($scope);
}]);

function ajax_loading($type, $id) {
    if ($id == '') $id = 'ajaxResult';
    if ($type == 'show') {
        $('#' + $id).addClass('ajax_loading');
    } else {
        setTimeout(function () {
            $('#' + $id).removeClass('ajax_loading');
        }, 600);//延时半秒消除
    }

}