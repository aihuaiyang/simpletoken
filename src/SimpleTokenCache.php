<?php

namespace Huaiyang\SimpleToken;

use Cache;

class SimpleTokenCache
{

    private $cacheDriver = null;

    private $prefix = 'simpletoken:';



    public function __construct()
    {
        $this->cacheDriver = config('cache.default');

        if ('file' == $this->cacheDriver) {
            $this -> clearExpirationTimeFile();
        }
    }



    public function putBlackList($key,$value,$blacklistGracePeriod){

        $key = $this -> basePrefix($key);

        if(Cache::has($key)){
            return true;
        }else{
            $this -> cachePut($key,$value,$blacklistGracePeriod);
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
     */
    public function clearExpirationTimeFile(){

        $cacheListSerialize = Cache::get('SimpleTokenCacheList');

        $cacheList = unserialize($cacheListSerialize);

        if(false !== $cacheList){
            foreach ($cacheList as $v){
                Cache::get($v);
            }
        }

    }

    /**
     * 构建缓存基本前缀
     * @param $key
     * @return string
     */
    private function basePrefix($key){

        return $this -> prefix.$key;
    }
}