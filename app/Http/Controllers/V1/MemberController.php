<?php
/**
 * 登录注册控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V1;

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
    /** SendVerify
     *  发送短信验证码
     *  @author kexun
     *  @since v1.0
     */
    public function SendVerify($phone,$code)
    {
        $config = [
            'accessKeyId'    => 'LTAIpLM8zFyv4wTv',
            'accessKeySecret' => 'R0AjAJeeD9Tvjeok99PfpjPTVSGGbf',
        ];

        $client  = new Client($config);
        $sendSms = new SendSms;
        $sendSms->setPhoneNumbers($phone);
        $sendSms->setSignName('十方云水');
        $sendSms->setTemplateCode('SMS_120125423');
        $sendSms->setTemplateParam(['code' => $code]);
        $sendSms->setOutId('demo');
        $data = $client->execute($sendSms);
        return $data;
    }
    /**
     *  发送短信验证码
     *  @author neek
     *  @since v1.0
     */
    public function sendCheckcode(){

        $phone = Input::get('phone');

        if(Input::get('send_type')==1){
            $user_res = User::where([['status','=',1],['phone','=',$phone]])->value('phone');
            if($user_res){
                $return['status'] = 0;
                $return['msg'] = '该手机号已被注册';
                extjson($return);
            }
        }
        if(Input::get('send_type')==2){
            $user_res = User::where([['status','=',1],['phone','=',$phone]])->value('phone');
            if(!$user_res){
                $return['status'] = 0;
                $return['msg'] = '该手机号未注册';
                extjson($return);
            }
        }
        if(!preg_match("/^1[134578]\d{9}$/",$phone)){
            $return['status'] = 0;
            $return['msg'] = '请输入合法手机号';
            extjson($return);
        }

        $srand = rand(100000,999999);
        //$srand = 123456;
        $data = self::SendVerify($phone,$srand);
        if ($data->Message === 'OK') {
            Redis::set($phone,$srand);
            //Redis::expire($phone,600);//10分钟的有效期
            Redis::expire($phone,180);//10分钟的有效期
            $return['status'] = 1;
            $return['msg'] = '验证码发送成功!';
            extjson($return);
        } else {
            $return['status'] = 0;
            $return['msg'] = '获取验证码失败';
            extjson($return);
        }
    }


    /**
     *  用户注册
     *  @author neek
     *  @since v1.0
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
        if(strpos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(strpos($devid, 'android')!==false){
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
                User::where('id', $user_id)->update(['phone' => $phone,'password'=>$password,'platform_type'=>$platform_type,'source'=>$source_name,'source_id'=>$source_id]);
                $time = time();
                $key = md5($time."_yunshui^");
                $type = 1;//注册用户默认为普通用户
                $access_token = $user_id."::::".$nickname."::::".$phone."::::".$devid."::::".$type."::::".time()."::::".$key;
                $access_token = authcode($access_token,86400*720);

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
        $access_token = authcode($access_token,86400*720);

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
     *  @since v1.0
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

        $time = time();
        $key = md5($time."_yunshui^");
        $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
        $access_token = authcode($access_token,86400*720);

        $return['status'] = $user_info ? 1 : 0;
        $return['data'] = $user_info ? $user_info : '';
        $return['access_token'] = $user_info ? $access_token : '';
        $return['msg'] = $user_info ? '' : '登录失败';
        extjson($return);
    }

    /**
     * 三方登录
     * @author neekli
     * @since v1.0
     */
    public function snsLogin(){

        $openid = Input::get('openid');
        $sns_from = Input::get('sns_from');
        $devid = Input::get('devid');
        $username = Input::get('username') ? Input::get('username') : '';
        $img_url = Input::get('img_url') ? Input::get('img_url') : '';

        $platform_type = 0;
        if(strpos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(strpos($devid, 'android')!==false){
            $platform_type = 1;
        }

        //如果用户是存在的
        $check_sns = UserSns::where([['status','=',1],['from_sns','=',$sns_from],['openid','=',$openid]])->first();
        if($check_sns['id']>0){//直接登录成功
            UserSns::where('id', $check_sns['id'])->update(['lasttime' => time()]);
            $user_info = User::where([['status','=',1],['id','=',$check_sns['user_id']]])
                ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type','phone')
                ->first();
            if($user_info['phone']){
                $time = time();
                $key = md5($time."_yunshui^");
                $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
                $access_token = authcode($access_token,86400*720);
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
        $insert_userid = User::insertGetId(
            ['avatar' => $img_url ,'nickname' => $username, 'source'=>$source_name, 'source_id'=>$source_id,'platform_type'=>$platform_type,'password' => $password,'create_time'=>time()]
        );

        $insert_user_sns_id = UserSns::insertGetId(
            ['user_id' => $insert_userid ,'from_sns' => $sns_from ,'sns_username' => $username,'openid'=>$openid,'regtime'=>time()]
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
     * @since v1.0
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
        $access_token = authcode($access_token,86400*720);

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
     * @since v1.0
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

        $user_info = User::where([['status','=',1],['phone','=',$phone]])
            ->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type')
            ->first();
        if($user_info){
            $time = time();
            $key = md5($time."_yunshui^");
            $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$phone."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
            $access_token = authcode($access_token,86400*720);

            $return['status'] = 1;
            $return['data'] = $user_info;
            $return['access_token'] = $access_token;
            extjson($return);
        }

        //下载端
        $platform_type = 0;
        if(strpos($devid, 'ios')!==false){
            $platform_type = 2;
        }else if(strpos($devid, 'android')!==false){
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

        $time = time();
        $key = md5($time."_yunshui^");
        $access_token = $user_id."::::".$nickname."::::".$phone."::::".$devid."::::".time()."::::".$key;
        $access_token = authcode($access_token,86400*720);

        $return['status'] = $res ? 1 : 0;
        $return['data'] = $res ? array('id'=>$user_id,'avatar'=>'','nickname'=>$nickname,'gender'=>1,'birth_date'=>'','birth_place'=>'','type'=>1) : '';
        $return['access_token'] = $res ? $access_token : '';
        $return['msg'] = $res ? '' : '快速登录失败';
        extjson($return);
    }

}
