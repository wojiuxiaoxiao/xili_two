<?php

/*
|--------------------------------------------------------------------------
| Yunshuiv2.3 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Yunshuiv2.3 routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$api = app('Dingo\Api\Routing\Router');
$api->version('v2.3', function ($api) {
    $api->any('/', function () {
        return view('index');
    });

//首页部分
    $api->any('/index', 'App\Http\Controllers\V2_3\IndexController@index');//首页
    $api->any('/attentionProgram', 'App\Http\Controllers\V2_3\IndexController@attentionProgram');//首页关注
    $api->any('/cancelAttention', 'App\Http\Controllers\V2_3\IndexController@cancelAttention');//首页取消关注
    $api->any('/goodClickNums', 'App\Http\Controllers\V2_3\IndexController@goodClickNums');//商品点击数量递增
    $api->any('/updateVersion', 'App\Http\Controllers\V2_3\IndexController@updateVersion');//版本升级
    $api->any('/updateClientId', 'App\Http\Controllers\V2_3\IndexController@updateClientId');//更新client_id

//登录注册部分
    $api->any('/memberRegister', 'App\Http\Controllers\V2_3\MemberController@memberRegister');//注册
    $api->any('/sendCheckcode', 'App\Http\Controllers\V2_3\MemberController@sendCheckcode');//发送手机注册验证码
    $api->any('/memberLogin', 'App\Http\Controllers\V2_3\MemberController@memberLogin');//手机登录
    $api->any('/snsLogin', 'App\Http\Controllers\V2_3\MemberController@snsLogin');//三方登录
    $api->any('/smallLogin', 'App\Http\Controllers\V2_3\MemberController@smallLogin');//小程序登录
    $api->any('/forgetPassword', 'App\Http\Controllers\V2_3\MemberController@forgetPassword');//忘记密码
    $api->any('/fastLogin', 'App\Http\Controllers\V2_3\MemberController@fastLogin');//快速登录

//个人中心部分
    $api->any('/baseInfo', 'App\Http\Controllers\V2_3\UserCenterController@baseInfo');//基本信息
    $api->any('/uploadAvatar', 'App\Http\Controllers\V2_3\UserCenterController@uploadAvatar');//上传头像
    $api->any('/setNickname', 'App\Http\Controllers\V2_3\UserCenterController@setNickname');//设置昵称
    $api->any('/setSignature', 'App\Http\Controllers\V2_3\UserCenterController@setSignature');//设置签名
    $api->any('/setGender', 'App\Http\Controllers\V2_3\UserCenterController@setGender');//设置性别
    $api->any('/setBirthdate', 'App\Http\Controllers\V2_3\UserCenterController@setBirthdate');//设置出生日期
    $api->any('/setBirthplace', 'App\Http\Controllers\V2_3\UserCenterController@setBirthplace');//设置出生地
    $api->any('/issueFb', 'App\Http\Controllers\V2_3\UserCenterController@issueFb');//问题反馈
    $api->any('/contactUs', 'App\Http\Controllers\V2_3\UserCenterController@contactUs');//联系我们
    $api->any('/myAttention', 'App\Http\Controllers\V2_3\UserCenterController@myAttention');//我的关注
    $api->any('/myCollect', 'App\Http\Controllers\V2_3\UserCenterController@myCollect');//我的收藏
    $api->any('/myMessage', 'App\Http\Controllers\V2_3\UserCenterController@myMessage');//我的消息
    $api->any('/clearMyMesssage', 'App\Http\Controllers\V2_3\UserCenterController@clearMyMesssage');//清空我的消息
    $api->any('/deleteClientid', 'App\Http\Controllers\V2_3\UserCenterController@deleteClientid');//删除clientid
    $api->any('/myMessage_test', 'App\Http\Controllers\V2_3\UserCenterController@myMessage_test');//我的消息test

    $api->any('/wechatJump', 'App\Http\Controllers\V2_3\SpringBoardController@wechatJump');//扫码中转页面

//upload
    $api->any('/radioUpload', 'App\Http\Controllers\V2_3\QiniuController@radioUpload');//qiniu音频上传脚本
    $api->any('/uploadInit', 'App\Http\Controllers\V2_3\QiniuController@uploadInit');//音频上传到自己服务器

//二维码
    $api->any('createCode', 'App\Http\Controllers\V2_3\QrcodeController@createCode');
    $api->any('callBackCode', 'App\Http\Controllers\V2_3\QrcodeController@callBackCode');

//听风水
    $api->any('programSearch', 'App\Http\Controllers\V2_3\ListenGeomancyController@programSearch');//搜索节目
    $api->any('hotProgram', 'App\Http\Controllers\V2_3\ListenGeomancyController@hotProgram');//热门节目
    $api->any('columnInfo', 'App\Http\Controllers\V2_3\ListenGeomancyController@columnInfo');//栏目详情
    $api->any('programListhead', 'App\Http\Controllers\V2_3\ListenGeomancyController@programListhead');//待播放节目列表头部数据
    $api->any('programList', 'App\Http\Controllers\V2_3\ListenGeomancyController@programList');//待播放节目列表
    $api->any('playRadio', 'App\Http\Controllers\V2_3\ListenGeomancyController@playRadio');//播放节目
    $api->any('masterList', 'App\Http\Controllers\V2_3\ListenGeomancyController@masterList');//大师列表
    $api->any('playRadioDesc', 'App\Http\Controllers\V2_3\ListenGeomancyController@playRadioDesc');//播放声音简介详情接口
    $api->any('shareProgram', 'App\Http\Controllers\V2_3\ListenGeomancyController@shareProgram');//节目分享
    $api->any('downloadNums', 'App\Http\Controllers\V2_3\ListenGeomancyController@downloadNums');//节目下载次数累加
    $api->any('shareNums', 'App\Http\Controllers\V2_3\ListenGeomancyController@shareNums');//节目分享次数累加
    $api->any('playNums', 'App\Http\Controllers\V2_3\ListenGeomancyController@playNums');//节目播放次数累加
    $api->any('attentionNums', 'App\Http\Controllers\V2_3\ListenGeomancyController@attentionNums');//关注节目次数累加
    $api->any('collectProgram', 'App\Http\Controllers\V2_3\ListenGeomancyController@collectProgram');//节目收藏
    $api->any('cancelCollect', 'App\Http\Controllers\V2_3\ListenGeomancyController@cancelCollect');//取消收藏
    $api->any('isPlay', 'App\Http\Controllers\V2_3\ListenGeomancyController@isPlay');//是否可以播放

//评论
    $api->any('allCommentList', 'App\Http\Controllers\V2_3\CommentController@allCommentList');//一级评论列表
    $api->any('allCommentInfo', 'App\Http\Controllers\V2_3\CommentController@allCommentInfo');//所有评论的详情
    $api->any('allPostComment', 'App\Http\Controllers\V2_3\CommentController@allPostComment');//写评论
    $api->any('allReplyComment', 'App\Http\Controllers\V2_3\CommentController@allReplyComment');//回复评论
    $api->any('allCommentDelete', 'App\Http\Controllers\V2_3\CommentController@allCommentDelete');//评论点赞

    $api->any('pAllLikes', 'App\Http\Controllers\V2_3\CommentController@pAllLikes');//文章、活动、节目等的点赞

    $api->any('imgUpload', 'App\Http\Controllers\V2_3\ImgUploadController@imgUpload');//图片上传
    $api->any('imgMuchUpload', 'App\Http\Controllers\V2_3\ImgUploadController@imgMuchUpload');//多图片上传

//分享页面
    $api->any('shareRadio', 'App\Http\Controllers\V2_3\ShareController@shareRadio');//分享音频
    $api->any('share', '\App\Http\Controllers\V2_3\ShareController@share');

//banner
    $api->any('bannerList', 'App\Http\Controllers\V2_3\BannerController@bannerList');//Banner列表
    $api->any('bannerInfo', 'App\Http\Controllers\V2_3\BannerController@bannerInfo');//Banner详情

//文章
    $api->any('siftArticle', 'App\Http\Controllers\V2_3\ArticleController@siftArticle');//精选文章
    $api->any('hotArticle', 'App\Http\Controllers\V2_3\ArticleController@hotArticle');//热门文章
    $api->any('articleSearch', 'App\Http\Controllers\V2_3\ArticleController@articleSearch');//搜索文章
    $api->any('ArAcInof', 'App\Http\Controllers\V2_3\ArticleController@ArAcInof');//文章或者活动详情
    $api->any('ArAcCollect', 'App\Http\Controllers\V2_3\ArticleController@ArAcCollect');//文章或者活动收藏
    $api->any('allLikes', 'App\Http\Controllers\V2_3\ArticleController@allLikes');//文章或者活动点赞
    $api->any('cancelArAcCollect', 'App\Http\Controllers\V2_3\ArticleController@cancelArAcCollect');//文章或者活动取消收藏

//我的消息
    $api->any('systemNotice', 'App\Http\Controllers\V2_3\MessageController@systemNotice');//系统消息
    $api->any('programMessage', 'App\Http\Controllers\V2_3\MessageController@programMessage');//节目评论回复消息
    $api->any('activityMessage', 'App\Http\Controllers\V2_3\MessageController@activityMessage');//活动评论回复消息
    $api->any('articleMessage', 'App\Http\Controllers\V2_3\MessageController@articleMessage');//文章评论回复消息
    $api->any('deleteSystemMessage', 'App\Http\Controllers\V2_3\MessageController@deleteSystemMessage');//删除系统消息
    $api->any('deleteTopicMessage', 'App\Http\Controllers\V2_3\MessageController@deleteTopicMessage');//删除评论回复消息
    $api->any('clearFeedMessage', 'App\Http\Controllers\V2_3\MessageController@clearFeedMessage');//情况反馈回复消息
    $api->any('redHint', 'App\Http\Controllers\V2_3\MessageController@redHint');//红点信息提示
    $api->any('clearRedHint', 'App\Http\Controllers\V2_3\MessageController@clearRedHint');//清空红点信息提示

//单元测试
    $api->any('aesTest', 'App\Http\Controllers\V2_3\FuwuController@aesTest');//清空红点信息提示

});


