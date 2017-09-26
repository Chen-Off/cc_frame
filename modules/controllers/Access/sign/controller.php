<?php
namespace Access\Controller;

use Access\Model\sign_model;
use cc\Db;
use CommonClass\Common_Class;
use vCode\vCode;

class sign extends Common_Class
{
    public function index()
    {

    }

    public function sign_in()
    {
        $post = file_get_contents("php://input");
        
        if (!empty($post)) {
            $MODEL = new sign_model();
            $MODEL->sign_in_post($post);
        }
        //die;
    }


    public function logout() {
        if(isset($_SESSION['u']['id']) && isset($_SESSION['u']['token'])) {
            $where = ['admin_id = ?' , 'session_token = ?'];
            $bind = [$_SESSION['u']['id'], $_SESSION['u']['token']];
            Db::table('admin_session')->where($where)->bind($bind)->delete();


            setcookie('u', '', time() -1, '/');
            unset($_SESSION['u']);
        }

        jumpUrl(createUrl(URL_MODULES, URL_MODEL, 'sign_in'));
    }


    //注册帐号
    public function sign_up()
    {
        die();
        $post = file_get_contents("php://input");
        if (!empty($post)) {
            $MODEL = new sign_model();
            $MODEL->sign_up_post($post);
        }
        die;
    }

    //注册验证码
    public function sign_up_captcha()
    {
        $chinese = URL_PARAMS == 'cn' ? true : false;
        $vCode = new vCode();
        $vCode->showImage($chinese);
        $_SESSION['vCode'] = $vCode->code;
    }

}