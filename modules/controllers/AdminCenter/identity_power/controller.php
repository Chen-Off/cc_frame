<?php
namespace AdminCenter\Controller;

use AdminCenter\Model\identity_power_model;
use cc\Db;
use cc\View;


class identity_power
{
    public $accountStatus = [0 => '停用', 1 => '使用'];

    public function identity()
    {

    }

    //基本权限设置
    public function base_power()
    {
        $MODEL = new identity_power_model();
        //获取除管理员外的基本帐号权限名称
        $AL = $MODEL->get_admin_level();
        if (!empty(URL_PARAMS) && !in_array(URL_PARAMS, array_column($AL, 'admin_level_id'))) {
            die('请不要进行非法操作');
        }

        //提交

        if($_POST && !empty(URL_PARAMS)) {
            $MODEL->base_power_post($_POST, URL_PARAMS);
        }

        $AL_list = '';
        foreach ($AL as $v) {
            $al_id = $v['admin_level_id'];
            if ($al_id == URL_PARAMS) {
                $active = ' btn-danger';
            } else {
                $active = ' btn-info';
            }
            $url = createUrl(URL_MODULES, URL_MODEL, URL_ACTION, $al_id);
            $AL_list .= '<a class="btn btn-info btn-sm m-b-xs col-sm-12' . $active . '" href="' . $url . '">' . $v['admin_level_name'] . '</a>';
        }

        View::push('al_list', $AL_list);

        //如果选中了身份
        if (!empty(URL_PARAMS)) {
            //获取指定身份功能数据并转化成特殊数组
            $f_json = $MODEL->get_function_array('level', URL_PARAMS);
            View::push('f_json', $f_json);
        }

    }

    //添加新的需要独立授权功能
    public function auth_content()
    {
        $MODEL = new identity_power_model();


        if ($_POST) {
            $MODEL->auth_content_post($_POST);
        }
        //获取所有功能数据并转化成特殊数组
        $f_json = $MODEL->get_function_array();
        View::push('f_json',$f_json);


    }

    //独立授权功能
    public function auth_power()
    {
        $MODEL = new identity_power_model();

        if ($_POST) {
            $MODEL->auth_power_post($_POST);
        }

        //获取所有可授权的操作员
        $adminArr = $MODEL->get_auth_admin();
        $admin_list = '';
        foreach ($adminArr as $v) {
            $admin_list .= '<option value="' . $v['admin_id'] . '">' . $v['admin_name'] . '【' . $v['group_name'] . '】 - ' . $v['admin_level_name'] . '</option>';
        }
        View::push('admin_list',$admin_list);

        //获取所有的可用的特别授权
        $authArr = $MODEL->get_auth_content_list();
        $auth_list = '';
        foreach ($authArr as $v) {
            $auth_list .= '<option value="' . $v['auth_c_id'] . '">' . $v['auth_c_name'] . '</option>';
        }
        View::push('auth_list',$auth_list);


        //获取已经授权的
        $AIA_arr = $MODEL->get_auth_in_admin();
        $AIA_list = '';
        foreach ($AIA_arr as $v) {
            $AIA_list .= '<label>' . $v['admin_name'] . ' - ' . $v['auth_c_name'] . '/' . cc__getDate('TIME', $v['auth_time']) . '</label>';
        }
        View::push('auth_in_admin_list',$AIA_list);



    }

    //功能模块设置
    public function action_set()
    {
        $post = file_get_contents("php://input");

        if (!empty($post)) {
            $MODEL = new identity_power_model();
            $MODEL->action_set_post($post);
        }

        //获取所有的功能列表，排除管理员操作项
        $MODEL = new identity_power_model();
        $MODEL->get_function_json();
    }


}