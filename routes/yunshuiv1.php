<?php

/*
|--------------------------------------------------------------------------
| Yunshuiv1 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Yunshuiv1 routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', function ($api) {
    $api->any('/', function () {
        return view('index');
    });
//支付回调
    $api->any('callBack', 'App\Http\Controllers\V1\IndexController@callBack');//Banner详情
    $api->any('alipayCallBack', 'App\Http\Controllers\V1\IndexController@alipayCallBack');//Banner详情
//首页部分
    $api->any('/index', 'App\Http\Controllers\V1\IndexController@index');//首页
    $api->any('/attentionProgram', 'App\Http\Controllers\V1\IndexController@attentionProgram');//首页关注
    $api->any('/cancelAttention', 'App\Http\Controllers\V1\IndexController@cancelAttention');//首页取消关注
    $api->any('/goodClickNums', 'App\Http\Controllers\V1\IndexController@goodClickNums');//商品点击数量递增

//登录注册部分
    $api->any('/memberRegister', 'App\Http\Controllers\V1\MemberController@memberRegister');//注册
    $api->any('/sendCheckcode', 'App\Http\Controllers\V1\MemberController@sendCheckcode');//发送手机注册验证码
    $api->any('/memberLogin', 'App\Http\Controllers\V1\MemberController@memberLogin');//手机登录
    $api->any('/snsLogin', 'App\Http\Controllers\V1\MemberController@snsLogin');//三方登录
    $api->any('/forgetPassword', 'App\Http\Controllers\V1\MemberController@forgetPassword');//忘记密码
    $api->any('/fastLogin', 'App\Http\Controllers\V1\MemberController@fastLogin');//快速登录

//个人中心部分
    $api->any('/baseInfo', 'App\Http\Controllers\V1\UserCenterController@baseInfo');//基本信息
    $api->any('/uploadAvatar', 'App\Http\Controllers\V1\UserCenterController@uploadAvatar');//上传头像
    $api->any('/setNickname', 'App\Http\Controllers\V1\UserCenterController@setNickname');//设置昵称
    $api->any('/setSignature', 'App\Http\Controllers\V1\UserCenterController@setSignature');//设置签名
    $api->any('/setGender', 'App\Http\Controllers\V1\UserCenterController@setGender');//设置性别
    $api->any('/setBirthdate', 'App\Http\Controllers\V1\UserCenterController@setBirthdate');//设置出生日期
    $api->any('/setBirthplace', 'App\Http\Controllers\V1\UserCenterController@setBirthplace');//设置出生地
    $api->any('/issueFb', 'App\Http\Controllers\V1\UserCenterController@issueFb');//问题反馈
    $api->any('/contactUs', 'App\Http\Controllers\V1\UserCenterController@contactUs');//联系我们
    $api->any('/myAttention', 'App\Http\Controllers\V1\UserCenterController@myAttention');//我的关注
    $api->any('/myCollect', 'App\Http\Controllers\V1\UserCenterController@myCollect');//我的收藏
    $api->any('/myMessage', 'App\Http\Controllers\V1\UserCenterController@myMessage');//我的消息
    $api->any('/clearMyMesssage', 'App\Http\Controllers\V1\UserCenterController@clearMyMesssage');//清空我的消息
    $api->any('/myMessage_test', 'App\Http\Controllers\V1\UserCenterController@myMessage_test');//我的消息test

    $api->any('/wechatJump', 'App\Http\Controllers\V1\SpringBoardController@wechatJump');//扫码中转页面          

//upload
    $api->any('/radioUpload', 'App\Http\Controllers\V1\QiniuController@radioUpload');//qiniu音频上传脚本
    $api->any('/uploadInit', 'App\Http\Controllers\V1\QiniuController@uploadInit');//音频上传到自己服务器

//二维码
    $api->any('createCode', 'App\Http\Controllers\V1\QrcodeController@createCode');
    $api->any('callBackCode', 'App\Http\Controllers\V1\QrcodeController@callBackCode');

//听风水
    $api->any('programSearch', 'App\Http\Controllers\V1\ListenGeomancyController@programSearch');//搜索节目
    $api->any('hotProgram', 'App\Http\Controllers\V1\ListenGeomancyController@hotProgram');//热门节目
    $api->any('columnInfo', 'App\Http\Controllers\V1\ListenGeomancyController@columnInfo');//栏目详情
    $api->any('programListhead', 'App\Http\Controllers\V1\ListenGeomancyController@programListhead');//待播放节目列表头部数据
    $api->any('programList', 'App\Http\Controllers\V1\ListenGeomancyController@programList');//待播放节目列表
    $api->any('playRadio', 'App\Http\Controllers\V1\ListenGeomancyController@playRadio');//播放节目
    $api->any('shareProgram', 'App\Http\Controllers\V1\ListenGeomancyController@shareProgram');//节目分享
    $api->any('downloadNums', 'App\Http\Controllers\V1\ListenGeomancyController@downloadNums');//节目下载次数累加
    $api->any('shareNums', 'App\Http\Controllers\V1\ListenGeomancyController@shareNums');//节目分享次数累加
    $api->any('playNums', 'App\Http\Controllers\V1\ListenGeomancyController@playNums');//节目播放次数累加
    $api->any('attentionNums', 'App\Http\Controllers\V1\ListenGeomancyController@attentionNums');//关注节目次数累加
    $api->any('collectProgram', 'App\Http\Controllers\V1\ListenGeomancyController@collectProgram');//节目收藏
    $api->any('cancelCollect', 'App\Http\Controllers\V1\ListenGeomancyController@cancelCollect');//取消收藏
    $api->any('isPlay', 'App\Http\Controllers\V1\ListenGeomancyController@isPlay');//是否可以播放

//评论
    $api->any('commentList', 'App\Http\Controllers\V1\CommentController@commentList');//节目评论列表
    $api->any('postComment', 'App\Http\Controllers\V1\CommentController@postComment');//提交评论
    $api->any('replyComment', 'App\Http\Controllers\V1\CommentController@replyComment');//回复评论
    $api->any('hotComment', 'App\Http\Controllers\V1\CommentController@hotComment');//热门评论
    $api->any('commentLike', 'App\Http\Controllers\V1\CommentController@commentLike');//评论点赞

    $api->any('imgUpload', 'App\Http\Controllers\V1\ImgUploadController@imgUpload');//图片上传
    $api->any('imgMuchUpload', 'App\Http\Controllers\V1\ImgUploadController@imgMuchUpload');//多图片上传

//分享页面
    $api->any('shareRadio', 'App\Http\Controllers\V1\ShareController@shareRadio');//分享音频
    $api->any('share', '\App\Http\Controllers\V1\ShareController@share');

//单元测试
    $api->any('aesTest', 'App\Http\Controllers\V1\UnitTestCenterController@aesTest');//aes测试
});


