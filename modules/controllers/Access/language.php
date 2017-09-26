<?php
namespace Access\Language;
use CommonLanguage\Common_Language;

class Access_language extends Common_Language
{
    public $Module, $Model;
    public function __construct() {
        $this->Module['name'] = '常规访问';
    }

/*============================首页============================================================*/

    public function index() {
        $this->Model['index'] = ['name' => '访问'];
    }
    

    public function page() {
            $this->Model['index'] = [
                'name' => '访问'];


        $this->Model['page_404'] = [
            'name' => '页面未发现'];


        $this->Model['no_power'] = [
            'name' => '无权限访问'];
    }

    public function sign() {
        $this->Model['index'] = [
            'name' => '登录'];


        $this->Model['sign_in'] = [
            'name' => '登录帐号'];
        $this->Model['sign_up'] = [
            'name' => '注册帐号'];
        $this->Model['sign_up_post'] = [
            'name' => '注册帐号提交'];
    }
}