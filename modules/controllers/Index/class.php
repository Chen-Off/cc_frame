<?php
namespace Index\M_Class;
use cc\Msg;



class Index_class
{
    function msgExit($msg,$msg_status = 'error') {
        Msg::add_session($msg, $msg_status);
        jumpUrl();
    }
}