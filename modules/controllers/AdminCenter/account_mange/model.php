<?php
namespace AdminCenter\Model;

use cc\Db;
use cc\Msg;
use ccCrypt\ccCrypt;


class account_mange_model
{
    /**
     * get_all_groups 获取所有的分组
     * @return array
     */
    public function get_all_groups()
    {

        $where = ['status = 1'];
        $arr = Db::table('admin_groups')->where($where)->select();
        $result = array_column($arr, 'group_name', 'group_id');
        return $result;
    }

    /**
     * check_account_exist 检查操作员帐号是否存在
     * @param $id   [description]   操作员ID
     */
    public function check_account_exist($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $msg = '非法访问!';
        }

        if (empty($msg)) {
            Db::table('admin')->where('admin_id = ?')->bind(0, $id)->select('admin_id');
            if (Db::rowCount() == 0) {
                $msg = '该操作员帐号不存在';
            }
        }

        if (!empty($msg)) {
            $jump = [URL_MODULES, URL_MODEL, 'list_show'];
            Msg::add_session($msg, 'warning', $jump);
            jumpUrl(createUrl($jump));
        }
    }


    /**
     * edit_account_post 修改用户
     */
    public function edit_account_post()
    {
        //获取旧的数据
        $oldData = Db::table('admin')->where('admin_id =' . URL_PARAMS)->find();
        $msg_status = 'error';
        $msg = '';

        //检查POST参数
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $msg .= '请设置操作员昵称 ' . PHP_EOL;
        }
        if (!isset($_POST['pwd']) || empty($_POST['pwd'])) {
            $msg .= '请设置操作员的登陆密码 ' . PHP_EOL;
        }
        if (!isset($_POST['email']) || empty($_POST['email'])) {
            $msg .= '请设置操作员的登陆邮箱 ' . PHP_EOL;
        }
        if (!isset($_POST['status']) || !in_array($_POST['status'], [0, 1])) {
            $msg .= '请设置操作员的使用状态 ' . PHP_EOL;
        }

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pwd = trim($_POST['pwd']);
        $status = $_POST['status'];

        //检查新密码
        $jm_pwd = '';
        if ($pwd != $oldData['admin_password']) {
            if (!preg_match('/[\d\S]{6,15}$/i', $pwd) ||
                preg_match('/[\x{4e00}-\x{9fa5}]/u', $pwd) > 0
            ) {
                $msg .= '密码格式必须为中文除外的6-15位任意字符' . PHP_EOL;
            }
            //加密密码
            $cryptObj = new ccCrypt($pwd);
            $jm_pwd = $cryptObj->encrypt($pwd);
            if ($jm_pwd[0] != 'success') {
                $msg .= '密码加密失败， 请联系管理员 ' . PHP_EOL;
            }
            $jm_pwd = $jm_pwd[1];
        }

        //检测昵称是否存在
        if ($name != $oldData['admin_name']) {
            Db::table('admin')->where('admin_name = ?')->bind(0, $name)->select('admin_id');
            if (Db::rowCount() > 0) {
                $msg .= '[' . $name . '] 该操作员昵称已经存在 ' . PHP_EOL;
            }
        } else {
            $name = false;
        }

        //检测登录邮箱是否存在
        if ($email != $oldData['admin_email']) {
            Db::table('admin')->where('admin_email = ?')->bind(0, $email)->select('admin_id');
            if (Db::rowCount() > 0) {
                $msg .= '[' . $email . '] 该登录邮箱已经存在 ' . PHP_EOL;
            }
        } else {
            $email = false;
        }

        //检测新分组
        $group = false;
        if (isset($_POST['group']) && !empty($_POST['group']) && $_POST['group'] != $oldData['group_id']) {
            //如果是组长
            if ($oldData['admin_level_id'] == '2') {
                $msg .= '该操作员身份为组长，请先对该操作员进行降级（管理操作组中进行） ' . PHP_EOL;
            } else {
                $where = ['group_id = ' . $_POST['group'], 'status = 1'];
                Db::table('admin_groups')->where($where)->select('group_id');
                if (Db::rowCount() > 0) {
                    $group = $_POST['group'];
                } else {
                    $msg .= '操作组不存在，请选择正常使用的操作组 ' . PHP_EOL;
                }
            }
        }


        //检测使用状态
        if ($status == $oldData['status']) {
            $status = false;
        }


        $data = [];
        if (empty($msg)) {
            if (false !== $group || false !== $jm_pwd || false !== $name || false !== $email || false !== $status) {
                $bind = [];
                if (false !== $group) {
                    $data['group_id'] = $group;
                }
                if (false !== $jm_pwd) {
                    $bind[] = $jm_pwd;
                    $bind[] = $pwd;
                    $data['admin_password'] = '?';
                    $data['admin_password_true'] = '?';
                }
                if (false !== $name) {
                    $data['admin_name'] = '?';
                    $bind[] = $name;
                }
                if (false !== $email) {
                    $data['admin_email'] = '?';
                    $bind[] = $email;
                }
                if (false !== $status) {
                    $data['status'] = $status;
                }
                Db::table('admin')->where('admin_id = ' . URL_PARAMS)->bind($bind)->update($data);
                if (Db::rowCount() > 0) {
                    //如果操作组转移成功，则对该操作员名下的所有客户数据回归到操作组手上
                    if (false !== $group || false !== $status) {
                        $this->reset_customer_to_group(URL_PARAMS, $oldData['group_id']);
                    }

                    $msg_status = 'success';
                    $msg = '操作员资料修改成功';
                } else {
                    $msg = '操作员资料修改失败， 请联系管理';
                }
            } else {
                $msg = '提交的操作员资料没有变化';
            }
        }

        Msg::add_session($msg, $msg_status);
        jumpUrl();
    }

    /**
     * 如果转移成功，则对该操作员名下的所有客户数据回归到操作组手上
     * reset_customer_to_group
     * @param $aID  [description]   操作员ID
     * @param $gID  [description]   操作组ID
     */
    public function reset_customer_to_group($aID, $gID)
    {
        $where = [
            'admin_id =' . $aID,
            'group_id=' . $gID,
        ];
        $update = [
            'admin_id' => 0,
            'update_time' => time()
        ];
        Db::table('customer_to_admin')->where($where)->update($update);
    }

    /**
     * add_new_post 添加新的操作员 post
     */
    public function add_new_post()
    {
        $status = 'warning';
        $msg = '';

        $name = false;
        $email = false;
        $pwd = false;
        $jm_pwd = '';

        //检查POST参数
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $msg .= '请设置操作员昵称 ' . PHP_EOL;
        }
        if (!isset($_POST['pwd']) || empty($_POST['pwd'])) {
            $msg .= '请设置操作员的登陆密码 ' . PHP_EOL;
        }
        if (!isset($_POST['email']) || empty($_POST['email'])) {
            $msg .= '请设置操作员的登陆邮箱 ' . PHP_EOL;
        }

        if (!isset($_POST['rank']) || !in_array($_POST['rank'], [3, 7])) {
            $msg .= '请设置正确的基本身份 ' . PHP_EOL;
        }


        if (empty($msg)) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $pwd = trim($_POST['pwd']);

            if (!preg_match('/[\d\S]{6,15}$/i', $pwd) ||
                preg_match('/[\x{4e00}-\x{9fa5}]/u', $pwd) > 0
            ) {
                $msg .= '密码格式必须为中文除外的6-15位任意字符' . PHP_EOL;
            }
            /*
            if (!preg_match('/[a-zA-Z]{1}[0-9]{5,8}$/i', $pwd)) {
                $msg .= '密码格式必须为首字符英文字母加5-8个数字' . PHP_EOL;
            }
            */
            //加密密码
            $cryptObj = new ccCrypt($pwd);
            $jm_pwd = $cryptObj->encrypt($pwd);
            if ($jm_pwd[0] != 'success') {
                $msg .= '密码加密失败， 请联系管理员 ' . PHP_EOL;
            }
            $jm_pwd = $jm_pwd[1];
        }


        if (empty($msg)) {

            //检查是否已经存在
            $whereOr = ['admin_name = ?', 'admin_email = ?'];
            Db::table('admin')->whereOr($whereOr)->bind([$name, $email])->select('admin_id');
            if (Db::rowCount() == 0) {
                $data = [
                    'group_id' => 0,
                    'admin_level_id' => '?',
                    'create_time' => cc__getDateStr(),
                    'status' => 1, //1 true 0 false
                    'admin_name' => '?',
                    'admin_email' => '?',
                    'admin_password' => '?',
                    'admin_password_true' => '?',
                ];
                $bind = [$_POST['rank'],$name, $email, $jm_pwd, $pwd];
                Db::table('admin')->bind($bind)->insert($data);
                if (Db::getLastInsId() > 0) {
                    $status = 'success';
                    $msg = '添加新的操作员 [' . $name . '|' . $email . '] 成功, 请及时为该操作员分配操作组';
                } else {
                    $msg = '添加新的操作员失败， 请联系管理员';
                }


            } else {
                $msg = '[' . $name . '|' . $email . '] 操作员已经存在';
            }
        }

        Msg::add_session($msg, $status);
    }

    /**
     * 获取操作员登录日志
     * get_admin_sign_log
     * @param $aID  [description]   操作员ID
     * @return string
     */
    public function get_admin_sign_log($aID)
    {
        $where = [
            'admin_id = ' . $aID,
            'type = 1'
        ];
        $query = Db::table('admin_sign_log')->where($where)->order('id desc')->select('sign_time');

        $html = '';
        foreach ($query as $v) {
            $html .= '<label class="col-lg-12">' . cc__getDate('TIME', $v['sign_time']) . '</label>';
        }
        return $html;
    }

}