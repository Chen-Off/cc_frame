<?php
namespace Access\Controller;

use Access\Model\address_model;
use cc\Cache;
use cc\Db;


class address
{
    public $result = ['status' => 'success', 'json' => []];

    public function index()
    {

    }

    public function check_link()
    {

    }

    public function province_json()
    {
        $cacheName = 'chinaAddressProvince';
        $MODEL = new address_model();

        $cache = $MODEL->getCache($cacheName);
        if (false === $cache) {
            $select = 'provinceID as id, province as name';
            $province = Db::table('hat_province')->select($select);
            Cache::set($cacheName, $province);
        } else {
            $province = $cache;
        }

        $this->result['json'] = $province;


        cc__outputPage($this->result);
    }

    public function city_json()
    {
        if (empty(URL_PARAMS) || !is_numeric(URL_PARAMS)) {
            $result['status'] = 'error';
            cc__outputPage($this->result);
        }

        $cacheName = 'chinaAddressCity';
        $MODEL = new address_model();
        $cache = $MODEL->getCache($cacheName);
        if (false === $cache) {

            $select = 'cityID as id, city as name';
            $city = Db::table('hat_city')->where('father = ' . URL_PARAMS)->select($select);
            Cache::set($cacheName, $city);
        } else {
            $city = $cache;
        }

        $this->result['json'] = $city;
        cc__outputPage($this->result);
    }

    public function area_json()
    {
        if (empty(URL_PARAMS) || !is_numeric(URL_PARAMS)) {
            $result['status'] = 'error';
            cc__outputPage($this->result);
        }

        $cacheName = 'chinaAddressArea';
        $MODEL = new address_model();
        $cache = $MODEL->getCache($cacheName);
        if (false === $cache) {

            $select = 'areaID as id, area as name';
            $area = Db::table('hat_area')->where('father = ' . URL_PARAMS)->select($select);
            Cache::set($cacheName, $area);
        } else {
            $area = $cache;
        }

        $this->result['json'] = $area;
        cc__outputPage($this->result);
    }

}