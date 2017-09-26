<?php
namespace Index\Controller;

use CommonClass\Common_Class;

use Index\Model\index_model;
use Index\M_Class\Index_class;
use Index\Model\my_account_model;


class my_account extends Common_Class
{
    public function index() {

    }


    //修改密码
    public function edit_pwd() {
        if($_POST) {
            $MODEL = new my_account_model();
            $MODEL->edit_pwd_post();
        }
    }
}