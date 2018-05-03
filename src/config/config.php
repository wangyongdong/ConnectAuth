<?php
/**
 * 联合登陆配置文件
 * @author wangyongdong
 */
$config = array(
    'qq' => array(
        'APP_ID' => '101386771',
        'APP_SECRET' => '4fb6a7796a601821d42d9d6ecf67ab0c',
        'RED_URL' => 'http://localhost/connect/auth_login/qq', //需要在对应开发平台配置
//        'RED_URL' => 'http://setting.medlive.cn/connect/auth_login/qq',
    ),
    'weibo' => array(
        'APP_ID' => '',
        'APP_SECRET' => '',
        'RED_URL' => 'http://localhost/connect/auth_login/weibo',//需要在对应开发平台配置
    ),
    'wechat' => array(
        'APP_ID' => '',
        'APP_SECRET' => '',
        'RED_URL' => 'http://localhost/connect/auth_login/wechat',//需要在对应开发平台配置
    ),
);