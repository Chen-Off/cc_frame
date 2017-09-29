'use strict';


ngApp.controller('PlanResultAddress', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.addressAuthError = null;
    $scope.ajaxResult = '';
    $scope.vp_id = $('#vp_id').val();
    $scope.vp_status = '';
    $scope.vp_result = '';
    $scope.a_id = $('#a_id').val();
    $scope.g_id = $('#g_id').val();
    $scope.c_id = $('#c_id').val();
    $scope.cta_id = $('#cta_id').val();
    $scope.vp_n_text = '';


    $scope.editBaseAjax = '';


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

        $http.post('app/CustomerMange/visit_plan/vp_address_edit', {
            vp_id: $scope.vp_id,
            a_id: $id,
            type: 'del'
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

            $http.post('app/CustomerMange/visit_plan/vp_address_new', {
                vp_id: $scope.vp_id,
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

//回复结果
var vp_status_default = $('#vp_status').val();
ngApp.controller('PlanResult', ['$scope', '$http', '$sce', function ($scope, $http, $sce) {
    $scope.addressAuthError = null;
    $scope.ajaxResult = '';
    $scope.vp_id = $('#vp_id').val();
    $scope.vp_status = '';
    $scope.vp_result = '';
    $scope.a_id = $('#a_id').val();
    $scope.g_id = $('#g_id').val();
    $scope.c_id = $('#c_id').val();
    $scope.cta_id = $('#cta_id').val();
    $scope.vp_n_text = '';


    $scope.editBaseAjax = '';


    //修改联系方式
    $scope.contactEditShow = function ($id) {
        $('.contactHide' + $id).removeClass('hidden');
        $('.contactShow' + $id).addClass('hidden');
    };


    //删除联系方式
    $scope.contactDel = function ($id) {
        /*
         if (!window.confirm('你确定要删除该联系方式吗？')) {
         return false;
         }
         */

        $scope.AR__contact = '正在删除，请稍等。。。';
        ajax_loading('show', 'ContactAjax');
        $http.post('app/CustomerMange/visit_plan/vp_contact_edit', {
            vp_id: $scope.vp_id,
            c_id: $id,
            type: '3'
        })
            .then(function (response) {
                $scope.AR__contact = response.data.msg;


            }, function (x) {
                $scope.AR__contact = '服务器错误';
            });
        ajax_loading('hide', 'ContactAjax');


    };

    $scope.contactEdit = function ($id) {
        $scope.AR__contact = '正在修改，请稍等。。。';
        ajax_loading('show', 'ContactAjax');
        var $new_c = $('#new_c_' + $id).val();
        if ($new_c != '') {
            $http.post('app/CustomerMange/visit_plan/vp_contact_edit', {
                vp_id: $scope.vp_id,
                c_id: $id,
                new_c: $new_c,
                type: '2'
            })
                .then(function (response) {
                    $scope.AR__contact = response.data.msg;

                }, function (x) {
                    $scope.AR__contact = '服务器错误';
                });
            ajax_loading('hide', 'ContactAjax');

        } else {
            $scope.AR__contact = '请先填写联系方式新的内容';
        }

    };


    $scope.contact_type = '';
    $scope.contact_c = '';

    //新增联系方式
    $scope.contactAdd = function () {

        $scope.AR__contact = '正在新增，请稍等。。。';
        ajax_loading('show', 'ContactAjax');
        if ($scope.contact_type == '') {
            $scope.AR__contact = '请先选择联系方式类型';
        } else if ($scope.contact_c == '') {
            $scope.AR__contact = '请先填写联系方式内容';
        } else {
            $http.post('app/CustomerMange/visit_plan/vp_contact_new', {
                vp_id: $scope.vp_id,
                contact_type: $scope.contact_type,
                contact_c: $scope.contact_c
            })
                .then(function (response) {
                    $scope.AR__contact = response.data.msg;
                }, function (x) {
                    $scope.AR__contact = '服务器错误';
                });
            ajax_loading('hide', 'ContactAjax');


        }


    };

    //修改客户基本信息
    $scope.editBase = function () {

        $scope.editBaseAjax = '正在修改，请稍等。。。';
        ajax_loading('show', 'editBaseAjax');
        $http.post('app/CustomerMange/visit_plan/vp_base_edit', {
            vp_id: $scope.vp_id,
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
            money: $('#c_money').val()
        })
            .then(function (response) {
                $scope.editBaseAjax = response.data.msg;
            }, function (x) {
                $scope.editBaseAjax = '服务器错误';
            });

        ajax_loading('hide', 'editBaseAjax');

    };


    $scope.vp_status = vp_status_default;
    //设置结束时间
    $scope.setVP = function () {

        $scope.setVPResult = '正在修改，请稍等。。。';
        ajax_loading('show', 'setVPAjax');
        if ($scope.vp_status != '') {
            $http.post('app/CustomerMange/visit_plan/vp_result',
                {
                    vp_id: $scope.vp_id,
                    vp_status: $scope.vp_status,
                }
            )
                .then(function (response) {
                    if (response.data.status == 'success') {
                        if (response.data.time != '') {
                            $('#o_vp_d').html(response.data.time);
                        }
                        $('#o_vp_t').html(response.data.text);
                        $scope.setVPResult = '修改成功';
                        $('#setVP').addClass('hidden');
                    } else {
                        $scope.setVPResult = response.data.msg;
                    }
                }, function (x) {
                    $scope.setVPResult = '服务器错误';
                });

            ajax_loading('hide', 'setVPAjax');
        } else {
            $scope.setVPResult = '请先选择回访结果，再进行设置';
        }

    };

    //设置回访内容
    $scope.addVP_Info = function () {
        var newList;
        ajax_loading('show', 'vpRAjax');
        $scope.AR__vp_r = '正在设置，请稍等。。。';

        if ($scope.vp_result != '') {
            $http.post('app/CustomerMange/visit_plan/vp_result_info', {
                vp_id: $scope.vp_id,
                vp_result: $scope.vp_result,
                type: '1'
            })
                .then(function (response) {
                    if (response.data.status == 'success') {
                        newList = '<p class="b-b"><span>' + response.data.time + '</span><br>' + $scope.vp_result + '</p>';
                        document.getElementById('vp_result_list').innerHTML += newList;
                        $scope.AR__vp_r = response.data.msg;
                        if (response.data.first !== undefined) {
                            $('#o_vp_d').html(response.data.time);
                            $('#o_vp_t').html('默认完结');
                        }
                    } else {
                        $scope.AR__vp_r = response.data.msg;
                    }
                }, function (x) {
                    $scope.AR__vp_r = '服务器错误';
                });
            ajax_loading('hide', 'vpRAjax');

        } else {
            $scope.AR__vp_r = '请先填写回馈内容';
        }
    };

    //设置回访内容
    $scope.verify_power = function (x) {
        ajax_loading('show', 'verify_powerAjax');
        $scope.verify_powerAjax = '正在设置回访内容，请稍等。。。';
        $http.post('app/CustomerMange/visit_plan/vp_result/verify', {
            c_id: $scope.c_id,
            a_id: $scope.a_id,
            g_id: $scope.g_id,
            type: x
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.verify_powerAjax = '修改成功';
                } else {
                    $scope.verify_powerAjax = response.data.msg;
                }
            }, function (x) {
                $scope.verify_powerAjax = '服务器错误';
            });
        ajax_loading('hide', 'verify_powerAjax');
    };


    $scope.ip_n_text = '';
    //设置新的邀约计划
    $scope.newIP = function () {
        ajax_loading('show', 'newIPAjax');
        ajax_mask('show');
        var $date = $('#new_ip_date').val();
        $http.post('app/CustomerMange/visit_plan/ip_new_plan', {
            date: $date, vp_id: $scope.vp_id, ip_n_text: $scope.ip_n_text
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    $('#newIP').addClass('hidden');
                    $('#ipShow').removeClass('hidden');
                    $('#ip_s_time').html(response.data.date);
                    $('#ip_s_content').html(response.data.text);
                    $('#ip_s_name').html(response.data.name);
                    alert('设置邀约计划成功');
                } else {
                    $scope.newIPMsg = response.data.msg;
                }
            }, function (x) {
                $scope.newIPMsg = '服务器错误';
            });
        ajax_loading('hide', 'newIPAjax');
        ajax_mask('hide');
    };

    //设置新的回访计划
    $scope.newVP = function () {
        ajax_loading('show', 'newVPAjax');
        ajax_mask('show');
        var $date = $('#new_vp_date').val();
        $http.post('app/CustomerMange/visit_plan/vp_new_plan', {
            date: $date, vp_id: $scope.vp_id, vp_n_text: $scope.vp_n_text
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    $('#newVP').addClass('hidden');
                    alert('设置新的回访计划成功，请刷新查看');
                } else {
                    $scope.newVPPResult = response.data.msg;
                }
            }, function (x) {
                $scope.newVPPResult = '服务器错误';
            });
        ajax_loading('hide', 'newVPAjax');
        ajax_mask('hide');
    };

    //日期回访计划数量
    $scope.getPlanNum = function () {
        $scope.dayPlanCount = '正在获取....';
        $http.post('app/CustomerMange/customer_info/get_plan_count', {
            date: $('#new_vp_date').val(),
            group: $scope.g_id,
            admin: $scope.a_id
        })
            .then(function (response) {
                $scope.dayPlanCount = response.data.msg;
            }, function (x) {
                $scope.dayPlanCount = '服务器错误';
            });

    };


    //铺垫项目追加
    $scope.arrayObj = [];
    $scope.pd_project = '';
    $scope.pd_price = 0;
    $scope.pd_cause = '';

    $scope.pdProject = function () {
        //var oSelect = document.getElementById('pd_project');
        //console.log(oSelect);

        if ($scope.pd_project != '') {
            var new_data = [$scope.pd_project, $scope.pd_price, $scope.pd_cause];
            $scope.arrayObj.push(new_data);

            var listHtml = '';
            angular.forEach($scope.arrayObj, function (data) {
                //var oText = oSelect.option[data[0]].text;
                listHtml += '<label class="col-lg-12">铺垫项目：' + data[0] + ' / 价格：' + data[1] + ' / 原因：' + data[2] + '</label>';
            });
            $scope.visitList = $sce.trustAsHtml(listHtml);
        }
    }

    //成交项目提交

    $scope.ask_project = '';
    $scope.deal_project = '';
    $scope.deal_price = 0;
    $scope.deal_assess = '';

    $scope.dealProject = function () {

        if ($scope.ask_project == '' || $scope.deal_project == '') {
            $scope.dealProjectAjax = '请先选择【咨询项目】和【成交项目】';
            return false;
        }
        ajax_loading('show', 'dealProjectAjax');
        $http.post('app/CustomerMange/visit_plan/vp_deal_p', {
            vp_id: $scope.vp_id,
            ask_project: $scope.ask_project,
            deal_project: $scope.deal_project,
            deal_price: $scope.deal_price,
            deal_assess: $scope.deal_assess,
            pd_data: $scope.arrayObj
        })
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.dealProjectAjax = '成交项目提交成功';
                } else {
                    $scope.dealProjectAjax = response.data.msg;
                }
            }, function (x) {
                $scope.verify_powerAjax = '服务器错误';
            });
        ajax_loading('hide', 'dealProjectAjax');
    }


}]);

//ajax_mask('show');
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
        }, 600);//延时半秒消除
    }

}