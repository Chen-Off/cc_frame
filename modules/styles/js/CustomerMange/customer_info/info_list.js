'use strict';

ngApp.controller('InfoList', ['$scope', '$sce', function ($scope, $sce) {

    var listHtml = '';
    var ctaTime = '';
    var viewUrl = 'app/CustomerMange/customer_info/ci_view/';
    $scope.cJson = angular.fromJson(cJson);
    //console.log($scope.cJson);
    if ($scope.cJson != '') {
        angular.forEach($scope.cJson, function (data) {

            ctaTime = false == data.cta_time ? '' : data.cta_time;
            listHtml += '<tr>';
            listHtml += '<td>' + data.c_time + '</td>';
            listHtml += '<td>' + ctaTime + data.cta_status + '</td>';
            listHtml += '<td>' + (data.name == '' ? '未知' : data.name) + '</td>';
            listHtml += '<td>' + data.rating + '</td>';
            listHtml += '<td>' + data.gender + '</td>';
            listHtml += '<td>' + data.age + '</td>';
            listHtml += '<td>' + data.address + '</td>';
            listHtml += '<td>' + data.vp_time + '</td>';
            listHtml += '<td><a class="btn-info btn-sm" href="' + viewUrl + data.c_id + '" class="btn-info btn-sm">查看详细</a></td>';
            listHtml += '</tr>';
        });
    } else {
        listHtml += '<tr><td colspan="9">没有找到客户资料</td></tr>';
    }
    $scope.htmlContent = $sce.trustAsHtml(listHtml);
}]);


function sx_show($type) {
    var tr = $('#sx_condition_body .sx_tr');
    tr.removeClass('hidden');
    $('.sx_on').removeClass('hidden');
    if ($type == 0) {
        tr.addClass('hidden');
    }

    $('#sx_on_' + $type).addClass('hidden');
}


function IF_vp($k) {
    var hc = 'IF_h';
    var kid = $('#IF_vp_' + $k);
    if (false === kid.hasClass(hc)) {
        kid.addClass(hc);
    } else {
        kid.removeClass(hc);
    }
}
function IF_cta($k) {
    var hc = 'IF_h';
    var kid = $('#IF_cta_' + $k);
    if (false === kid.hasClass(hc)) {
        kid.addClass(hc);
    } else {
        kid.removeClass(hc);
    }
}
$('.sx_on').css('cursor', 'pointer');