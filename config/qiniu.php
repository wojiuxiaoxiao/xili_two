<?php
/**
 * Created by PhpStorm.
 * User: XUN
 * Date: 2017/12/26
 * Time: 11:31
 */
return [
    'accessKey' => env('QiniuAccessKey', ''),
    'secretKey' => env('QiniuSecretKey', ''),
    'bucket' => env('QiniuBucket', ''),
    'bucketPic' => env('QiniuBucketPic', ''),
    'thumb' => env('QiniuThumb', ''),
    'onlineUrl' => env('QiniuOnlineUrl', ''),
    'onlineUrlPic' => env('QiniuOnlineUrlPic', ''),

    //扫码地址
    'qrcodeurl' => env('QRcodeUrl', ''),
];