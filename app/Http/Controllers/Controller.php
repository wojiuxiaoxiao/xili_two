<?php

namespace App\Http\Controllers;

use App\Http\Models\Answer;
use App\Http\Models\Client;
use App\Http\Models\UserProfile;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Article;
use App\Http\Models\Active;
use App\Http\Models\Program;
use App\Http\Models\Comment;
use App\Http\Models\CommentActive;
use App\Http\Models\CommentArticle;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public  $userid = 0;

    public function __construct()
    {

        $access_token = Input::get('access_token');
        if ($access_token) {
            $authStr = authcode($access_token,'DECODE');
//            if($authStr == ''){
//                $return['status'] = 99999;
//                $return['msg'] = '请登录';
//                extjson($return);
//            }
//            var_dump($authStr);die;

            @list($uid, $nickname, $phone, $devid,$type) = explode("::::", $authStr);
            if ($uid) {
                $this->userid = $uid;
                defined("USERID") OR define("USERID", $uid);
                defined("NICKNMAE") OR define("NICKNMAE", $nickname);
                defined("PHONE") OR define("PHONE", $phone);
                defined("DEVID") OR define("DEVID", $devid);
                defined("TYPE") OR define("TYPE", $type);
            }

            //账户单端登录
//            $clientId = Input::get('client_id');
//
//            if($clientId) {
//                $client_res = Client::where([['user_id','=',$this->userid],['clientId','=',$clientId]])->value('id');
//                if(!$client_res){
//                    $return['status'] = 99999;
//                    $return['msg'] = '您的账号已在其他设备登录！';
//                    extjson($return);
//                }
//            }
        }

    }

    /**
     * 统一验证用户是否登录
     */
    public function checkUser(){
        if($this->userid<=0){
            $return['status'] = 99999;
            $return['msg'] = '请登录';
            extjson($return);
        }
    }


    /**
     * 判断活动、文章、节目是否存在
     */
    public function checkThree($type,$multi_id){

        $return['warm']['status'] = 1;
        $info = array();
        switch ($type)
        {
            case 1:
                $info = Active::where([['status','=',1],['id','=',$multi_id]])->select('id','likes')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '活动不存在';
                }
                break;
            case 2:
                $info = Article::where([['status','=',1],['id','=',$multi_id]])->select('id','likes')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '文章不存在';
                }
                break;
            case 3:
                $info = Program::where([['status','=',1],['id','=',$multi_id],['type','=',0]])->select('id','author_id')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '节目不存在';
                }
                break;

        }
        $return['data'] = $info;

        return $return;
    }

    /**
     * 判断评论宿主环境是否存在
     */
    public function checkCommentThree($type,$pmulti_id,$checkAppstatus=0){

        $return['warm']['status'] = 1;
        $info = array();
        switch ($type)
        {
            case 1:
                $where = array(
                    ['comment_active.status','=',1],
                    ['comment_active.id','=',$pmulti_id],
                    ['active.status','=',1],
                );
                $info = CommentActive::where($where)
                    ->leftJoin('active', 'comment_active.active_id', '=', 'active.id')
                    ->select('comment_active.id','comment_active.rootid')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '评论所在的活动不存在';
                }
                break;
            case 2:
                $where = array(
                    ['comment_article.status','=',1],
                    ['comment_article.id','=',$pmulti_id],
                    ['article.status','=',1],
                );
                $info = CommentArticle::where($where)
                    ->leftJoin('article', 'comment_article.article_id', '=', 'article.id')
                    ->select('comment_article.id','comment_article.rootid')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '评论所在的文章不存在';
                }
                break;
            case 3:
                $where = array(
                    ['comment.status','=',1],
                    ['comment.id','=',$pmulti_id],
                    ['program.status','=',1],
                    ['program.type','=',0],
                );
                $info = Comment::where($where)
                    ->leftJoin('program', 'comment.program_id', '=', 'program.id')
                    ->select('comment.id','comment.rootid','comment.user_id')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '评论所在的节目不存在';
                }
                break;
        }
        $return['data'] = $info;

        return $return;
    }

    /**
     * 检查评论是否存在
     */
    public function checkComment($type,$pmulti_id,$message="回复的评论不存在"){

        $return['warm']['status'] = 1;
        $info = array();
        switch ($type)
        {
            case 1:
                $info = CommentActive::where([['status','=',1],['id','=',$pmulti_id]])->select('active_id as multi_id','id','rootid','user_id','likes')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
            case 2:
                $info = CommentArticle::where([['status','=',1],['id','=',$pmulti_id]])->select('article_id as multi_id','id','rootid','user_id','likes')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
            case 3:
                $info = Comment::where([['status','=',1],['id','=',$pmulti_id]])->select('program_id as multi_id','id','rootid','user_id','likes')->first();
                if(!$info['id']){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
            case 5:
                $where = ['id' => $pmulti_id];
                $info = Answer::where($where)
                              ->select('id', 'likes', 'rootid', 'status', 'user_id')
                              ->first();
                if($info){
                    if (0 === $info['status']) {
                        $return['warm']['status'] = 0;
                        $return['warm']['msg'] = 0 === $info->rootid ? '回答已被删除' : '评论已被删除';
                    }
                    if (0 !== $info['rootid']) {
                        $answer = Answer::where('id', $info['rootid'])->value('status');
                        if (0 === $answer) {
                            $return['warm']['status'] = 0;
                            $return['warm']['msg'] = '回答已被删除';
                        }
                    }

                } else {
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = '回答不存在';
                }
                break;
        }
        $return['data'] = $info;

        return $return;
    }

    /**
     * 检查主评论是否存在
     */
    public function checkRootComment($type,$pmulti_id,$message="主评论不存在"){
        $return['warm']['status'] = 1;
        switch ($type)
        {
            case 1:
                $root_id = CommentActive::where([['status','=',1],['id','=',$pmulti_id]])->value('rootid');
                $info = CommentActive::where([['status','=',1],['id','=',$root_id]])->select('id')->first();
                if(!$info['id'] && $root_id!=0){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
            case 2:
                $root_id = CommentArticle::where([['status','=',1],['id','=',$pmulti_id]])->value('rootid');
                $info = CommentArticle::where([['status','=',1],['id','=',$root_id]])->select('id')->first();
                if(!$info['id']  && $root_id!=0){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
            case 3:
                $root_id = Comment::where([['status','=',1],['id','=',$pmulti_id]])->value('rootid');
                $info = Comment::where([['status','=',1],['id','=',$root_id]])->select('id')->first();
                if(!$info['id']  && $root_id!=0){
                    $return['warm']['status'] = 0;
                    $return['warm']['msg'] = $message;
                }
                break;
        }

        return $return;
    }

    /**
     * 更新client_id
     */
    public function updateClient($userid,$source_id,$client_id){
        if(!isset($client_id))return 0;
        $clinet_info = DB::table('client')->where([['clientId','=',$client_id],['status','=',1]])->first();
        if($userid){
            $common_info = DB::table('client')->where([['user_id','=',$userid],['clientId','=',$client_id],['status','=',1]])->first();
            if($common_info)return 0;
            $user_info = DB::table('client')->where([['user_id','=',$userid],['status','=',1]])->first();
            //同一个用户更换手机
            if(!$user_info && $clinet_info){
                DB::table('client')->where([['clientId','=',$client_id],['status','=',1]])->update(['user_id' => $userid]);
                return 0;
            }elseif (isset($user_info->clientId) && isset($user_info->user_id) && ($client_id != $user_info->clientId)) {
                DB::table('client')->where([['user_id','=',$userid],['status','=',1]])->update(['clientId' => $client_id]);
                return 0;
            }elseif(!$user_info && !$clinet_info){
                DB::table('client')->insert(
                    ['user_id' => $userid, 'clientId' => $client_id,'source_id'=>$source_id]
                );
                return 0;
            }elseif($user_info && $user_info->clientId == ''){
                DB::table('client')->where([['user_id','=',$userid],['status','=',1]])->update(['clientId' => $client_id]);
                return 0;
            }

            //同一个手机切换不同账户
            if(isset($clinet_info->clientId) && ($clinet_info->user_id) && ($clinet_info->user_id)!=0 && ($clinet_info->user_id!=$userid)){
                DB::table('client')->where([['clientId','=',$client_id],['status','=',1]])->update(['user_id' => $userid]);
                return 0;
            }
        }

        if(!isset($clinet_info->clientId)){
            DB::table('client')->insert(
                ['user_id' => $userid, 'clientId' => $client_id,'source_id'=>$source_id]
            );
            return 0;
        }

    }

    /**
     * 递增评论数、阅读数、点赞数、收藏数
     * @type 1活动、2文章
     * @dotype 1:评论 2:点赞 3:阅读 4:收藏
     * $multi_id 上面三者对应的id
     */
    public function increTimes($type,$multi_id,$dotype){
        if($dotype==1)$dotype='comment_nums';
        if($dotype==2)$dotype='likes';
        if($dotype==3)$dotype='views';
        if($dotype==4)$dotype='collect_nums';

        switch($type){
            case 1:
                Active::where([['status','=',1],['id','=',$multi_id]])->increment($dotype);
                break;
            case 2:
                Article::where([['status','=',1],['id','=',$multi_id]])->increment($dotype);
                break;
        }
    }

    /**
     * 递减评论数[暂时有这一个功能]decrement
     * @type 1活动、2文章、3节目
     * @dotype 1:评论 2:点赞 3:收藏
     */
    public function decreTimes($type,$multi_id,$dotype,$pmulti_id=0){
        if($dotype==1)$dotype='comment_nums';
        if($dotype==2)$dotype='likes';
        if($dotype==3)$dotype='collect_nums';
        switch($type){
            case 1:
                if($pmulti_id){
                    $root_id = CommentActive::where([['id','=',$pmulti_id]])->value('rootid');
                    if($root_id!==0)break;
                }
                Active::where([['status','=',1],['id','=',$multi_id]])->decrement($dotype);
                break;
            case 2:
                if($pmulti_id){
                    $root_id = CommentArticle::where([['id','=',$pmulti_id]])->value('rootid');
                    if($root_id!==0)break;
                }
                Article::where([['status','=',1],['id','=',$multi_id]])->decrement($dotype);
                break;
            case 3://暂时没用到
                Program::where([['status','=',1],['id','=',$multi_id]])->decrement($dotype);
                break;
        }
    }

    /**
     * 判断数组是否达到了万
     */
      public function testMillion($data){

          $changeM = $data/10000;
          $res = $changeM >= 1 ? substr(sprintf('%.2f', $changeM), 0, -1) . '万' : $data;
          return $res;
      }

      /**
       * 根据提交参数返回第几页和偏移量
       */
      public function getPageStart(){
          $input_arr = array();

          $page = Input::get('page') ?: 1;
          $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
          $input_arr['start'] = $pagesize*($page-1);
          $input_arr['pagesize'] = $pagesize;

          return $input_arr;
      }

    /**
     * 获取用户等级
     * @param $code
     * @return mixed
     */
    public function getLv($code){
        $lv = UserProfile::where([['score_end','>=',$code]])->orderBy('score_end','asc')->select('rankName')->first();
        return $lv['rankName'];
    }

}
