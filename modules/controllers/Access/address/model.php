<?php
namespace Access\Model;

use cc\Cache;
use CommonClass\Common_Class;

class address_model extends Common_Class
{
    /**
     * getCache
     * @param $cacheName    [description]   缓存名称
     * @return bool|mixed
     */
    public function getCache($cacheName) {
        if(false === Cache::has($cacheName)) {
            return false;
        } else {
            $data = Cache::get($cacheName);
            if(!is_array($data) || empty($data)) {
                return false;
            } else {
                return $data;
            }
        }
    }
}