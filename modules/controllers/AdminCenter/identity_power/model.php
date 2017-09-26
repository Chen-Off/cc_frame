<?php
namespace AdminCenter\Model;

use cc\Db;
use cc\Msg;
use cc\View;
use CommonClass\Common_Class;

class identity_power_model extends Common_Class
{
    private $mangerPowerFunction = 19;//管理员管理项目


    public function base_power_post($post, $level)
    {
        if (!isset($post['s_f']) || empty($post['s_f']) || !is_array($post['s_f'])) {
            Msg::add_session('请先选择要授权的功能');
            return;
        }

        //检测所提交的ID时候都是存在的
        $fiDArr = $this->get_function_data(3);
        $fiDArr = array_column($fiDArr, 'function_title','function_id');

        $newFArr = [];
        foreach ($post['s_f'] as $lv1 => $arr2) {
            if (!isset($fiDArr[$lv1])) {
                Msg::add_session('非法的要授权的功能ID【modules级别】');
                return;
            }
            foreach ($arr2 as $lv2 => $arr3) {
                if (!isset($fiDArr[$lv2])) {
                    Msg::add_session('非法的要授权的功能ID【model级别】');
                    return;
                }

                foreach ($arr3 as $lv3) {
                    if (!isset($fiDArr[$lv3])) {
                        Msg::add_session('非法的要授权的功能ID 【action级别】');
                        return;
                    }

                    $newFArr[$lv3] = $fiDArr[$lv3];
                }

                $newFArr[$lv2] = $fiDArr[$lv2];
            }
            $newFArr[$lv1] = $fiDArr[$lv1];
        }

        //删除旧的授权
        Db::table('admin_level_power')->where('admin_level_id = '.$level)->delete();

        //添加新的授权
        foreach ($newFArr as $fId=> $fTitle) {
            $insert = [
                'admin_level_id' => $level,
                'function_id' => $fId
            ];
            $newID = Db::table('admin_level_power')->insert($insert);


            if ($newID == 0) {
                Msg::add_session('【'.$fTitle.'】授权失败，请联系管理员');
            }
        }
    }


    /**
     * get_admin_level 获取除管理员外的基本帐号权限名称
     * @return array
     */
    public function get_admin_level()
    {
        $where = ['admin_level_id > 1'];
        $qr = Db::table('admin_level')->where($where)->select();
        return $qr;
    }

    public function auth_power_post($post)
    {
        //检测参数
        if (!isset($post['a_id']) || !is_numeric($post['a_id']) || empty($post['a_id'])) {
            Msg::add_session('请先选择操作员');
            return;
        }

        if (false === $this->check_auth_admin_exist($post['a_id'])) {
            Msg::add_session('操作员非法');
            return;
        }

        if (!isset($post['auth_c_id']) || !is_numeric($post['auth_c_id']) || empty($post['auth_c_id'])) {
            Msg::add_session('请先选择特别授权');
            return;
        }

        if (false === $this->check_auth_content_exist($post['auth_c_id'])) {
            Msg::add_session('特别授权非法');
            return;
        }

        //检测是否已经授权过了
        if (true === $this->check_auth_in_admin_exist($post['auth_c_id'], $post['a_id'])) {
            Msg::add_session('该操作员已经拥有该授权内容了');
            return;
        }


        //新增授权
        $insert = [
            'admin_id' => $post['a_id'],
            'auth_c_id' => $post['auth_c_id'],
            'auth_time' => time(),
        ];
        $newID = Db::table('admin_auth_power')->insert($insert);
        if ($newID > 0) {
            Msg::add_session('授权成功', 'success');
        } else {
            Msg::add_session('授权失败，请联系管理员');
        }
    }

    private function check_auth_in_admin_exist($cID, $aID)
    {
        $where = [
            'auth_c_id = ?',
            'admin_id = ?',
            'status = 1'
        ];
        $rs = Db::table('admin_auth_power')->where($where)->bind([$cID, $aID])->find('auth_id');
        if (empty($rs)) {
            return false;
        } else {
            return true;
        }
    }

    private function check_auth_content_exist($cID)
    {

        $where = [
            'auth_c_id = ?',
            'status = 1'
        ];
        $rs = Db::table('admin_auth_content')->where($where)->bind(0, $cID)->find('auth_c_id');
        if (empty($rs)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检测操作员帐号是否可用
     * check_auth_admin_exist
     * @param $aID
     * @return bool
     */
    private function check_auth_admin_exist($aID)
    {
        $where = [
            'admin_id = ?',
            'status = 1'
        ];
        $rs = Db::table('admin')->where($where)->bind(0, $aID)->find('admin_id');
        if (empty($rs)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * get_auth_in_admin 获取已经授权的
     * @return array
     */
    public function get_auth_in_admin()
    {
        $where = [
            't1.status = 1',
            't2.status = 1',
            't3.status = 1',
        ];

        $table = 'admin_auth_power t1';
        $join = [
            'admin_auth_content t2 ON t2.auth_c_id = t1.auth_c_id',
            'admin t3 ON t3.admin_id = t1.admin_id'

        ];
        $select = 't3.admin_name, t2.auth_c_name, t1.auth_time';
        $query = Db::table($table)->join($join)->where($where)->select($select);
        return $query;
    }

    /**
     * 获取所有的可用的特别授权
     * get_auth_content_list
     * @return array
     */
    public function get_auth_content_list()
    {
        $query = Db::table('admin_auth_content')->where('status =1')->select();
        return $query;
    }

    /**
     * get_auth_admin 获取所有可授权的操作员
     * @return array
     */
    public function get_auth_admin()
    {
        $where = [
            't1.status = 1',
            't1.admin_level_id > 1',
            't1.admin_level_id < 4'
        ];
        $join = [
            'admin_groups ag ON ag.group_id = t1.group_id',
            'admin_level al ON al.admin_level_id = t1.admin_level_id'
        ];
        $select = 't1.admin_id, t1.admin_name, ag.group_name, al.admin_level_name';
        $order = 't1.group_id ASC, t1.admin_level_id ASC';
        $query = Db::table('admin t1')->join($join, 'LEFT')->where($where)->order($order)->select($select);
        return $query;
    }



    /**
     * auth_content_post 添加新的需要独立授权功能
     * @param $post
     */
    public function auth_content_post($post)
    {
        //参数检测
        if (!isset($post['auth_c_name']) || empty($post['auth_c_name'])) {
            Msg::add_session('请先填写授权功能名称');
            return;
        }

        //检测授权功能名称时候已经存在
        if (true === $this->check_auth_c_name_exist($post['auth_c_name'])) {
            Msg::add_session('请先填写授权功能名称');
            return;
        }

        if (!isset($post['s_f']) || empty($post['s_f']) || !is_array($post['s_f'])) {
            Msg::add_session('请先选择要授权的功能');
            return;
        }
        //检测所提交的ID时候都是存在的
        $fiDArr = $this->get_function_data(3);
        $fiDArr = array_column($fiDArr, 'function_name', 'function_id');

        $ID_json = [];
        foreach ($post['s_f'] as $lv1 => $arr2) {
            if (!isset($fiDArr[$lv1])) {
                Msg::add_session('非法的要授权的功能ID【modules级别】');
                return;
            }

            foreach ($arr2 as $lv2 => $arr3) {
                if (!isset($fiDArr[$lv2])) {
                    Msg::add_session('非法的要授权的功能ID【model级别】');
                    return;
                }

                foreach ($arr3 as $lv3) {
                    if (!isset($fiDArr[$lv3])) {
                        Msg::add_session('非法的要授权的功能ID 【action级别】');
                        return;
                    }

                    $ID_json[$lv3] = $fiDArr[$lv3];
                }

                $ID_json[$lv2] = $fiDArr[$lv2];
            }
            $ID_json[$lv1] = $fiDArr[$lv1];
        }


        //生成JSON数组
        $ID_json = array_unique($ID_json);
        $ID_json = json_encode($ID_json);

        //写入数据库
        $insert = [
            'auth_c_name' => '?',
            'auth_c_json' => '?',
        ];

        $bind = [$post['auth_c_name'], $ID_json];
        $newID = Db::table('admin_auth_content')->bind($bind)->insert($insert);
        if ($newID > 0) {
            Msg::add_session('新的授权功能设置成功', 'success');
        } else {
            Msg::add_session('新的授权功能设置失败，请联系管理员');
        }
    }


    private function check_auth_c_name_exist($name)
    {
        $where = [
            'auth_c_name = ?'
        ];
        $rs = Db::table('admin_auth_content')->where($where)->bind(0, $name)->find('auth_c_id');
        if (empty($rs)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * get_function_array 获取指定身份功能数据并转化成特殊数组
     * @param null $type
     * @param int $id
     * @return string
     */
    public function get_function_array($type = null,$id = 0)
    {
        $rankArr = [];
        switch ($type) {
            case 'level':
                $rankArr = $this->get_level_power_data($id);
                break;
        }
        $data = $this->get_function_data(3);
        $result = [1 => [], 2 => []];
        foreach ($data as $v) {
            $f_lv = $v['function_level'];
            $f_id = $v['function_id'];
            $f_name = $v['function_name'];
            $f_title = $v['function_title'];
            $f_p_id = $v['parent_id'];
            //$f_order = $v['sort_order'];

            $active = in_array($f_id, $rankArr) ? 1 : 0;

            switch ($f_lv) {
                case 1:
                    $result[$f_lv][$f_id] = [
                        'id' => $f_id,
                        'name' => $f_name,
                        'title' => $f_title,
                        'active' => $active,
                        'sub' => []
                    ];
                    break;


                case 2:
                    if (isset($result[1][$f_p_id])) {
                        $result[1][$f_p_id]['sub'][$f_id] = [
                            'name' => $f_name,
                            'id' => $f_id,
                            'title' => $f_title,
                            'active' => $active,
                        ];
                        $result[2][$f_id] = [];
                    }

                    break;


                case 3:
                    if (isset($result[2][$f_p_id])) {
                        $result[2][$f_p_id][$f_id] = [
                            'name' => $f_name,
                            'id' => $f_id,
                            'title' => $f_title,
                            'active' => $active,
                        ];
                    }
                    break;
            }

        }

        $json = 'var f_json_1 = \'' . json_encode($result[1], JSON_UNESCAPED_UNICODE) . '\';
        var f_json_2 = \'' . json_encode($result[2], JSON_UNESCAPED_UNICODE) . '\';
        ';

        return $json;
    }

    /**
     * get_level_power_data 获取指定身份访问权限
     * @param $level
     * @return array
     */
    private function get_level_power_data($level) {
        $where = ['admin_level_id = '.$level];
        $qr = Db::table('admin_level_power')->where($where)->select();
        $result = array_column($qr, 'function_id');
        return $result;
    }

    public function action_set_post($post)
    {
        $result = ['status' => 'error', 'msg' => '非法参数'];
        $post = json_decode($post);

        //BOF 参数检测
        switch (true) {
            case !isset($post->f_title) || !isset($post->f_name) || !isset($post->f_level) || !isset($post->f_parent_1) || !isset($post->f_parent_2_ico) || !isset($post->f_parent_2) || !isset($post->f_sort) || !isset($post->f_show):
                $msg = '非法参数';
                break;
            case !in_array($post->f_level, [1, 2, 3]):
                $msg = '非法等级';
                break;

            case !in_array($post->f_show, [1, 0]):
                $msg = '非法的菜单显示';
                break;

            case !is_numeric($post->f_sort):
                $msg = '非法的排序';
                break;

            case !is_numeric($post->f_parent_1):
                $msg = '非法父模块';
                break;

            case !is_numeric($post->f_parent_2) :
                $msg = '非法子模块';
                break;

            case strlen($post->f_name) > 24 :
                $msg = '模块名称超长';
                break;

            case strlen($post->f_title) > 64 :
                $msg = '模块标题超长';
                break;


        }
        if (isset($msg)) {
            $result['msg'] = $msg;
            $this->js_json_exit($result);
        }

        //EOF 参数检测


        if ($post->f_level > 1) {
            //检测模块是否链接
            if (false === $this->check_action_link($post->f_level, $post->f_parent_1, $post->f_parent_2)) {
                $result['msg'] = '模块无法对应的上，请认真选择上级模块';
                $this->js_json_exit($result);
            }
        }

        //检测新的模块是否已经存在名称或者标题
        $parent = 0;
        $icon = '';
        switch ($post->f_level) {
            case 1:
                $parent = 0;
                $icon = '';
                break;
            case 2:
                $parent = $post->f_parent_1;
                $icon = $post->f_parent_2_ico;
                break;
            case 3:
                $parent = $post->f_parent_2;
                $icon = '';
                break;
        }
        if (true === $this->check_action_exist($post->f_name, $post->f_title, $parent, $post->f_level)) {
            $result['msg'] = '该级别下，模块名称或者标题已经存在了。禁止重名';
            $this->js_json_exit($result);
        }

        //写入数据库，并提示
        $data = [
            'function_name' => '?',
            'function_title' => '?',
            'parent_id' => '?',
            'function_level' => '?',
            'function_icon' => '?',
            'order_sort' => '?',
            'show_status' => '?',
        ];
        $bind = [
            $post->f_name, $post->f_title, $parent, $post->f_level, $icon, $post->f_sort, $post->f_show,
        ];

        Db::table('admin_program_function')->bind($bind)->insert($data);
        if (Db::getLastInsId() > 0) {
            $result['msg'] = '添加功能成功';
            $result['status'] = 'success';
        } else {
            $result['msg'] = '添加功能失败，请联系管理员';
        }

        $this->js_json_exit($result);
    }

    private function js_json_exit($array)
    {
        $this->page_header_code('json');
        die(json_encode($array, JSON_UNESCAPED_UNICODE));
    }

    /**
     * check_action_exist 检测新的模块是否已经存在名称或者标题
     * @param $name
     * @param $title
     * @param $parent
     * @param $fLv
     * @return bool
     */
    private function check_action_exist($name, $title, $parent, $fLv)
    {
        $where = [
            'parent_id =' . $parent,
            'function_level =' . $fLv
        ];
        $whereOr = [
            'function_name = ' . $name,
            'function_title = ' . $title
        ];
        $exist = Db::table('admin_program_function')->where($where)->whereOr($whereOr)->find('function_id');

        if (empty($exist)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检测模块是否链接
     * check_action_link
     * @param $level
     * @param $one
     * @param null $two
     * @return bool
     */
    private function check_action_link($level, $one, $two = null)
    {
        $where = ['function_id = ' . $one, 'function_level = 1', 'parent_id = 0'];
        $oneF = Db::table('admin_program_function')->where($where)->find('function_id');
        if (empty($oneF)) {
            return false;
        }

        if ($level == 3 && null !== $two) {
            $where = ['function_id = ' . $two, 'function_level = 2', 'parent_id = ' . $one];
            $twoF = Db::table('admin_program_function')->where($where)->find('function_id');
            if (empty($twoF)) {
                return false;
            }
        }
        return true;
    }

    /**
     * get_function_json
     * @return string
     */
    public function get_function_json()
    {
        $l1 = [];
        $l2 = [];
        $query = $this->get_function_data(2, true);
        foreach ($query as $v) {
            $f_lv = $v['function_level'];
            $f_id = $v['function_id'];


            if ($f_lv == 1) {
                $l1[$f_id] = [
                    'id' => $f_id,
                    'name' => $v['function_name'],
                    'title' => $v['function_title'],
                    'father' => $v['parent_id'],
                    'sort' => $v['order_sort'],
                ];
            } else {
                $l2[$f_id] = [
                    'id' => $f_id,
                    'name' => $v['function_name'],
                    'title' => $v['function_title'],
                    'father' => $v['parent_id'],
                    'sort' => $v['order_sort'],
                ];
            }
        }
        $result = 'var f1_json = \'' . json_encode($l1) . '\';
                var f2_json = \'' . json_encode($l2) . '\';';

        View::push('f_json', $result);
    }


    private function get_function_data($maxLv = null, $admin = false)
    {

        $where = [];
        if (null !== $maxLv) {
            $where[] = 'function_level <= ' . $maxLv;
        }
        if (false === $admin) {
            //管理员管理项目
            $admin_f_id = $this->mangerPowerFunction;
            $where[] = 'function_id != ' . $admin_f_id;
            $where[] = 'parent_id != ' . $admin_f_id;
        }
        $order = 'function_level ASC, order_sort ASC';
        $query = Db::table('admin_program_function')->where($where)->order($order)->select();
        //echo Db::getLastSql();die;
        return $query;
    }

}