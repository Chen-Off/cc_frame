<?php
namespace cc;
/**
 * Class Oauth 鉴权
 * @package Oauth
 */

/**
 * $user =[];
 */
class Oauth
{
    /**
     * 帐号详情
     * @var array
     */
    static $user = [
        'u_id' => 0,        //用户ID
        'u_name' => '',     //用户名称
        'u_gender' => '',   //用户性别
        'u_headImg' => '',  //用户头像
        'u_age' => 0,       //用户年龄


        'u_power' => 0,    //用户权限级别
        'u_power_name' => '',    //用户权限级别
        'u_group' => 0,    //用户所属操作组
        'u_group_name' => '',    //用户所属操作组

        'u_franchise' => 0,    //用户所属加盟店
        'u_franchise_name' => 0,    //用户所属加盟店
        'u_franchise_number' => 0,    //用户所属加盟店编号

        'info' => []
    ];

    static $page = [
        'modules_id' => 0,
        'model_id' => 0,
        'action_id' => 0
    ];

    //权限类型、名称
    static $power = [];
    static $powerName = [];
    static $powerShop = [];

    //特别授权页面
    static private $specialAuth = [];

    //常规授权页面
    static private $commonAuth = [];


    static function getCommonAuth() {
        return self::$commonAuth;
    }

    static function getSpecialAuth() {
        return self::$specialAuth;
    }

    private static $signToken;

    /**
     * OauthPage 访问页面是否存在
     */
    static function OauthPage()
    {
        if (true !== Config::CB('oauth', 'view_page') || URL_MODULES == 'Access') {
            return true;
        }

        $page_404 = createUrl('Access', 'page', 'page_404');
        $whereOr = ['function_name = ' . URL_MODULES];
        $whereOr[] = 'function_name = ' . URL_MODEL;
        $whereOr[] = 'function_name = ' . URL_ACTION;

        $select = 'function_id, function_name, parent_id, function_level';
        $query = Db::table('admin_program_function')->order('function_level asc')->select($select);

        $module_id = 0;
        $model_id = 0;
        $action_id = 0;
        foreach ($query as $v) {
            if (!empty($module_id) && !empty($model_id) && !empty($action_id)) {
                break;
            }

            $f_id = $v['function_id'];
            $f_name = $v['function_name'];
            $f_t_id = $v['parent_id'];

            //已经获取退出
            if ($f_id == 1 && !empty($module_id) ||
                $f_id == 2 && !empty($model_id) ||
                $f_id == 3 && !empty($action_id)
            ) {
                continue;
            }

            //判断
            switch ($v['function_level']) {
                //modules
                case 1:
                    if ($f_name == URL_MODULES) {
                        $module_id = $f_id;
                    }
                    break;
                //model
                case 2:
                    if (empty($module_id)) {
                        jumpUrl($page_404);
                    }
                    if ($f_name == URL_MODEL && $f_t_id == $module_id) {
                        $model_id = $f_id;
                    }
                    break;
                //action
                case 3:
                    if (empty($model_id)) {
                        jumpUrl($page_404);
                    }

                    if ($f_name == URL_ACTION && $f_t_id == $model_id) {
                        $action_id = $f_id;
                    }
                    break;
            }
        }

        //如果有一个不存在
        if (empty($module_id) || empty($model_id) || empty($action_id)) {
            jumpUrl($page_404);
        } else {
            self::$page['modules_id'] = $module_id;
            self::$page['model_id'] = $model_id;
            self::$page['action_id'] = $action_id;
        }

        return true;
    }

    /**
     * OauthSign 是否登录状态
     */
    static function OauthSign()
    {
        if (true !== Config::CB('oauth','account') || URL_MODULES == 'Access') {
            return true;
        }

        $token = '';
        $u_id = '';
        if (isset($_COOKIE['u'])) {
            $cookie = json_decode($_COOKIE['u'], true);
            if (isset($cookie['token']) && strlen($cookie['token']) == 32 &&
                isset($cookie['id']) && is_numeric($cookie['id'])
            ) {
                $token = $cookie['token'];
                $u_id = $cookie['id'];
            }
        } else {
            setcookie('u', '', time() - 1, '/');
        }

        if (isset($_SESSION['u'])) {
            $session = $_SESSION['u'];

            if (empty($token) && empty($u_id) &&
                isset($session['token']) && strlen($session['token']) == 32 &&
                isset($session['id']) && is_numeric($session['id'])
            ) {
                $token = $session['token'];
                $u_id = $session['id'];
            }
        } else {
            $token = '';
            $u_id = '';
        }


        if (!empty($token) && !empty($u_id)) {
            $where = ['admin_id = ' . $u_id, 'session_token = ' . $token];
            $expired_time = Db::table('admin_session')->where($where)->select('expired_time');
            if (!empty($expired_time)) {
                $expired_time = $expired_time[0]['expired_time'];
                if ($expired_time <= time()) {
                    $msg = '登录过期，请重新登录';
                    unset($_SESSION['u']);
                    setcookie('u', '', time() - 1, '/');
                } else {
                    $session = [
                        'token' => $token,
                        'id' => $u_id
                    ];

                    if (!isset($_COOKIE['u'])) {
                        setcookie('u', json_encode($session), $expired_time, '/');
                    }

                    if (!isset($_SESSION['u'])) {
                        $_SESSION['u'] = $session;
                    }
                    $msg = '';
                }
            } else {
                Db::table('admin_session')->where('admin_id = ' . $u_id)->delete();
                $msg = '登录过期，请重新登录';
            }
        } else {
            $msg = '请先登录';
        }
        //echo $msg;die;

        if (!empty($msg)) {
            //没有登录 跳转到登录页面
            $JumpUrl = createUrl('Access', 'sign', 'sign_in');
            jumpUrl($JumpUrl);
        }

        static::$signToken = $token;

        //已经登录且访问登录页面 跳转到首页
        createUrl('Index', 'index', 'index');
        return true;
    }

    /**
     * 获取常规权限内容
     * getCommonAuthPage
     * @return bool
     */
    static function getCommonAuthPage()
    {
        //载入缓存
        $cacheName = md5('power_common_'.static::$signToken);
        $data = static::getCache($cacheName);

        //生成新的缓存
        if(false === $data) {
            $power = static::$user['u_power'];
            $join = [];
            $where = [];
            if ($power != 1) {
                $join = ['admin_level_power t2 ON t2.function_id = t1.function_id'];
                $where[] = 't2.admin_level_id = ' . $power;
            }

            //获取特殊授权页面
            $table = 'admin_program_function t1';

            $order = 'order_sort asc';
            $select = 't1.*';
            $query = Db::table($table)->join($join)->where($where)->order($order)->select($select);
            $data = [];
            foreach ($query as $arr) {
                $data[$arr['function_id']] = $arr;
            }
            Cache::set($cacheName, $data);
        }

        static::$commonAuth = $data;
    }

    /**
     * getSpecialAuthPage 特别授权
     * @return bool
     */
    static function getSpecialAuthPage()
    {
        //载入缓存
        $cacheName = md5('power_auth_'.static::$signToken);
        $data = static::getCache($cacheName);

        //生成新的缓存
        if(false === $data) {
            //获取该帐号所有可用的已授权内容
            $authList = static::getAuthToAdmin($_SESSION['u']['id']);
            $data = [];

            //获取所有的页面
            $allFunction = !empty($authList) ? static::getALLFunctionPage() : [];
            foreach ($authList as $json) {
                $json = json_decode($json['auth_c_json'], true);
                if (is_array($json) || !empty($json)) {
                    foreach ($json as $id => $v) {
                        if (!isset($data[$id]) && isset($allFunction[$id])) {
                            $data[$id] = $allFunction[$id];
                        }

                    }
                }
            }
            $rs = Cache::set($cacheName, $data);
        }

        static::$specialAuth = $data;
    }

    /**
     * 载入缓存
     * getCache
     * @param $cacheName
     * @return bool|mixed
     */
    static private function getCache($cacheName) {
        if(true === Cache::has($cacheName)) {
            $cacheData = Cache::get($cacheName);
            if(!is_array($cacheData)) {
                Cache::rm($cacheName);
            } else {
                return $cacheData;
            }
        }
        return false;
    }

    /**
     * 获取所有的可用页面
     * getALLFunctionPage
     * @return array
     */
    static private function getALLFunctionPage()
    {
        $query = Db::table('admin_program_function')->select();
        $newArr = [];
        foreach ($query as $data) {
            $newArr[$data['function_id']] = $data;
        }
        return $newArr;
    }

    /**
     * getAuthToAdmin 获取该帐号所有可用的已授权内容
     * @param $aID
     * @return array
     */
    static private function getAuthToAdmin($aID)
    {
        $where = [
            't1.status = 1',
            't2.status = 1',
            't3.status = 1',
            't3.admin_id = ' . $aID,
        ];

        $table = 'admin_auth_power t1';
        $join = [
            'admin_auth_content t2 ON t2.auth_c_id = t1.auth_c_id',
            'admin t3 ON t3.admin_id = t1.admin_id'
        ];

        $select = 't2.auth_c_json';
        $query = Db::table($table)->join($join)->where($where)->select($select);


        return $query;
    }

    /**
     * 访问用户是否具备访问权限
     * OauthPower
     * @return bool
     */
    static function OauthPower()
    {
        if (true !== Config::CB('oauth', 'power') || URL_MODULES == 'Access') {
            return true;
        }

        self::getUserInfo();
        self::$power = Config::CE('admin_power');
        if(false === self::$power) {
            DeBug::msgExit('[Config Extend Error]: 错误的权限设定{admin_power}');
        }
        self::$powerName = Config::CE('admin_power_name');
        if(false === self::$powerName) {
            DeBug::msgExit('[Config Extend Error]: 错误的权限名称设定{admin_power_name}');
        }
        self::$powerShop = Config::CE('admin_power_shop');
        if(false === self::$powerShop) {
            DeBug::msgExit('[Config Extend Error]: 错误的权限设定{admin_power_shop}');
        }


        //获取常规授权页面
        static::getCommonAuthPage();

        //获取特殊授权页面
        static::getSpecialAuthPage();

        $page_no_power = createUrl('Access', 'page', 'no_power');
        $power = static::$user['u_power'];
        //管理员/特殊授权不计算
        if ($power == 1 ||
            isset(static::$commonAuth[self::$page['action_id']]) ||
            isset(static::$specialAuth[self::$page['action_id']])
        ) {
        } else {
            //没有权限访问 跳转到禁止页面
            jumpUrl($page_no_power);
        }


        //如果是加盟侧，获取加盟店信息
        if ($power == 5 || $power == 6) {
            $f_info = self::getFranchiseInfo();
            if (empty($f_info)) {
                //没有权限访问 跳转到禁止页面
                jumpUrl($page_no_power);
            }
            $user = [];
            $user['u_franchise'] = $f_info['franchise_id'];
            $user['u_franchise_number'] = $f_info['shop_number'];
            $user['u_franchise_name'] = $f_info['shop_name'];
            self::$user = array_merge(self::$user, $user);
        }


        return true;
    }

    /**
     * 获取加盟店数据详细
     * getFranchiseInfo
     * @return array
     */
    private static function getFranchiseInfo()
    {
        $join = 'franchise t2 ON t2.franchise_id = t1.franchise_id';
        $where = 't1.admin_id=' . static::$user['u_id'];
        $find = 't2.shop_number, t2.franchise_id, t2.shop_name';
        $info = Db::table('franchise_to_admin t1')->join($join)->where($where)->find($find);
        return $info;
    }

    /**
     * 获取帐号详细
     * getUserInfo
     */
    private static function getUserInfo()
    {
        //载入缓存
        $cacheName = md5('account_'.static::$signToken);
        $info = static::getCache($cacheName);
        if(false === $info) {
            //获取权限总数
            $query = Db::table('admin_level')->select();
            $powerArr = self::$power = array_column($query, 'admin_level_id');

            $join = ['admin_level t2 ON t2.admin_level_id = t1.admin_level_id'];
            $join[] = 'admin_groups t3 ON t3.group_id = t1.group_id';
            $where = ['t1.admin_id =' . $_SESSION['u']['id']];
            $find = 't1.admin_level_id, t1.group_id, t1.admin_name, t1.admin_email, t1.tel, t1.admin_id, t2.admin_level_name, t3.group_name';
            $info = Db::table('admin t1')->join($join, 'LEFT')->where($where)->find($find);

            if (empty($info) || !in_array($info['admin_level_id'], $powerArr)) {
                //没有权限访问 跳转到禁止页面
                jumpUrl(createUrl('Access', 'page', 'no_power'));
            }
            Cache::set($cacheName, $info);
        }

        //操作员信息设置
        $user['u_id'] = $_SESSION['u']['id'];
        $user['u_power'] = $info['admin_level_id'];
        $user['u_group'] = $info['group_id'];
        $user['u_group_name'] = $info['group_id'];
        $user['u_name'] = $info['admin_name'];
        $user['u_email'] = $info['admin_email'];
        $user['u_tel'] = $info['tel'];
        $user['u_power_name'] = $info['admin_level_name'];
        $user['u_group_name'] = $info['group_name'];

        self::$user = array_merge(self::$user, $user);
    }

}