<?php

return [


    'default' => [
        'role' => 'user'
    ],

    'roles' => [
        'user' => [
            //角色名称
            'role' => 'user',
            //有效期
            'expiration_time' => 60*60*2,
            //可刷新时间
            'refreshable_time' => 60*60*24*15,
            //是否单点登录
            'SSO' => false,
            //单点登录缓存前缀
            'SSO_cacheprefix' => 'sso_user',
            //黑名单宽限期，加入黑名单后的token,宽限期内可以继续使用，如果需要异步请求，需要这里不等于0
            'blacklist_grace_period' => 0
        ],
        'admin' => [
            'role' => 'admin',
            'expiration_time' => 60*60*2,
            'refreshable_time' => 60*60*24*15,
            'SSO' => true,
            'SSO_cacheprefix' => 'sso_admin',
            'blacklist_grace_period' => 0
        ]
    ]
];