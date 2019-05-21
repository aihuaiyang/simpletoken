<?php

namespace Huaiyang\SimpleToken;

use Cache;

/**
 *
 * 缓存
 * Class SimpleTokenCache
 * @package Huaiyang\SimpleToken
 */
class SimpleTokenCache
{
    
    private $cacheDriver = null;

    private $prefix = 'simpletoken:';



    public function __construct()
    {
        $this->cacheDriver = config('cache.default');

        if ('file' == $this->cacheDriver) {
            //如果是文件驱动的缓存。清除缓存已过期但未删除的缓存数据
            $this -> clearExpirationTimeFile();
        }

    }


    /**
     *
     * 缓存文件
     * @param $key
     * @param $value
     * @param $expirationTime
     * @return mixed
     */
    public function cachePut($key,$value,$expirationTime){

        $key = $this -> basePrefix($key);

        $this -> cacheKeys($key);
        return Cache::put($key,$value,$expirationTime);
    }

    /**
     *
     * 设置缓存
     * @param $key
     * @param null $defaultValue
     * @return mixed
     */
    public function cacheGet($key,$defaultValue=null){

        $key = $this -> basePrefix($key);

        return Cache::get($key,$defaultValue);
    }


    /**
     * 清理文件缓存驱动已过期的缓存文件
     * 此方法针对缓存驱动是文件的，如果缓存后不访问文件。即使过期也不会删除
     * redis等可以自行过期删除的不会调用此方法
     */
    public function clearExpirationTimeFile(){

        $keys = $this -> getCacheKeys();

        foreach ($keys as $k => $v){
            $value = Cache::get($v,null);
            if($value === null){
                unset($keys[$k]);
            }
        }

        $key = $this -> prefix.'keys';

        Cache::forever($key,serialize($keys));

    }

    /**
     * 构建缓存基本前缀
     * @param $key
     * @return string
     */
    private function basePrefix($key){

        return $this -> prefix.$key;
    }

    /**
     *
     * 缓存所有的Key
     * @param $key
     */
    private function cacheKeys($cacheKey){

        $keys = $this -> getCacheKeys();

        array_push($keys,$cacheKey);

        $key = $this -> prefix.'keys';

        Cache::forever($key,serialize($keys));

    }

    /**
     *  获取所有的keys
     */
    private function getCacheKeys(){

        $key = $this -> prefix.'keys';

        $keys = Cache::get($key,serialize([]));

        return unserialize($keys);
    }
}