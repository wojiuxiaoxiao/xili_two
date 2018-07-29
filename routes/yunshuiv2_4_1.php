<?php

/*
|--------------------------------------------------------------------------
| Yunshuiv2.4.1 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Yunshuiv2.3 routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$api = app('Dingo\Api\Routing\Router');
$api->version('v2.4.1', function ($api) {
    $api->any('/', function () {
        return view('index');
    });

//首页部分
    $api->any('/index', 'App\Http\Controllers\V2_4_1\IndexController@index');//首页
    $api->any('/attentionProgram', 'App\Http\Controllers\V2_4_1\IndexController@attentionProgram');//首页关注
    $api->any('/cancelAttention', 'App\Http\Controllers\V2_4_1\IndexController@cancelAttention');//首页取消关注
    $api->any('/goodClickNums', 'App\Http\Controllers\V2_4_1\IndexController@goodClickNums');//商品点击数量递增
    $api->any('/updateVersion', 'App\Http\Controllers\V2_4_1\IndexController@updateVersion');//版本升级
    $api->any('/updateClientId', 'App\Http\Controllers\V2_4_1\IndexController@updateClientId');//更新client_id

//登录注册部分
    $api->any('/memberRegister', 'App\Http\Controllers\V2_4_1\MemberController@memberRegister');//注册
    $api->any('/sendCheckcode', 'App\Http\Controllers\V2_4_1\MemberController@sendCheckcode');//发送手机注册验证码
    $api->any('/memberLogin', 'App\Http\Controllers\V2_4_1\MemberController@memberLogin');//手机登录
    $api->any('/snsLogin', 'App\Http\Controllers\V2_4_1\MemberController@snsLogin');//三方登录
    $api->any('/smallLogin', 'App\Http\Controllers\V2_4_1\MemberController@smallLogin');//小程序登录
    $api->any('/forgetPassword', 'App\Http\Controllers\V2_4_1\MemberController@forgetPassword');//忘记密码
    $api->any('/fastLogin', 'App\Http\Controllers\V2_4_1\MemberController@fastLogin');//快速登录

//个人中心部分
    $api->any('/baseInfo', 'App\Http\Controllers\V2_4_1\UserCenterController@baseInfo');//基本信息
    $api->any('/uploadAvatar', 'App\Http\Controllers\V2_4_1\UserCenterController@uploadAvatar');//上传头像
    $api->any('/setNickname', 'App\Http\Controllers\V2_4_1\UserCenterController@setNickname');//设置昵称
    $api->any('/setSignature', 'App\Http\Controllers\V2_4_1\UserCenterController@setSignature');//设置签名
    $api->any('/setGender', 'App\Http\Controllers\V2_4_1\UserCenterController@setGender');//设置性别
    $api->any('/setBirthdate', 'App\Http\Controllers\V2_4_1\UserCenterController@setBirthdate');//设置出生日期
    $api->any('/setBirthplace', 'App\Http\Controllers\V2_4_1\UserCenterController@setBirthplace');//设置出生地
    $api->any('/issueFb', 'App\Http\Controllers\V2_4_1\UserCenterController@issueFb');//问题反馈
    $api->any('/contactUs', 'App\Http\Controllers\V2_4_1\UserCenterController@contactUs');//联系我们
    $api->any('/myAttention', 'App\Http\Controllers\V2_4_1\UserCenterController@myAttention');//我的关注
    $api->any('/myCollect', 'App\Http\Controllers\V2_4_1\UserCenterController@myCollect');//我的收藏
    $api->any('/myMessage', 'App\Http\Controllers\V2_4_1\UserCenterController@myMessage');//我的消息
    $api->any('/clearMyMesssage', 'App\Http\Controllers\V2_4_1\UserCenterController@clearMyMesssage');//清空我的消息
    $api->any('/deleteClientid', 'App\Http\Controllers\V2_4_1\UserCenterController@deleteClientid');//删除clientid
    $api->any('/myMessage_test', 'App\Http\Controllers\V2_4_1\UserCenterController@myMessage_test');//我的消息test

    $api->any('/wechatJump', 'App\Http\Controllers\V2_4_1\SpringBoardController@wechatJump');//扫码中转页面

//upload
    $api->any('/radioUpload', 'App\Http\Controllers\V2_4_1\QiniuController@radioUpload');//qiniu音频上传脚本
    $api->any('/uploadInit', 'App\Http\Controllers\V2_4_1\QiniuController@uploadInit');//音频上传到自己服务器
    $api->any('/rollback_pic', 'App\Http\Controllers\V2_4_1\QiniuController@rollback_pic');//音频上传到自己服务器

//二维码
    $api->any('createCode', 'App\Http\Controllers\V2_4_1\QrcodeController@createCode');
    $api->any('callBackCode', 'App\Http\Controllers\V2_4_1\QrcodeController@callBackCode');

//听风水
    $api->any('programSearch', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@programSearch');//搜索节目
    $api->any('hotProgram', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@hotProgram');//热门节目
    $api->any('columnInfo', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@columnInfo');//栏目详情
    $api->any('programListhead', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@programListhead');//待播放节目列表头部数据
    $api->any('programList', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@programList');//待播放节目列表
    $api->any('playRadio', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@playRadio');//播放节目
    $api->any('masterList', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@masterList');//大师列表
    $api->any('playRadioDesc', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@playRadioDesc');//播放声音简介详情接口
    $api->any('shareProgram', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@shareProgram');//节目分享
    $api->any('downloadNums', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@downloadNums');//节目下载次数累加
    $api->any('shareNums', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@shareNums');//节目分享次数累加
    $api->any('playNums', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@playNums');//节目播放次数累加
    $api->any('attentionNums', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@attentionNums');//关注节目次数累加
    $api->any('collectProgram', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@collectProgram');//节目收藏
    $api->any('cancelCollect', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@cancelCollect');//取消收藏
    $api->any('isPlay', 'App\Http\Controllers\V2_4_1\ListenGeomancyController@isPlay');//是否可以播放

//评论
    $api->any('allCommentList', 'App\Http\Controllers\V2_4_1\CommentController@allCommentList');//一级评论列表
    $api->any('allCommentInfo', 'App\Http\Controllers\V2_4_1\CommentController@allCommentInfo');//所有评论的详情
    $api->any('allPostComment', 'App\Http\Controllers\V2_4_1\CommentController@allPostComment');//写评论
    $api->any('allReplyComment', 'App\Http\Controllers\V2_4_1\CommentController@allReplyComment');//回复评论
    $api->any('allCommentDelete', 'App\Http\Controllers\V2_4_1\CommentController@allCommentDelete');//评论点赞


    $api->any('allCom', 'App\Http\Controllers\V2_4_1\CommentController@allCom');//ceshi

    $api->any('pAllLikes', 'App\Http\Controllers\V2_4_1\CommentController@pAllLikes');//文章、活动、节目等的点赞

    $api->any('imgUpload', 'App\Http\Controllers\V2_4_1\ImgUploadController@imgUpload');//图片上传
    $api->any('imgMuchUpload', 'App\Http\Controllers\V2_4_1\ImgUploadController@imgMuchUpload');//多图片上传

//分享页面
    $api->any('shareRadio', 'App\Http\Controllers\V2_4_1\ShareController@shareRadio');//分享音频
    $api->any('share', '\App\Http\Controllers\V2_4_1\ShareController@share');

//banner
    $api->any('bannerList', 'App\Http\Controllers\V2_4_1\BannerController@bannerList');//Banner列表
    $api->any('bannerInfo', 'App\Http\Controllers\V2_4_1\BannerController@bannerInfo');//Banner详情

//文章
    $api->any('siftArticle', 'App\Http\Controllers\V2_4_1\ArticleController@siftArticle');//精选文章
    $api->any('hotArticle', 'App\Http\Controllers\V2_4_1\ArticleController@hotArticle');//热门文章
    $api->any('articleSearch', 'App\Http\Controllers\V2_4_1\ArticleController@articleSearch');//搜索文章
    $api->any('ArAcInof', 'App\Http\Controllers\V2_4_1\ArticleController@ArAcInof');//文章或者活动详情
    $api->any('ArAcCollect', 'App\Http\Controllers\V2_4_1\ArticleController@ArAcCollect');//文章或者活动收藏
    $api->any('allLikes', 'App\Http\Controllers\V2_4_1\ArticleController@allLikes');//文章或者活动点赞
    $api->any('cancelArAcCollect', 'App\Http\Controllers\V2_4_1\ArticleController@cancelArAcCollect');//文章或者活动取消收藏

//我的消息
    $api->any('systemNotice', 'App\Http\Controllers\V2_4_1\MessageController@systemNotice');//系统消息
    $api->any('programMessage', 'App\Http\Controllers\V2_4_1\MessageController@programMessage');//节目评论回复消息
    $api->any('activityMessage', 'App\Http\Controllers\V2_4_1\MessageController@activityMessage');//活动评论回复消息
    $api->any('articleMessage', 'App\Http\Controllers\V2_4_1\MessageController@articleMessage');//文章评论回复消息
    $api->any('deleteSystemMessage', 'App\Http\Controllers\V2_4_1\MessageController@deleteSystemMessage');//删除系统消息
    $api->any('deleteTopicMessage', 'App\Http\Controllers\V2_4_1\MessageController@deleteTopicMessage');//删除评论回复消息
    $api->any('clearFeedMessage', 'App\Http\Controllers\V2_4_1\MessageController@clearFeedMessage');//情况反馈回复消息
    $api->any('redHint', 'App\Http\Controllers\V2_4_1\MessageController@redHint');//红点信息提示
    $api->any('clearRedHint', 'App\Http\Controllers\V2_4_1\MessageController@clearRedHint');//清空红点信息提示
    $api->any('answerTopic', 'App\Http\Controllers\V2_4_1\MessageController@answerTopic');//问答评论消息
    $api->any('answerReplys', 'App\Http\Controllers\V2_4_1\MessageController@answerReplys');//问答回复消息

//问答的提问
    $api->any('indexGroup', 'App\Http\Controllers\V2_4_1\InterlocutionController@getGroup');//获取分类
    $api->any('interIndex', 'App\Http\Controllers\V2_4_1\InterlocutionController@index');//提问列表
    $api->any('interDetail', 'App\Http\Controllers\V2_4_1\InterlocutionController@detail');//提问详情
    $api->any('interSave', 'App\Http\Controllers\V2_4_1\InterlocutionController@save');//添加提问
    $api->any('interDelete', 'App\Http\Controllers\V2_4_1\InterlocutionController@delete');//删除提问
    $api->any('interCollect', 'App\Http\Controllers\V2_4_1\InterlocutionController@collect');//收藏提问
    $api->any('interUcollect', 'App\Http\Controllers\V2_4_1\InterlocutionController@ucollect');//收藏提问

//问答的回答
    $api->any('answerIndex', 'App\Http\Controllers\V2_4_1\AnswerController@index');//回答列表
    $api->any('answerDetail', 'App\Http\Controllers\V2_4_1\AnswerController@detail');//回答详情
    $api->any('answerSaveComment', 'App\Http\Controllers\V2_4_1\AnswerController@saveComment');//对回答添加回复
    $api->any('answerDelete', 'App\Http\Controllers\V2_4_1\AnswerController@delete');//删除回答
    $api->any('answerLike', 'App\Http\Controllers\V2_4_1\AnswerController@like');//对回答进行点赞
    $api->any('answerSave', 'App\Http\Controllers\V2_4_1\AnswerController@save');//对问题进行回答

//个人中心提问列表
    $api->any('userCenterAskList', 'App\Http\Controllers\V2_4_1\UserCenterController@askList');//个人中心提问列表
    $api->any('userCenterAnswerList', 'App\Http\Controllers\V2_4_1\UserCenterController@answerList');//个人中心回答列表
//单元测试
    $api->any('aesTest', 'App\Http\Controllers\V2_4_1\FuwuController@aesTest');//清空红点信息提示
    $api->any('demo', 'App\Http\Controllers\V2_4_1\InterlocutionController@demoP');
//end
});


