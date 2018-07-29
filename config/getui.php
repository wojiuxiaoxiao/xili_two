<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/4/12
 * Time: 15:43
 */

return [
    'APPKEY'        => env('GETUI_APP_KEY', 'ENpg69cvWm6YeFT75p3a57'),
    'APPID'         => env('GETUI_APP_ID', 'PPO6UPDFTu6FSPAcje74n6'),
    'MASTERSECRET'  => env('GETUI_MASTER_SECRET', 'IWiPaN7iQq5F84iNwVwpV1'),

    'HOST'          => 'http://sdk.open.api.igexin.com/apiex.htm',//https://api.getui.com/apiex.htm
    'CID'           => 'c558e8078e2c588948aa78acba6306bc',
    'DEVICETOKEN'  => '',
    'Alias'         => '请输入别名',

    'isOffline' => 'true',//是否离线
    'ExpireTime' => 3600*12*1000,
    'Type' => 1,//͸透传
    'Badge' => 1,//角标
];