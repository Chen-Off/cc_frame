<?php
namespace Index\M_Class;
use cc\Msg;
use CommonClass\Common_Class;
use CommonLanguage\Common_Language;



class Index_class extends Common_Class
{
    function msgExit($msg,$msg_status = 'error') {
        Msg::add_session($msg, $msg_status);
        jumpUrl();
    }
}