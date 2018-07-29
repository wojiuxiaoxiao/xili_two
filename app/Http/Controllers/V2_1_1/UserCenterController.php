<?php
/**
 * 个人中心控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_1_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Models\User;
use App\Http\Models\FeedBack;
use App\Http\Models\Column;
use App\Http\Models\Program;
use App\Http\Models\Collect;
use App\Http\Models\Comment;
use App\Http\Controllers\Controller;

class UserCenterController extends Controller
{

    //客服电话
    public $service_phone = '0571-87813025';
    //客服email
    public $service_email = 'info@sfys365.com';

    /**
     * 个人中心基本信息
     * @author neekli
     * @since v1.0
     */
    public function baseInfo(){
        $this->checkUser();

        $user_info = User::where([['status','=',1],['id','=',USERID]])->select('avatar','nickname','signature','gender','birth_date','birth_place','type')->first();

        if (TYPE == 2) {
            $comment_num = Comment::where([['status','=',1],['read_status','=','0'],['author_id','=',USERID]])->count();
        } else {
            $comment_num = Comment::where([['comment.status','=',1],['read_status','=','0']])
                ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                ->count();
        }

        //数据适配
        $user_info['gender'] = $user_info['gender']==1 ? '男' : '女';
        $user_info['birth_date'] = date('Y-m-d',($user_info['birth_date'] ?? time()));
        $user_info['avator'] = ''.$user_info['avator'];
        $user_info['comment_num'] = $comment_num;

        $return['status'] = 1;
        $return['data'] = $user_info ? $user_info : '';
        $return['msg'] = $user_info ? '' : '加载数据失败';
        $return['access_token'] = $user_info ? Input::get('access_token') : '';
        extjson($return);
    }

    /**
     * 选择头像
     * @author neekli
     * @since v1.0
     */
    public function uploadAvatar(){
        $this->checkUser();

        $img_url = Input::get('img_url');

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['avatar' => $img_url]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0);
        }
    }

    /**
     *  问题反馈
     * @author neekli
     * @since v1.0
     */
    public function issueFb(){

        $img_url = json_decode(Input::get('img_url'));//将上传返回的img_url的作为入参
        $img_url = $img_url ? implode(',',$img_url) : '';
        $data['user_id'] = USERID;
        $data['user_name'] = NICKNMAE;
        $data['user_phone'] = PHONE;

        $data['type'] = Input::get('type');
        $data['content'] = Input::get('content') ?: '';
        $data['create_time'] = time();
        $data['pic'] = $img_url;

        $create_res = FeedBack::create($data);

        extOperate($create_res,'反馈失败','谢谢您的反馈');
    }

    /**
     * 设置或者修改昵称
     * @author neekli
     * @since v1.0
     */
    public function setNickname(){
        $this->checkUser();

        $nick_name = Input::get('nickname');
        $devid = Input::get('devid');

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['nickname' => $nick_name]);
            $user_info = User::where([['status','=',1],['id','=',USERID]])->first();

            FeedBack::where([['status','=',1],['user_id','=',USERID]])->update(['user_name' => $nick_name]);//反馈表
            Column::where([['status','=',1],['user_id','=',USERID]])->update(['user_name' => $nick_name]);//栏目表
            Program::where([['status','=',1],['author_id','=',USERID]])->update(['author_name' => $nick_name]);//节目表

            $time = time();
            $key = md5($time."_yunshui^");
            $access_token = $user_info['id']."::::".$user_info['nickname']."::::".$user_info['phone']."::::".$devid."::::".$user_info['type']."::::".time()."::::".$key;
            $access_token = authcode($access_token,86400*720);

            $return['status'] = 1;
            $return['access_token'] = $user_info ? $access_token : '';
            $return['msg'] = '修改成功';
            extjson($return);
        } catch (Exception $e) {

        } finally {
            extOperate(0,'设置昵称失败');
        }
    }

    /**
     * 设置或者修改个性签名
     * @author neekli
     * @since v1.0
     */
    public function setSignature(){
        $this->checkUser();
        $signature = Input::get('signature');

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['signature' => $signature]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(false);
        }
    }

    /**
     * 设置或者修改性别
     * @author neekli
     * @since v1.0
     */
    public function setGender(){
        $this->checkUser();
        $gender = intval(Input::get('gender'));

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['gender' => $gender]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(false);
        }
    }

    /**
     * 设置或者修改出生日期
     * @author neekli
     * @since v1.0
     */
    public function setBirthdate(){
        $this->checkUser();
        $birthdate = intval(Input::get('birthdate'));

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['birth_date' => $birthdate]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0);
        }
    }

    /**
     * 设置或者修改出生地
     * @author neekli
     * @since v1.0
     */
    public function setBirthplace(){
        $this->checkUser();
        $birthplace = trim(Input::get('birthplace'));

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['birth_place' => $birthplace]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0,'设置出生地失败');
        }
    }

    /**
     * 联系我们
     * @author neekli
     * @since v1.0
     */
    public function contactUs(){

        $data = array(
            'service_phone' => $this->service_phone,
            'service_email' => $this->service_email,
            'logo' => config('yunshui.HTTP_RUL'),
        );

        extInfo($data);
    }


    /**
     * 我的收藏
     * @author neekli
     * @since v1.0
     */
    public function myCollect(){
        $this->checkUser();

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $collect_list = Collect::where([['collect.status','=',1],['collect.user_id','=',USERID]])
            ->select('program.id','program.name','program.radio_pic','program.radio_url','program.column_name','program.burning_time','program.type','program.status as del_status')
            ->leftJoin('program', 'program.id', '=', 'collect.program_id')
            ->offset($start)
            ->orderBy('collect.create_time','desc')
            ->limit($pagesize)
            ->get();

        extInfo($collect_list);
    }

    /**
     * 我的消息
     * @author neekli
     * @since v1.0
     */
    public function myMessage(){
        $this->checkUser();
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $type = User::where([['status','=',1],['id','=',USERID]])->value('type');
        if($type==2){//如果是大师  直接评论观南的节目 和 回复评论观南的评论  （作者是观南 rootid=0  or  找出观南的评论id 评论的父级id是观南的评论id ）
            $comment_list = Comment::where([['comment.status','=',1],['comment.author_id','=',USERID],['rootid','=',0]])->orWhereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                ->leftJoin('program', 'program.id', '=', 'comment.program_id')
                ->leftJoin('user', 'user.id', '=', 'comment.user_id')
                ->select('user.avatar','comment.id','comment.user_nickname as nickname','comment.content','comment.rootid','comment.pid','comment.create_time','program.name','program.id as program_id')
                ->orderBy('create_time', 'desc')
                ->offset($start)
                ->limit($pagesize)
                ->get();

            foreach($comment_list as $k=>$comment){//如果该评论是子评论的话  找出该评论的父评论   找出该评论的节目
                if ($comment['rootid'] != 0) {
                    $comment_father= Comment::where([['status','=',1],['id','=',$comment['pid']]])->select('content','user_nickname')->orderBy('create_time', 'desc')->first();
                    $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
                }
                $program_info = Program::where([['id','=',$comment['program_id']]])->select('id','name','showup_time','radio_url','radio_pic','burning_time','column_name')->first();
                $comment_list[$k]['program'] = $program_info ? $program_info->toArray() : null;
            }
            $return['data'] = $comment_list ? $comment_list : array();
            $return['status'] = 1;
//            $return['msg'] = $comment_list ? '' : '加载数据失败';
            $return['msg'] = '';
            extjson($return);
        }

        $comment_list = Comment::where([['comment.status','=',1]])//所有评论
            ->select('user.avatar','user.nickname','comment.id','comment.create_time','comment.rootid','comment.content','comment.pid','comment.program_id')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->orderBy('create_time', 'desc')
            ->get()->toArray();

        $return = array();
        foreach($comment_list as $k=>$comment){//找出评论的父评论且是用户的评论

            $comment_father= Comment::where([['status','=',1],['id','=',$comment['pid']],['user_id','=',USERID]])->select('content','user_nickname')->orderBy('comment.create_time', 'desc')->first();
            $program_info = Program::where([['id','=',$comment['program_id']]])->select('id','name','showup_time','radio_url','radio_pic','burning_time','column_name')->first();
            $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
            $comment_list[$k]['program'] = $program_info ? $program_info->toArray() : null;
            if($comment_list[$k]['father']){
                $return[$k] = $comment_list[$k];
            }
        }

        $return = array_splice($return,$start,$pagesize);
        $tmp['data'] = $return ? $return : array();
        $tmp['status'] = 1;
//        $tmp['msg'] = $return ? '' : '加载数据失败';
        $tmp['msg'] = '';
        extjson($tmp);
    }

    /**
     * 请空我的消息
     * @author neekli
     * @since v1.0
     */
    public function clearMyMesssage(){
        $this->checkUser();

        try {
            if (TYPE == 2) {
                Comment::where([['status','=',1],['read_status','=','0'],['author_id','=',USERID]])->update(['read_status' => 1]);
            } else {
                Comment::where([['comment.status','=',1],['comment.read_status','=','0']])
                    ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                    ->update(['comment.read_status' => 1]);
            }
            extOperate(1);
        } catch (Exception $e) {

        } finally {
            extOperate(0);
        }

    }

    public function myMessage_test(){
        //phpinfo();die;
        echo de_crypt('o5ZPvhrKkcqT3tjMZTjLaw%3D%3D%0A');die;
//dd(USERID);
        $this->checkUser();
        $user_info = User::where([['status','=',1],['id','=',USERID]])->with('comment')->first();
        //$data                    = AD::where('id', $id)->with('News')->with('Time')->first();

        //$comment = Comment::where([['comment.status','=',1]])->pluck('id');
        $comment_list = Comment::where([['comment.status','=',1]])
            ->whereIn('id', Comment::where([['comment.status','=',1]])->pluck('id'))
            ->count();
        var_dump($comment_list);
    }

}
