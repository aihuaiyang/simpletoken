<?php

namespace Huaiyang\SimpleToken;

/**
 *
 * 单点登录
 * Class Sso
 * @package Huaiyang\SimpleToken
 */
class Sso
{


    private $cachePrefix = 'sso:';

    private $cache;
    public function __construct()
    {

        $this -> cache = new SimpleTokenCache();
    }

    /**
     *
     * 将单点登录的角色对应的UUID存储到缓存
     * @param $uuid
     */
    public function putSsoItem($key,$uuid,$cacheExpiration){
        $key = $this -> createPrefix($key);
        $this -> cache -> cachePut($key,$uuid,$cacheExpiration);

    }

    /**
     * 获取黑名单情况
     * @param $uuid
     * @return mixed
     */
    public function getSsoUuid($key){
        $key = $this -> createPrefix($key);
     
        return $this -> cache -> cacheGet($key);
    }

    /**
     * 构建存储前缀
     * @param $key
     * @return string
     */
    private function createPrefix($key){
        return $this -> cachePrefix.$key;
    }

}