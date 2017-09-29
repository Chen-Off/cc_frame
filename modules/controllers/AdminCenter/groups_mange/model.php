<?php
namespace AdminCenter\Model;

use cc\Db;
use cc\Msg;


class groups_mange_model
{


    /**
     * mange_group_post 管理操作组
     */
    public function mange_group_post()
    {
        //获取旧的数据
        $oldData = Db::table('admin_groups')->where('group_id =' . URL_PARAMS)->find();
        $msg_status = 'error';
        $msg = '';

        $name = false;
        $status = false;
        $leader = false;

        //检查POST参数
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $msg .= '操作组名称不能为空 ' . PHP_EOL;
        }
        if (!isset($_POST['leader'])) {
            $msg .= '请设置组长 ' . PHP_EOL;
        }
        if (!isset($_POST['status']) || !in_array($_POST['status'], [0, 1])) {
            $msg .= '操作组状态为选择 ' . PHP_EOL;
        }
        if (!isset($_POST['verify_power']) || !in_array($_POST['verify_power'], [0, 1, 2])) {
            $msg .= '非法的转移与审核权限 ' . PHP_EOL;
        }

        if (empty($msg)) {
            $name = trim($_POST['name']);
            $status = $_POST['status'];
            $leader = empty($_POST['leader']) ? '0' : trim($_POST['leader']);

            //检测使用状态
            if ($status == $oldData['status']) {
                $status = false;
            }

            //检查新操作员
            if ($leader != $oldData['leader_id']) {
                if (!empty($leader)) {
                    //新组长ID是否存在
                    Db::table('admin')->where('admin_id = ' . $leader)->select('admin_id');
                    if (Db::rowCount() == 0) {
                        $msg .= '新组长不存在， 请误提交错误参数' . PHP_EOL;
                    }
                }
            } else {
                $leader = false;
            }

            //检查组名是否已经被创建
            if ($name != $oldData['group_name']) {
                Db::table('admin_groups')->where('group_name = ' . $name)->select('group_id');
                if (Db::rowCount() > 0) {
                    $msg .= '[' . $name . '] 该组名已经存在' . PHP_EOL;
                }
            } else {
                $name = false;
            }
        }


        if (empty($msg)) {
            $data = [];
            if (false !== $leader) {
                //检测是不是管理员账户
                $l_id = Db::table('admin')->where('admin_id=' . $leader)->find('admin_level_id');
                if ($l_id['admin_level_id'] == 1) {

                    Msg::add_session('请不要设置管理员为组长', $msg_status);
                    jumpUrl();
                } else {
                    //取消旧组长
                    $where = ['group_id = ' . URL_PARAMS, 'admin_level_id != 1'];
                    Db::table('admin')->where($where)->update('admin_level_id = 3');

                    if (!empty($leader)) {
                        //设置新的组长
                        Db::table('admin')->where('admin_id = ' . $leader)->update('admin_level_id = 2');
                    }
                    $data['leader_id'] = $leader;
                }
            }
            if (false !== $status) {
                $data['status'] = $status;
            }
            if (false !== $name) {
                $data['group_name'] = $name;
            }

            $data['verify_power'] = $_POST['verify_power'];

            Db::table('admin_groups')->where('group_id = ' . URL_PARAMS)->update($data);
            if (Db::rowCount() > 0) {
                $msg_status = 'success';
                $msg .= '操作组资料修改成功';
            } else {
                $msg .= '操作组资料修改失败， 请联系管理';
            }

        }


        Msg::add_session($msg, $msg_status);
        jumpUrl();
    }

    /**
     * get_all_team 获取所有的组员
     * @param $gId
     * @return array
     */
    public function get_all_team($gId)
    {
        $arr = Db::table('admin')->where('group_id = ' . $gId)->order('status DESC')->select('admin_id, admin_name, status');
        return $arr;
    }

    //添加新的操作组 post
    public function add_new_post()
    {
        $status = 'error';
        if ($_POST) {
            $status = 'warning';
            if (isset($_POST['new_name']) && !empty($_POST['new_name'])) {
                $name = trim($_POST['new_name']);

                //检查是否已经存在
                Db::table('admin_groups')->where('group_name = ?')->bind(0, $name)->select();

                if (Db::rowCount() == 0) {
                    $data = [
                        'group_name' => '?',
                        'leader_id' => 0,
                        'create_time' => cc__getDateStr(),
                        'status' => 1, //1 true 0 false
                    ];
                    Db::table('admin_groups')->bind(0, $name)->insert($data);
                    if (Db::getLastInsId() > 0) {
                        $status = 'success';
                        $msg = '添加新的操作组 [' . $name . '] 成功, 请及时添加操作员以及设置组长';
                    } else {
                        $msg = '添加新的操作组失败， 请联系管理员';
                    }
                } else {
                    $msg = '[' . $name . '] 已经存在';
                }
            } else {
                $msg = '请提交正确的参数';
            }
        } else {
            $msg = '非法访问';
        }
        Msg::add_session($msg, $status);
    }

    //检测操作组是否存在
    public function check_group_exist($gId)
    {
        if (empty($gId) || !is_numeric($gId)) {
            $msg = '非法访问!';
        }

        if (empty($msg)) {
            Db::table('admin_groups')->where('group_id = ?')->bind(0, $gId)->select('group_id');
            if (Db::rowCount() == 0) {
                $msg = '该操作组不存在';
            }
        }

        if (!empty($msg)) {
            $jump = [URL_MODULES, URL_MODEL, 'list_show'];
            Msg::add_session($msg, 'warning', $jump);
            jumpUrl(createUrl($jump));
        } else {
        }
    }
}