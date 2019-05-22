<?php

namespace Huaiyang\SimpleToken;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Contracts\Encryption\DecryptException;

use Ramsey\Uuid\Uuid;

/**
 *
 * token处理
 * Class SimpleToken
 * @package Huaiyang\SimpleToken
 */
class SimpleToken
{



    private $role = null;

    private $config = [];

    private $blackList;

    public function __construct()
    {

        $this->blackList = new BlackList();
    }

    public function test()
    {


        echo 'hello simpleToken';

    }

    /**
     * 初始化要操作的模型和配置文件
     * @param $roleKey
     * @return $this
     */
    public function init($roleKey = null)
    {

        if (null == $roleKey) {
            $default = config('simpletoken.default.role');
            $this->role = config('simpletoken.roles.' . $default . '.role');
            $this->config = config('simpletoken.roles.' . $default);
        } else {
            $this->role = config('simpletoken.roles.' . $roleKey . '.role');
            $this->config = config('simpletoken.roles.' . $roleKey);
        }

        return $this;
    }

    /**
     * 创建token
     * @param $params
     * @return array
     */
    public function createToken($params)
    {

        //公共信息
        $publicToken = $this->publicToken();

        //设置公共信息的UUID
        $uuid = $this->getUuid4();
        $publicToken['uuid'] = $uuid;

        //多角色识别
        $roleIdCode = md5($this->role . $params['primary_key']);

        $publicToken['roleIdCode'] = $roleIdCode;

        //设置公共信息的单点登录
        if ($this->config['SSO']) {
            //更新单点登录的有效Token
            $cacheKey = $this->config['SSO_cacheprefix'] . ':' . $roleIdCode;

            $cacheExpiration = now()->addSeconds($this->config['refreshable_time']);

            $sso = new Sso();

            $sso->putSsoItem($cacheKey, $uuid, $cacheExpiration);

        }

        //构建token
        $tokenArr['params'] = $params;
        $tokenArr['public'] = $publicToken;

        //加密
        $tokenStr = encrypt($tokenArr);

        $token['uuid'] = $uuid;
        $token['tokenStr'] = $tokenStr;

        return $token;
    }

    /**
     * token合法性校验
     * @param $token
     * @return array
     */

    public function verifyToken($token)
    {


        $response['result'] = 0;
        //获取解构好的token
        $tokenArr = $this->decodeToken($token);

        if(false === $tokenArr){
            $response['message'] = 'Token无法解密';
            $response['error_code'] = '1000';
            return $response;
        }


        /*根据当前角色判断是否开启了异步宽限期*/

        //获取当前token的宽限期
        $gracePeriod = abs($this->config['blacklist_grace_period']);

        //过期时间戳
        $expirationTime = $tokenArr['public']['expiration_time'];
        //可刷新时间戳
        $refreshableTime = $tokenArr['public']['refreshable_time'];
        //生效时间
        $notBeforeTime = $tokenArr['public']['not_before'];
        //黑名单UUID
        $uuid = $tokenArr['public']['uuid'];

        //判断Token是否已经生效
        if ($notBeforeTime > time()) {
            //如果token还未是生效，则Token校验失败
            $response['message'] = 'Token还未生效';
            $response['error_code'] = '1001';
            return $response;
        }

        /*判断token是否已经超过了可刷新时间*/
        if (time() >= $refreshableTime) {
            //如果已经超过了可刷新时间，则Token校验失败
            $response['message'] = 'Token已完全失效';
            $response['error_code'] = '1002';
            return $response;
        }

        //判断roleIdCode
        $roleIdCode = md5($this->role . $tokenArr['params']['primary_key']);

        if ($roleIdCode != $tokenArr['public']['roleIdCode']) {

            $response['message'] = 'Token角色不匹配';
            $response['error_code'] = '1003';
            return $response;
        }

        //处理单点登录
        if ($this->config['SSO']) {
            //获取当前用户的token的UUID
            $sso = new Sso();
            $ssoKey = $this->config['SSO_cacheprefix'] . ':' . $tokenArr['public']['roleIdCode'];
            $cacheUuid = $sso->getSsoUuid($ssoKey);

            if($cacheUuid != $uuid){
                $response['message'] = '当前账户在另外一台设备登录';
                $response['error_code'] = '1008';
                return $response;
            }
        }

        //获取黑名单情况
        $blackItem = $this->blackList->getBlackItem($uuid);

        if (0 == $gracePeriod) {
            //首先判断过期时间
            if (time() >= $expirationTime) {

                $response['message'] = 'Token已过期';
                $response['error_code'] = '1004';
                return $response;
            }
            //如果已经加入黑名单，则token校验失败
            if (null !== $blackItem) {
                $response['message'] = 'Token已失效';
                $response['error_code'] = '1005';
                return $response;

            }
        } else {

            //首先判断过期时间
            if (time() >= $expirationTime + $refreshableTime) {

                //如果当前时间已经大于 token过期时间和可刷新时间的和，则Token校验失败
                $response['message'] = 'Token已过期';
                $response['error_code'] = '1006';
                return $response;
            }

            /*判断是否在宽限期内*/
            $gracePeriodTime = $this->config['blacklist_grace_period'];

            if (null !== $blackItem) {
                if (time() >= $gracePeriodTime + $blackItem) {
                    //如果当前时间已经大于 token加入黑名单的时间与宽限时间的和，则Token校验失败
                    $response['message'] = 'Token已失效';
                    $response['error_code'] = '1007';
                    return $response;
                }
            }


        }

        //黑名单存储时间
        if ($expirationTime - time() > 0) {

            $blackExistTime = now()->addSeconds($expirationTime - time());

            $this->blackList->putBlackList($uuid, $blackExistTime);
        } else {

            $response['message'] = 'Token已过期';
            $response['error_code'] = '1008';
            return $response;
        }

        $response['result'] = 1;
        $response['message'] = 'Token可以使用';

        return $response;

    }

    /**
     * 刷新token
     * @param $token
     * @return mixed
     */
    public function refreshToken($token)
    {

        //获取解构好的token
        $tokenArr = $this->decodeToken($token);

        if(false === $tokenArr){
            $response['message'] = 'Token无法解密';
            $response['error_code'] = '1000';
            return $response;
        }

        //刷新token
        $uuid = $this->getUuid4();
        $tokenArr['public']['expiration_time'] = time() + $this->config['expiration_time'];
        $tokenArr['public']['not_before'] = time();
        $tokenArr['public']['uuid'] = $uuid;

        if ($this->config['SSO']) {

            //更新单点登录的有效Token的UUID
            $cacheKey = $this->config['SSO_cacheprefix'] . ':' . $tokenArr['public']['roleIdCode'];

            $cacheExpiration = now()->addSeconds($tokenArr['public']['refreshable_time'] - time());

            $sso = new Sso();

            $sso->putSsoItem($cacheKey, $uuid, $cacheExpiration);

        }

        //加密
        $tokenStr = encrypt($tokenArr);

        $tokenArr['uuid'] = $uuid;
        $tokenArr['tokenStr'] = $tokenStr;
        return $tokenArr;
    }

    /**
     * 解构Token
     * @param $token
     * @return mixed
     */
    private function decodeToken($token)
    {

        try {
            $tokenArr = decrypt($token);
        } catch (DecryptException $e) {
            $tokenArr = false;
        }

        return $tokenArr;
    }

    /**
     * 创建公共信息
     * @return array
     */
    private function publicToken()
    {

        $publicToken = [
            'issuer' => config('app.name'),  //签发人
            'expiration_time' => time() + $this->config['expiration_time'],    //过期时间
            'not_before' => time(),    //生效时间
            'create_time' => time(),   //创建时间
            'refreshable_time' => time() + $this->config['refreshable_time']     //可刷新时间
        ];

        return $publicToken;
    }

    /**
     * 获取UUID
     * @return mixed
     */
    private function getUuid4()
    {
        return Uuid::uuid4()->toString();
    }


}