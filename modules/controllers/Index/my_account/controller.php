<?php
namespace Index\Controller;


use Index\M_Class\Index_class;
use Index\Model\my_account_model;


class my_account
{
    /**
     * index
     */
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