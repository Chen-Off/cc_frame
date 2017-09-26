'use strict';

ngApp.controller('BasePower', ['$scope', '$http', '$sce', '$compile', function ($scope, $http, $sce, $compile) {
    $scope.authError = null;


    $scope.f_json_1 = angular.fromJson(f_json_1);
    $scope.f_json_2 = angular.fromJson(f_json_2);


    var listHtml = '';
    var lv_1_id, lv_2_id, lv_3_active, lv_2_active;
    var radio_name;

    if ($scope.f_json_1 != '') {
        angular.forEach($scope.f_json_1, function (data) {
            lv_1_id = data['id'];
            listHtml += '<div class="checkbox">';
            listHtml += '<h4 class="b-b" style="cursor: pointer;" ng-click="modelHide(' + lv_1_id + ')">' + data['title'] + '【' + data['name'] + '】[点击隐藏/展开]</h4>';
            listHtml += '</div>';


            listHtml += '<div id="model_' + data['id'] + '">';
            //model
            angular.forEach(data['sub'], function (data_2) {
                lv_2_active = data_2['active'] == 1 ? 'checked="checked"' : '';
                lv_2_id = data_2['id'];
                radio_name = 's_f[' + lv_1_id + '][' + lv_2_id + '][]';
                listHtml += '<div class="m-t">';
                listHtml += '<div class="checkbox m-l-md">';
                listHtml += '<label class="i-checks">';
                listHtml += '<input ' + lv_2_active + ' name="' + radio_name + '" value="' + lv_2_id + '" type="checkbox" ng-click="changeALL(' + lv_1_id + ', ' + lv_2_id + ')">';
                listHtml += '<i></i>';

                listHtml += '<b>' + data_2['title'] + '</b>';
                //listHtml += data_2['title'] +'【'+data_2['name']+'】';
                listHtml += '</label></div>';

                //aciton
                angular.forEach($scope.f_json_2[data_2['id']], function (data_3) {
                    lv_3_active = data_3['active'] == 1 ? 'checked="checked"' : '';

                    listHtml += '<div class="checkbox m-l-xl">';
                    listHtml += '<label class="i-checks">';
                    listHtml += '<input ' + lv_3_active + ' name="' + radio_name + '" value="' + data_3['id'] + '" type="checkbox">';
                    listHtml += '<i></i>';
                    listHtml += data_3['title'];
                    //listHtml += data_3['title'] +'【'+data_3['name']+'】';

                    listHtml += '</label></div>';
                });

                listHtml += '</div>';

            });

            listHtml += '</div>';
        });

        //listHtml = $compile(listHtml)($scope);
        var ele = $compile(listHtml)($scope);
        angular.element('#visitList').append(ele);
        //$scope.visitList = $sce.trustAsHtml(listHtml);
    }


    $scope.modelHide = function (id) {
        var model = '#model_' + id;
        if ($(model).is(":hidden")) {
            $('#model_' + id).show();

        } else {

            $('#model_' + id).hide();
        }
    };

    //选中/反选
    $scope.changeALL = function (id_1, id_2) {
        var i, action;
        var CheckBox = document.getElementsByName('s_f[' + id_1 + '][' + id_2 + '][]');

        if (true === CheckBox[0].checked) {
            action = true;
        } else {
            action = false;
        }

        for (i = 0; i < CheckBox.length; i++) {
            CheckBox[i].checked = action;
        }

    };

}]);
