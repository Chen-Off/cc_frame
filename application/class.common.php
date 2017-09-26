<?php
/**
 * @author Chen
 * @copyright 2015
 * 全站通用类
 * @since
 * @version class.common.php|2016年7月8日 11:56:09
 */

namespace CommonClass;

use CommonLanguage\Common_Language;

class Common_Class
{
    public $viewData;

    /**
     * @var Common_Language
     */
    public $LangC;
    public $LangA;


    /**
     * 页面编码类型修改（主要使用于输出JS所使用的JSON集合）
     * page_header_code
     * @param $code
     */
    function page_header_code($code)
    {
        header('Content-Type: application/' . $code);
    }

    /**
     * 简易版模拟提交
     * simulation_login
     * @param $login_url
     * @param $get_url
     * @param $post
     * @return string
     */
    function simulation_login($login_url, $get_url, $post)
    {
        $cookie = dirname(__FILE__) . '/cookie_simulation.txt';

        //登录
        $curl = curl_init();//初始化curl模块
        curl_setopt($curl, CURLOPT_URL, $login_url);//登录提交的地址
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息


        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源

        //获取
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //读取cookie
        $rs = curl_exec($ch); //执行cURL抓取页面内容
        curl_close($ch);

        @unlink($cookie);
        return $rs;
    }

    function curl($url, $post_data = [], $time = 20)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);//是否显示头信息
        if (!empty($post_data)) {
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);

        $output = curl_exec($ch);
        //var_dump(curl_error($ch));
        curl_close($ch);

        return $output;
    }

    /** ****************************我是分割线******************************* **/

    /**
     * 验证URL参数是否等于POST参数
     * verification_params
     * @param $params
     * @param $post
     * @param $jump
     */
    function verification_params($params, $post, $jump = false)
    {
        if ($params != $post) {
            if ($jump === true) {
                jumpUrl(brtUrl('model'));
            }
            $text = $params . ',' . $post;
            $this->error_exit('not_equal', $text);
        }
    }

    /**
     * DataManipulation_class::verification_empty()
     * 验证参数是否为空,为空退出
     * @param array|string $data
     * @param null|array|string $exception 不需要验证的
     */
    function verification_empty($data, $exception = null)
    {
        $exception = is_array($exception) ? $exception : array($exception);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $exception) && trim($value) == '')
                    $this->error_exit('empty', $key);
            }
        } else {
            if (empty($data))
                $this->error_exit('empty', $data);
        }
    }


    /**
     * 验证指定的POST参数是否存在
     * oauth_post_params
     * @param array $postData
     * @param array $oathArr
     * @param bool|string $dataType
     * @return bool
     */
    function oauth_post_params($postData, $oathArr, $dataType = false)
    {
        if (!is_array($oathArr) || !is_array($postData)) {
            return false;
        }
        foreach ($oathArr as $v) {
            if (!isset($postData[$v])) {
                var_dump($v);
                return false;
            }
            $str = $postData[$v];

            if (false !== $dataType) {
                switch ($dataType) {
                    case 'number':
                        if (!is_numeric($str) || $str == '') return false;
                        break;
                    case 'array':
                        if (!is_array($str) || empty($str)) return false;
                        break;
                }
            }
        }
    }

    /** ****************************我是分割线******************************* **/




    /** ****************************我是分割线******************************* **/

    /**
     * @name GBK转UTF8
     * @param array|string $string
     * @return array|string
     */
    function gb2312_utf8($string)
    {
        $result = '';
        if (is_array($string)) {
            foreach ($string as $k => $v) {
                $result[$k] = $this->gb2312_utf8($v);
            }
        } else {
            if (!empty(trim($string))) {
                $result = iconv('GB2312', 'UTF-8', $string);
            } else {
                $result = $string;
            }
        }
        return $result;
    }

    /**
     * @name UTF8转GBK
     * @param array|string $string
     * @return string|array
     */

    function utf8_gb2312($string)
    {
        $result = '';
        if (is_array($string)) {
            foreach ($string as $k => $v) {
                $result[$k] = $this->gb2312_utf8($v);
            }
        } else {
            if (!empty(trim($string))) {
                $result = iconv('UTF-8', 'GB2312', $string);
            } else {
                $result = $string;
            }
        }
        return $result;
    }

    /** ****************************我是分割线******************************* **/

    /**
     * Common_Class::encode_json()
     * JSON encode 中文不转义
     * @param mixed $str
     * @return mixed
     */
    function encode_json($str)
    {
        $error_string = array("\r", "\t");
        foreach ($error_string as $value) {
            $str = str_replace($value, '', $str);
        }
        $str = $this->encode_replace($str);
        return urldecode(json_encode($this->url_encode($str)));
    }

    /**
     * Common_Class::url_encode()
     * JSON encode 中文不转义
     * @param string|array $str
     * @return mixed
     */
    function url_encode($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[urlencode($key)] = $this->url_encode($value);
            }
        } else {
            $str = urlencode($str);
        }
        return $str;
    }

    /**
     * Common_Class::encode_replace()
     * 使用中文不转义会导致不会自动处理 标点符号
     * @param mixed $str
     * @return string $new_str
     */
    function encode_replace($str)
    {
        if (is_array($str)) {
            $new_str = array();
            foreach ($str as $key => $value) {
                $value = $this->encode_replace($value);
                $new_str[$key] = $value;
            }
        } else {
            $new_str = addslashes($str);
        }
        return $new_str;
    }


    /** ****************************我是分割线?******************************* **/


    /**
     * Common_Class::error_warning()
     * 错误警告
     * @param mixed $params 参数
     * @param mixed $model 内容
     * @return void
     */
    function error_warning($params, $model)
    {
        switch ($params) {
            case 'products_dir':
                $contents = '项目库文件夹没有被发现，已经重新创建了新的项目库文件夹';
                break;

            case 'up_file_txt':
                $contents = '请上传TXT格式文件';
                break;

            case 'up_file_fail':
                $contents = '上传失败';
                break;

            case 'up_file_illegal':
                $contents = '非法上传！！';
                break;

            case 'add_pro_name':
                $contents = '项目名称为空';
                break;

            case 'add_pro_keywords':
                $contents = '关键词为空';
                break;

            case 'params_error':
                $contents = '[' . $model . '] 参数错误';
                break;


            case 'empty_data':
                $contents = '[' . $model . '] 所调用的数据不存在';
                break;

            case 'add_pro_exist':
                $contents = $model . '所属的级别中该项目已经存在';
                break;

            case 'dir_path':
                $contents = $model . ' 文件夹不存在';
                break;

            default :
                $contents = '错误原因未知';
        }
        $contents = '<div class="error_warning">警告:' . $contents . '</div>';
        echo $contents;
    }

    /**
     * Common_Class::error_exit()
     * 错误退出
     * @param mixed $params 参数
     * @param null $value 内容
     * @return void
     */
    function error_exit($params, $value = null)
    {
        switch ($params) {
            case 'select_empty':
                $contents = '{' . $value . '} 有必选项目未选择';
                break;
            case 'error_date':
                $contents = '错误的时间格式或者时间 - {' . $value . '}';
                break;
            case 'json_decode_empty':
                $contents = '{' . $value . '} JSON_DECODE 失败，内容中可能包含非法字符';
                break;
            case 'modules':
                $contents = '{' . $value . '} 模块组件没有找到，请打开正确的链接';
                break;
            case 'modules_model':
                $contents = '{' . $value . '} 模块组件下功能文件没有找到，请打开正确的链接';
                break;
            case 'modules_controller':
                $contents = '{' . $value . '} 模块组件下命名空间定义错误';
                break;
            case 'empty':
                $contents = '{' . $value . '} 所提交的参数为空';
                break;
            case 'templates_null':
                $contents = ' 模版库为空';
                break;
            case 'min_max_error':
                $contents = '{' . $value . '} 最大最小值错误';
                break;
            case 'ip_address':
                $contents = '{' . $value . '} 不是一个正常的IP地址';
                break;
            case 'not_equal':
                $contents = '{' . $value . '} 参数不相等';
                break;
            case 'params_error':
                $contents = '请不要提交非法参数 - ' . '{' . $value . '}';
                break;
            case 'upload_tmp_name':
                $contents = '请不要上传请他的文件';
                break;
            case 'upload_type':
                $contents = '只能上传 {' . $value . '} 格式的文件';
                break;
            case 'upload_error':
                $contents = '上传失败，请重新尝试';
                break;
            case 'function_empty':
                $contents = '{' . $value . '} 方法为定义';
                break;


            default :
                $contents = '错误原因未知';


        }
        $contents = '<div class="error_exit">错误:' . $contents . '</div>';
        exit($contents);
    }
}