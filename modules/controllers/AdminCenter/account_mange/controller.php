<?php
/**
 * 2017年3月15日 15:15:20
 */
namespace AdminCenter\Controller;

use AdminCenter\Model\account_mange_model;
use cc\Db;
use cc\View;



class account_mange
{
    public $accountStatus = [0 => '停用', 1 => '使用'];

    public function list_show()
    {
        //如果参数为操作员ID，则跳转到该操作员所拥有的客户列表页
        if(is_numeric(URL_PARAMS) && !empty(URL_PARAMS)) {
            //检测操作员是否存在
            $MODEL = new account_mange_model();
            $MODEL->check_account_exist(URL_PARAMS);

            //追加session
            $_SESSION['info_list_c'] = [];
            $_SESSION['info_list_c']['sx_who'] = URL_PARAMS;
            jumpUrl(createUrl('CustomerMange', 'customer_info', 'info_list',1));
        }
    }

    //添加新的操作员
    public function add_new()
    {
        if ($_POST) {
            $MODEL = new account_mange_model();
            $MODEL->add_new_post();
        }
    }


    //编辑操作员
    public function edit()
    {
        $viewData = [];
        //检测操作员是否存在
        $MODEL = new account_mange_model();
        $MODEL->check_account_exist(URL_PARAMS);

        if ($_POST) {
            $MODEL = new account_mange_model();
            $MODEL->edit_account_post();
        }

        //获取操作员账户参数
        $join = [];
        $join[] = 'admin_groups t2 ON t2.group_id = t1.group_id';
        $join[] = 'admin_level t3 ON t3.admin_level_id = t1.admin_level_id';
        $select = 't1.*, t2.group_name, t3.admin_level_name';
        $data = Db::table('admin t1')->join($join, 'LEFT')->where('t1.admin_id = ' . URL_PARAMS)->find($select);
        $viewData['c_time'] = cc__getDate('time', $data['create_time']);
        $viewData['name'] = $data['admin_name'];
        $viewData['email'] = $data['admin_email'];
        $viewData['pwd'] = $data['admin_password_true'];
        $viewData['rank'] = $data['admin_level_name'];

        $viewData['select_status'] = '';
        foreach ($this->accountStatus as $k => $s) {
            $selected = ($k == $data['status'] ? 'selected="selected"' : '');
            $viewData['select_status'] .= '<option value="' . $k . '" ' . $selected . '>' . $s . '</option>';
        }

        //获取所有分组
        $groups = $MODEL->get_all_groups();
        $viewData['select_group'] = '';
        foreach ($groups as $gid => $g_name) {
            $selected = ($gid == $data['group_id'] ? 'selected="selected"' : '');
            $viewData['select_group'] .= '<option value="' . $gid . '" ' . $selected . '>' . $g_name . '</option>';
        }

        $viewData['edit_post_url'] = createUrl();
        
        
        //获取客户3小时登录记录
        $viewData['sign_log']= $MODEL->get_admin_sign_log(URL_PARAMS);


        View::pushBatch($viewData);
    }

    //获取分组JSON数据
    public function groups_json()
    {
        $statusArr = [0 => '关闭使用', 1 => '正常使用'];

        $json = [];
        $join = [
            'admin_groups t2 ON t2.group_id = t1.group_id',
            'admin_level t3 ON t3.admin_level_id = t1.admin_level_id'
        ];
        
        $where = [];
        $select = 't1.* ,t2.group_name ,t3.admin_level_name';
        $order = 't1.group_id DESC, t1.status ASC, t1.create_time ASC';
        $groupsArr = Db::table('admin t1')->join($join,'left')->where($where)->order($order)->select($select);
        foreach ($groupsArr as $k => $v) {
            $cListUrl = createUrl(URL_MODULES, URL_MODEL, 'list_show', $v['admin_id']);
            $cListLink = '【<a href="'.$cListUrl.'" class="text-info">查看客户</a>】 ';
            $json[$k]['name'] = $cListLink.$v['admin_name'];
            $json[$k]['group'] = empty($v['group_name']) ? '未分配' : $v['group_name'];
            $json[$k]['rank'] = $v['admin_level_name'];
            $json[$k]['plan'] = $this->get_cta_c_count($v['admin_id']);
            $json[$k]['status'] = $statusArr[$v['status']];
            $json[$k]['last_login'] = empty($v['last_time']) ? '未登录' : cc__getDate('time', $v['last_time']);


            $edit_url = createUrl(URL_MODULES, URL_MODEL, 'edit', $v['admin_id']);
            $json[$k]['edit_url'] = '<a target="_blank" class="btn-info btn-sm" href="' . $edit_url . '">编辑</a>';
        }


        $json = ['aaData' => $json];
        cc__outputPage($json);
    }

    //获取操作员的客户
    private function get_cta_c_count($aID) {
        //获取操作员所有的客户
        $where = ['admin_id = ' . $aID, 'status != 0'];
        $all = Db::table('customer_to_admin')->where($where)->find('count(customer_id) as c');
        if(empty($all)) {
            return '0 / 0';
        }

        //获取操作员未设置回访计划的客户
        $join = [
            'visit_plan vp ON vp.customer_id = cta.customer_id'
        ];

        $where = [
            'cta.admin_id = ' . $aID,
            'cta.status != 0',
            'vp.visit_result_status = 0'
        ];

        $find = 'count(DISTINCT cta.customer_id) as c';
        $have = Db::table('customer_to_admin cta')->join($join)->where($where)->find($find);
        if(empty($have)) {
            $no = $all['c'];
        } else {
            $no = $all['c'] - $have['c'];
        }
        return $no. ' / '.$all['c'];
    }

}