<?php
/**
 * 准备制作自己的公共函数库
 * 开始时间：2016年11月5日 11:27:20
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/5
 * Time: 11:26
 */

/**
 * 获取上(N)级目录路径
 * cc__parentDir
 * @param $dir
 * @param int $parentLevel
 * @return array|string
 */
function cc__parentDir($dir, $parentLevel = 1) {
    $dir = str_replace('/', DS, $dir);
    if(substr($dir, -1) == DS) {
        $dir = substr($dir, 0, -1);
        $endDS = DS;
    } else {
        $endDS = '';
    }
    $dir = explode(DS, $dir);
    for ($i = 1; $i <= $parentLevel; $i++) {
        array_pop($dir);
    }
    $dir = implode(DS, $dir).$endDS;
    return $dir;
}

/**
 * 上传图片
 * 2017年8月19日 15:21:35
 * cc__uploadImg
 * @param $file
 * @param null $path
 * @param null $name
 * @return bool|null|string
 */
function cc__uploadImg($file, $path, $name = null)
{
    $imgType = ['image/png', 'image/jpeg', 'image/gif'];
    if (empty($file['type']) || !in_array($file['type'], $imgType)) {
        return false;
    } elseif (!is_dir($path)) {
        return false;
    }

    if (substr($path, -1) != DS) {
        $path .= DS;
    }

    if (null === $name) {
        $name = date('Ymd') . cc__getRandStr('all', 10) . '.jpeg';
    }

    $rs = move_uploaded_file($file['tmp_name'], $path . $name);
    if (true === $rs) {
        return $name;
    } else {
        return false;
    }
}

/**
 * EN JSON 给JS 使用的格式
 * 2017年8月19日 15:20:20
 * cc__jsonEncodeToJs
 * @param $json
 * @return mixed|string
 */
function cc__jsonEncodeToJs($json)
{
    $o = ['\\', "'", '"'];
    $n = ['\\\\', "\\'", '\"'];
    $json = json_encode($json, JSON_UNESCAPED_UNICODE);
    $json = str_replace($o, $n, $json);
    return $json;
}

/**
 * EN JSON 给JS 是否需要转义
 * 2017年8月19日 15:20:22
 * cc__jsonEncode
 * @param $json
 * @param bool $unicode
 * @return string
 */
function cc__jsonEncode($json, $unicode = false)
{
    if (true === $unicode) {
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
    } else {
        $json = json_encode($json);
    }
    return $json;
}

/**
 * 2017年8月21日 22:34:43
 * 判断函数变量是否存在
 * cc__isset
 * @param $array
 * @param $params
 * @param null $empty
 * @return bool
 */
function cc__isset($array, $params = [], $empty = null)
{
    $result = false;
    switch (true) {
        case empty($params) && isset($array):
            $result = $array;
            break;

        case !empty($params) && is_array($array):
            $params = !is_array($params) ? [$params] : $params;
            foreach ($params as $p) {
                if(is_array($array) && isset($array[$p])) {
                    $result = $array = $array[$p];
                } else {
                    $result = false;
                    break;
                }
            }
            break;

    }

    if (false !== $result && null !== $empty && empty($result)) {
        return false;
    }
    return $result;
}

/**
 * cc__getCookie 获取 Cooke 的某个值
 * @param $name
 * @return bool
 */
function cc__getCookie($name)
{
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    } else {
        return false;
    }
}

/**
 * cc__delCookie 删除 Cookie 的某个值
 * @param $name
 * @param $path
 * @return bool
 */
function cc__delCookie($name, $path = '/')
{
    if (isset($_COOKIE[$name])) {
        setcookie($name, '', time() - 1, $path);
        return true;
    } else {
        return false;
    }
}

/**
 * cc__setCookie 写入 Cookie 的某个值
 * @param $name
 * @param $value
 * @param $expiredTime
 * @param $path
 */
function cc__setCookie($name, $value, $expiredTime = 0, $path = '/')
{
    setcookie($name, $value, $expiredTime, $path);
}

/**
 * cc__getSession 获取session 的某个值
 * @param $name
 * @return bool
 */
function cc__getSession($name)
{
    if (isset($_SESSION[$name])) {
        return $_SESSION[$name];
    } else {
        return false;
    }
}

/**
 * cc__delSession 删除session 的某个值
 * @param $name
 * @return bool
 */
function cc__delSession($name)
{
    if (isset($_SESSION[$name])) {
        unset($_SESSION[$name]);
        return true;
    } else {
        return false;
    }
}

/**
 * cc__setSession 写入 session 的某个值
 * @param $name
 * @param $value
 */
function cc__setSession($name, $value)
{
    $_SESSION[$name] = $value;
}


/**
 * 检测是否为QQ号码 2017年9月15日 16:51:15
 * cc__checkQQ
 * @param $qq
 * @return bool
 */
function cc__checkQQ($qq) {
    if(!is_numeric($qq)) {
        return false;
    }
    $length = strlen($qq);
    if($length > 5 && $length < 20) {
        return true;
    } else {
        return false;
    }
}

/**
 * cc__checkPhone 检测是否是手机号码 2017年3月27日 10:06:35
 * @param $phone
 * @return bool
 */
function cc__checkPhone($phone)
{
    if (preg_match('/1[\d]{10}$/', $phone)) {
        return true;
    } else {
        return false;
    }
}

function cc__checkWeChat($weChat)
{
    if (is_string($weChat) && preg_match('/^[_0-9a-z-]{6,16}$/i', $weChat)) {
        return true;
    } else {
        return false;
    }
}

/**
 * cc__checkEmail 检测是否是邮箱 2017年3月27日 10:06:38
 * @param $email
 * @return bool
 */
function cc__checkEmail($email)
{
    if (is_string($email) && preg_match('/([\w\-]+\@[\w\-]+\.[\w\-]+)/', $email)) {
        return true;
    } else {
        return false;
    }
}

/**
 * cc__getClientIp  获取访问者的IP
 * @return string
 */
function cc__getClientIp()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ip_address = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ip_address = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ip_address = $_SERVER['REMOTE_ADDR'];
    else
        $ip_address = 'UNKNOWN';

    if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip_address = '';
    }
    return $ip_address;
}

/**
 * cc__stringTrim
 * cc__stringTrim()
 * 字符串整理去空
 * @param string|array $data
 * @return string|array
 */
function cc__stringTrim($data)
{
    $result = '';
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $result[$key] = cc__stringTrim($value);
        }
    } else {
        $array = array("\n", "\t", "\r");
        $string = str_replace($array, '', $data);
        $result = trim($string);
    }
    return $result;
}

/**
 * 图片压缩
 * cc__resizeImage
 * @param string $im_path 原图路径
 * @param string $maxWidth 新图最大宽度
 * @param string $maxHeight 新图最大高度
 * @param string $name 新图名称
 * @param string $fileType 图片格式
 * @return bool
 */
function cc__resizeImage($im_path, $maxWidth, $maxHeight, $name, $fileType = 'jpeg')
{
    if (!file_exists($im_path)) return false;
    $im = getimagesize($im_path);
    if (!isset($im['mime'])) return false;

    switch ($im['mime']) {
        case 'image/png':
            $im = @imagecreatefrompng($im_path);
            break;
        case 'image/jpeg':
            $im = @imagecreatefromjpeg($im_path);
            break;
        case 'image/gif':
            $im = @imagecreatefromgif($im_path);
            break;
        default:
            return false;
            break;
    }

    if ($im === false) return false;

    $pic_width = imagesx($im);
    $pic_height = imagesy($im);

    $widthRatio = '';
    $heightRatio = '';
    $ratio = '';
    $resizeWidth_tag = '';
    $resizeHeight_tag = '';

    if (($maxWidth && $pic_width > $maxWidth) || ($maxHeight && $pic_height > $maxHeight)) {
        if ($maxWidth && $pic_width > $maxWidth) {
            $widthRatio = $maxWidth / $pic_width;
            $resizeWidth_tag = true;
        }

        if ($maxHeight && $pic_height > $maxHeight) {
            $heightRatio = $maxHeight / $pic_height;
            $resizeHeight_tag = true;
        }

        if ($resizeWidth_tag && $resizeHeight_tag) {
            if ($widthRatio < $heightRatio)
                $ratio = $widthRatio;
            else
                $ratio = $heightRatio;
        }

        if ($resizeWidth_tag && !$resizeHeight_tag)
            $ratio = $widthRatio;
        if ($resizeHeight_tag && !$resizeWidth_tag)
            $ratio = $heightRatio;

        $newWidth = $pic_width * $ratio;
        $newHeight = $pic_height * $ratio;

        if (function_exists("imagecopyresampled")) {
            $new_im = imagecreatetruecolor($newWidth, $newHeight);//PHP系统函数
            imagecopyresampled($new_im, $im, 0, 0, 0, 0, $newWidth, $newHeight, $pic_width, $pic_height);//PHP系统函数
        } else {
            $new_im = imagecreate($newWidth, $newHeight);
            imagecopyresized($new_im, $im, 0, 0, 0, 0, $newWidth, $newHeight, $pic_width, $pic_height);
        }
    } else {
        $new_im = $im;
    }

    switch ($fileType) {
        case 'jpeg':
            imagejpeg($new_im, $name);
            break;
        case 'png':
            imagepng($new_im, $name);
            break;
        case 'gif':
            imagegif($new_im, $name);
            break;
    }
    @imagedestroy($new_im);
    return true;
}


/**
 * cc_getRands
 * 获取随机字符串
 * @param string $type 类型
 * @param int $length 数量
 * @return string
 */
function cc__getRandStr($type, $length)
{
    $result = '';
    $letter = 'abcdefghijklmnopqrstuvwhyzABCDEFGHIJKLMNOPQRSTUVWHYZ';
    $number = '1234567890';

    //$randomArr = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "y", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "h", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "Y", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "H", "Y", "Z");

    switch ($type) {
        case 'letter':
            $chars = $letter;
            break;
        case 'number':
            $chars = $number;
            break;
        case 'all':
            $chars = $letter . $number;
            break;
        default:
            $chars = $letter . $number;
            break;
    }


    for ($i = 0; $i < $length; $i++) {
        $result .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $result;
}

/**
 * 计算两个时间戳之差 请确保两个日期 前小 后大
 * @param $begin_time ['description'] 开始时间戳
 * @param $end_time ['description'] 结束时间戳
 * @return false|array
 */
function cc__timeDiff($begin_time, $end_time)
{
    $res = ['day' => 0, "hour" => 0, "min" => 0, "sec" => 0];
    if ($begin_time < $end_time) {
        $startTime = $begin_time;
        $endTime = $end_time;
    } else {
        return false;
    }
    $timeDiff = $endTime - $startTime;
    $res['day'] = intval($timeDiff / 86400);
    $remain = $timeDiff % 86400;
    $res['hour'] = intval($remain / 3600);
    $remain = $remain % 3600;
    $res['min'] = intval($remain / 60);
    $res['sec'] = $remain % 60;
    return $res;
}

/**
 * 获取特定的时间格式
 * cc_getDate
 * @param string $type 格式|可以直接设置
 * @param bool|int $strTime 时间戳
 * @return string
 */
function cc__getDate($type = 'time', $strTime = false)
{
    if(false === $strTime) {
        $strTime = time();
    }
    if(empty($strTime) || $strTime > 2147454847 || $strTime < 0) {
        return false;
    }
    $time = $strTime; //设置正确的时间戳
    switch ($type) {
        case 'min':
            $showType = 'Y-m-d H:i';
            break;


        case 'MIN':
            $showType = 'Y年m月d日 H点i分';
            break;

        case 'hours':
            $showType = 'Y-m-d H';
            break;

        case 'HOURS':
            $showType = 'Y年m月d日 H点';
            break;

        case 'time':
            $showType = 'Y-m-d H:i:s';
            break;

        case 'TIME':
            $showType = 'Y年m月d日 H点i分s秒';
            break;

        case 'day':
            $showType = 'Y-m-d';
            break;

        case 'DAY':
            $showType = 'Y年m月d日';
            break;

        case 'MD':
            $showType = 'm月d日';
            break;

        case 'MAY':
            $showType = 'm月';
            break;

        default :
            $showType = $type;
    }
    return date($showType, $time);
}

function cc__getDateStr($dateTime = '')
{
    if (empty($dateTime)) {
        $showType = time();
    } else {
        switch ($dateTime) {
            case 'day':
                $showType = strtotime(cc__getDate('day'));
                break;

            default:
                $showType = strtotime($dateTime);
        }

    }
    return $showType;
}

/**
 * 一维数组去空，去重，重新排序和重新定义键名
 * cc__arrayRand
 * @param $array
 * @return array
 */
function cc__arrayRand($array)
{
    if (is_array($array)) {
        $array = array_filter($array);//去空
        $array = array_unique($array);//去重
        shuffle($array);//随机
        $array = array_values($array);//键值重新排序
    }
    return $array;
}

/**
 * 随机从数组中抽取一定的内容
 * cc_arrayGetRand
 * @param array $array 数组源
 * @param int $num 抽取的数量
 * @return array|string
 */
function cc__arrayGetRand($array, $num = 1)
{
    $result = [];
    if (!is_array($array)) $array = [$array]; //如果不是数组，重新定义为数组
    if (!is_numeric($num)) $num = 1;//如果抽取值不是数字

    //有可能会超出数组的数量, 直接返回随机过后的数组
    if ($num >= count($array)) {
        shuffle($array);
        if ($num == 1) {
            return $array[0];
        } else {
            return $array;
        }
    }

    $rand = array_rand($array, $num);
    if (is_array($rand)) {
        foreach ($rand as $key) {
            $result[] = $array[$key];
        }
    } else {
        $result = $array[$rand];
    }
    return $result;
}

/**
 * 验证是否为IP地址
 * cc_detectIpAddress
 * @param string $ip IP地址
 * @return bool|string
 */
function cc__detectIp($ip)
{
    $result = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    return $result;
}


/**
 * 写入TXT 文件
 * cc_writeTxt
 * @param string $path 文件路径
 * @param string $content 写入内容
 * @param string $type 文件打开方式
 */
function cc__writeTxt($path, $content, $type = 'w')
{
    $file = fopen($path, $type);
    fwrite($file, $content);
    fclose($file);
}


/**
 * 遍历文件夹，获取特定类型的子文件。深入子文件夹
 * cc_listDir
 * @param string $dirPath 文件夹路径
 * @param array|string|array $fileTypes 获取的文件类型
 * @param bool $child 是否遍历子文件夹
 * @return array|string
 */
function cc__listDir($dirPath, $fileTypes = [], $child = false)
{
    $ds = DIRECTORY_SEPARATOR; //目录分割符号
    if (substr($dirPath, -1) != $ds) {
        $dirPath .= $ds;
    }

    $result = [];
    $imgArr = ['.jpg', '.jpeg', '.jpe', '.png', '.gif', '.bmp', '.ico', '.wbm'];
    $I = 0;
    if (!is_array($fileTypes)) $fileTypes = [$fileTypes];

    if (!is_dir($dirPath)) {
        return false;
        //return '{ccFunctionError: cc_listDir - [' . $dirPath . ']未发现该文件夹}';
    } else {
        $dir = opendir($dirPath); //打开文件夹
        //循环访问子文件
        while (($file = readdir($dir)) !== false) {
            //如果是 . |.. 跳到下一个文件
            if ($file == '.' || $file == '..') continue;

            $subFilePath = $dirPath . $file; //子文件路径
            $fileStatus = true;
            $type = '';
            //循环判定要获取的内容
            if (!empty($fileTypes)) {
                foreach ($fileTypes as $type) {
                    $fileStatus = false;
                    switch ($type) {
                        case 'dir' :
                            if (is_dir($subFilePath)) {
                                $fileStatus = true;
                                $subFilePath = $subFilePath . $ds;
                            }
                            break;
                        case 'img' :
                            if (str_ireplace($imgArr, '', $file) !== $file) {
                                $fileStatus = true;
                            }
                            break;
                        default :
                            if (strpos($subFilePath, '.' . $type) !== false) {
                                $fileStatus = true;
                            }
                            break;
                    }
                }
            }

            //如果文件是要获取的文件类型，获取并跳往下一个文件
            if ($fileStatus === true) {
                $result[$I] = ['file' => $file, 'type' => $type, 'path' => $subFilePath];
            }

            //获取子文件夹内容
            if ($child === true && is_dir($subFilePath)) {
                $result[$I]['child'] = cc__listDir($subFilePath, $fileTypes, $child);
            }
            $I++;
        }
        closedir($dir);
        return $result;
    }
}

/**
 * 复制文件夹内容
 * cc_copyDir
 * @param string $sourceDir 源文件夹
 * @param string $targetDir 目标文件夹
 * @param bool $child 是否复制子文件夹
 * @return bool
 */
function cc__copyDir($sourceDir, $targetDir, $child = true)
{
    //源文件不存在
    if (!is_dir($sourceDir)) {
        echo '{ccFunctionError: cc_copyDir - [' . $sourceDir . ']未发现该文件夹}';
        return false;
    }

    //创建新的文件夹
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $dir = opendir($sourceDir); //打开文件夹
    //循环访问子文件
    while (($file = readdir($dir)) !== false) {
        if ($file != "." && $file != "..") {
            $childFile = $sourceDir . "/" . $file;
            $targetFile = $targetDir . "/" . $file;
            if (is_dir($childFile)) {
                if ($child === true)
                    cc__copyDir($childFile, $targetFile, $child);
            } else {
                copy($childFile, $targetFile);
            }
        }
    }
    closedir($dir);
    return true;
}

/**
 * 删除文件夹
 * cc_delDir
 * @param string $dirPath 文件夹路径
 * @param bool $self 是否删除自己
 * @return bool
 */
function cc__delDir($dirPath, $self = false)
{

    if (!is_dir($dirPath)) {
        echo '{ccFunctionError: cc_delDir - [' . $dirPath . ']未发现该文件夹}';
        return false;
    }

    //$self 是否类型包含自己
    $dir = opendir($dirPath);
    while (($file = readdir($dir)) !== false) {
        if ($file != "." && $file != "..") {
            $fullPath = $dirPath . "/" . $file;
            if (!is_dir($fullPath)) {
                unlink($fullPath);
            } else {
                cc__delDir($fullPath, $self);
            }
        }
    }
    closedir($dir);
    //删除当前文件夹：
    if ($self === true) {
        return rmdir($dirPath);
    }
    return true;
}

/**
 * cc__requireFile 加载文件
 * @param $filePath [description] 文件路径
 * @param $back [description] 是否返回内容
 * @return bool|mixed
 */
function cc__requireFile($filePath, $back = false)
{
    if (is_file($filePath)) {
        $fileContent = require $filePath;
        if (true === $back) {
            return $fileContent;
        }
    } else {
        return false;
    }
}
