'use strict';
var postUrl = 'app/CustomerMange/customer_info/edit_info';
var postUrlChat = 'app/CustomerMange/customer_info/add_new_chat';

//var vpList = angular.fromJson(vpList);

/**
 * 聊天对话日志
 */
ngApp.controller('CI_chat_log', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {

    $scope.new_chat_log = '';
    $scope.c_id = $('#c_id').val();
    $scope.a_id = $('#a_id').val();


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

ngApp.controller('CI_base', ['$scope', '$http', '$sce', function ($scope, $http) {
    $scope.a_id = $('#a_id').val();
    $scope.c_id = $('#c_id').val();
    $scope.at_type = 0;

    $scope.at_change = false;
    $scope.atChange = function (t) {
        if (t == 'y') {
            $scope.at_change = true;
            $('#at_change,#ap_2').removeClass('hidden');
            $('#at_show').addClass('hidden');
        } else {
            $scope.at_change = false;
            $('#at_change,#ap_2').addClass('hidden');
            $('#at_show').removeClass('hidden');

        }
    };

    //修改客户基本信息
    $scope.editBase = function () {

        var advisory_time = 0;
        if (true == $scope.at_change) {
            advisory_time = $('#advisory_time_ymd').val() + ' ' + $('#advisory_time_h').val() + ':00:00';
        }
        $scope.editBaseAjax = '正在修改客户资料，请稍等。。。';
        ajax_loading('show', 'editBaseAjax');
        $http.post(postUrl, {
            type: 'base',
            a_id: $scope.a_id,
            c_id: $scope.c_id,
            origin: $('#c_origin').val(),
            rating: $('#c_rating').val(),
            ethnic: $('#c_ethnic').val(),
            age: $('#c_age').val(),
            gender: $('#c_gender').val(),
            name: $('#c_name').val(),
            summary: $('#c_summary').val(),
            assess: $('#c_assess').val(),
            short_wish: $('#c_short_wish').val(),
            profession: $('#c_profession').val(),
            resident_address: $('#c_r_address').val(),
            join_brand_id: $('#c_join_brand_id').val(),
            join_shop_area: $('#c_join_shop_area').val(),
            money: $('#c_money').val(),
            seo_engine: $('#c_seo_engine').val(),
            seo_search: $('#c_seo_search').val(),
            advisory_time: advisory_time
        })
            .then(function (response) {
                $scope.editBaseAjax = response.data.msg;
            }, function (x) {
                $scope.editBaseAjax = '服务器错误';
            });

        ajax_loading('hide', 'editBaseAjax');

    };


}]);


ngApp.controller('CI_plan_one', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.vpList = angular.fromJson(vpList);

    //最新计划列表
    var listHtml = '';
    var vpReturnUrl = 'app/CustomerMange/visit_plan/ci_plan_result/';
    $scope.vpList = angular.fromJson(vpList);

    if ($scope.vpList != '') {
        angular.forEach($scope.vpList, function (data) {
            listHtml += '<div class="form-group b-b" id="vp_box_' + data['vp_id'] + '">';

            listHtml += '<label class="col-sm-12 ">操作员：' + data['g_name'] + ' - ' + data['a_name'] + '</label>';
            listHtml += '<label class="col-sm-12 ">计划回访时间：' + data['vp_time'] + '</label>';
            listHtml += '<label class="col-sm-12 ">计划内容：' + data['vp_content'] + '</label>';


            listHtml += '<label class="col-sm-12">回访结果填写：' + '<a href="' + vpReturnUrl + data['vp_id'] + '" target="_blank" class="btn btn-info btn-sm">回访结果填写</a><a class="btn btn-danger btn-sm m-l" ng-click="diePlan(' + data['vp_id'] + ',' + data['hold_id'] + ')">快速废弃该计划</a><a id="dieShow_' + data['vp_id'] + '" class="btn"></a>' + '</label>';
            listHtml += '</div>';
        });

        $scope.htmlContent = $sce.trustAsHtml(listHtml);

    }


    //废弃计划
    $scope.diePlan = function (vpID, holdID) {
        if (!window.confirm('你确定要废弃该回访计划吗？\n请慎重选择')) {
            return false;
        }

        var showBox = 'dieShow_' + vpID;
        var vpBox = '#vp_box_' + vpID;
        ajax_loading('show', showBox);
        $http.post('app/CustomerMange/visit_plan/vp_result/quick', {
            vp_id: vpID,
            hold_id: holdID,
            type: 'die'
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    $(vpBox).hide();
                }
                $('#' + showBox).html(response.data.msg);
            }, function (x) {
                $('#' + showBox).html('服务器错误');
            });
        ajax_loading('hide', showBox);

    };

}]);

ngApp.controller('CI_plan_two', ['$scope', '$http','$sce', function ($scope, $http, $sce) {
    $scope.AR__new_plan = null;
    $scope.dayPlanCount = '';
    $scope.new_vp_cta_id = 0;
    $scope.new_ip_cta_id = 0;
    $scope.c_id = $('#c_id').val();
    $scope.a_id = $('#a_id').val();
    $scope.g_id = $('#g_id').val();
    $scope.invite_plan_json = angular.fromJson(invite_plan_json);

    //最新到店邀约计划列表
    var listHtml = '';
    $scope.vpList = angular.fromJson(invite_plan_json);

    if ($scope.vpList != '') {
        angular.forEach($scope.vpList, function (data) {
            listHtml += '<div class="form-group b-b" id="vp_box_' + data['ip_id'] + '">';
            listHtml += '<label class="col-sm-12 ">邀约操作员：' + data['g_name'] + ' - ' + data['a_name'] + '</label>';
            listHtml += '<label class="col-sm-12 ">到店邀约时间：' + data['ip_time'] + '</label>';
            listHtml += '<label class="col-sm-12 ">邀约计划内容：' + data['ip_content'] + '</label>';

            listHtml += '</div>';
        });

        $scope.invitePlanHtmlContent = $sce.trustAsHtml(listHtml);

    }

    //获取指定日回访计划数量
    $scope.getPlanNum = function () {
        $scope.dayPlanCount = '';
        $scope.authError = null;
        $http.post('app/CustomerMange/customer_info/get_plan_count', {
            date: $('#sp_date').val(),
            cta_id: $scope.new_vp_cta_id,
            group: $scope.g_id,
            admin: $scope.a_id
        })
            .then(function (response) {
                $scope.dayPlanCount = response.data.msg;
            }, function (x) {
                $scope.dayPlanCount = '服务器错误';
            });
        return false;

    };

    $scope.AR__new_invitePlan = null;

    //设置邀约到店计划
    $scope.setInvitePlan = function () {
        $scope.AR__new_plan = '正在设置新的邀约到店计划，请稍等。。。';
        ajax_loading('show', 'AR__new_invitePlan');
        ajax_mask('show');
        $http.post('app/CustomerMange/customer_info/set_plan/invite_plan', {
            date: $('#ip_date').val(),
            cta_id: $scope.new_ip_cta_id,
            group: $scope.g_id,
            admin: $scope.a_id,
            c_id: $scope.c_id,
            content: $scope.ip_content
        })
            .then(function (response) {
                $scope.AR__new_invitePlan = response.data.msg;
            }, function (x) {
                $scope.AR__new_invitePlan = '计划设置失败';
            });

        ajax_loading('hide', 'AR__new_invitePlan');
        ajax_mask('hide');
    };


    //设置回访计划
    $scope.setNewPlan = function () {
        $scope.AR__new_plan = '正在设置新的回访计划，请稍等。。。';
        ajax_loading('show', 'AR__new_plan');
        ajax_mask('show');
        $http.post('app/CustomerMange/customer_info/set_plan/visit_plan', {
            date: $('#sp_date').val(),
            cta_id: $scope.new_vp_cta_id,
            group: $scope.g_id,
            admin: $scope.a_id,
            c_id: $scope.c_id,
            content: $scope.content
        })
            .then(function (response) {
                $scope.AR__new_plan = response.data.msg;
            }, function (x) {
                $scope.AR__new_plan = '计划设置失败';
            });

        ajax_loading('hide', 'AR__new_plan');
        ajax_mask('hide');
    };
}]);


ngApp.controller('CI_plan_three', ['$scope', '$http', function ($scope, $http) {
    $scope.AR__q_plan = null;
    $scope.vp_q_content = '';
    $scope.vp_q_result = '';
    $scope.vp_q_new_content = '';
    $scope.cta_id = 0;

    //快速定制回访内容
    $scope.setQuickVP = function () {
        //数据初步检测
        ajax_loading('show', 'AR__q_plan');
        ajax_mask('show');


        $http.post('app/CustomerMange/customer_info/quick_visit/', {
            vp_q_date: $('#vp_q_date').val(),
            vp_q_content: $scope.vp_q_content,
            vp_q_result: $scope.vp_q_result,
            vp_q_new_date: $('#vp_q_new_date').val(),
            vp_q_new_content: $scope.vp_q_new_content,
            cta_id: $scope.cta_id,
            c_id: $('#c_id').val()
        })
            .then(function (response) {
                $scope.AR__q_plan = response.data.msg;
            }, function (x) {
                $scope.AR__q_plan = '城市获取失败,请重新选择';
            });

        ajax_loading('hide', 'AR__q_plan');
        ajax_mask('hide');
    };
}]);

ngApp.controller('CI_contact', ['$scope', '$http', '$sce', function ($scope, $http) {
    $scope.a_id = $('#a_id').val();
    $scope.c_id = $('#c_id').val();


    //修改联系方式
    $scope.contactEditShow = function ($id) {
        $('.contactHide' + $id).removeClass('hidden');
        $('.contactShow' + $id).addClass('hidden');
    };


    $scope.contactDel = function ($id) {
        if (!window.confirm('你确定要删除该联系方式吗？')) {
            return false;
        }

        $http.post(postUrl, {
            type: 'delContact',
            a_id: $scope.a_id,
            c_id: $scope.c_id,
            contact_id: $id
        })
            .then(function (response) {
                $scope.AR__contact = response.data.msg;
            }, function (x) {
                $scope.AR__contact = '服务器错误';
            });
    };

    $scope.contactEdit = function ($id) {

        $scope.AR__contact = '正在修改，请稍等。。。';
        var $new_c = $('#new_c_' + $id).val();
        if ($new_c != '') {
            $http.post(postUrl, {
                type: 'editContact',
                a_id: $scope.a_id,
                c_id: $scope.c_id,
                contact_id: $id,
                contact_c: $new_c
            })
                .then(function (response) {
                    $scope.AR__contact = response.data.msg;
                }, function (x) {
                    $scope.AR__contact = '服务器错误';
                });
        } else {
            $scope.AR__contact = '请先填写联系方式新的内容';
        }
    };


    //新增联系方式
    $scope.contactAdd = function () {
        if ($scope.contact_type == '') {
            $scope.AR__contact = '请先选择联系方式类型';
        } else if ($scope.contact_c == '') {
            $scope.AR__contact = '请先填写联系方式内容';
        } else {

            $scope.AR__contact = '正在新增，请稍等。。。';
            ajax_loading('show', 'editBaseAjax');
            $http.post(postUrl, {
                type: 'newContact',
                a_id: $scope.a_id,
                c_id: $scope.c_id,
                contact_type: $scope.contact_type,
                contact_c: $scope.contact_c
            })
                .then(function (response) {
                    $scope.AR__contact = response.data.msg;
                }, function (x) {
                    $scope.AR__contact = '服务器错误';
                });

            ajax_loading('hide', 'editBaseAjax');
        }
    };
}]);


ngApp.controller('CI_address', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.a_id = $('#a_id').val();
    $scope.c_id = $('#c_id').val();

    //BOF 地址获取
    $scope.address = [];
    $scope.h_province = 0;
    $scope.h_city = 0;
    $scope.h_area = 0;
    $scope.h_detail = '';
    var defaultSelect = '<option value="">请选择</option>';


    $http.get('app/Access/address/province_json')
        .then(function (response) {
            var listHtml = defaultSelect;
            $scope.address.p_json = response.data;
            angular.forEach(response.data.json, function (data) {
                listHtml += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
            });
            $scope.address.p_list = $sce.trustAsHtml(listHtml);
        }, function (x) {
            $scope.addressAuthError = '省份获取失败,请刷新';
        });
    $scope.address.c_list = $sce.trustAsHtml('<option value="">请先选择省份</option>');
    $scope.address.a_list = $sce.trustAsHtml('<option value="">请先选择城市</option>');


    //选择省份。城市
    $scope.addressChangeP = function () {
        if ($scope.h_province != '') {
            $http.get('app/Access/address/city_json/' + $scope.h_province)
                .then(function (response) {
                    var listHtml = defaultSelect;
                    if (response.data.status == 'success') {
                        angular.forEach(response.data.json, function (data) {
                            listHtml += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
                        });
                        $scope.address.c_list = $sce.trustAsHtml(listHtml);
                    } else {
                        $scope.addressAuthError = '城市获取失败,请重新选择';
                    }
                }, function (x) {
                    $scope.addressAuthError = '城市获取失败,请重新选择';
                });
        }
    };
    //城市
    $scope.addressChangeC = function () {
        if ($scope.h_city != '') {
            $http.get('app/Access/address/area_json/' + $scope.h_city)
                .then(function (response) {
                    var listHtml = defaultSelect;
                    if (response.data.status == 'success') {
                        angular.forEach(response.data.json, function (data) {
                            listHtml += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
                        });
                        $scope.address.a_list = $sce.trustAsHtml(listHtml);
                    } else {
                        $scope.addressAuthError = '县/区获取失败,请重新选择';
                    }
                }, function (x) {
                    $scope.addressAuthError = '县/区获取失败,请重新选择';
                });
        }
    };


    //EOF 地址获取

    //删除地址
    $scope.addressDel = function ($id) {
        /*
         if (!window.confirm('你确定要删除该联系方式吗？')) {
         return false;
         }
         */

        var msg = '';
        $http.post(postUrl, {
            type: 'delAddress',
            cta_id: $scope.cta_id,
            a_id: $scope.a_id,
            c_id: $scope.c_id,
            address_id: $id
        })
            .then(function (response) {
                $scope.AR__address = response.data.msg;
            }, function (x) {
                $scope.AR__address = '服务器错误';
            });

    };

    //添加新的联系地址
    $scope.addressAdd = function () {
        if ($scope.h_province != '' && $scope.h_city != '' && $scope.h_area != '') {

            $http.post(postUrl, {
                type: 'newAddress',
                a_id: $scope.a_id,
                c_id: $scope.c_id,
                h_province: $scope.h_province,
                h_city: $scope.h_city,
                h_area: $scope.h_area,
                h_detail: $scope.h_detail
            }).then(function (response) {
                $scope.AR__address = response.data.msg;
            }, function (x) {
                $scope.AR__address = '服务器错误';
            });
        } else {
            $scope.AR__address = '请先选择省份、城市、县/区';

        }

    };


}]);

function ajax_mask(type) {
    var div_id = $('#ajax_mask');
    var width = document.body.clientWidth;
    var height = window.innerHeight;
    div_id.css('width', width);
    div_id.css('height', height);

    if ((type == 'show')) {
        div_id.css('display', 'block');
    } else {
        setTimeout(function () {
            div_id.css('display', 'none');
        }, 600);//延时半秒消除遮罩
    }
}

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

