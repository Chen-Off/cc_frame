<?php
namespace Access\Model;

use cc\Db;
use ccCrypt\ccCrypt;
use CommonClass\Common_Class;

class sign_model extends Common_Class
{

    /**
     * sing_in_post 登录帐号
     * @param $post
     */
    public function sign_in_post($post)
    {
        $result = ['status' => 'error', 'msg' => ''];
        //验证参数
        $post = json_decode($post);

        if (empty($post)) {
            $msg = '非法参数';
        } else {
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
            switch (true) {
                case !isset($post->email) || empty($post->email):
                    $msg = '请填写邮箱';
                    break;
                case !preg_match($pattern, $post->email):
                    $msg = '邮箱不合法';
                    break;

                case !isset($post->password) || empty($post->password):
                    $msg = '请填写密码';
                    break;
                case !preg_match('/[\d\S]{6,15}$/i', $post->password) ||
                    preg_match('/[\x{4e00}-\x{9fa5}]/u', $post->password)>0:
                    $msg = '密码不合法';
                    break;
            }
        }


        //验证帐号
        if (empty($msg)) {
            //链接数据库
            $where = ['admin_email = ?', 'status = 1'];
            //检测用户是否存在
            $find = 'admin_id, admin_password,admin_level_id';
            $uData = Db::table('admin')->where($where)->bind(0,$post->email)->find($find);
            if (!empty($uData)) {
                //检测密码
                $cryptObj = new ccCrypt($post->password);
                $uPwd = $cryptObj->decrypt($uData['admin_password']);

                if ($uPwd[0] == 'success' && $uPwd[1] == $post->password) {
                    //删除旧的session
                    $uId = $uData['admin_id'];
                    $where = 'admin_id = ' . $uId;
                    Db::table('admin_session')->where($where)->delete();


                    //生成session
                    $time = cc__getDateStr();
                    $expiredTime = $time + (3600 * 12);
                    $sessionToken = cc__getRandStr('all', 32);

                    $data = [
                        'admin_id' => $uId,
                        'create_time' => $time,
                        'session_token' => $sessionToken,
                        'expired_time' => $expiredTime,//十二小时过期
                    ];
                    Db::table('admin_session')->where($where)->insert($data);
                    if (Db::getLastInsId('session_id') > 0) {

                        //最后登录时间记录
                        Db::table('admin')->where($where)->update('last_time = "' . $time . '"');

                        //每3小时一次记录登录状态
                        $this->sign_log_write($uId);


                        $session = [
                            'token' => $sessionToken,
                            'id' => $uId
                        ];

                        $_SESSION = [];
                        $_SESSION['u'] = $session;
                        setcookie('u', json_encode($session), $expiredTime, '/');

                        //写入session
                        $msg = '登录成功';

                        $result['status'] = 'success';

                        //跳转页面
                        if($uData['admin_level_id'] == 3) {
                            $result['jump_url'] = 'app/CustomerMange/customer_info/info_list';
                        } else {
                            $result['jump_url'] = 'app/Index/index';
                        }

                    } else {
                        $msg = '登录失败';
                    }
                } else {
                    $msg = '密码错误';
                }
            } else {
                $msg = '该用户不存在';

            }
        }

        $result['msg'] = $msg;
        $this->page_header_code('json');
        echo json_encode($result);
        die;
    }

    /**
     * 每3小时一次记录登录状态
     * sign_log_write
     * @param $uId  [description]   操作员ID
     */
    private function sign_log_write($uId) {
        $interval = 10800;//3 hours
        $time = time() - $interval;

        //检测最大值
        $table = 'admin_sign_log';
        $last = Db::table($table)->where('admin_id = '.$uId)->find('max(sign_time) max');
        if(empty($last) || $last['max'] <= $time) {
            $data = [
                'admin_id' => $uId,
                'sign_time' => time()
            ];
            Db::table('admin_sign_log')->insert($data);
        }
    }

    /**
     * sign_up_post 注册帐号
     * @param $post
     *
     */
    public function sign_up_post($post)
    {
        $msg = '';
        $result = ['status' => 'error', 'msg' => ''];
        //验证参数

        $post = json_decode($post);
        if (empty($post)) {
            $msg = '非法参数';
        } else {
            //var_dump($post);
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
            switch (true) {
                case !isset($post->name) || empty($post->name) || mb_strlen($post->name, 'utf8') > 12:
                    $msg = '请填写正确的用户名称';
                    break;
                case !isset($post->email) || empty($post->email):
                    $msg = '请填写邮箱';
                    break;
                case !preg_match($pattern, $post->email):
                    $msg = '邮箱不合法';
                    break;

                case !isset($post->password) || empty($post->password):
                    $msg = '请填写密码';
                    break;
                case !preg_match('/[\d\S]{6,15}$/i', $post->password) ||
                    preg_match('/[\x{4e00}-\x{9fa5}]/u', $post->password)>0:
                    $msg = '密码不合法';
                    break;

                case !isset($post->confirm_password) || $post->password != $post->confirm_password:
                    $msg = '两次密码不一致';
                    break;
            }
        }
        if (empty($msg)) {

            //链接数据库
            $whereOr = ['admin_name =?', 'admin_email = ?'];
            $bind = [$post->name, $post->email];
            Db::table('admin')->whereOr($whereOr)->bind($bind)->select('admin_id');

            if (Db::rowCount() == 0) {
                $time = time();
                $ip = cc__getClientIp();
                if (empty($ip)) $ip = '127.0.0.1';
                //新增用户准备
                $data = [
                    'admin_name' => '?',
                    'admin_email' => '?',
                    'admin_password' => '?',
                    'admin_password_true' => $post->password,
                    'group_id' => 0,
                    'create_time' => $time,
                    'last_time' => 0,
                    'tel' => '',
                    'admin_level_id' => 0
                ];

                //加密密码
                $ccCrypt = new ccCrypt($post->password);
                $pwd = $ccCrypt->encrypt($post->password);
                if ($pwd[0] == 'error') {
                    $msg = '密码不合法';
                } else {
                    $bind[] = $pwd[1];
                    Db::table('admin')->bind($bind)->insert($data);

                    if (Db::getLastInsId() > 0) {
                        $msg = '注册帐号成功，请联系管理员激活';
                        $result['status'] = 'success';
                    } else {
                        $msg = '注册帐号失败，请联系管理员';
                    }
                }
            } else {
                $msg = '用户名称或邮箱已经存在';
            }
        }

        $result['msg'] = $msg;
        $this->page_header_code('json');
        echo json_encode($result);
        die;
    }
}