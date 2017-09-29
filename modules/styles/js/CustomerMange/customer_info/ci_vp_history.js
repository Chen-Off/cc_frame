'use strict';

ngApp.controller('CI_vp_list', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.authError = null;
    $scope.customerInfo = '';
    $scope.visitList = '';
    $scope.a_id = $('#a_id').val();
    $scope.c_id = $('#c_id').val();
    $scope.cta_id = $('#cta_id').val();

    //载入回访计划历史
    $http.post('app/CustomerMange/customer_info/customer_vp_json', {
        a_id: $scope.a_id,
        c_id: $scope.c_id
    })
        .then(function (response) {
            if (response.data.status == 'success') {
                /*
                 if (response.data.new_vp == '') {
                 $('#newVP').hide();
                 } else {
                 $scope.newVPUrl = response.data.new_vp;
                 }
                 */

                var listHtml = '';
                var listC, listH,listA;

                angular.forEach(response.data.json, function (data) {

                    if(data['status'] == '5') {
                        listC = ' b-danger ';
                        listH = ' style="height:60px; overflow:hidden;"';
                        listA = ' arrow-danger';
                    } else {
                        listC = 'b-a';
                        listH = '';
                        listA = '';
                    }
                    listHtml += '<li class="tl-item">';
                    listHtml += '<div class="tl-wrap b-info m_l_150">';

                    listHtml += '<span class="tl-date ci_vp_history_l"><b>设置【操作员|时间】</b><br/>'  + data['site_user'] + '<br/>'+ data['b_time'] + '</span>';

                    listHtml += '<div class="tl-content panel padder block '+listC+'"><span class="arrow left pull-up '+listA+'"></span>';

                    listHtml += '<div class="b-light" '+listH+'>';
                    listHtml += '<div class="clear "><b>回访结果: </b><span>' + data['status_name'] + '</span></div>';

                    listHtml += '<div class="clear "><b>回访内容: </b><span>' + data['vp_text'] + '</span></div>';

                    listHtml += '<div class="clear "><b>回访时间: </b><span>' + data['e_time'] + '</span></div>';
                    listHtml += '<div class="clear "><b>操作人员: </b><span>' + data['operator'] + '</span></div>';


                    listHtml += '<div class="clear b-b m-t"><b>结果反馈: </b></div>';
                    listHtml += '<div>' + data['result'] + '</div>';

                    if (data['contact'] != '') {
                        listHtml += '<div class="clear b-b m-t"><b>联系方式变更: </b></div>';

                        listHtml += '<div>' + data['contact'] + '</div>';
                    }

                    if (data['address'] != '') {
                        listHtml += '<div class="clear b-b m-t"><b>地址变更: </b></div>';
                        listHtml += '<div>' + data['address'] + '</div>';
                    }

                    if (data['pt_project'] != '') {
                        listHtml += '<div class="clear b-b m-t"><b>成交项目: </b></div>';
                        listHtml += '<div>' + data['pt_project'] + '</div>';
                    }


                    listHtml += '</div>';


                    listHtml += '</div>';
                    listHtml += '</li>';
                });
                $scope.visitList = $sce.trustAsHtml(listHtml);
            }
        }, function (x) {
            $scope.addressAuthError = '省份获取失败,请刷新';
        });



}]);

