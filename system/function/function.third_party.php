<?php
use Overtrue\Pinyin\Pinyin;

/**
 * 加载第三方插件
 * load_third_party
 * @param $name
 * @return Pinyin|PHPExcel
 */

function loadThirdParty($name)
{
    $path = THIRD_PARTY_PATH . '/' . $name;

    if (is_dir($path)) {
        switch ($name) {
            case 'PHPExcel':
                require_once $path . '/Classes/PHPExcel.php';
                require_once $path . '/Classes/PHPExcel/Writer/Excel2007.php';
                //return new PHPExcel_Reader_Excel2007();
                break;

            case 'PinYin':
                require_once $path . '/autoload.php';
                return new Pinyin();
                break;
        }
    }
}
