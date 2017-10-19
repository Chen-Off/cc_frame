<?php
/**
 * 分页
 */
namespace cc;

use cc\Paginator\Bootstrap;

class Paginator
{
    /** @var bool 是否启用 */
    private static $start = false;

    /** @var int 数据总数 */
    private static $total = 0;

    /** @var  integer 每页的数量 */
    private static $listRows = 30;

    /** @var  array 当前页面数据 */
    private static $listData = [];

    /** @var  array 页眉 */
    private static $listHead = [];

    /** @var  integer 显示页码数 */
    private static $pageRows = 5;

    /** @var  integer 当前页码数 */
    private static $pageNow = 1;

    /** @var  string 页码URL */
    private static $pageUrl = '';

    /** @var  string 显示的项目名称 */
    private static $itemName = '项目';


    /** @var  string 页面显示风格 */
    private static $pageStyle = 'Bootstrap';

    /**
     * @var array AJAX 格式输出时需要的数据格式
     */
    private static $resultJson = [
        'page-prev' => false, //上一页是否存在
        'page-next' => false, //下一页是否存在
        'page-first' => 1,//第一页
        'page-last' => 1,//最后一页
        'page-now' => 1,    //当前页码
        'page-url' => '', //页码按钮URL

        'total-all' => 0,//数据总数
        'total-current' => 0,//当前页面数据数量

        'list-rows' => 0, //每页显示数量
        'list-head' => [],//当前页面页眉
        'list-data' => [],//当前页面数据集
    ];

    public static function start() {
        return self::$start;
    }

    /**
     * 载入页面风格
     * loaderStyle
     * @return Bootstrap
     */
    private static function loaderStyle()
    {
        $config = Config::getCB('paginator');
        $style = $config['page_style'];

        $class = '\\cc\\Paginator\\' . $style;

        //检测是否存在
        if (false === class_exists($class, false)) {
            $style = self::$pageStyle;
            $class = '\\cc\\Paginator\\' . $style;
        }

        self::$listRows = $config['list_limit'];
        self::$pageRows = $config['paging_items'];

        $obj = new $class();
        $obj->total = self::$total;
        $obj->listRows = self::$listRows;
        $obj->listHead = self::$listHead;
        $obj->listData = self::$listData;
        $obj->pageRows = self::$pageRows;
        $obj->pageNow = self::$pageNow;
        $obj->pageUrl = self::$pageUrl;
        $obj->itemName = self::$itemName;
        $obj->maxPage = (int)ceil(self::$total / self::$listRows);
        return $obj;
    }

    /**
     * 数据处理并输出
     * pageShow
     * @return string
     */
    public static function pageShow()
    {
        $btnHtml = '';

        //载入页面风格
        $styleObj = self::loaderStyle();

        //数据列表处理
        $btnHtml .= $styleObj->getPageList();

        //页码处理
        $btnHtml .= $styleObj->getPageBtn();
        unset($styleObj);
        return $btnHtml;
    }


    /**
     * 数据处理并输出JSON格式内容
     * pageJson
     * @return array
     */
    public static function pageJson()
    {
        $json = [
            'page-last' => ceil(self::$total / self::$listRows),//最后一页
            'page-now' => self::$pageNow,    //当前页码
            'page-url' => self::$pageUrl, //页码按钮URL

            'total-all' => self::$total,//数据总数

            'list-rows' => self::$listRows, //每页显示数量
            'list-head' => self::$listHead,//当前页面页眉
            'list-data' => self::$listData,//当前页面数据集
        ];

        $json = array_merge(self::$resultJson,$json);

        self::__define();
        return $json;
        //return cc__jsonEncodeToJs($json);
    }

    /**
     * 释放内存
     * __define
     */
    public static function __define() {
        self::$start = false;
        self::$itemName = '项目';
        self::$total = 0;
        self::$pageNow = 1;
        self::$pageRows = 5;
        self::$pageUrl = '';
        self::$pageStyle = 'Bootstrap';
        self::$listRows = 30;
        self::$listHead = [];
        self::$listData = [];
    }


    /**
     * 设置页面头部规则
     * setListHead
     * @param $array
     */
    public static function setListHead($array)
    {
        self::$listHead = $array;
        self::$start = true;
    }

    /**
     * 当前页面数据
     * setListData
     * @param $data
     */
    public static function setListData($data)
    {
        self::$listData = $data;
        self::$start = true;
    }

    /**
     * 设置当前页码
     * setPageNow
     * @param $page
     */
    public static function setPageNow($page)
    {
        if(!empty($page)) {
            self::$pageNow = is_numeric($page) ? $page : 1;
        }
    }

    /**
     * 设置项目名称
     * setItemName
     * @param $name
     */
    public static function setItemName($name)
    {
        self::$itemName = $name;
    }

    /**
     * 页码URL
     * setUrl
     * @param $pageUrl
     */
    public static function setUrl($pageUrl)
    {
        self::$pageUrl = $pageUrl;
    }

    /**
     * 数据总数
     * setTotal
     * @param $total
     */
    public static function setTotal($total)
    {
        if (is_numeric($total)) {
            self::$total = $total;
        }
    }

    /**
     * 每页的数量
     * setRows
     * @param $rows
     */
    public static function setRows($rows)
    {
        if (is_numeric($rows)) {
            self::$listRows = $rows;
        }
    }

}