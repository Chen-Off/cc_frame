<?php

/**
 * 加密解密
 * @author Chen<2795265136@qq.com>
 * @copyright 2016 (OA 管理系统)
 * @since 2017年2月21日 16:45:02 初版 使用微信加密方式
 * @version class.crypt.php
 */

namespace ccCrypt;

class ccCrypt
{
    public $key;

    /**
     * ccCrypt constructor.
     * @param string $k 输入的密码
     */
    function __construct($k)
    {
        $k = strrev($k);
        $this->key = hash('sha256', $k, true);
        //$this->key = base64_decode($k . "=");
    }

    /**
     * 进行加密
     * cc__encrypt
     * @param string $text 需要加密的明文
     * @return array 加密后的密文
     */
    function encrypt($text)
    {
        try {
            //$key = hash('sha256', $text, true);

            //获得16位随机字符串，填充到明文之前
            $randStr = cc__getRandStr('all', 16);
            $text = $randStr . pack("N", strlen($text)) . $text;

            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_DEV_URANDOM);
            //使用自定义的填充方式对明文进行补位填充
            $pkc_encoder = new PKCS7Encoder;
            $text = $pkc_encoder->encode($text);
            mcrypt_generic_init($module, $this->key, $iv);

            $encrypt_data = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
            $encrypt_data = base64_encode($encrypt_data);
            return ['success', $encrypt_data];
        } catch (Exception $e) {
            return ['error', ''];
        }
    }

    /**
     * 进行解密验证
     * @param string $encrypted 需要解密的密文
     * @return array 解密得到的明文
     */
    function decrypt($encrypted)
    {
        try {
            //$key = hash('sha256', $text, true);

            //使用BASE64对需要解密的字符串进行解码
            $cipherText_dec = base64_decode($encrypted);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            mcrypt_generic_init($module, $this->key, $iv);

            //解密
            $decrypted = mdecrypt_generic($module, $cipherText_dec);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            return ['error', ''];
        }


        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);

            $content = substr($decrypted, 16, strlen($result));
            $xml_len = unpack("N", substr($content, 0, 4));
            $xml_content = substr($content, 4, $xml_len[1]);

            return ['success', $xml_content];
        } catch (Exception $e) {
            return ['error', ''];
        }

    }
}


/**
 * PKCS7Encoder class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder
{
    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     * @param string $text 需要进行填充补位操作的明文
     * @return string 补齐明文字符串
     */
    function encode($text)
    {
        //$block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::$block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text decrypted 解密后的明文
     * @return string 删除填充补位后的明文
     */
    function decode($text)
    {

        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}
