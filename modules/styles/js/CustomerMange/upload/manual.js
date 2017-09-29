'use strict';

//var vpList = angular.fromJson(vpList);

/**
 * 聊天对话日志
 */
ngApp.controller('AP_time', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {

    $scope.new_chat_log = '';
    $scope.at_type = 0;


    $scope.at_type_change = function () {
        if ($scope.at_type == 2) {
            $('#ap_2').removeClass('hidden');
        } else {
            $('#ap_2').addClass('hidden');
        }
    };


    $scope.start_add = function () {
        $('#chat_add_btn').addClass('hidden');
        $('.chat_chancel_btn,#new_chat_box').removeClass('hidden');
    };

    $scope.chanel_add = function () {
        $('#chat_add_btn').removeClass('hidden');
        $('.chat_chancel_btn,#new_chat_box').addClass('hidden');
    };

    $scope.query_add = function () {
        if ($scope.new_chat_log == '') {
            alert('请先添加聊天对话内容');
            return false;
        }

        var listHtml = '';

        $scope.chatLogAjax = '正在添加聊天内容，请稍等。。。';
        ajax_loading('show', 'chatLogAjax');

        $http.post(postUrlChat, {
            a_id: $scope.a_id,
            c_id: $scope.c_id,
            chat_content: $scope.new_chat_log
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    listHtml = $scope.new_chat_log.replace(/\r\n/g, "<br />");
                    listHtml = listHtml.replace(/\n/g, "<br />");
                    $scope.new_log = $sce.trustAsHtml(listHtml);
                }
                $scope.chatLogAjax = response.data.msg;
            }, function (x) {
                $scope.chatLogAjax = '服务器错误';
            });

        ajax_loading('hide', 'chatLogAjax');

    };

}]);
