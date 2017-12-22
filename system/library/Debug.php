<?php
namespace cc;

class DeBug {
    private static $logPath = '';

    public static function record($msg, $type) {
        $fileName = self::getLogPath().date('Y-m-d').'_'.$type.LOG_EXT;
        cc__writeTxt($fileName, $msg.PHP_EOL,'a');
    }

    private static function getLogPath() {
        if(empty(self::$logPath)) {
            self::$logPath = TEMP_EP_PATH.'log'.DS;
            if(!is_dir(self::$logPath)) mkdir(self::$logPath, 0744, true);
        }
        return self::$logPath;
    }


    public static function msgExit($msg = '')
    {
        if(true === Config::getCB('debug_show')) {
            echo $msg;
        }
        die;
    }
}