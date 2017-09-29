'use strict';

ngApp.controller('BatchAssign', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.authError = null;
    $scope.teamsList = $sce.trustAsHtml('<option value="">请选择操作组</option>');
    $scope.selectTeams = function () {
        $scope.teamsList = '';
        $scope.authError = null;
        if (!isNaN($scope.group)) {
            $http.post('app/CustomerMange/customer_assign/get_teams', {group: $scope.group})
                .then(function (response) {
                    if (response.data.status == 'error') {
                        $scope.authError = response.data.msg;
                    } else {
                        var teams = '<option value="">请选择操作组</option>';
                        angular.forEach(response.data.json, function (data) {
                            teams += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
                        });

                        $scope.teamsList = $sce.trustAsHtml(teams);
                    }
                }, function (x) {
                    $scope.authError = '服务器错误';
                });
        } else {
            $scope.authError = '非法提交';
        }

    };


    $scope.rand_n = 50;
    var genders = new Array();
    genders['m'] = '男';
    genders['f'] = '女';
    genders[''] = '未知';
    //随机抽取一定数量的客户
    $scope.getRandCustomer = function () {
        var $sx_rating = getValue('sx_rating');
        var $sx_gender = getValue('sx_gender');
        var $sx_contact = getValue2('sx_contact');

        $http.post('app/CustomerMange/customer_assign/batch_select_assign', {
            a_id: $('#a_id').val(),
            num: $scope.rand_n,
            sx_rating: $sx_rating,
            sx_gender: $sx_gender,
            sx_contact: $sx_contact
        })
            .then(function (response) {
                if (response.data.status == 'error') {
                    $scope.authError = response.data.msg;
                } else {
                    var listHtml = '<div class="checkbox p_b_8 b-b"><lable class="i-checks">';
                    listHtml += '<i id="checkALL" onclick="checkALL()"></i>';
                    listHtml += '<i id="unALL" onclick="unALL()" class="hidden" >X</i>';
                    listHtml += '姓名 / 年龄 / 性别 职业 / 常驻地区 / 信息来源</label></div>';

                    angular.forEach(response.data.json, function (data) {
                        listHtml += '<div class="checkbox">';
                        listHtml += '<label class="i-checks">';
                        listHtml += '<input name="rand_c[]" value="' + data['id'] + '" type="checkbox">';
                        listHtml += '<i></i>';
                        listHtml += data['n'] + ' / ' + data['a'] + ' / ' + genders[data['g']];

                        listHtml += ' / ' + data['p'] + ' / ' + data['ta'] + ' / ' + data['o'];

                        listHtml += '</label></div>';
                    });
                    $scope.visitList = $sce.trustAsHtml(listHtml);
                }
            }, function (x) {
                $scope.authError = '服务器错误';
            });
    }
}]);

function getValue($name) {
    var i;
    // method 1
    var radio = document.getElementsByName($name);
    for (i = 0; i < radio.length; i++) {
        if (radio[i].checked) {
            return radio[i].value;
        }
    }
}

function getValue2($name) {
    var obj = document.getElementsByName($name);
    var check_val = [];
    var k;
    for (k in obj) {
        if (obj[k].checked)
            check_val.push(obj[k].value);
    }
    return check_val;
}

//选中所有的未分配客户
function checkALL() {
    var i;
    var CheckBox = document.getElementsByName('rand_c[]');
    for (i = 0; i < CheckBox.length; i++) {
        CheckBox[i].checked = true;
    }
    $('#checkALL').hide();
    $('#unALL').removeClass('hidden').show();
}
function unALL() {
    var i;
    var CheckBox = document.getElementsByName('rand_c[]');
    for (i = 0; i < CheckBox.length; i++) {
        CheckBox[i].checked = false;
    }

    $('#checkALL').show();
    $('#unALL').hide();
}


$(document).ready(function () {


    //移动选中内容到修改框中
    $('#resultContent').delegate('li', 'click', function () {
        moveChangeLi(this, '#resultContent', '#changeContent')
    });
    $('#changeContent').delegate('li', 'click', function () {
        moveChangeLi(this, '#changeContent', '#resultContent')
    });

    //关闭打开移动功能
    $('#moveFunctionChange').click(function () {
        var nowS = document.getElementsByName('moveFunction')[0];
        if (nowS.value == '0') {
            $(this).html('打开点击移动功能');
            $(this).addClass('bg_red');
            nowS.value = '1';
        } else if (nowS.value == '1') {
            $(this).html('关闭点击移动功能');
            $(this).removeClass('bg_red');
            nowS.value = '0';
        }
    });

    //选择要检查内容
    $('#changeType').change(function () {
        var changeType = document.getElementById('changeType').value;
        var nounTypeId = $('#nounTypeId');
        var keywordTypeId = $('#keywordTypeId');
        var nounModelStatus = $('#nounModelStatus');
        var keywordModelStatus = $('#keywordModelStatus');
        var all = $('#nounTypeId, #keywordTypeId, #nounModelStatus, #keywordModelStatus');

        all.addClass('box_none');

        switch (changeType) {
            case '1' :
                keywordTypeId.removeClass('box_none');
                break;
            case '2' :
                keywordModelStatus.removeClass('box_none');
                break;
            case '3' :
                nounTypeId.removeClass('box_none');
                break;
            case '4' :
                nounModelStatus.removeClass('box_none');
                break;
        }
    });

});

//移动选中内容到修改框中
function moveChangeLi(nowLI, divFor, divTo) {
    var moveFunction = document.getElementsByName('moveFunction')[0].value;
    if (moveFunction == '0') {
        var li_box = $(divFor + ' li');
        var index = li_box.index(nowLI);
        var li_box_index = $(li_box[index]);
        var val = li_box_index.html();
        var scale = li_box_index.attr('scale');
        li_box_index.remove();

        var li = '<li scale=' + scale + '>' + val + '</li>';
        $(divTo).append(li);
    }
}