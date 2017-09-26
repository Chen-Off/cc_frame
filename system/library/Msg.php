<?php
/**
 * messageStack Class.
 * 这个类是用来管理程序执行的结果消息汇总堆栈
 *
 * @author Chen<2795265136@qq.com>
 * @copyright 2017
 * @internal 消息堆栈转入 library
 * @version 2017年4月7日 22:01:02
 */
namespace cc;


class Msg
{
    static public $alertStatus = ['success' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-danger'];
    static private $messages = array();

    /**
     * messageStack constructor.
     */
    function __construct()
    {
        
    }

    static function getMessage()
    {

        if (isset($_SESSION['messageToStack']) && !empty($_SESSION['messageToStack'])) {
            self::$messages = $_SESSION['messageToStack'];
            $_SESSION['messageToStack'] = '';
        }
    }

    /**
     * showMsg showMsg 展示MSG效果
     * @return mixed
     */
    static function showMsg()
    {
        if (isset($_SESSION['messageToStack']) && !empty($_SESSION['messageToStack'])) {
            self::$messages = $_SESSION['messageToStack'];
        }
        return self::output();
    }

    /**
     * @return string $output
     */
    static function output()
    {
        $output = '';
        $outputArr = ['success' => '', 'warning' => '', 'error' => ''];
        $n = sizeof(self::$messages);
        for ($i = 0; $i < $n; $i++) {
            if (self::$messages[$i]['modules'] == URL_MODULES &&
                self::$messages[$i]['model'] == URL_MODEL &&
                self::$messages[$i]['action'] == URL_ACTION &&
                self::$messages[$i]['params'] == URL_PARAMS &&
                is_string(self::$messages[$i]['text'])
            ) {
                $type = self::$messages[$i]['type'];
                $outputArr[$type] .= '<li class="result_' . self::$messages[$i]['type'] . '"><span>';
                $outputArr[$type] .= trim(self::$messages[$i]['text']);
                $outputArr[$type] .= '</span></li>' . PHP_EOL;
            }
        }

        foreach ($outputArr as $k => $v) {
            if (!empty($v)) {
                $output .= '<div class="alert ' . self::$alertStatus[$k] . ' margin-top"><ul style="padding-left:16px">' . $v . '</ul></div>';
            }
        }
        return $output;
    }

    /**
     * push 新版接收信息2.0 2017年7月22日 14:56:57
     * @param $msg
     * @param string $type
     * @param null $page
     */
    static function push($msg, $type = 'error', $page = null)
    {
        self::add_session($msg, $type, $page);
    }

    /**
     * messageStack::add_session()
     * 接收信息 模块 全空白表示接收给自己
     * @param string $msg
     * @param mixed $type
     * @param string|array $page
     *        eg. modules/model/action/null 主模块/子模块/动作/当前 （直接添加到当前URL模块组指定的位置）
     *        eg.['modules', 'model', 'action', 'params'] [主模块, 子模块, 动作, 参数] （添加msg到指定的URL链接处）
     * @return mixed self::output()
     */
    static function add_session($msg, $type = 'error', $page = null)
    {
        if (!isset($_SESSION['messageToStack'])) {
            $messageToStack = array();
        } else {
            $messageToStack = $_SESSION['messageToStack'];
        }

        $url = ['modules' => '', 'model' => '', 'action' => '', 'params' => ''];

        switch ($page) {
            case null:
                $url = ['modules' => URL_MODULES, 'model' => URL_MODEL, 'action' => URL_ACTION, 'params' => URL_PARAMS];
                break;

            case 'modules':
                $url['modules'] = URL_MODULES;
                break;

            case 'model':
                $url['modules'] = URL_MODULES;
                $url['model'] = URL_MODEL;
                break;

            case 'action':
                $url['modules'] = URL_MODULES;
                $url['model'] = URL_MODEL;
                $url['action'] = URL_ACTION;
                break;

            case is_array($page):
                if (isset($page[0])) $url['modules'] = $page[0];
                if (isset($page[1])) $url['model'] = $page[1];
                if (isset($page[2])) $url['action'] = $page[2];
                if (isset($page[3])) $url['params'] = $page[3];
                break;
        }

        $messageToStack[] = array_merge($url, ['text' => $msg, 'type' => $type]);
        $_SESSION['messageToStack'] = $messageToStack;
        self::$messages = $messageToStack;
        //return self::output();
    }

    static function reset()
    {
        self::$messages = array();
    }
}