'use strict';
var postUrl = 'app/CustomerMange/customer_info/edit_info';

ngApp.controller('CI_admin', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.a_id = $('#a_id').val();
    $scope.c_id = $('#c_id').val();
    $scope.g_id = $('#g_id').val();

    var postUrl = 'app/CustomerMange/customer_info/edit_info';


    //选择客服分组
    $scope.AR__edit_kf = '';
    $scope.a_group = $('#a_group').val();
    //$scope.teamsList = $sce.trustAsHtml('<option value="">请选择操作组</option>');
    $scope.selectTeams = function() {
        $scope.AR__edit_kf = '';
        if($scope.a_group == 0) {
            $('#a_kf').html('<option value="0">回收到中心</option>');
        } else {

            //$scope.teamsList = '';
            $http.post('app/CustomerMange/customer_assign/get_teams', {
                group: $scope.a_group
            })
                .then(function (response) {
                    var teams = '<option value="0">不分配</option>';
                    if (response.data.status == 'error') {
                        $scope.AR__edit_kf = response.data.msg;
                    } else {
                        angular.forEach(response.data.json, function(data){
                            teams += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
                        });
                    }
                    $('#a_kf').html(teams);
                }, function (x) {
                    $scope.AR__edit_kf = '服务器错误';
                });
        }
    };


    
    //分配客服
    $scope.edit_group_kf = function() {
        ajax_loading('show', 'AR__edit_kf');
        //$scope.teamsList = '';
        $http.post('app/CustomerMange/customer_info/roll_assign/kf', {
            type:'kf',
            c_id:$scope.c_id,
            group: $scope.a_group,
            kf: $('#a_kf').val()
        })
            .then(function (response) {
                $scope.AR__edit_kf = response.data.msg;
                ajax_loading('hide', 'AR__edit_kf');
            }, function (x) {
                $scope.AR__edit_kf = '服务器错误';
            });
    };

    //转移审核
    $scope.move_verify_group = function() {
        ajax_loading('show', 'AR__move_verify');
        //$scope.teamsList = '';
        $http.post('app/CustomerMange/customer_info/roll_assign/verify', {
            type:'verify',
            c_id:$scope.c_id,
            group: $('#m_group').val()
        })
            .then(function (response) {
                $scope.AR__move_verify = response.data.msg;
                ajax_loading('hide', 'AR__move_verify');
            }, function (x) {
                $scope.AR__edit_kf = '服务器错误';
            });
    };

    //废弃客户资源
    $scope.discard = function() {
        if (!window.confirm('你确定要废弃该客户资源吗？\n请慎重选择')) {
            return false;
        } else {
            window.open('app/CustomerMange/customer_info/discard_customer/'+ $scope.c_id);
        }
    };

    //审核组审核
    $scope.verify_powerAjax = '';
    $scope.verify_power = function (x) {
        ajax_loading('show', 'verify_powerAjax');
        $http.post('app/CustomerMange/visit_plan/vp_result/verify', {
            c_id: $scope.c_id,
            a_id: $scope.a_id,
            g_id: $scope.g_id,
            type: x
        })
            .then(function (response) {
                if(x == '2') {
                    alert(response.data.msg);
                } else {
                    if (response.data.status == 'success') {
                        $scope.verify_powerAjax = '修改成功';
                    } else {
                        $scope.verify_powerAjax = response.data.msg;
                    }
                }
            }, function (x) {
                $scope.verify_powerAjax = '服务器错误';
            });
        ajax_loading('hide', 'verify_powerAjax');
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