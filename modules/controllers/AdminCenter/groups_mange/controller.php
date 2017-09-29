<?php
/**
 * 2017年3月15日 15:15:20
 */
namespace AdminCenter\Controller;

use AdminCenter\Model\groups_mange_model;
use cc\Db;
use cc\View;




class groups_mange
{

    public $groupStatus = [0 => '停用', 1 => '使用'];
    public $verifyPower = [0 => '无权' , 1 => '转移组', 2=> '审核组'];

    //添加新的操作组
    public function add_new()
    {
        if ($_POST) {
            $MODEL = new groups_mange_model();
            $MODEL->add_new_post();
        }
    }

    //管理操作组
    public function mange()
    {
        //检测操作组是否存在
        $MODEL = new groups_mange_model();
        $MODEL->check_group_exist(URL_PARAMS);

        if ($_POST) {
            $MODEL = new groups_mange_model();
            $MODEL->mange_group_post();
        }

        //获取操作组的详细
        $join = [];
        $join[] = 'admin t2 ON t2.admin_id = t1.leader_id';
        $select = 't1.*, t2.admin_name';
        $data = Db::table('admin_groups t1')->join($join,'LEFT')->where('t1.group_id = ' . URL_PARAMS)->find($select);

        $viewData = [];
        $viewData['c_time'] = cc__getDate('day', $data['create_time']);
        $viewData['name'] = $data['group_name'];
        $viewData['leader'] = empty($data['admin_name']) ? '未设置' : $data['admin_name'];


        $viewData['select_leader'] = '';
        $viewData['team'] = '';
        $teams = $MODEL->get_all_team(URL_PARAMS);
        if (!empty($teams)) {
            foreach ($teams as $v) {
                //获取所有的组员
                if ($v['admin_id'] != $data['leader_id']) {
                    $stem_name_class = ($v['status'] == 0) ? 'text-dlt' : '';
                    $viewData['team'] .= '<span class="' . $stem_name_class . '">' . $v['admin_name'] . '</span>
                <span class="p_l_10 p_r_10">/</span>';
                }

                //选取新组长
                if ($v['status'] == 1) {
                    if ($v['admin_id'] == $data['leader_id']) {
                        $selected = 'selected="selected"';
                        $selectClass = 's_active';
                    } else {
                        $selected = '';
                        $selectClass = '';

                    }
                    $viewData['select_leader'] .= '<option class="' . $selectClass . '" value="' . $v['admin_id'] . '" ' . $selected . '>' . $v['admin_name'] . '</option>';
                }

            }
        } else {
            $viewData['team'] = '还未分配组员';
        }


        //转移与审核权限
        $verifyPower = $this->verifyPower;
        $verify_power = '';
        foreach ($verifyPower as $k => $vp) {
            $selected = $k == $data['verify_power'] ? 'selected="selected"' : '';
            $verify_power .= '<option value="'.$k.'" '.$selected.'>'.$vp.'</option>';
        }
        $viewData['verify_power'] = $verify_power;
        

        $viewData['select_status'] = '';
        foreach ($this->groupStatus as $k => $s) {
            $selected = ($k == $data['status'] ? 'selected="selected"' : '');
            $viewData['select_status'] .= '<option value="' . $k . '" ' . $selected . '>' . $s . '</option>';
        }

        //获取所有分组

        $viewData['mange_post_url'] = createUrl();
        View::pushBatch($viewData);
    }


    public function list_show()
    {

    }

    //获取分组JSON数据
    public function groups_json()
    {
        $json = [];
        $join = ['admin a ON a.admin_id = ag.leader_id'];
        $select = 'ag.*, a.admin_name';
        $groupsArr = Db::table('admin_groups ag')->join($join, 'LEFT')->select($select);
        foreach ($groupsArr as $k => $v) {
            if (empty($v['group_id'])) {
                continue;
            }
            $g_id = $v['group_id'];
            $json[$k]['name'] = $v['group_name'];
            $json[$k]['leader'] = empty($v['admin_name']) ? '未设置' : $v['admin_name'];
            $json[$k]['time'] = cc__getDate('day', $v['create_time']);


            //查询正常使用和关闭使用的操作员数量
            $liveCount = Db::table('admin')->where(['status = 1', 'group_id = ' . $g_id])->find('count(admin_id) as c');
            $dieCount = Db::table('admin')->where(['status = 0', 'group_id = ' . $g_id])->find('count(admin_id) as c');


            //获取操作组所有的客户
            $where = ['group_id = '.$v['group_id'], 'status != 0'];
            $c_all_num = Db::table('customer_to_admin')->where($where)->find('count(customer_id) as c');
            //查询分配了操作员的客户资源
            $where[] = 'admin_id > 0';
            $c_have_num = Db::table('customer_to_admin')->where($where)->find('count(customer_id) as c');



            $json[$k]['user_num'] = $liveCount['c'] . ' / ' . $dieCount['c'];
            $json[$k]['customer_num'] = $c_have_num['c'] . ' / ' . $c_all_num['c'];

            $json[$k]['verify_power'] = $this->verifyPower[$v['verify_power']];

            $json[$k]['status'] = $this->groupStatus[$v['status']];

            $mange_url = createUrl(URL_MODULES, URL_MODEL, 'mange', $g_id);
            $json[$k]['mange_url'] = '<a target="_blank" class="btn-info btn-sm" href="' . $mange_url . '">管理</a>';
        }

        $json = ['aaData' => $json];
        cc__outputPage($json);
    }

}