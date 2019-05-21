<?php

namespace Huaiyang\SimpleToken;

/**
 *
 * token 失效黑名单
 * Class BlackList
 * @package Huaiyang\SimpleToken
 */
class BlackList
{


    private $cachePrefix = 'blackList:';

    private $cache;
    public function __construct()
    {

        $this -> cache = new SimpleTokenCache();
    }

    /**
     *
     * 将token加入到黑名单
     * @param $uuid
     */
    public function putBlackList($uuid,$blacklistGracePeriod){

        $blackItem = $this -> getBlackItem($uuid);

        if(null === $blackItem){
            $key = $this -> createPrefix($uuid);
            $this -> cache -> cachePut($key,time(),$blacklistGracePeriod);
        }


    }

    /**
     * 获取黑名单情况
     * @param $uuid
     * @return mixed
     */
    public function getBlackItem($uuid){
        $key = $this -> createPrefix($uuid);
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