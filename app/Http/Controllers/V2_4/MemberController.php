<?php
/**
 * 登录注册控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_4;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Models\User;
use App\Http\Models\UserSns;
use App\Http\Models\UserSource;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Controller;
use Flc\Dysms\Client;
use Flc\Dysms\Request\SendSms;

class MemberController extends Controller
{
    private $AppID = 'wxc4b2be868074c841';
    private $AppSecret = '340d6b5ccbadccecdfa27225f74927a9';
    private $grant_type = 'authorization_code';
    /** SendVerify
     *  发送短信验证码
     *  @type  1用户注册验证码 2忘记密码验证码 3快速登录验证码  4绑定验证码
     *  @author kexun
     *  @since v2.4
     */
    public function SendVerify($phone,$code,$type)
    {
        $config = [
            'accessKeyId'    => 'LTAIpLM8zFyv4wTv',
            'accessKeySecret' => 'R0AjAJeeD9Tvjeok99PfpjPTVSGGbf',
        ];
        switch ($type)
        {
            case 1: $SMS_CODE = 'SMS_120375346';break;
            case 2: $SMS_CODE = 'SMS_120405340';break;
            case 3: $SMS_CODE = 'SMS_120405344';break;
            case 4: $SMS_CODE = 'SMS_120405351';break;
        }

        $client  = new Client($config);
        $sendSms = new SendSms;
        $sendSms->setPhoneNumbers($phone);
        $sendSms->setSignName('十方云水');
        $sendSms->setTemplateCode($SMS_CODE);
        $sendSms->setTemplateParam(['code' => $code]);
        $sendSms->setOutId('demo');
        $data = $client->execute($sendSms);
        return $data;
    }
    /**
     *  发送短信验证码
     *  @author neek
     *  @since v2.4
     */
    public function sendCheckcode(){

        $phone = Input::get('phone');
        $send_type = Input::get('send_type');

        if($send_type==1 || $send_type==4){
            $user_res = User::where([['status','=',1],['phone','=',$phone]])->value('phone');
            if($user_res){
                $return['status'] = 0;
                $return['msg'] = '该手机号已被注册';
                extjson($return);
            }
        }
        if($send_type == 2){
            $user_res = User::where([['status','=',1],['phone','=',$phone]])->value('phone');
            if(!$user_res){
                $return['status'] = 0;
                $return['msg'] = '该手机号未注册';
                extjson($return);
            }
        }
        if(!preg_match("/^1[1345678]\d{9}$/",$phone)){
            $return['status'] = 0;
            $return['msg'] = '请输入合法手机号';
            extjson($return);
        }

        $srand = rand(100000,999999);
        //$srand = 123456;

        $data = self::SendVerify($phone,$srand,$send_type);

        if ($data->Code === 'OK') {
            Redis::set($phone,$srand);
            Redis::expire($phone,180);//10分钟的有效期
            $return['status'] = 1;
            $return['msg'] = '验证码发送成功!';
            extjson($return);
        } else if($data->Code === 'isv.BUSINESS_LIMIT_CONTROL'){
            $return['status'] = 0;
            $return['msg'] = '短信发送频率太高，请稍后重试';
            extjson($return);
        }else{
            $return['status'] = 0;
            $return['msg'] = '获取验证码失败';
            extjson($return);
        }
    }


    /**
     *  用户注册
     *  @author neek
     *  @since v2.4
     */
    public function memberRegister(){

        $devid = Input::get('devid');
        $phone = Input::get('phone');
        $user_id = intval(Input::get('user_id'));//如果是第三方登录要传此参数
        $password = de_crypt(Input::get('password'));

        $user_info = User::where([['status','=',1],['phone','=',$phone]])->first();
        if($user_info){
            $return['status'] = 0;
            $return['msg'] = '手机号已经注册，请直接登录';
            extjson($return);
        }

        $msg_code = Input::get('msg_code');
        $save_code = Redis::get($phone);
        if($msg_code != $save_code){
            $return['status'] = 0;
            $return['msg'] = '短信验证码有误';
            extjson($return);
        }
        //删除验证码
        Redis::del($phone);

        $platform_type = 0;
        if(stripos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(stripos($devid, 'android')!==false){
            $platform_type = 1;
        }

        //获取来源
        $ip = client_ip();
        $source_id = Redis::get($ip);
        if($source_id){
            $source_info = UserSource::where([['status','=',1],['id','=',$source_id]])->first();
            $source_name = $source_info['source_name'];
        }else{
            $source_id = 0;
            $source_name = '';
        }

        //注册成功，生成用户并分配唯一票据
        $nickname = 'ys_'. mt_rand(10000, 99999);//随机用户名
        if($user_id>0){
            try {
                $password = md5(md5($password).'yunshui');
                User::where('id', $user_id)->update(['phone' => $phone,'password'=>$password]);
                $time = time();
                $key = md5($time."_yunshui^");
                $type = 1;//注册用户默认为普通用户
                $access_token = $user_id."::::".$nickname."::::".$phone."::::".$devid."::::".$type."::::".time()."::::".$key;
                $access_token = authcode($access_token,'yunshui',86400*5);

                //返回用户信息
                $return_user = User::where([['status','=',1],['id','=',$user_id]])
                    ->select('avatar','nickname','signature','gender','birth_date','birth_place','type')
                    ->first();
                $return['status'] = 1;
                $return['data'] = $return_user;
                $return['access_token'] = $access_token;
                $return['msg'] = '';
                extjson($return);
            } catch (Exception $e) {

            } finally {
                extOperate(0,'注册失败');
            }
        }else{
            $pwd = md5(md5($password).'yunshui');
            $data['password'] = $pwd;
            $data['phone'] = $phone;
            $data['platform_type'] = $platform_type;
            $data['source'] = $source_name;
            $data['source_id'] = $source_id;
            $data['nickname'] = $nickname;
            $data['create_time'] = time();
            $res = User::create($data);
            $user_id = $res->id;
        }

        $time = time();
        $key = md5($time."_yunshui^");
        $type = 1;//注册用户默认为普通用户
        $access_token = $user_id."::::".$nickname."::::".$phone."::::".$devid."::::".$type."::::".time()."::::".$key;
        $access_token = authcode($access_token,'yunshui',86400*5);

        //返回用户信息
        $return_user = User::where([['status','=',1],['id','=',$user_id]])
            ->select('avatar','nickname','signature','gender','birth_date','birth_place','type')
            ->first();
        $return['status'] = $res ? 1 : 0;
        $return['data'] = $res ? $return_user : null;
        $return['access_token'] = $res ? $access_token : '';
        $return['msg'] = $res ? '' : '注册失败';
        extjson($return);
    }

    /**
     *  手机登录功能
     *  @author neek
     *  @since v2.4
     */
    public function memberLogin(){

        $devid = Input::get('devid');
        $password = de_crypt(Input::get('password'));
        $phone = Input::get('phone');

        if(!preg_match("/^1[134578]\d{9}$/",$phone)){
            $return['status'] = 0;
            $return['msg'] = '请输入合法手机号';
            extjson($return);
        }

        $user_res = User::where([['status','=',1],['phone','=',$phone]])->value('phone');
        if(!$user_res){
            $return['status'] = 0;
            $return['msg'] = '手机号未注册';
            extjson($return);
        }

        $pwd = md5(md5($password).'yunshui');
        $user_info = User::where([['status','=',1],['phone','=',$phone],['password','=',$pwd]])
            ->select('id','avatar','nickname','signature','phone','gender','birth_date','birth_place','type')
            ->first();
        if($user_res && !$user_info){
            $return['status'] = 0;
            $return['msg'] = '密码错误';
            extjson($return);
        }

        $client_id = Input::get('client_id');
        //获取来源
        $ip = client_ip();
        $source_id = (Redis::get($ip)) ?? 0;
        $this->updateClient($user_info['id'], $source_id, $client_id);

        $time = time();
        $key = md5($time."_yunshui^");
        $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
        $access_token = authcode($access_token,'yunshui',86400*5);

        $return['status'] = $user_info ? 1 : 0;
        $return['data'] = $user_info ? $user_info : '';
        $return['access_token'] = $user_info ? $access_token : '';
        $return['msg'] = $user_info ? '' : '登录失败';
        extjson($return);
    }

    /**
     * 三方登录
     * @author neekli
     * @since v2.4
     */
    public function snsLogin(){

        $openid = Input::get('openid');
        $sns_from = Input::get('sns_from');
        $unionid = Input::get('unionid') ?? '';
        $devid = Input::get('devid');
        $username = Input::get('username') ? Input::get('username') : '';
        $img_url = Input::get('img_url') ? Input::get('img_url') : '';

        $platform_type = 0;
        if(stripos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(stripos($devid, 'android')!==false){
            $platform_type = 1;
        }

        //如果用户是存在的
        $check_sns = UserSns::where([['status','=',1],['from_sns','=',$sns_from],['openid','=',$openid]])->first();
        if($check_sns['id']>0){//直接登录成功
            if($check_sns['unionid'] == "") {
                $input['unionid'] = $unionid;
                UserSns::where([['status','=',1],['from_sns','=',$sns_from],['openid','=',$openid]])->update($input);
            }
            UserSns::where('id', $check_sns['id'])->update(['lasttime' => time()]);
            $user_info = User::where([['status','=',1],['id','=',$check_sns['user_id']]])
                ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type','phone')
                ->first();
            if($user_info['phone']){
                $time = time();
                $key = md5($time."_yunshui^");
                $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
                $access_token = authcode($access_token,'yunshui',86400*5);
                $return['status'] = $user_info ? 1 : 0;
                $return['access_token'] = $access_token;
                $return['data'] = $user_info ? $user_info : '';
                $return['msg'] = $user_info ? '' : '第三方登录失败';
                extjson($return);
            }

            $return['status'] = $user_info ? 1 : 0;
            $return['data'] = $user_info ? $user_info : '';
            $return['msg'] = $user_info ? '' : '第三方登录失败';
            extjson($return);
        }

        $username = $username != "" || $username != null ? $username : 'ys_' . mt_rand(10000, 99999);//随机用户名
        $password = 123456;
        $password = md5(md5($password).'yunshui');

        //获取来源
        $ip = client_ip();
        $source_id = Redis::get($ip);
        if($source_id){
            $source_info = UserSource::where([['status','=',1],['id','=',$source_id]])->first();
            $source_name = $source_info['source_name'];
        }else{
            $source_id = 0;
            $source_name = '';
        }
        $userSns = UserSns::where('status',1)->where('unionid',$unionid)->first();
        if($userSns['id']) {
            $insert_userid = $userSns['user_id'];
        } else {
            $insert_userid = User::insertGetId(
                ['avatar' => $img_url ,'nickname' => $username, 'source'=>$source_name, 'source_id'=>$source_id,'platform_type'=>$platform_type,'password' => $password,'create_time'=>time()]
            );
        }

        $client_id = Input::get('client_id');
        //获取来源
        $ip = client_ip();
        $source_id = (Redis::get($ip)) ?? 0;
        $this->updateClient($insert_userid, $source_id, $client_id);

        $insert_user_sns_id = UserSns::insertGetId(
            ['user_id' => $insert_userid ,'from_sns' => $sns_from ,'sns_username' => $username,'openid'=>$openid,'unionid'=>$unionid,'regtime'=>time()]
        );

        $return_user = User::where([['status','=',1],['id','=',$insert_userid]])
            ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type')
            ->first();
        
        $data['user_id'] = $insert_userid;
        $return['status'] = $insert_userid ? 1 : 0;
        $return['data'] = $insert_userid ? $return_user : '';
        $return['msg'] = $insert_userid ? '' : '第三方登录失败';
        extjson($return);
    }

    /**
     * 忘记密码
     * @author neekli
     * @since v2.4
     */
    public function forgetPassword(){
        $devid = Input::get('devid');
        $phone = Input::get('phone');
        $password = de_crypt(Input::get('password'));

        $user_info = User::where([['status','=',1],['phone','=',$phone]])
            ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type')
            ->first();

        if(!$user_info){
            $return['status'] = 0;
            $return['msg'] = '该手机号未注册';
            extjson($return);
        }

        $msg_code = Input::get('msg_code');
        $save_code = Redis::get($phone);
        if($msg_code != $save_code){
            $return['status'] = 0;
            $return['msg'] = '短信验证码有误';
            extjson($return);
        }
        //删除验证码
        Redis::del($phone);

        $time = time();
        $key = md5($time."_yunshui^");
        $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$phone."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
        $access_token =authcode($access_token,'yunshui',86400*5);

        try {
            $password = md5(md5($password).'yunshui');
            User::where([['status','=',1],['phone','=',$phone],['id','=',$user_info['id']]])->update(['password' => $password]);

            $return['status'] = 1;
            $return['data'] = $user_info;
            $return['access_token'] = $access_token;
            $return['msg'] = '';
            extjson($return);
        } catch (Exception $e) {

        } finally {
            $return['status'] = 0;
            $return['access_token'] = '';
            $return['msg'] = '忘记密码操作失败';
            extjson($return);
        }
    }

    /**
     * 快速登录
     * @author neekli
     * @since v2.4
     */
    public function fastLogin(){

        $devid = Input::get('devid');
        $phone = Input::get('phone');

        $msg_code = Input::get('msg_code');
        $save_code = Redis::get($phone);
        if($msg_code != $save_code){
            $return['status'] = 0;
            $return['msg'] = '验证码有误';
            extjson($return);
        }
        //删除验证码
        Redis::del($phone);

        $client_id = Input::get('client_id');
        //获取来源
        $ip = client_ip();
        $source_id = (Redis::get($ip)) ?? 0;

        $user_info = User::where([['status','=',1],['phone','=',$phone]])
            ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type')
            ->first();
        if($user_info){
            $this->updateClient($user_info['id'], $source_id, $client_id);

            $time = time();
            $key = md5($time."_yunshui^");
            $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$phone."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
            $access_token = authcode($access_token,'yunshui',86400*5);

            $return['status'] = 1;
            $return['data'] = $user_info;
            $return['access_token'] = $access_token;
            extjson($return);
        }

        //下载端
        $platform_type = 0;
        if(stripos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(stripos($devid, 'android')!==false){
            $platform_type = 1;
        }

        //获取来源
        $ip = client_ip();
        $source_id = Redis::get($ip);
        if($source_id){
            $source_info = UserSource::where([['status','=',1],['id','=',$source_id]])->first();
            $source_name = $source_info['source_name'];
        }else{
            $source_id = 0;
            $source_name = '';
        }

        $nickname = 'ys_'. mt_rand(10000, 99999);//随机用户名
        $password =  mt_rand(100000, 999999);
        $pwd = md5(md5($password).'yunshui');
        $data['password'] = $pwd;
        $data['phone'] = $phone;
        $data['platform_type'] = $platform_type;
        $data['source'] = $source_name;
        $data['source_id'] = $source_id;
        $data['nickname'] = $nickname;
        $data['create_time'] = time();
        $res = User::create($data);
        $user_id = $res['attributes']['id'];

        $this->updateClient($user_id, $source_id, $client_id);

        $time = time();
        $key = md5($time."_yunshui^");
        $access_token = $user_id."::::".$nickname."::::".$phone."::::".$devid."::::".time()."::::".$key;
        $access_token = authcode($access_token,'yunshui',86400*5);

        $return['status'] = $res ? 1 : 0;
        $return['data'] = $res ? array('id'=>$user_id,'avatar'=>'','nickname'=>$nickname,'gender'=>1,'birth_date'=>'','birth_place'=>'','type'=>1) : '';
        $return['access_token'] = $res ? $access_token : '';
        $return['msg'] = $res ? '' : '快速登录失败';
        extjson($return);
    }

    /**
     * 小程序登录
     * @author kexun
     * @since v2.4
     */
    public function smallLogin() {
        $appid = $this->AppID;
        $secret = $this->AppSecret;
        $grant_type = $this->grant_type;
        $encryptedData= Input::get('encryptedData');
        $iv= Input::get('iv');
        $devid = Input::get('devid');
        $username = Input::get('username') ? Input::get('username') : '';
        $img_url = Input::get('img_url') ? Input::get('img_url') : '';

        $platform_type = 0;
        if(stripos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(stripos($devid, 'android')!==false){
            $platform_type = 1;
        }

        $api_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=%s';
//        $request = '{"session_key":"C4EkrXP\/ah2OJriCa67ydA==","openid":"ojCU65NKKKFnxZR5iHWUUhF0rhv8"}';
        if(!is_null(Input::get('code'))) {
            $request = sprintf($api_url, $appid, $secret,Input::get('code'),$grant_type);
            $data = file_get_contents($request);
            $data = json_decode($data,true);
            if (isset($data['openid'])) {
                $sns_from = 3;//小程序即微信登录
                //如果用户是存在的
                $check_sns = UserSns::where([['status','=',1],['from_sns','=',$sns_from],['openid','=',$data['openid']]])->first();
                if ($check_sns['id']>0) {//直接登录成功
                    if(!isset($data['unionid'])) {
                        require_once(app_path()."/Libs/aes/wxBizDataCrypt.php");
                        $pc = new \WXBizDataCrypt($appid, $data['session_key']);
                        $errCode = $pc->decryptData($encryptedData, $iv, $Data );
                        if ($errCode == 0) {
                            $Data = json_decode($Data,true);
                            $data['unionid'] = $Data['unionId'];
                        } else {
                            $return['status'] = 0;
                            $return['msg'] =  '微信账号异常！';
                            extjson($return);
                        }
                    }
                    if ($check_sns['unionid'] == "") {
                        UserSns::where('id', $check_sns['id'])->update(['unionid' => $data['unionid']]);
                    }
                    UserSns::where('id', $check_sns['id'])->update(['lasttime' => time()]);
                    $user_info = User::where([['status','=',1],['id','=',$check_sns['user_id']]])
                        ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type','phone')
                        ->first();
                    if (!$user_info['id']) {
                        $return['status'] = 0;
                        $return['msg'] =  '账号异常请联系管理员！';
                        extjson($return);
                    }
                } else {
                    $username = $username != "" || $username != null ? $username : 'ys_' . mt_rand(10000, 99999);//随机用户名
                    $password = 123456;
                    $password = md5(md5($password).'yunshui');

                    //获取来源
                    $source_id = 0;
                    $source_name = '';
                    if(!isset($data['unionid'])) {
                        require_once(app_path()."/Libs/aes/wxBizDataCrypt.php");
                        $pc = new \WXBizDataCrypt($appid, $data['session_key']);
                        $errCode = $pc->decryptData($encryptedData, $iv, $Data );
                        if ($errCode == 0) {
                            $Data = json_decode($Data,true);
                            $data['unionid'] = $Data['unionId'];
                        } else {
                            $return['status'] = 0;
                            $return['msg'] =  '微信账号异常！';
                            extjson($return);
                        }
                    }
                    $userSns = UserSns::where('status',1)->where('unionid',$data['unionid'])->first();
                    if($userSns['id']) {
                        $insert_userid = $userSns['user_id'];
                    } else {
                        $insert_userid = User::insertGetId(
                            ['avatar' => $img_url ,'nickname' => $username, 'source'=>$source_name, 'source_id'=>$source_id,'platform_type'=>$platform_type,'password' => $password,'create_time'=>time()]
                        );
                    }
                    $insert_user_sns_id = UserSns::insertGetId(
                        ['user_id' => $insert_userid ,'from_sns' => $sns_from ,'sns_username' => $username,'openid'=>$data['openid'],'unionid'=>$data['unionid'],'regtime'=>time()]
                    );

                    $user_info = User::where([['status','=',1],['id','=',$insert_userid]])
                        ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type')
                        ->first();
                }
                $time = time();
                $key = md5($time."_yunshui^");
                $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
                $access_token = authcode($access_token,'yunshui',86400*5);
                $return['status'] = 1;
                $return['access_token'] = $access_token;
                $return['data'] = $user_info;
                extjson($return);
            }
        }

        $return['status'] = 0;
        $return['msg'] = 'code验证失败！';
        extjson($return);

    }
}
