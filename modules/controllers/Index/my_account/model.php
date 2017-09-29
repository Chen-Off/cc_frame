<?php
namespace Index\Model;
use cc\Db;
use cc\Oauth;
use ccCrypt\ccCrypt;

use Index\M_Class\Index_class;

class my_account_model
{

    /**
     * edit_pwd_post 
     */
    public function edit_pwd_post()
    {
        $CLASS = new Index_class();
        //获取旧的数据
        $oldData = Db::table('admin')->where('admin_id =' . URL_PARAMS)->find();
        $msg_status = 'error';
        $msg = '';
        $user = Oauth::$user;

        //检查POST参数
        if (!isset($_POST['o_pwd']) || empty($_POST['o_pwd'])) {
            $CLASS->msgExit('请填写旧密码登陆密码');
        }


        if (!isset($_POST['n_pwd']) || empty($_POST['n_pwd'])) {
            $CLASS->msgExit('请填写新密码登陆密码');
        }

        //检测旧密码是否正确
        $where = [
            'admin_password_true = ?',
            'admin_id = '.$user['u_id']
        ];

        if(empty(Db::table('admin')->where($where)->bind(0,$_POST['o_pwd'])->find('admin_id'))){
            $CLASS->msgExit('旧密码不正确');
        }

        $pwd = $_POST['n_pwd'];
        //检查新密码
        if (!preg_match('/[\d\S]{6,15}$/i', $pwd) ||
            preg_match('/[\x{4e00}-\x{9fa5}]/u', $pwd)>0) {
            $CLASS->msgExit('密码格式必须为中文除外的6-15位任意字符');
        }
        //加密密码
        $cryptObj = new ccCrypt($pwd);
        $jm_pwd = $cryptObj->encrypt($pwd);
        if ($jm_pwd[0] != 'success') {
            $CLASS->msgExit('密码加密失败， 请联系管理员');
        }
        $jm_pwd = $jm_pwd[1];


        $data = [
            'admin_password_true' => '?',
            'admin_password' => '?'
        ];
        Db::table('admin')->where('admin_id = '.$user['u_id'])->bind([$pwd,$jm_pwd])->update($data);
        if (Db::rowCount() > 0) {
            $CLASS->msgExit('新的登录密码修改成功', 'success');
        } else {
            $CLASS->msgExit('操作员资料修改失败， 请联系管理', 'success');
        }

    }
}