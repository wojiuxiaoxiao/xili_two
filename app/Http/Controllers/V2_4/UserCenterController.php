<?php
/**
 * 个人中心控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_4;

use App\Http\Models\Active;
use App\Http\Models\CollectMulti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Models\User;
use App\Http\Models\FeedBack;
use App\Http\Models\Column;
use App\Http\Models\Program;
use App\Http\Models\Collect;
use App\Http\Models\Comment;
use App\Http\Models\CommentActive;
use App\Http\Models\CommentArticle;
use App\Http\Models\FeedReply;
use App\Http\Models\CommentInterlocution;
use App\Http\Models\Interlocution;
use App\Http\Models\Client;
use App\Http\Controllers\Controller;
use App\Providers\GetuiServiceProvider;

use App\Http\Provider\V2_4\UserCenterService;

class UserCenterController extends Controller
{

    //客服电话
    public $service_phone = '0571-87813025';
    //客服email
    public $service_email = 'info@sfys365.com';

    private $userCenterService = null;

    public function __construct(UserCenterService $userCenterService)
    {
        parent::__construct();
        $this->userCenterService = $userCenterService;
    }

    /**
     * 个人中心基本信息
     * @author neekli
     * @since v2.4
     */
    public function baseInfo(){
        $this->checkUser();

        $user_info = User::where([['status','=',1],['id','=',USERID]])->select('id','avatar','nickname','signature','gender','birth_date','birth_place','type as user_type')->first();

        if (TYPE == 2) {
            $comment_num = Comment::where([['status','=',1],['read_status','=','0'],['author_id','=',USERID]])->count();
        } else {
            $comment_num = Comment::where([['comment.status','=',1],['read_status','=','0']])
                ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                ->count();
        }

        //判断红点
        $res1 = Comment::where([['comment.status','=',1],['read_status','=','0']])
            ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
            ->count();

        $res2 = CommentActive::where([['comment_active.status','=',1],['comment_active.read_status','=','0']])
            ->whereIn('pid', CommentActive::where([['comment_active.status','=',1],['comment_active.user_id','=',USERID]])->pluck('id'))
            ->count();
        $res3 = CommentArticle::where([['comment_article.status','=',1],['comment_article.read_status','=','0']])
            ->whereIn('pid', CommentArticle::where([['comment_article.status','=',1],['comment_article.user_id','=',USERID]])->pluck('id'))
            ->count();

        $where = array(
            ['feed_reply.status','=',1],
            ['feed_reply.read_status','=',0],
            ['feed_back.user_id','=',USERID],
        );
        $res4 = FeedReply::where($where)->leftJoin('feed_back', 'feed_reply.feed_id', '=', 'feed_back.id')->count();

        //回复消息
        $res5 = CommentInterlocution::whereIn('pid', CommentInterlocution::where([['comment_interlocution.user_id','=',USERID]])->pluck('id'))
            ->where([['comment_interlocution.rootid','<>',0],['comment_interlocution.read_status','=','0']])
            ->count();

        //回答消息
        $res6 = CommentInterlocution::whereIn('interlocut_id', Interlocution::where([['interlocution.user_id','=',USERID]])->pluck('id'))
            ->where([['comment_interlocution.rootid','=',0],['comment_interlocution.read_status','=','0']])
            ->count();

        $user_info['program_c_status'] = $res1 ? 1 : 0;
        $user_info['active_c_status'] = $res2 ? 1 : 0;
        $user_info['article_c_status'] = $res3 ? 1 : 0;
        $user_info['feed_status'] = $res4 ? 1 : 0;
        $user_info['answer_tp_status'] = $res5 ? 1 : 0;
        $user_info['answer_rp_status'] = $res6 ? 1 : 0;

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
     * @since v2.4
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
     * @since v2.4
     */
    public function issueFb(){
        $img_url = json_decode(Input::get('img_url'));//将上传返回的img_url的作为入参
        $img_url = $img_url ? implode(',',$img_url) : '';
        $devid = Input::get('devid');
        $version = Input::get('version');
        $platform_type = 0;
        if(stripos($devid, 'ios')!==false){
            $platform_type = 1;
        }else if(stripos($devid, 'android')!==false){
            $platform_type = 2;
        }

        $data['user_id'] = USERID;
        $data['user_name'] = NICKNMAE;
        $data['user_phone'] = PHONE;

        $data['type'] = Input::get('type');
        $data['content'] = Input::get('content') ?: '';

        $data['device'] = $platform_type;
        $data['version'] = $version;

        $data['create_time'] = time();
        $data['pic'] = $img_url;

        $create_res = FeedBack::create($data);

        extOperate($create_res,'反馈失败','谢谢您的反馈');
    }

    /**
     * 设置或者修改昵称
     * @author neekli
     * @since v2.4
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
     * @since v2.4
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
     * @since v2.4
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
     * @since v2.4
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
     * @since v2.4
     */
    public function setBirthplace(){
        $this->checkUser();
        $birthplace = trim(Input::get('birthplace'));

        try {
            User::where([['status','=',1],['id','=',USERID]])->update(['birth_place' => $birthplace]);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0,'');
        }
    }

    /**
     * 联系我们
     * @author neekli
     * @since v2.4
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
     * @since v2.4
     */
    public function myCollect(){
        $this->checkUser();

        $input_arr = $this->getPageStart();
        $input_arr['type'] = Input::get('type');
        $input_arr['userid'] = USERID;

        $return_c_list = array();
        switch ($input_arr['type'])
        {
            case 2:
                $return_c_list = $this->userCenterService->article_c_list($input_arr);
                break;
            case 3:
                $return_c_list = $this->userCenterService->program_c_list($input_arr);
                break;
            case 5:
                $return_c_list = $this->userCenterService->answer_c_list($input_arr);
                break;
        }

        extInfo($return_c_list);
    }


    /**
     * 删除client_id
     */
    public function deleteClientid(){  
        $clientId = Input::get('client_id');
        try {
            Client::where([['clientId','=',$clientId],['status','=',1]])->update(['clientId' => '']);
            extOperate(1,'','修改成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0,'');
        }

    }


    /**
     * 个人中心提问列表
     * @author zhuoshan
     * @access public
     */
    public function askList()
    {
        $input = $this->getPageStart();
        $input['user_id'] = Input::get('user_id');
        $result = $this->userCenterService->_askList($input);
        extjson($result);
    }

    /**
     * 个人中心回答列表
     * @author zhuoshan
     * @access public
     */
    public function answerList()
    {
        $input = $this->getPageStart();
        $input['user_id'] = Input::get('user_id');
        $result = $this->userCenterService->_answerList($input);
        extjson($result);
    }

}
