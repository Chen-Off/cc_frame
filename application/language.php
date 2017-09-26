<?php
/**
 * 全站通用语言包
 * @author Chen<2795265136@qq.com>
 * @copyright 2016 (OA 管理系统)
 */
namespace CommonLanguage;

class Common_Language{
    public $common, $home, $action, $view, $status, $select, $create, $add, $update, $delete, $sex, $paging, $ajax;

    public function common($key = null) {
        switch ($key) {
            case 'params_error' : $text = ' 不是一个合法有效的参数'; break;
            case 'title' : $text = ' 默认标题'; break;
            default: $text = '';
        }
        return $text;
    }

    public function home($key = null) {
        switch ($key) {
            case 'name' : $text = '首页'; break;
            default: $text = '';
        }
        return $text;
    }

    public function action($key = null) {
        switch ($key) {
            case 'manage' : $text = '管理'; break;
            case 'get' : $text = '获取'; break;
            case 'edit' : $text =  '编辑'; break;
            case 'update' : $text =  '更新'; break;
            case 'add' : $text =  '添加'; break;
            case 'save' : $text =  '保存'; break;
            case 'create' : $text =  '生产'; break;
            case 'delete' : $text =  '删除'; break;
            case 'upload' : $text =  '上传'; break;
            case 'modify' : $text =  '修改'; break;
            case 'download' : $text =  '下载'; break;
            default: $text = '';
        }
        return $text;
    }

    public function create($key = null) {
        switch ($key) {
            case 'success' : $text = '创建成功'; break;
            case 'error' : $text = '创建失败'; break;
            default: $text = '';
        }
        return $text;
    }

    public function add($key = null) {
        switch ($key) {
            case 'success' : $text = '添加成功'; break;
            case 'error' : $text = '添加失败'; break;
            case 'exist' : $text = '已经存在'; break;
            default: $text = '';
        }
        return $text;
    }

    public function delete($key = null) {
        switch ($key) {
            case 'success' : $text = '删除成功'; break;
            case 'fail' : $text = '删除失败'; break;
            default: $text = '';
        }
        return $text;
    }

    public function update($key) {
        switch ($key) {
            case 'success' : $text = '更新成功'; break;
            case 'fail' : $text = '更新失败'; break;
            case 'no_change' : $text = '数据没有更新'; break;
            case 'no_exists' : $text = '不存在该数据，无法更新'; break;
            default: $text = '';
        }
        return $text;
    }

    public function view($key = null) {
        switch ($key) {
            case 'information' : $text = '详情'; break;
            case 'onShow' : $text = '展开'; break;
            case 'onHidden' : $text = '隐藏'; break;
            default: $text = '';
        }
        return $text;
    }

    public function status($key = null) {
        switch ($key) {
            case 'name' : $text = '状态'; break;
            case 'use' : $text = '启用'; break;
            case 'stop' : $text = '停用'; break;
            case 0 : $text = '启用'; break;
            case 1 : $text = '停用'; break;
            case 2 : $text = '关闭'; break;
            default: $text = '';
        }
        return $text;
    }
    public function select($key = null) {
        switch ($key) {
            case 'default' : $text = '请选择'; break;
            case 'name' : $text = '请选择'; break;
            case 'open' : $text = '开启'; break;
            case 'close' : $text = '关闭'; break;
            case 'yes' : $text = '是'; break;
            case 'no' : $text = '否'; break;
            case 'pending' : $text = '待审核'; break;
            case 'finished' : $text = '开放'; break;
            case 'discard' : $text = '废弃'; break;
            default: $text = '';
        }
        return $text;
    }

    public function ajax($key = null) {
        switch ($key) {
            case 'result_empty' : $text = '没有内容结果可供使用(请检查条件或者数据源)'; break;
            default: $text = '';
        }
        return $text;
    }

    public function paging($key = null) {
        switch ($key) {
            case 'previous' : $text = '上一页'; break;
            case 'next' : $text = '下一页'; break;
            case 'all' : $text = '共'; break;
            case 'page' : $text = '页'; break;
            case 'items' : $text = '个项目'; break;
            default: $text = '';
        }
        return $text;
    }

    public function sex($key = null) {
        switch ($key) {
            case 0 : $text = '未知'; break;
            case 1 : $text = '男士'; break;
            case 2 : $text = '女士'; break;
            case 'd' : $text = '未知'; break;
            case 'm' : $text = '男士'; break;
            case 'w' : $text = '女士'; break;
            default: $text = '';
        }
        return $text;
    }
}