<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
////    return "<script>window.location.href='http://www.sfys365.com/'</script>";
//});
//
Route::any('/', '\App\Http\Controllers\V1\IndexController@location');
//Route::any('/share', '\App\Http\Controllers\V2_1\ShareController@share');
//Route::any('indexGroup', '\App\Http\Controllers\V2_4\InterlocutionController@getGroup');

//Route::any('interIndex', '\App\Http\Controllers\V2_4\InterlocutionController@index');//提问列表
//Route::any('interDetail', '\App\Http\Controllers\V2_4\InterlocutionController@detail');//提问详情
//Route::any('interSave', '\App\Http\Controllers\V2_4\InterlocutionController@save');//添加提问
//Route::any('interDelete', '\App\Http\Controllers\V2_4\InterlocutionController@delete');//删除提问
//Route::any('interCollect', '\App\Http\Controllers\V2_4\InterlocutionController@collect');//收藏提问

//Route::any('shareRadio', 'App\Http\Controllers\V2_3\ShareController@shareRadio');//分享音频
//Route::any('share', '\App\Http\Controllers\V2_3\ShareController@share');
////首页部分
//Route::any('/index', 'IndexController@index');//首页
//Route::any('/attentionProgram', 'IndexController@attentionProgram');//首页关注
//Route::any('/cancelAttention', 'IndexController@cancelAttention');//首页取消关注
//
////登录注册部分
//Route::any('/memberRegister', 'MemberController@memberRegister');//注册
//Route::any('/sendCheckcode', 'MemberController@sendCheckcode');//发送手机注册验证码
//Route::any('/memberLogin', 'MemberController@memberLogin');//手机登录
//Route::any('/snsLogin', 'MemberController@snsLogin');//三方登录
//Route::any('/forgetPassword', 'MemberController@forgetPassword');//忘记密码
//Route::any('/fastLogin', 'MemberController@fastLogin');//快速登录
//
////个人中心部分
//Route::any('/baseInfo', 'UserCenterController@baseInfo');//基本信息
//Route::any('/uploadAvator', 'UserCenterController@uploadAvator');//上传头像
//Route::any('/setNickname', 'UserCenterController@setNickname');//设置昵称
//Route::any('/setSignature', 'UserCenterController@setSignature');//设置签名
//Route::any('/setGender', 'UserCenterController@setGender');//设置性别
//Route::any('/setBirthdate', 'UserCenterController@setBirthdate');//设置出生日期
//Route::any('/issueFb', 'UserCenterController@issueFb');//问题反馈
//Route::any('/contactUs', 'UserCenterController@contactUs');//联系我们
//Route::any('/myAttention', 'UserCenterController@myAttention');//我的关注
//Route::any('/myCollect', 'UserCenterController@myCollect');//我的收藏
//Route::any('/myMessage', 'UserCenterController@myMessage');//我的消息
//
//Route::any('/wechatJump', 'SpringBoardController@wechatJump');//扫码中转页面
//
////upload
//Route::any('/radioUpload', 'QiniuController@radioUpload');//qiniu音频上传脚本
//Route::any('/uploadInit', 'QiniuController@uploadInit');//音频上传到自己服务器
//
////二维码
//Route::any('createCode', 'QrcodeController@createCode');
//Route::any('callBackCode', 'QrcodeController@callBackCode');
//
////听风水
//Route::any('programSearch', 'ListenGeomancyController@programSearch');//搜索节目
//Route::any('hotProgram', 'ListenGeomancyController@hotProgram');//热门节目
//Route::any('programInfo', 'ListenGeomancyController@programInfo');//节目详情
//Route::any('programListhead', 'ListenGeomancyController@programListhead');//待播放节目列表头部数据
//Route::any('programList', 'ListenGeomancyController@programList');//待播放节目列表
//Route::any('playRadio', 'ListenGeomancyController@playRadio');//播放节目
//Route::any('shareProgram', 'ListenGeomancyController@shareProgram');//节目分享
//Route::any('downloadNums', 'ListenGeomancyController@downloadNums');//节目下载次数累加
//Route::any('shareNums', 'ListenGeomancyController@shareNums');//节目分享次数累加
//Route::any('playNums', 'ListenGeomancyController@playNums');//节目播放次数累加
//Route::any('collectProgram', 'ListenGeomancyController@collectProgram');//节目收藏
//Route::any('cancelCollect', 'ListenGeomancyController@cancelCollect');//取消收藏
//
////评论
//Route::any('commentList', 'CommentController@commentList');//节目评论列表
//Route::any('postComment', 'CommentController@postComment');//提交评论
//Route::any('replyComment', 'CommentController@replyComment');//回复评论
//Route::any('hotComment', 'CommentController@hotComment');//热门评论
//Route::any('commentLike', 'CommentController@commentLike');//评论点赞
//
//Route::any('imgUpload', 'ImgUploadController@imgUpload');//图片上传
