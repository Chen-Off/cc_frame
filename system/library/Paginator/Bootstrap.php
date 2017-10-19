<?php

namespace cc\Paginator;

use cc\Config;

class Bootstrap
{
    public $total, $listRows,$listHead, $pageRows, $pageUrl, $pageNow, $listData, $itemName, $maxPage;
    public $listKeys = [];

    private $urlType = '';

    public function __construct()
    {
        
    }


    /**
     * 页码显示
     * getPageBtn
     * @return string
     */
    public function getPageBtn()
    {
        $html = '<footer class="panel-footer"><div class="row"><div class="col-sm-3 hidden-xs"></div>
<div class="col-sm-4 text-center"><small class="text-muted inline m-t-sm m-b-sm">' . $this->getCountShow() . '</small></div><div class="col-sm-5 text-right text-center-xs"><ul class="pagination pagination-sm m-t-none m-b-none">' . $this->getPagingShow() . '</ul></div></div></footer>';
        return $html;
    }

    /**
     * 处理数据
     * getPageList
     * @return string
     */
    public function getPageList() {
        $html = '<div class="table-responsive"><table class="table table-striped b-t b-light ng-scope">';
        $html .= $this->getListHead();
        $html .= $this->getListData();
        $html .= '</div></table>';
        return $html;
    }

    private function getListData() {
        $listKeys = $this->listKeys;
        $listData = $this->listData;


        $html = '<tbody class="ng-binding">';
        foreach ($listData as $data) {
            $html .= '<tr class="ng-scope">';
            //列表内容排序依靠页眉KEY排序
            foreach ($listKeys as $key => $style) {
                $str = isset($data[$key]) ? $data[$key] : '';
                $html .= '<td '.$style.'>'.$str.'</td>';
            }
            $html .= '<tr/>';
        }

        $html .= '</tbody>';
        return $html;
    }

    /**
     * 处理页眉
     * getListHead
     */
    private function getListHead() {
        $head = '<thead><tr>';
        $headData = $this->listHead;
        foreach ($headData as $key => $data) {
            $width = '';
            $style = '';
            if(is_array($data)) {
                if(isset($data['width'])) {
                    $width = ' width="'.$data['width'].'"';
                }
                if(isset($data['style'])) {
                    $style = ' class="'.$data['style'].'"';
                }
                $title = cc__isset($data, 'title', '空名');
            } else {
                $title = $data;
            }

            $head .= '<th'.$width.$style.'>'.$title.'</th>';
            $this->listKeys[$key] = $style;
        }
        $head .= '</tr></thead>';
        return $head;
    }


    /**
     * 获取页码按钮
     * getPagingShow
     */
    private function getPagingShow()
    {
        $html = '';

        $html .= $this->getPreviousButton();
        $html .= $this->getFirstButton();

        $html .= $this->getPageLinks();

        $html .= $this->getNextButton();
        $html .= $this->getLastButton();

        return $html;

    }

    /**
     * getCountShow 获取统计显示内容
     * @return string
     */
    private function getCountShow()
    {
        $pageNow = $this->pageNow;
        $listRows = $this->listRows;

        $endNumber = $pageNow * $listRows;
        $startNumber = $endNumber - $listRows;
        if ($startNumber < 0) {
            $startNumber = 0;
        }
        if ($endNumber > $this->total) {
            $startNumber = $this->total;
        }
        $countShow = '当前显示第 ' . $startNumber . ' - ' . $endNumber . ' 个' . $this->itemName . ' / 共 ' . $this->total . ' 个' . $this->itemName;
        return $countShow;
    }

    /**
     * getCountShow 获取普通的页码按钮
     * @return string
     */
    private function getPageLinks()
    {
        $linksHtml = '';
        $pageNow = $this->pageNow;
        $maxPage = $this->maxPage;
        $pageRows = $this->pageRows;


        //是否显示前置省略号
        if($pageNow > $pageRows) {
            $linksHtml .= $this->getDots();
        }

        if($pageNow > $pageRows) {
            $side = floor($pageRows / 2);
            $startPage = $pageNow - $side;
            $overPage = $pageNow + $side;
        } else {
            $startPage = 1;
            $overPage = $pageRows;
        }

        if($overPage > $maxPage) {
            $overPage = $maxPage;
        }

        for ($i = $startPage; $i <= $overPage; $i++) {
            if ($pageNow == $i) {
                $linksHtml .= $this->getActivePageWrapper($i);
            } else {
                $pageUrl = $this->getLinkUrl($i);
                $linksHtml .= $this->getAvailablePageWrapper($pageUrl, $i);
            }
        }

        //是否显示后置省略号
        if($overPage < $maxPage) {
            $linksHtml .= $this->getDots();
        }
        return $linksHtml;
    }

    /**
     * 下一页按钮
     * @return string
     */
    private function getNextButton()
    {
        if ($this->pageNow == $this->maxPage) {
            return '<li><span><i class="fa fa-chevron-right"></i></span></li>';
        } else {
            $nextUrl = ' href="' . $this->getLinkUrl(($this->pageNow + 1)) . '"';
            return '<li><a' . $nextUrl . '><i class="fa fa-chevron-right"></i></a></li>';
        }
    }

    /**
     * 末页按钮
     * @param $text
     * @return string
     */
    private function getLastButton($text = '末页')
    {
        if ($this->pageNow == $this->maxPage) {
            return $this->getDisabledTextWrapper($text);
        } else {
            $firstUrl = $this->getLinkUrl($this->maxPage);
            return $this->getAvailablePageWrapper($firstUrl, $text);
        }
    }

    /**
     * 上一页按钮
     * @return string
     */
    private function getPreviousButton()
    {
        if ($this->pageNow == 1) {
            return '<li><span><i class="fa fa-chevron-left"></i></span></li>';
        } else {
            $prevUrl = ' href="' . $this->getLinkUrl(($this->pageNow - 1)) . '"';
            return '<li><a' . $prevUrl . '><i class="fa fa-chevron-left"></i></a></li>';
        }
    }

    /**
     * 获取链接URL
     * getLinkUrl
     * @param $params
     * @return string
     */
    private function getLinkUrl($params) {
        $url = $this->pageUrl;
        if(empty($this->urlType)) {
            $this->urlType = Config::getCB('url_type');
        }

        switch ($this->urlType) {
            case 'static':
                $url .= '/'.$params;
                break;

            case 'dynamic':
                $url .= '?params='.$params;
                break;

            default:
                $url .= '/'.$params;
        }
        return $url;
    }

    /**
     * 首页按钮
     * @param $text
     * @return string
     */
    private function getFirstButton($text = '首页')
    {
        if ($this->pageNow == 1) {
            return $this->getDisabledTextWrapper($text);
        } else {
            $firstUrl = $this->pageUrl;
            return $this->getAvailablePageWrapper($firstUrl, $text);
        }
    }


    /**
     * 生成一个禁用的按钮
     *
     * @param  string $text
     * @return string
     */
    private function getDisabledTextWrapper($text)
    {
        return '<li class="disabled"><span>' . $text . '</span></li>';
    }

    /**
     * 生成一个激活的按钮
     *
     * @param  string $text
     * @return string
     */
    private function getActivePageWrapper($text)
    {
        return '<li class="active"><span>' . $text . '</span></li>';
    }

    /**
     * 生成省略号按钮
     *
     * @return string
     */
    private function getDots()
    {
        return $this->getDisabledTextWrapper('...');
    }


    /**
     * 生成一个可点击的按钮
     *
     * @param  string $url
     * @param  int $page
     * @return string
     */
    private function getAvailablePageWrapper($url, $page)
    {
        return '<li><a href="' . htmlentities($url) . '">' . $page . '</a></li>';
    }

}