<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/22
 * Time: 16:00
 */


/**
 * pagingBreadcrumbs
 * @param $pagingData
 * @param array $urlData
 * @return string
 */
function pagingBreadcrumbs($pagingData, $urlData = [])
{
    $pagesOpton = \cc\Config::CB('Paginator');
    $pages_paging_items = $pagesOpton['paging_items'];
    $pages_list_limit = $pagesOpton['list_limit'];
    if (empty($urlData)) {
        $urlData = [URL_MODULES, URL_MODEL, URL_ACTION];
    }
    $now_paging = empty($pagingData[0]) ? 0 : $pagingData[0];
    $now_paging++;
    $max_paging = ceil($pagingData[1] / $pages_list_limit);

    //第一页 + 上一页
    if ($now_paging == 1) {
        $min_url = '';
        $prev_url = '';
    } else {
        $urlData[3] = '';
        $min_url = ' href="' . createUrl($urlData) . '"';
        $urlData[3] = $now_paging - 1;
        $prev_url = ' href="' . createUrl($urlData) . '"';
    }
    $bofHtml = '<li><a' . $prev_url . '><i class="fa fa-chevron-left"></i></a></li>';
    $bofHtml .= '<li><a' . $min_url . '>第一页</a></li>';


    //分页内容
    $surplus = array('prev' => false, 'next' => false);
    $pages_paging_items_2 = floor($pages_paging_items / 2);
    if($max_paging > $pages_paging_items) {
        if ($now_paging == 1 || $now_paging < $pages_paging_items) {
            $star_i = 1;
            $surplus['prev'] = false;
        } elseif ($now_paging >= $pages_paging_items && ($now_paging + $pages_paging_items_2) <= $max_paging) {
            $star_i = $now_paging - $pages_paging_items_2;
            $surplus = array('prev' => true, 'next' => true);
        } else {
            $star_i = $max_paging - $pages_paging_items + 1;
        }

        $over_i = $star_i + $pages_paging_items - 1;
    } else {
        $star_i = 1;
        $over_i = $max_paging;
    }


    //中间部分
    $pagingHtml = '';
    if ($surplus['prev'] === true) $pagingHtml .= '<li><a>...</a></li>';
    for ($i = $star_i; $i <= $over_i; $i++) {
        if ($now_paging == $i) {
            $url = '';
            $bg_class = 'active';
        } else {
            $urlData[3] = $i;
            $url = ' href="' . createUrl($urlData) . '"';
            $bg_class = '';
        }

        $pagingHtml .= '<li  class="' . $bg_class . '"><a ' . $url . '>' . $i . '</a></li>';
        if ($i == $max_paging) break;
    }
    if ($surplus['next'] === true) $pagingHtml .= '<li><a>...</a></li>';




    //最后一页 + 下一页
    if ($now_paging == $max_paging) {
        $next_url = '';
        $max_url = '';
    } else {
        $urlData[3] = $max_paging;
        $max_url = ' href="' . createUrl($urlData) . '"';
        $urlData[3] = $now_paging + 1;
        $next_url = ' href="' . createUrl($urlData) . '"';
    }

    $eofHtml = '<li><a' . $max_url . '>末页</a></li>';
    $eofHtml .= '<li><a' . $next_url . '><i class="fa fa-chevron-right"></i></a></li>';



    //$html = '<div class="col-sm-4 text-right text-center-xs">' . PHP_EOL;
    //$html .= '<ul class="pagination pagination-sm m-t-none m-b-none">' . PHP_EOL;
    $html = $bofHtml . $pagingHtml . $eofHtml;
    //$html .= '</ul></div>';


    return $html;
}


function pagingBreadcrumbs2($pagingData, $urlData = [])
{
    if (empty($urlData)) {
        $urlData = ['modules' => URL_MODULES, 'model' => URL_MODEL, 'action' => URL_ACTION];
    }
    $html = '';
    $now_paging = $pagingData['now_paging'];
    $max_paging = $pagingData['max_paging'];
    $url_p = '-' . $pagingData['params'];

    $html .= '<ul class="pages_paging"><li>' . PHP_EOL;
    //数据统计
    $html .= '<div id="paging_total">' . $pagingData['count'] . '项 / 共' . $max_paging . '页</div>' . PHP_EOL;

    $html .= '<div id="paging_items">';

    //上一页
    if ($now_paging == 1) {
        $url = '';
        $bg_class = 'paging_color_none';
    } else {
        $url = 'href="' . createUrl($urlData['modules'], $urlData['model'], $urlData['action'], ($now_paging - 1) . $url_p) . '"';
        $bg_class = '';
    }
    $html .= '<a class="w_70 ' . $bg_class . '" ' . $url . '"><i class="icon-angle-left m_r_5"></i>上一页</a>';

    //分页内容
    $pages_paging_items_2 = floor($pages_paging_items / 2);
    if ($now_paging == 1 || $now_paging < $pages_paging_items) {
        $star_i = 1;
        $surplus = array('prev' => false, 'next' => true);
    } elseif ($now_paging >= $pages_paging_items && ($now_paging + $pages_paging_items_2) <= $max_paging) {
        $star_i = $now_paging - $pages_paging_items_2;
        $surplus = array('prev' => true, 'next' => true);
    } else {
        $star_i = $max_paging - $pages_paging_items + 1;
        $surplus = array('prev' => true, 'next' => false);
    }

    $over_i = ($pagingData['max_paging'] == 0) ? 1 : $star_i + $pages_paging_items - 1;
    //中间部分
    if ($surplus['prev'] === true) $html .= '<a>...</a>';
    for ($i = $star_i; $i <= $over_i; $i++) {
        if ($now_paging == $i) {
            $url = '';
            $bg_class = 'paging_now';
        } else {
            $url = 'href="' . createUrl($urlData['modules'], $urlData['model'], $urlData['action'], $i . $url_p) . '"';
            $bg_class = '';
        }

        $html .= '<a class="' . $bg_class . '" ' . $url . '>' . $i . '</a>';
        if ($i == $max_paging) break;
    }
    if ($surplus['next'] === true) $html .= '<a>...</a>';


    //下一页
    if ($now_paging == $max_paging) {
        $url = '';
        $bg_class = 'paging_color_none';
    } else {
        $url = 'href="' . createUrl($urlData['modules'], $urlData['model'], $urlData['action'], ($now_paging + 1) . $url_p) . '"';
        $bg_class = '';
    }

    $html .= '<a class="w_70 ' . $bg_class . '" ' . $url . '>下一页<i class="icon-angle-right m_l_5"></i></a>';

    $html .= '</div>' . PHP_EOL;
    $html .= '</div></li></ul>' . PHP_EOL;
    return $html;
}