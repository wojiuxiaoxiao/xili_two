<?php
/**
 * Created by PhpStorm.
 * User: XUN
 * Date: 2018/6/20
 * Time: 12:25
 */
return [
    /*微信支付参数配置*/
    'WX_APPID' => 'wxb3f642c82275f277',
    'WX_MCHID' => '1506987141',
    'WX_KEY' => 'Kyssfaf1234424223jxniah5511fdadf',
    'WX_APPSECRET' => 'bc4c8357b5dfff24dd55109298ee26b1',
    'WX_NOTIFY_URL' => env('WX_NOTIFY_URL'),

    /*支付宝支付*/
    'AL_URL' => 'https://openapi.alipay.com/gateway.do',
    'AL_APP_ID' => '2018062060391733',
    'AL_notify_url' => env('AL_notify_url'),
];