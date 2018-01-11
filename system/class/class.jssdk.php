<?php

namespace weChatJSSDK;

use cc\Db;

class JSSDK
{
    private $appId;
    private $gzh_id;
    private $appSecret;
    private $redirect_uri;
    public $openId = '', $cookie_signature = '', $jsAccessToken = '', $unionId = '';
    public $jsAccessTokenDir = PUBLIC_DATA_PATH . 'js_cache' . DS;


    public function __construct($option)
    {
        if (!is_dir($this->jsAccessTokenDir)) {
            mkdir($this->jsAccessTokenDir, 0755, true);
        }
        $this->gzh_id = $option['gzh_id'];
        $this->appId = $option['appid'];
        $this->appSecret = $option['appsecret'];
    }

    /**
     * 设置需要静默授权的链接
     * @param $url
     */
    public function setRedirectUri($url)
    {
        if (!empty($url)) {
            $this->redirect_uri = $url;
        }
    }

    /**
     * getJsAccessToken
     * 拉取微信用户openID 和登陆token,cookie
     */
    public function getJsAccessToken()
    {
        $jsAccessToken = '';
        $newGetData = false;

        //检测是否存在cookie
        if (isset($_COOKIE['cookie_signature']) && !empty($_COOKIE['cookie_signature'])) {
            $cookie_signature = $_COOKIE['cookie_signature'];
            //获取oAuto_session
            $filename = sha1($cookie_signature) . '.log';
            $data = json_decode($this->get_log_file($filename));


            //检测参数是否存在
            if (isset($data->expire_time) && isset($data->refresh_expire_time) && isset($data->refresh_expire_time) && isset($data->access_token) && isset($data->refresh_token) && isset($data->openid) && isset($data->scope)) {
                //检测是否未过期
                if ($data->expire_time > time()) {
                    $jsAccessToken = $data->access_token;
                    $this->openId = $data->openid;
                    $this->cookie_signature = $cookie_signature;
                } else {
                    //accessToken刷新期限为过期
                    if ($data->refresh_expire_time > time() && !empty($data->refresh_token)) {
                        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $this->appId . '&grant_type=refresh_token&refresh_token=' . $data->refresh_token;
                        $res = json_decode($this->http_get($url));
                        if (isset($res->access_token)) {
                            $jsAccessToken = $res->access_token;
                            $newGetData = $res;
                            //删除旧的log
                            unlink($this->jsAccessTokenDir . '/' . $filename);
                        }
                    }
                }
            }
        }

        //为空重新获取
        if (empty($jsAccessToken)) {
            //检查是否为关注用户从公众号访问中静默跳转访问
            if (isset($_GET['code']) && !empty($_GET['code'])) {
                $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appId . '&secret=' . $this->appSecret . '&code=' . $_GET['code'] . '&grant_type=authorization_code';

                $res = json_decode($this->http_get($url));
                if (isset($res->access_token)) {
                    $newGetData = $res;
                    $jsAccessToken = $res->access_token;
                }
            }
        }
        $this->jsAccessToken = $jsAccessToken;

        //新获取的数据，重新写入log
        if (false !== $newGetData) {
            unset($newGetData->expires_in);
            $this->openId = $newGetData->openid;
            $newGetData->expire_time = time() + 7000;
            $newGetData->refresh_expire_time = strtotime('+ 29 day');

            $cookie_signature = $this->createNonceStr(26);
            $filename = sha1($cookie_signature) . '.log';
            $this->set_log_file($filename, json_encode($newGetData));
            setcookie('cookie_signature', $cookie_signature, $newGetData->refresh_expire_time);
            $this->cookie_signature = $cookie_signature;
        }
    }


    public function get_snsapi_userinfo_code($url)
    {
        $openUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->appId . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=snsapi_userinfo&state=snsapi_userinfo#wechat_redirect';

        $res = json_decode($this->http_get($openUrl));
        return $res;
    }

    public function get_snsapi_base_code($url)
    {
        $openUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->appId . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=snsapi_base&state=snsapi_base#wechat_redirect';

        $res = json_decode($this->http_get($openUrl));
        return $res;
    }

    /**
     * 获取账户基本信息
     * @return array
     */
    public function getCustomerInfo()
    {
        $info = [];
        //先获取JS-SDK access_token
        $this->getJsAccessToken();

        $info['openId'] = $this->openId;
        $info['cookie_signature'] = $this->cookie_signature;
        return $info;
    }


    /**
     * 载入微信用户详细信息
     * @return bool|mixed
     */
    public function getCustomerDetail()
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $this->jsAccessToken . '&openid=' . $this->openId . '&lang=zh_CN';

        $detail = json_decode($this->http_get($url), true);
        if(isset($detail['unionid'])) {
            $this->unionId = $detail['unionid'];
        }
        switch (true) {
            case isset($detail['errcode']) && !empty($detail['errcode']):
                return $detail['errcode'];
                break;

            case !isset($detail['openid']):
                return false;
                break;

            default:
        }
        return $detail;
    }


    /**
     * getSignPackage
     * @return array
     */
    public function getSignPackage()
    {
        $signPackage = ['appId' => $this->appId, 'nonceStr' => '', 'timestamp' => '', 'url' => '', 'signature' => '', 'rawString' => ''];
        $jsApiTicket = $this->getJsApiTicket();
        if (false !== $jsApiTicket) {
            // 注意 URL 一定要动态获取，不能 hardcode.
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            $timestamp = time();
            $nonceStr = $this->createNonceStr();

            // 这里参数的顺序要按照 key 值 ASCII 码升序排序
            $string = "jsapi_ticket=$jsApiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

            $signature = sha1($string);
            $signPackage = array(
                "appId" => $this->appId,
                "nonceStr" => $nonceStr,
                "timestamp" => $timestamp,
                "url" => $url,
                "signature" => $signature,
                "rawString" => $string
            );
        }
        return $signPackage;
    }

    /**
     * createNonceStr
     * @param int $length
     * @return string
     */
    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * getJsApiTicket
     * @return mixed
     */
    private function getJsApiTicket()
    {
        $jsapi_ticket = '';
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $typeName = 'jsapi_ticket';
        if ($jsapi_ticket = $this->db_get_auth($typeName)) {
            return $jsapi_ticket;
        }

        //空值，重新获取
        $accessToken = $this->getAccessToken();
        if (false !== $accessToken) {
            // 如果是企业号用以下 URL 获取 ticket
            //$url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->http_get($url));
            if (isset($res->ticket)) {
                $expire = $res->expires_in ? intval($res->expires_in) - 100 : 3600;
                $this->db_set_auth($typeName, $res->ticket, $expire);
                return $res->ticket;
            }
        }

        return false;
    }

    /**
     * 微信基础 access token
     * getAccessToken
     * @return string
     */
    private function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例

        $typeName = 'access_token';
        //自定义装载 CC
        if ($access_token = $this->db_get_auth($typeName)) {
            return $access_token;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        $result = $this->http_get($url);

        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $access_token = $json['access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            //$this->setCache($authname, $this->access_token, $expire);
            $this->db_set_auth($typeName, $access_token, $expire);
            return $access_token;
        }
        return false;
    }

    /**
     * GET 请求
     * @param string $url
     * @return boolean|string
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * httpGet
     * @param $url
     * @return mixed
     */
    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }


    /**
     * get_log_file
     * @param $filename
     * @return string
     */
    private function get_log_file($filename)
    {
        //改变路径
        $filename = $this->jsAccessTokenDir . $filename;
        if (is_file($filename)) {
            return trim(file_get_contents($filename));
        } else {
            return '';
        }
    }

    private function set_log_file($filename, $content)
    {
        //改变路径
        $filename = $this->jsAccessTokenDir . $filename;
        $fp = fopen($filename, "w+");
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * db_get_auth 自定义通过数据库方式获取auth验证内容
     * @param $type
     * @return false|string
     */
    public function db_get_auth($type)
    {
        if (!in_array($type, ['access_token', 'jsapi_ticket', 'api_ticket'])) {
            return false;
        }

        $where = [
            'type = ' . $type,
            'gzh_id =' . $this->gzh_id
        ];
        $info = Db::table('wechat_gzh_session')->where($where)->find('content, expired_time');

        if (!empty($info)) {
            if ($info['expired_time'] > time()) {
                $value = $info['content'];
                //检测是否有效
                //$this->getOauthAuth($access_token);
                return $value;
            } else {
                Db::table('wechat_gzh_session')->where($where)->delete();
                //$value = $this->getOauthRefreshToken();
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * db_set_auth 自定义通过数据库方式写入auth验证内容
     * @param $type
     * @param $content
     * @param $expire
     */
    public function db_set_auth($type, $content, $expire)
    {
        $gzh_id = $this->gzh_id;
        if (in_array($type, ['access_token', 'jsapi_ticket', 'api_ticket'])) {
            $where = [
                'type = ' . $type,
                'gzh_id =' . $gzh_id
            ];
            Db::table('wechat_gzh_session')->where($where)->delete();

            $data = [
                'type' => $type,
                'gzh_id' => $gzh_id,
                'expired_time' => time() + $expire,
                'create_time' => time(),
                'content' => $content
            ];
            Db::table('wechat_gzh_session')->insert($data);
        }
    }

    /**
     * db_del_auth
     * @param $type
     * @return false|string
     */
    public function db_del_auth($type)
    {
        $gzh_id = $this->gzh_id;
        if (in_array($type, ['access_token', 'jsapi_ticket', 'api_ticket'])) {
            $where = [
                'type = ' . $type,
                'gzh_id =' . $gzh_id
            ];
            Db::table('wechat_gzh_session')->where($where)->delete();
        }
    }
}

