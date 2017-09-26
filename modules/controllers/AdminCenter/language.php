<?php
namespace AdminCenter\Language;
use CommonLanguage\Common_Language;

class AdminCenter_language extends Common_Language
{
    public $Module, $Model;
    public function __construct() {
        $this->Module['name'] = '管理中心';
    }

/*============================首页============================================================*/

    public function groups_mange() {
        $this->Model['index'] = ['name' => '管理操作组'];
        $this->Model['add_new'] = ['name' => '新的操作组'];
        $this->Model['list_show'] = ['name' => '操作组列表'];
        $this->Model['mange'] = ['name' => '管理操作组'];
    }

    public function account_mange() {
        $this->Model['index'] = ['name' => '管理操作员'];
        $this->Model['add_new'] = ['name' => '新的操作员'];
        $this->Model['list_show'] = ['name' => '操作员列表'];
        $this->Model['edit'] = ['name' => '编辑操作员'];
    }

    public function identity_power() {
        $this->Model['index'] = ['name' => '身份权限管理'];
        $this->Model['identity'] = ['name' => '基本身份设置'];
        $this->Model['action_set'] = ['name' => '功能模块设置'];
        $this->Model['auth_power'] = ['name' => '特别功能授权'];
        $this->Model['auth_content'] = ['name' => '添加新的授权功能'];
        $this->Model['base_power'] = ['name' => '基本权限设置'];
    }
}