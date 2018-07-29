<?php
/**
 * Created by PhpStorm.
 * User: neekli
 * Date: 2018/5/15
 * Time: 14:36
 */

namespace App\Http\Provider;


use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use App\Http\Models\User;

class GrowthRuleService
{

    /**
     * category   sign   like    comment  share   collect  focus   listen  view    pose      reply   boutry      accept
     * type       1签到  2点赞   3评论    4分享   5收藏    6关注   7收听   8浏览   9发布提问 10回答  11发布悬赏  12采纳
     * score             2       5        10      2        2       10      10      10        20      50          20
     * limit             5       5        5       5        5       5       5       5         5
     * 除发布悬赏、采纳不入缓存，其他都入缓存，规则如下
     * 如 user:userid:9:type:3  123454:2
     */
    private $type_arr = array( 'like'=>2,'comment'=>3,'share'=>4, 'collect'=>5,'focus'=>6,'listen'=>7, 'view'=>8, 'pose'=>9,'reply'=>10, 'boutry'=>11,'accept'=>12,);

    private $score_arr = array( 'like'=>2, 'comment'=>5,'share'=>10,'collect'=>2,'focus'=>2,'listen'=>10,'view'=>10,'pose'=>10,'reply'=>20,'boutry'=>50,'accept'=>20,);

    private $user_id;
    private $type;
    private $score;
    public function __construct($user_id,$category){
        $this->user_id = $user_id;
        if($category!='sign'){
            $this->type = $this->type_arr[$category];
            $this->score = $this->score_arr[$category];
        }
    }

    /**
     * 获取键
     * @param $user_id
     * @param $type
     * @return string
     */
    public function getKey($user_id,$type){
        return 'user:userid:'.$user_id.':type:'.$type;
    }

    /**
     * 入口方法
     */
    public  function init(){
        $today_start = strtotime(date("Y-m-d",time()));

        if($this->type==11 || $this->type==12){
            $this->addScore();
            return;
        }

        $key = $this->getKey($this->user_id,$this->type);
        $value = Redis::get($key);
        if(!$value){
            $value = time().':1';
            Redis::set($key,$value);
            $this->addScore();
            return;
        }
        if($value){
            $value_arr = explode(':',$value);
            $time_start = strtotime(date("Y-m-d",$value_arr[0]));
            $time_diff = ($today_start-$time_start)/3600;

            if($time_diff>=24){
                $value = time().':1';
                Redis::set($key,$value);
                $this->addScore();
            }elseif($time_diff<24){
                if($value_arr[1]>=5){
                    return;
                }elseif($value_arr[1]<5){
                    $value = $value_arr[0].':'.($value_arr[1]+1);
                    Redis::set($key,$value);
                }

                $this->addScore();
            }
        }

    }

    /**
     * 加积分
     * @author neekli
     * @version common
     */
    public function addScore(){
        User::where('id',$this->user_id)->increment('active', $this->score);
        $this->updateRankid($this->user_id);
    }


    /**
     * 更新用户等级id
     */
    public function updateRankid($user_id){
        $scoure_res = User::where('user.id',$user_id)
            ->whereColumn([
                ['user.active', '>=', 'user_profile.score_start'],
                ['user.active', '<', 'user_profile.score_end']
            ])->select('user.active','user_profile.score_start','user_profile.score_end')
            ->leftJoin('user_profile', 'user_profile.id', '=', 'user.profile_id')
            ->first();

        if(!$scoure_res){
            User::where('id',$user_id)->increment('profile_id');
        }
    }


    /**
     * 签到
     * @author
     * @version common
     */
    public function signIn(){
        $today_start = strtotime(date("Y-m-d",time()));

        $h_user = 'useri'.$this->user_id;
        $sign_res = Redis::hGetAll($h_user);

        if(empty($sign_res)){
            Redis::hset($h_user,time(),1);
            $return['status'] =  1;
            $return['times'] = 1;
            $return['msg'] = '你已经连续1天签到';
            $this->addSignScore(1);
        }else{
            $sign_time = array_keys($sign_res)[0];
            $sign_value = array_values($sign_res)[0];

            $sign_time_start = strtotime(date("Y-m-d",$sign_time));
            $time_diff = ($today_start-$sign_time_start)/3600;

            if($time_diff>24){//当前时间对应的今日起始时间比上次签到时间多24小时，则清空记录
                Redis::del($h_user);

                Redis::hset($h_user,time(),1);
                $return['status'] =  1;
                $return['times'] = 1;
                $return['msg'] = '你已经连续1天签到';
                $this->addSignScore(1);
            }elseif($time_diff==0){//当前时间对应的今日起始时间与上次签到时间为同一天
                $return['status'] =  0;
                $return['msg'] = '';
            }elseif($time_diff=24){
                Redis::del($h_user);

                Redis::hset($h_user,time(),$sign_value+1);
                $return['status'] =  1;
                $return['times'] = $sign_value+1;
                $return['msg'] = '你已经连续'.$return['times'].'天签到';
                $this->addSignScore($return['times']);
            }

        }

        return $return;
    }

    /**
     * 添加签到积分
     * @times 连续签到次数
     * @author neekli
     * @version common
     */
    public function addSignScore($times){
        $score = 0;
        if($times<7){
            $score = 5;
        }elseif($times>=7 && $times<30){
            $score = 10;
        }elseif($times>=30){
            $score = 20;
        }

        User::where('id',$this->user_id)->increment('active', $score);
        $this->updateRankid($this->user_id);
    }

    /**
     * 返回签到状态和连续签到天数
     * @author neekli
     * @since common
     */
    public function signStatus(){
        $today_start = strtotime(date("Y-m-d",time()));

        $h_user = 'useri'.$this->user_id;
        $sign_res = Redis::hGetAll($h_user);

        if(empty($sign_res)){
            $return['status'] =  0;
            $return['times'] = 0;
        }else{
            $sign_time = array_keys($sign_res)[0];
            $sign_value = array_values($sign_res)[0];

            $sign_time_start = strtotime(date("Y-m-d",$sign_time));
            $time_diff = ($today_start-$sign_time_start)/3600;

            if($time_diff>24){//当前时间对应的今日起始时间比上次签到时间多24小时
                $return['status'] =  0;
                $return['times'] = 0;
            }elseif($time_diff==0){//当前时间对应的今日起始时间与上次签到时间为同一天
                $return['status'] =  1;
                $return['times'] = $sign_value;
            }elseif($time_diff=24){
                $return['status'] =  0;
                $return['times'] = $sign_value;
            }

        }

        return $return;
    }

}

/*
//累加积分
$growth_service = new GrowthRuleService(USERID,'like');
$growth_service->init();
*/