<?php
/**
 * 视图输出管理
 *
 * @author Chen<2795265136@qq.com>
 * @copyright 2017
 * @internal 视图输出管理
 * @version 2017年6月27日 15:44:21
 */

/**
 * push($name, $value)
 * pushBatch($array)
 */
namespace cc;

use cc\View\deal;

class View
{
    // 模板变量
    private static $data = [];

    //语言包
    private static $lang = [];

    //TPL 文件路径
    private static $tplPath = '';

    private static $smartyArr = ['header', 'index', 'aside', 'page_footer', 'settings', 'app_footer'];

    /**
     * @var deal
     */
    private static $dealObj;


    function __construct()
    {

    }

    public static function lang_data($lang, $tplPath)
    {
        self::$lang = $lang;
        self::$tplPath = $tplPath;
    }


    /**
     * 渲染输出
     * display
     * @param array $data
     * @return string
     */
    static function display($data = [])
    {
        self::$dealObj = new deal();
        self::$dealObj->lang = self::$lang;


        // 模板最后变量
        $vars = array_merge(self::$data, $data);
        self::$dealObj->data = $vars;

        // 页面缓存
        ob_start();
        ob_implicit_flush(0);


        //载入模版文件
        $tplContent = self::getTplContent();

        // 渲染输出
        if (!empty($tplContent) || false !== $tplContent) {
            echo self::rendering($tplContent);
        }

        // 获取并清空缓存
        $content = ob_get_clean();

        echo $content;
        die;
    }

    /**
     * rendering 渲染输出
     * @param $content
     * @return mixed
     */
    static private function rendering($content)
    {
        $dealObj = self::$dealObj;
        //替换HTML <head></head> 内容
        $content = $dealObj->deal_pageHead($content);

        //替换控制端产出的数据
        $content = $dealObj->deal__brtUrl($content);

        //替换BrtURL
        $content = $dealObj->deal__Url($content);

        //替换通用语言包
        $content = $dealObj->deal__commonLang($content);

        if (URL_MODULES != 'Access') {
            //加载NAV左侧导航栏
            if (strpos($content, '{$smarty:nav/}') !== false) {
                $content = str_replace('{$smarty:nav/}', $dealObj->nav(), $content);
            }

            //替换页面名称
            if (strpos($content, '{$page:name/}') !== false) {
                $content = str_replace('{$page:name/}', self::$lang['name'], $content);
            }

            //替换导航内容
            if (strpos($content, '{$page:breadcrumbs/}') !== false) {
                $content = str_replace('{$page:breadcrumbs/}', $dealObj->breadcrumbs(), $content);
            }


            //替换模块语言包
            $content = $dealObj->deal__moduleLang($content);


            //登录帐号信息
            $content = $dealObj->deal_account($content);
        }

        //加载提示结果
        $content = $dealObj->deal__resultMsg($content);

        //替换控制端产出的数据
        $content = $dealObj->deal__viewData($content);

        $content = preg_replace('/{\$.*}/isU', '', $content);

        if(true === Config::CB('TPL', 'compress_html')) {
            $content = $dealObj->compress_html($content);
        }
        return $content;
    }

    /**
     * 载入模版文件
     * getTplContent
     * @return bool|mixed|string
     */
    static private function getTplContent()
    {
        //访问页面主入口HTML文件
        $tplPath = TEMPLATES_PATH . '/page/' . self::$tplPath;

        if (false === Config::CB('tpl','smarty_loader')) {
            //不使用母版页
            $tplContent = self::getFile(MODULES_VIEW_FILE);
        } else {
            //加载访问页面主入口HTML文件 载入母板
            $tplContent = self::getFile($tplPath);
            if (false === $tplContent) {
                die('非法模版文件');
            }

            //加载公共HTML模块
            foreach (self::$smartyArr as $file) {
                if (strstr($tplContent, '{$smarty:' . $file . '/}')) {
                    $smartyFile = self::getFile(TEMPLATES_PATH . '/smarty/' . $file . '.html');
                    $tplContent = str_replace('{$smarty:' . $file . '/}', $smartyFile, $tplContent);
                }
            }

            //载入视图模版HTML文件
            $viewContent = self::getFile(MODULES_VIEW_FILE);

            if (URL_MODULES != 'Access' && false === $viewContent) {
                $viewContent = '<div class="alert alert-danger margin-top"><ul style="padding-left:16px">[视图文件未发现]：' . MODULES_VIEW_FILE . '</ul></div>';
            }

            if (strpos($tplContent, '{$page:ViewContent/}') !== false) {
                $tplContent = str_replace('{$page:ViewContent/}', $viewContent, $tplContent);
            }

            //载入INDEX HTML文件
            $pageContent = self::getFile(TEMPLATES_PATH . '/smarty/index.html');

            if (false === $pageContent) {
                die('INDEX 主模版文件失效');
            }
            if (false !== $pageContent && strpos($pageContent, '{$page:pageFile/}') !== false) {
                $tplContent = str_replace('{$page:pageFile/}', $tplContent, $pageContent);
            }
        }



        return $tplContent;
    }

    /**
     * getFile 载入模版文件
     * @param $filePath
     * @return bool|string
     */
    static private function getFile($filePath)
    {
        if (is_file($filePath)) {
            return file_get_contents($filePath);
        } else {
            return false;
        }
    }

    /**
     * pushBatch 批量追加
     * @param $array [description] 变量数组
     *               eg.['name' => 'cc', 'email' => '123@qq.com']
     */
    static public function pushBatch($array)
    {
        foreach ($array as $n => $v) {
            self::$data[$n] = $v;
        }
    }

    /**
     * push 追加
     * @param $name [description] 变量名
     * @param $value [description] 变量值
     */
    static public function push($name, $value = '')
    {
        self::$data[$name] = $value;
    }


    /**
     * 模板变量赋值
     * @access public
     * @param string $name 变量名
     * @param mixed $value 变量值
     */
    public function __set($name, $value)
    {
        self::$data[$name] = $value;
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板变量
     * @return mixed
     */
    public function __get($name)
    {
        return self::$data[$name];
    }

    /**
     * 检测模板变量是否设置
     * @access public
     * @param string $name 模板变量名
     * @return bool
     */
    public function __isset($name)
    {
        return isset(self::$data[$name]);
    }

}