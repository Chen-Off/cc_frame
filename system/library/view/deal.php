<?php
namespace cc\View;

use cc\Config;
use cc\Msg;
use cc\Oauth;

class deal
{
    public $viewTplPath = '';
    public $data = [];

    //语言包
    public $lang = [];

    function __construct()
    {

    }

    /**
     * nav 左侧NAV导航内容
     * @return string
     */
    public function nav()
    {
        //组合常规页面和特殊页面授权
        $commonAuth = Oauth::getCommonAuth();
        $specialAuth = Oauth::getSpecialAuth();

        foreach ($specialAuth as $fID => $data) {
            if (!isset($commonAuth[$fID])) {
                if ($data['function_level'] == 1) {
                    $data['function_title'] .= ' 【授权】';
                }
                $commonAuth[$fID] = $data;
            }
        }

        $nowPage = Oauth::$page;
        $nav = '';

        //var_dump($commonAuth);die;
        //循环数据
        foreach ($commonAuth as $module) {
            //先获取主模块 MODULES
            if ($module['show_status'] == 0 || $module['function_level'] > 1) {
                continue;
            }

            $moduleID = $module['function_id'];
            $moduleName = $module['function_name'];

            $nav .= $this->nav_modules($module['function_title']);

            //子模块 MODEL
            foreach ($commonAuth as $model) {
                if ($model['show_status'] == 0 || $model['parent_id'] != $moduleID) {
                    continue;
                }

                $modelID = $model['function_id'];
                $modelName = $model['function_name'];
                $active = ($nowPage['model_id'] == $modelID ? 'active' : '');
                $nav .= $this->nav_model($model['function_icon'], $model['function_title'], $active);

                //功能模块 ACTION
                foreach ($commonAuth as $action) {
                    if ($action['show_status'] == 0 || $action['parent_id'] != $modelID) {
                        continue;
                    }

                    $actionID = $action['function_id'];
                    $actionName = $action['function_name'];

                    $active = ($nowPage['action_id'] == $actionID ? 'active' : '');
                    $url = createUrl($moduleName, $modelName, $actionName);

                    $nav .= $this->nav_action($url, $action['function_title'], $active);
                }
                $nav .= '</ul>';
            }
        }

        return $nav;
    }

    private function nav_action($url, $fTitle, $active)
    {
        return '<li class="' . $active . '" ui-sref-active="active">
        <a href="' . $url . '">
          <span>' . $fTitle . '</span>
        </a>
      </li>';
    }

    private function nav_model($fIco, $fTitle, $active)
    {
        return '<li class="' . $active . '">
    <a href class="auto">
      <span class="pull-right text-muted">
        <i class="fa fa-fw fa-angle-right text"></i>
        <i class="fa fa-fw fa-angle-down text-active"></i>
      </span>
      <i class="' . $fIco . ' icon"></i>
      <span>' . $fTitle . '</span>
    </a>
    <ul class="nav nav-sub dk">
    <li class="nav-sub-header">
        <a href>
          <span>' . $fTitle . '</span>
        </a>
      </li>';
    }

    private function nav_modules($fTitle)
    {
        return '<li class="hidden-folded padder m-t m-b-sm text-muted text-xs"><span>' . $fTitle . '</span></li>';
    }

    /**
     * 面包屑导航
     * breadcrumbs
     * @return string
     */
    public function breadcrumbs()
    {
        $result = '<a class="icon-home"></a>';

        if (!empty(URL_MODULES))
            $result .= '<span class="fa fa-angle-right"></span>' . '<a>' . $this->lang['module'] . '</a>';

        if (!empty(URL_MODEL))
            $result .= '<span class="fa fa-angle-right"></span>' . '<a>' . $this->lang['model'] . '</a>';

        if (!empty(URL_ACTION))
            $result .= '<span class="fa fa-angle-right"></span>' . '<a href="' . brtUrl('action') . '">' . $this->lang['action']['name'] . '</a>';

        if (!empty(URL_PARAMS))
            $result .= '<span class="fa fa-angle-right m_r_10"></span>' . URL_PARAMS;

        return $result;
    }


    /**
     * deal__commonLang 替换通用语言包
     * @param $content
     * @return string
     */
    public function deal__commonLang($content)
    {
        $lang = $this->lang['common'];
        preg_match_all('/{\$CommonLang->(.*)\(\'(.*)\'\)}/isU', $content, $match);
        if (!empty($match[1])) {
            foreach ($match[1] as $k => $fun) {
                if (method_exists($lang, $fun) && isset($match[2][$k])) {
                    $new_item = $lang->$fun($match[2][$k]);
                } else {
                    $new_item = '';
                }

                $old_item = $match[0][$k];
                $content = str_replace($old_item, $new_item, $content);
            }
        }
        return $content;
    }

    /**
     * 替换模块语言包
     * deal__moduleLang
     * @param $content
     * @return mixed
     */
    public function deal__moduleLang($content)
    {
        $lang = $this->lang['action'];
        preg_match_all('/{\$ModuleLang\[\'(.*)\'\]}/isU', $content, $match);
        if (!empty($match[1])) {
            foreach ($match[1] as $k => $item) {
                $new_item = isset($lang['action'][$item]) ? $lang['action'][$item] : '';

                $old_item = $match[0][$k];
                $content = str_replace($old_item, $new_item, $content);
            }
        }
        return $content;
    }

    /**
     * 替换控制端产出的数据
     * deal__viewData
     * @param $content
     * @return mixed
     */
    public function deal__viewData($content)
    {
        $data = $this->data;
        if (empty($data)) {
            return $content;
        }
        $this->data = [];

        preg_match_all('/{\$show(\:|->)(.*)}/isU', $content, $match);

        if (!empty($match[2])) {
            $arr = $match[2];
            foreach ($arr as $k => $item) {
                if(!isset($data[$item]) || is_array($data[$item])) {
                    continue;
                }
                $new_item = $data[$item];
                $old_item = $match[0][$k];
                $content = str_replace($old_item, $new_item, $content);
            }
        }
        return $content;
    }

    /**
     * 替换BrtURL
     * deal__brtUrl
     * @param $content
     * @return mixed
     */
    public function deal__brtUrl($content)
    {
        preg_match_all('/{\$brtUrl(\:|->)(.*)}/isU', $content, $match);

        if (!empty($match[2])) {
            foreach ($match[2] as $k => $item) {
                $brtUrl = brtUrl(trim($item));
                $old_item = $match[0][$k];
                $content = str_replace($old_item, $brtUrl, $content);
            }
        }
        return $content;
    }


    /**
     * 替换URL路由
     * deal__Url
     * @param $content
     * @return mixed
     */
    public function deal__Url($content)
    {

        preg_match_all('/{\$url(\:|->)(.*)}/isU', $content, $match);
        if (!empty($match[2])) {
            foreach ($match[2] as $k => $item) {
                $expType = $match[1][$k];
                $u = explode($expType, $item);
                switch (count($u)) {
                    case '1':
                        $new_item = createUrl($u[0]);
                        break;
                    case '2':
                        $new_item = createUrl($u[0], $u[1]);
                        break;
                    case '3':
                        $new_item = createUrl($u[0], $u[1], $u[2]);
                        break;
                    case '4':
                        $new_item = createUrl($u[0], $u[1], $u[2], $u[3]);
                        break;
                    default :
                        $new_item = createUrl();
                        break;
                }

                $old_item = $match[0][$k];
                $content = str_replace($old_item, $new_item, $content);
            }
        }

        //替换URL常量
        $content = str_replace('URL_PARAMS', URL_PARAMS, $content);
        $content = str_replace('URL_ACTION', URL_ACTION, $content);
        $content = str_replace('URL_MODEL', URL_MODEL, $content);
        $content = str_replace('URL_MODULES', URL_MODULES, $content);

        //加载主页URL
        $content = str_replace('{$page:DomainUrl/}', Config::CB('domain_url'), $content);
        return $content;
    }


    /**
     * 替换HTML <head></head> 内容
     * deal_pageHead
     * @param $content
     * @return mixed
     */
    public function deal_pageHead($content)
    {
        //加载META三大标签
        $content = str_replace('{$page:MetaTitle/}', META_TITLE, $content);
        $content = str_replace('{$page:MetaKeywords/}', META_KEYWORDS, $content);
        $content = str_replace('{$page:MetaDescription/}', META_DESCRIPTION, $content);

        //加载ICO图标
        $icoHref = 'tpl/styles/img/favicon.ico';
        $styleIco = '    <link rel="shortcut icon" href="' . $icoHref . '" />' . PHP_EOL;
        $content = str_replace('{$page:StyleIco/}', $styleIco, $content);


        //$stylePath = TEMPLATES_PATH . '/styles';
        $action = (empty(URL_ACTION)) ? 'index' : URL_ACTION;
        //加载模版CSS样式
        $styleCss = '';
        //加载项目功能CSS
        $actionCssFile = 'css/' . URL_MODULES . '/' . URL_MODEL . '/' . $action . '.css';
        if (is_file(MVC_STYLES_PATH . $actionCssFile)) {
            $href = MVC_STYLES_HTTP . $actionCssFile;
            $styleCss .= '<link rel="stylesheet" type="text/css" href="' . $href . '" />' . PHP_EOL;
        }
        $content = str_replace('{$page:StyleCss/}', $styleCss, $content);


        $styleJs = '';
        //加载模版JS样式


        //加载项目功能JS
        $actionJsFile = 'js/' . URL_MODULES . '/' . URL_MODEL . '/' . $action . '.js';
        if (is_file(MVC_STYLES_PATH . $actionJsFile)) {
            $src = MVC_STYLES_HTTP . $actionJsFile;
            $styleJs .= '<script type="text/javascript" src="' . $src . '"></script>' . PHP_EOL;
        }
        $content = str_replace('{$page:StyleJs/}', $styleJs, $content);

        //加载第三方插件
        if (isset($_SESSION['thirdParty']) && !empty($_SESSION['thirdParty'])) {
            $content = str_replace('</head>', $_SESSION['thirdParty'] . '</head>', $content);
            unset($_SESSION['thirdParty']);
        }

        return $content;
    }


    function compress_html($content)
    {
        $befor = ["/> *([^ ]*) *</", "//", "'/\*[^*]*\*/'", "/\r\n/", "/\n/", "/\t/", '/>[ ]+</'];
        $after = [">\\1<", '', '', '', '', '', '><'];
        return ltrim(rtrim(preg_replace($befor, $after, $content)));
    }

    /**
     * 加载提示结果
     * deal__resultMsg
     * @param $content
     * @return string
     */
    public function deal__resultMsg($content)
    {
        if (strpos($content, '{$page:alertResult/}') !== false) {
            Msg::getMessage();
            $alertResult = Msg::output();
            $content = str_replace('{$page:alertResult/}', $alertResult, $content);
        }
        return $content;
    }

    /**
     * 登录帐号信息
     * deal_account
     * @param $content
     * @return mixed
     */
    public function deal_account($content)
    {
        $user = Oauth::$user;
        $content = str_replace('{$account:name/}', $user['u_name'], $content);
        if (!empty($user['u_group_name'])) {
            $rank = $user['u_group_name'] . ' - ';
        } else {
            $rank = '';
        }
        $rank .= $user['u_power_name'];
        $content = str_replace('{$account:rank/}', $rank, $content);
        //$content = preg_replace('/\{\$account\:(.*)\/\}$/i', '', $content);

        return $content;
    }

}