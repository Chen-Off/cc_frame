<?php
namespace Index\Language;
use CommonLanguage\Common_Language;

class Index_language extends Common_Language
{
    public $Module, $Model;
    public function __construct() {
        $this->Module['name'] = '首页';
    }

/*============================首页============================================================*/

    public function index() {
        $this->Model['index'] = ['name' => '首页'];
    }

    public function my_account() {
        $this->Model['index'] = ['name' => '我的帐号'];
        $this->Model['edit_pwd'] = ['name' => '修改密码'];
    }



}