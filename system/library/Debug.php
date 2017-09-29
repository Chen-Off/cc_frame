<?php
namespace cc;

class DeBug {
    private static $logPath = '';
    function __construct()
    {
        self::$logPath = TEMP_EP_HTTP.DS.'log'.DS;
        if(!is_dir(self::$logPath)) mkdir(self::$logPath, 0744, true);
    }

    public static function record($msg, $type) {
        cc__writeTxt(self::$logPath.$type.LOG_EXT, $msg.PHP_EOL);


    }


    public static function msgExit($msg = '')
    {
        if(true === Config::getCB('debug_show')) {
            echo $msg;
        }
        die;
    }
}