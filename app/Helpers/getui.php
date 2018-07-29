<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 2017/6/29
 * Time: 下午5:52
 */



//require_once(app_path() . '/' . 'Libs/getuiphp/IGt.Push.php');
//require_once(app_path() . '/' . 'Libs/getuiphp/igetui/IGt.AppMessage.php');
//require_once(app_path() . '/' . 'Libs/getuiphp/igetui/IGt.APNPayload.php');
//require_once(app_path() . '/' . 'Libs/getuiphp/igetui/template/IGt.BaseTemplate.php');
//require_once(app_path() . '/' . 'Libs/getuiphp/IGt.Batch.php');
//require_once(app_path() . '/' . 'Libs/getuiphp/igetui/utils/AppConditions.php');


//
//define('APPKEY', 'nV2S9djKfBAnMlyylxBU44');
//define('APPID', 'tenwP1Pmqs9ZtBAkJOXWw6');
//define('MASTERSECRET', 't0UYHibbkV60pmYJRfYWtA');
//define('CID', '796b7a3b5ece54b48b0ace9b0e843a68');
//
//define('HOST', 'http://sdk.open.api.igexin.com/apiex.htm');
//
//define('DEVICETOKEN', '');
//define('Alias', '请输入别名');
//
////getPushMessageResultTest();
//function getPersonaTagsDemo()
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $ret = $igt->getPersonaTags(APPID);
//    var_dump($ret);
//}
//
//function getUserCountByTagsDemo()
//{
//    $igt     = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $tagList = array("金在中", "龙卷风");
//    $ret     = $igt->getUserCountByTags(APPID, $tagList);
//    var_dump($ret);
//}
//
//function getPushMessageResultTest()
//{
//    $igt  = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $date = date('Ymd', time());
//    $ret  = $igt->queryAppPushDataByDate(APPID, $date);
//    var_dump($ret);
//}
//
////用户状态查询
//function getUserStatus()
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $rep = $igt->getClientIdStatus(APPID, CID);
//    var_dump($rep);
//    echo("<br><br>");
//}
//
////推送任务停止
//function stoptask()
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $igt->stop("OSA-0416_n0Oad0AmYq5O4aZ0oyBAt3");
//}
//
////通过服务端设置ClientId的标签
//function setTag()
//{
//    $igt     = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    //$tagList = array('', '中文', 'English');
//    $tagList = array('xinbiao');
//    $rep     = $igt->setClientTag(APPID, CID, $tagList);
//    var_dump($rep);
//    echo("<br><br>");
//}
//
////获取用户标签
//function getUserTags()
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    $rep = $igt->getUserTags(APPID, CID);
//    //$rep.connect();
//    var_dump($rep);
//    echo("<br><br>");
//}
//
///**
// * 服务端推送接口
// * pushMessageToSingle  单推
// * pushMessageToList    多推 建议为50个用户
// * pushMessageToApp     群推 按tag标签约束
// * ------------------------------------------------------------------------
// */
//
//
///**
// * 单推接口
// * @param $config
// */
//function pushMessageToSingle($config)
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//
//
//    //消息模版：
//    // 1.transmission:   透传功能模板
//    // 2.link:           通知打开链接功能模板
//    // 3.notification：  通知透传功能模板
//    // 4.notyPopLoad：   通知弹框下载功能模板（停用）
//
//    switch ($config['template']) {
//        case 'transmission':
//            $template = IGtTransmissionTemplate($config['message']);
//            break;
//        case 'link':
//            $template = IGtLinkTemplate($config['message']);
//            break;
//        case 'notification':
//            $template = IGtNotificationTemplate($config['message']);
//            break;
//        case 'notypopload':
//            $template = IGtNotyPopLoadTemplate($config['message']);
//            break;
//        default:
//            die("Error: no such template!");
//            break;
//    }
//
//    //个推信息体
//    $message = new IGtSingleMessage();
//    $message->set_isOffline(true);//是否离线
//    $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
//    $message->set_data($template);//设置推送消息类型
//    $message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
//
//    //接收方
//    $target = new IGtTarget();
//    $target->set_appId(APPID);
//    $target->set_clientId($config['cid']);
////    $target->set_alias(Alias);
//
//    try {
//        $rep           = $igt->pushMessageToSingle($message, $target);
//        $rep['status'] = 0;
////        return $rep;
//    } catch (RequestException $e) {
//        $requestId = $e->getRequestId();
//        $rep      = $igt->pushMessageToSingle($message, $target, $requestId);
////        return $rep;
//    }
//}
//
///**
// * 多推接口
// * @param $config
// */
//function pushMessageToList($config)
//{
//    putenv("gexin_pushList_needDetails=true");
//    putenv("gexin_pushList_needAsync=true");
//
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    //消息模版：
//    //transmission:   透传功能模板
//    //link:           通知打开链接功能模板
//    //notification：  通知透传功能模板
//    //notyPopLoad：   通知弹框下载功能模板（停用）
//
//    switch ($config['template']) {
//        case 'transmission':
//            $template = IGtTransmissionTemplate($config['message']);
//            break;
//        case 'link':
//            $template = IGtLinkTemplate($config['message']);
//            break;
//        case 'notification':
//            $template = IGtNotificationTemplate($config['message']);
//            break;
//        case 'notypopload':
//            $template = IGtNotyPopLoadTemplate($config['message']);
//            break;
//        default:
//            die("Error: no such template!");
//            break;
//    }
//    $message = new IGtListMessage();
//    $message->set_isOffline(true);//是否离线
//    $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
//    $message->set_data($template);//设置推送消息类型
//    $message->set_PushNetWorkType(0);    //设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
////    $contentId = $igt->getContentId($message);
//    $contentId = $igt->getContentId($message, "toList任务别名功能");    //根据TaskId设置组名，支持下划线，中文，英文，数字
//
//    $cidList = $config['cidlist'];
//    $target  = new IGtTarget();
//    $target->set_appId(APPID);
//    for ($i = 0; $i < count($cidList); $i++) {
//        $target->set_clientId($cidList[$i]);
//        $targetList[] = $target;
//    }
//    $rep = $igt->pushMessageToList($contentId, $targetList);
//
//    var_dump($rep);
//
//    echo("<br><br>");
//
//}
//
///**
// * 群推接口
// * @param $config
// */
//function pushMessageToApp($config)
//{
//    $igt = new IGeTui(HOST, APPKEY, MASTERSECRET);
//    switch ($config['template']) {
//        case 'transmission':
//            $template = IGtTransmissionTemplate($config['message']);
//            break;
//        case 'link':
//            $template = IGtLinkTemplate($config['message']);
//            break;
//        case 'notification':
//            $template = IGtNotificationTemplate($config['message']);
//            break;
//        case 'notypopload':
//            $template = IGtNotyPopLoadTemplate($config['message']);
//            break;
//        default:
//            die("Error: no such template!");
//            break;
//    }
//
//    //个推信息体
//    //基于应用消息体
//    $message = new IGtAppMessage();
//    $message->set_isOffline(true);
//    $message->set_offlineExpireTime(10 * 60 * 1000);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2
//    $message->set_data($template);
//
//    $appIdList = array(APPID);
//    //$tagList   = array('xinbiao');
//    $tagList   = array('');
//    $cdt       = new AppConditions();
//    $cdt->addCondition(AppConditions::TAG, $tagList);
//
//    $message->set_appIdList($appIdList);
//    //$message->condition = $cdt;
//    $rep = $igt->pushMessageToApp($message);
//
//    var_dump($rep);
//    echo("<br><br>");
//}
//
///**
// * 消息模版：
// * transmission:   透传功能模板
// * link:           通知打开链接功能模板
// * notification：  通知透传功能模板
// * notyPopLoad：   通知弹框下载功能模板（停用）
// * ------------------------------------------------------------------------
// */
//
//
///**
// * 通知链接模板
// * @param $data
// * @return IGtLinkTemplate
// * 注：IOS离线推送需通过APN进行转发，需填写pushInfo字段，目前仅不支持通知弹框下载功能
// */
//function IGtLinkTemplate($data)
//{
//    $template = new IGtLinkTemplate();
//    $template->set_appId(APPID);//应用appid
//    $template->set_appkey(APPKEY);//应用appkey
//
//    //通知栏标题
//    $template->set_title($data['title']);
//
//    //通知栏内容
//    $template->set_text($data['text']);
//
//    //通知栏logo
//    isset($data['logo']) ? $template->set_logo($data['logo']) : $template->set_logo(ROOT_PATH . "public/splash/logo.jpeg");
//    $template->set_isRing(true);//是否响铃
//    $template->set_isVibrate(true);//是否震动
//    $template->set_isClearable(true);//通知栏是否可清除
//
//    //打开连接地址
//    $template->set_url($data['url']);
//
//    //设置ANDROID客户端在此时间区间内展示消息
//    isset($data['duration']) ? $template->set_duration($data['duration']['begintime'], $data['duration']['endtime']) : NULL;
//    return $template;
//}
//
///**
// * 通知透传模版
// * @param $data
// * @return IGtNotificationTemplate
// */
//function IGtNotificationTemplate($data)
//{
//    $template = new IGtNotificationTemplate();
//    $template->set_appId(APPID);//应用appid
//    $template->set_appkey(APPKEY);//应用appkey
//    $template->set_transmissionType(2);//透传消息类型 1 立即启动 2 广播启动
//    $template->set_isRing(true);//是否响铃
//    $template->set_isVibrate(true);//是否震动
//    $template->set_isClearable(true);//通知栏是否可清除
//    $template->set_transmissionContent($data['content']);//透传内容
//    $template->set_title($data['title']);//通知栏标题
//    $template->set_text($data['text']);//通知栏内容
//
//    //通知栏logo
//    isset($data['logo']) ? $template->set_logo($data['logo']) : $template->set_logo(ROOT_PATH . "public/splash/logo.jpeg");
//
//    //设置ANDROID客户端在此时间区间内展示消息
//    isset($data['duration']) ? $template->set_duration($data['duration']['begintime'], $data['duration']['endtime']) : NULL;
//    return $template;
//}
//
///**
// * 透传功能模板
// * @param $config
// * @return IGtTransmissionTemplate
// */
//function IGtTransmissionTemplate($data)
//{
//    $template = new IGtTransmissionTemplate();
//    $template->set_appId(APPID);//应用appid
//    $template->set_appkey(APPKEY);//应用appkey
//    $template->set_transmissionType(2);//透传消息类型
//    $template->set_transmissionContent($data['content']);//透传内容
//
//    //设置ANDROID客户端在此时间区间内展示消息
//    isset($data['duration']) ? $template->set_duration($data['duration']['begintime'], $data['duration']['endtime']) : NULL;
//
////    $content = json_decode($data['content'],true);
//    //APN高级推送
//    $apn                    = new IGtAPNPayload();
//    $alertmsg               = new DictionaryAlertMsg();
//    $alertmsg->body         = $data['body'];
//    $alertmsg->actionLocKey = "actionLocKey";
//    $alertmsg->locKey       = $data['title'];
//    $alertmsg->locArgs      = array("locargs");
//    $alertmsg->launchImage  = "launchimage";
////        IOS8.2 支持
//    $alertmsg->title        = $data['title'];
//    $alertmsg->titleLocKey  = $data['title'];
//    $alertmsg->titleLocArgs = array("TitleLocArg");
//    $apn->alertMsg          = $alertmsg;
//    $apn->badge             = 1;//角标
//    $apn->sound             = "";
//    $apn->add_customMsg("payload", $data['content']);
////    $apn->contentAvailable = 1;
//    $apn->category         = "ACTIONABLE";
//    $template->set_apnInfo($apn);
//    return $template;
//}