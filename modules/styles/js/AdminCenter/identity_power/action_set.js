'use strict';

ngApp.controller('ActionSet', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {

    $scope.AuthError = null;
    $scope.ajaxResult = '';
    $scope.f1_json = angular.fromJson(f1_json);
    $scope.f2_json = angular.fromJson(f2_json);
    $scope.f_level = 0; //模块等级
    $scope.f_title = ''; //模块的中文描述
    $scope.f_name = ''; //模块名称 index
    $scope.f_parent_2_ico = '';//子模块小图标
    $scope.f_sort = 0;


    //BOF 地址获取
    $scope.f_parent_list = [];
    $scope.h_province = 0;
    $scope.h_city = 0;
    $scope.h_area = 0;
    $scope.h_detail = '';
    var defaultSelect = '<option value="">请选择</option>';

    var listHtml = defaultSelect;
    angular.forEach($scope.f1_json, function (data) {
        listHtml += optionHtml(data.id, data.title + ' - ' + data.name + ' [' +  data.sort + ']');
    });
    $scope.f_parent_list.one = $sce.trustAsHtml(listHtml);

    $scope.f_parent_list.two = $sce.trustAsHtml('<option value="">请先选择主模块</option>');


    //选择主模块操作
    $scope.f_parent = [];
    $scope.f_parent.one = 0;
    $scope.f_parent.two = 0;

    $scope.changeParentOne = function () {
        if ($scope.f_parent.one > 0 && $scope.f_level == 3) {
            listHtml = defaultSelect;
            angular.forEach($scope.f2_json, function (data) {
                if ($scope.f_parent.one == data.father) {
                    listHtml += optionHtml(data.id, data.title + ' - ' + data.name +  ' [' +  data.sort + ']');
                }
            });
            $scope.f_parent_list.two = $sce.trustAsHtml(listHtml);
        }
    };

    $('#f_parent_1, #f_parent_2, #f_parent_1_ico').hide();
    $scope.changeFLevel = function (lv) {
        $('#f_parent_1, #f_parent_2, #f_parent_1_ico').hide();
        $scope.f_level = lv;
        switch (lv) {
            case 2:
                $('#f_parent_1, #f_parent_1_ico').show();
                break;
            case 3:
                $('#f_parent_1, #f_parent_2').show();
                break;
        }
    };


    //提交新的功能模块定义
    $scope.postNewAction = function () {
        console.log($scope);

        //检测参数是否健全
        if ($scope.f_title == '' || $scope.f_name == '' || $scope.f_level == 0) {
            $scope.AuthError = '错误的提交参数，请检查';
            return false;
        } else if ($scope.f_level == 2 && ($scope.f_parent.one == 0 || $scope.f_parent_2_ico == '')) {
            $scope.AuthError = '创建子模块，请先选择主模块和添加子模块的ICON图标';
            return false;
        } else if ($scope.f_level == 3 && ($scope.f_parent.one == 0 || $scope.f_parent.two == 0)) {
            $scope.AuthError = '创建功能模块，请先选择主模块和子模块';
            return false;
        }


        $http.post('app/AdminCenter/identity_power/action_set', {
            f_title: $scope.f_title,
            f_name: $scope.f_name,
            f_level: $scope.f_level,
            f_parent_1: $scope.f_parent.one,
            f_parent_2_ico: $scope.f_parent_2_ico,
            f_parent_2: $scope.f_parent.two,
            f_sort:$scope.f_sort,
            f_show:$('#f_show').val()
        }).then(function (response) {
            $scope.AuthError = response.data.msg;
        }, function (x) {
            $scope.AuthError = '服务器错误';
        });
    };

    //EOF 地址获取


}]);

function optionHtml(val, title) {
    return '<option value="' + val + '">' + title + '</option>';
}

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