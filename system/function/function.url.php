<?php
/**
 * createUrl()
 * URL 生成
 * @param string|null|array $modules 模块
 * @param string|null $model 子模块
 * @param string|null $action 功能
 * @param string|null $params 参数
 * @return string
 */


function createUrl($modules = null, $model = null, $action = null, $params = null)
{
    if(is_array($modules)) {
        $array = $modules;
        if(isset($array[0])) $modules = $array[0];
        if(isset($array[1])) $model = $array[1];
        if(isset($array[2])) $action = $array[2];
        if(isset($array[3])) $params = $array[3];
    }
    //当前URL
    if(empty($modules) && empty($model) && empty($action) && empty($params)) {
        $modules = URL_MODULES;
        $model = URL_MODEL;
        $action = URL_ACTION;
        $params = URL_PARAMS;
    } elseif(!empty($modules) && empty($model) && empty($action) && empty($params)) {
        if($modules == 'model' || $modules == 'action') {
            $modules = URL_MODULES;
            $model = URL_MODEL;
            if($modules == 'action') $action = URL_ACTION;
        }
    } else {
        $modules = empty($modules) ? 'Index' : $modules;
        $model = empty($model) ? 'index' : $model;
    }


    $newUrl = DOMAIN_URL;
    //$newUrl = '';

    $urlType = URL_TYPE;
    if ($urlType == 'dynamic') {//动态URL
        $newUrl .= '?modules=' . $modules . '&model=' . $model;
        if (!empty($action)) $newUrl .= '&action=' . $action;
        if (!empty($params)) $newUrl .= '&params=' . $params;
    } elseif ($urlType == 'static') {//静态URL
        $newUrl .= 'app/'.$modules . '/' . $model;
        if (!empty($action)) $newUrl .= '/' . $action;
        if (!empty($params)) $newUrl .= '/' . $params;
    }

    return $newUrl;
}


/**
 * btr_url()
 * 简易快速URL
 * @param string $brt_name
 * @return string $brt_url
 */
function brtUrl($brt_name)
{
    $brt_url = '';
    $modules = '';
    $model = '';
    $action = '';
    $params = '';
    switch ($brt_name) {
        case 'home'   :
            $brt_url = DOMAIN_URL;
            break;

        case 'now'    :
            $modules = empty(URL_MODULES) ? $modules : URL_MODULES;
            $model = empty(URL_MODEL) ? $model : URL_MODEL;
            $action = empty(URL_ACTION) ? $action : URL_ACTION;
            $params = empty(URL_PARAMS) ? $params : URL_PARAMS;
            break;

        case 'modules' :
            $modules = URL_MODULES;
            break;

        case 'model' :
            $modules = URL_MODULES;
            $model = URL_MODEL;
            break;


        case 'action' :
            $modules = URL_MODULES;
            $model = URL_MODEL;
            $action = URL_ACTION;
            break;

        case 'no_power' :
            $modules = 'Access';
            $model = 'page';
            $action = 'no_power';
            break;

        case '404' :
            $modules = 'Access';
            $model = 'page';
            $action = 'page_404';
            break;

        default:
            $modules = 'Access';
            $model = 'page';
            $action = 'page_404';
            break;
    }

    $brt_url = (empty($brt_url)) ? createUrl($modules, $model, $action, $params) : $brt_url;
    return $brt_url;
}

/**
 * 页面跳转
 * jumpUrl
 * @param null $url
 */
function jumpUrl($url = null)
{
    if ($url === null) {
        $url = brtUrl('now');
    } elseif (substr($url, 0, 7) != 'http://') {
        $url = brtUrl($url);
    }

    header("Location: " . $url);
    exit;
}