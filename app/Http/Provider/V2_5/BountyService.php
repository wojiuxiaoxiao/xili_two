<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/14
 * Time: 11:06
 */

namespace App\Http\Provider\V2_5;

use App\Http\Models\FeedBack;
use App\Http\Models\FeedReply;
use App\Http\Models\InterlocutionGroup;
use App\Http\Models\PropelHistory;
use App\Http\Provider\GrowthRuleService;
use App\Providers\GetuiServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Http\Provider\CommonService;

use App\Http\Models\Order;
use App\Http\Models\Focus;
use App\Http\Models\User;
use App\Http\Models\Request;
use App\Http\Models\SignedAuthor;
use App\Http\Models\InterlocutionBounty;
use App\Http\Models\CommentInterlocutionBounty;


class BountyService extends CommonService
{
    private $alipay_status=1;
    private $wxpay_status=1;

    /**
     * 添加悬赏
     */
    public function  addbounty($input_param){

        $res = false;
        $group = InterlocutionGroup::where('status',1)->where('shelves',1)->where('id',$input_param['group_id'])->first();
        if (!$group) {
            return array('status'=>0,'msg'=>'此分类已下架');
        }
        if($group['group_price'] > $input_param['price'] * 100) {
            return array('status'=>0,'msg'=>'悬赏额少于该分类最低金额'.number_format($group['group_price']/100,2).'元！');
        }
        DB::beginTransaction();
        try {
            $bounty_data = $order_data = array();
            $bounty_data['user_id'] = $input_param['user_id'];
            $bounty_data['title'] = $input_param['title'];
            $bounty_data['content'] = $input_param['content'];
            $bounty_data['pic'] = $input_param['pic'] ?? '';
            $bounty_data['group_id'] = $input_param['group_id'];//分组id
            $bounty_data['price'] = $input_param['price'] * 100;//实际存储的都为分
            $bounty_data['anonymous'] = $input_param['anonymous'];//是否匿名
            $bounty_data['create_time'] = time();
            $bounty_data['update_time'] = time();
            $bounty_data['status'] = 0;
            $insert_id = InterlocutionBounty::insertGetId($bounty_data);

            if(isset($input_param['master_id'])){
                //添加邀请表数据
                Request::insertGetId(['interlocboun_id'=>$insert_id,'master_id'=>$input_param['master_id'],'create_time'=>time()]);
            }

            //生成订单
            $order_data['out_trade_no'] = $this->getNumberId();//订单编号
            $order_data['goods_id'] = $insert_id;//商品id
            $order_data['user_id'] = $input_param['user_id'];
            $order_data['price'] = $input_param['price'] * 100;//实际存储的都为分
            $order_data['create_time'] = time();
            $order_data['order_status'] = 1;
            Order::insertGetId($order_data);

            DB::commit();
            $res = true;
        } catch (\Exception $e) {
            DB::rollBack();
        }

        if($res && isset($input_param['master_id'])){//添加推送
     /*       $price = $input_param['price'];
            $body = "赚赏金！有人邀请您回答他的悬赏".$input_param['title']."，赏金高达".$price."元马上去回答";
            $this->signedAuthorTui($input_param['master_id'], $body ,$insert_id);*/
            $price = $input_param['price'];
            $body = "赚赏金！有人邀请您回答他的悬赏<".$input_param['title'].">，赏金高达".$price."元马上去回答";

            //计入系统消息
            $feed_data['user_id'] = $input_param['master_id'];
            $feed_data['type'] = 15;
            $feed_data['content'] = $body;
            $feed_data['create_time'] = time();
            $feed_data['feed_id'] = FeedBack::insertGetId($feed_data);
            unset($feed_data['user_id']);
            unset($feed_data['type']);
            FeedReply::create($feed_data);
        }

        $result['status'] = $res ? 1 : 0;
        $result['msg'] = $res ? '' : '添加回答失败';
        $priceOut = number_format($input_param['price'], 2);
        $result['order_info'] = array('out_trade_no'=>$order_data['out_trade_no'],'price'=>$priceOut,'alipay_status'=>$this->alipay_status,'wxpay_status'=>$this->wxpay_status);

        return $result;
    }

    /**
     * 邀请人列表
     */
    public function inviters($input_param){

        $invitersList = array();
        if($input_param['type']==1){
            $invitersList = SignedAuthor::where([['signed_author.recommend','=',1],['signed_author.status','=',1],['user.status','=',1]])
                ->select('user.nickname','user.id as master_id','user.avatar','user.signature')
                ->leftJoin('user', 'user.id', '=', 'signed_author.user_id')
                ->orderBy('signed_author.update_time', 'desc')
                ->offset($input_param['start'])
                ->limit($input_param['pagesize'])
                ->get();
        }else{  
            $invitersList = Focus::where([['user.status','=',1],['focus.user_id','=',$input_param['user_id']]])
                ->select('user.nickname','user.id as master_id','user.avatar','user.signature')
                ->leftJoin('user', 'user.id', '=', 'focus.master_id')
                ->orderBy('focus.create_time', 'desc')
                ->offset($input_param['start'])
                ->limit($input_param['pagesize'])
                ->get();
        }

        return $invitersList;
    }

    /**
     * 悬赏列表
     * type 1:所有 2:未采纳 3:已采纳
     */
    public function bountryList($input_arr){

        switch ($input_arr['type'])
        {
            case 1:
                $where = [['interlocution_bounty.status','=',1],['interlocution_bounty.violate','=',1],['interlocution_group.shelves','=',1]];
                break;
            case 2:
                $where = [['interlocution_bounty.status','=',1],['interlocution_bounty.violate','=',1],['interlocution_bounty.comment_id','=',0],['interlocution_group.shelves','=',1]];
                break;
            case 3:
                $where = [['interlocution_bounty.status','=',1],['interlocution_bounty.violate','=',1],['interlocution_bounty.comment_id','>',0],['interlocution_group.shelves','=',1]];
                break;
        }

        //检索出不违规并且已经支付成功的悬赏
        $ibList = InterlocutionBounty::where($where)
            ->whereIn('order.order_status', [3, 4])
            ->select('interlocution_bounty.id as interlocut_id','interlocution_bounty.user_id','interlocution_bounty.anonymous','interlocution_bounty.title','interlocution_bounty.content','interlocution_bounty.price','interlocution_bounty.views','interlocution_bounty.answer_nums','interlocution_bounty.comment_id','user.avatar','user.nickname','user.active')
            ->leftJoin('order', 'order.goods_id', '=', 'interlocution_bounty.id')
            ->leftJoin('user', 'interlocution_bounty.user_id', '=', 'user.id')
            ->leftJoin('interlocution_group', 'interlocution_group.id', '=', 'interlocution_bounty.group_id')
            ->orderBy('interlocution_bounty.update_time', 'desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get();

        foreach($ibList as $k=>$v){
            $ibList[$k]['price'] = number_format($v['price']/100,2);
            $ibList[$k]['accept_status'] = $v['comment_id'] ? 1 :0;
            $ibList[$k]['lv'] = $this->getLv(intval($v['active']));
            $ibList[$k]['views'] = $this->testMillion($v['views']);
            $ibList[$k]['answer_nums'] = $this->testMillion($v['answer_nums']);
        }

        return $ibList;
    }

    /**
     * 悬赏问答详情
     */
    public function boutryInfo($input_arr){

        $group_id = $this->getGroIdByBountryInter($input_arr['interlocut_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($group_id);
            $this->verifyBountry($input_arr['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => 1,
                'msg' => $e->getMessage(),
                'data' => ['status' => 0]
            ];
        }

        $bountryInfo = InterlocutionBounty::where([['status','=',1],['id','=',$input_arr['interlocut_id']]])
            ->select('id as interlocution_bounty_id','title','pic','anonymous','content','create_time','views','price','user_id','answer_nums','comment_id')
            ->first();

        $userInfo = User::where('id',$bountryInfo['user_id'])->select('avatar','nickname','active','type as user_type')->first();
        $master_id = Request::where('interlocboun_id',$input_arr['interlocut_id'])->value('master_id');
        if($master_id>0){
            $masterInfo = User::where('id',$master_id)->select('avatar','nickname','id as master_id')->first();
        }

        $bountryInfo['views'] = $this->testMillion($bountryInfo['views']);
        $bountryInfo['price'] = number_format($bountryInfo['price']/100,2);
        $bountryInfo['lv'] = $this->getLv(intval($userInfo['active']));
        $bountryInfo['accept_status'] = $bountryInfo['comment_id'] ? 1 :0;
        $bountryInfo['avatar'] = $userInfo['avatar'] ? $userInfo['avatar'] : '';
        $bountryInfo['nickname'] = $userInfo['nickname'] ? $userInfo['nickname'] : '';
        $bountryInfo['user_type'] = $userInfo['user_type'] ?: 1;//获取用户类型
        $bountryInfo['master_info'] = $masterInfo ?? null;
        $bountryInfo['pic'] =  $bountryInfo['pic'] ? json_decode($bountryInfo['pic'], true) : [];//图片字段变为array

        $share = [
            'pic'=>config('yunshui.http_url').'/img/share.png',
            'name'=>$bountryInfo['title'],
            'summary'=>$bountryInfo['content'],
            'url'=>config('yunshui.http_url').'/share?share_id='.$input_arr['interlocut_id'].'&type=6',
        ];

        $bountryInfo['share'] =  $share;
        $bountryInfo['is_own'] = $this->is_own($bountryInfo['user_id']);//是否本人 1-是 0-不是

        //递增阅读数
        InterlocutionBounty::where([['status','=',1],['id','=',$input_arr['interlocut_id']]])->increment('views');

        $return = [
            //正常情况下，内层外层的status都为1
            'status' => 1,
            'msg' => '',
            'data' => $bountryInfo
        ];
        $return['data']['status'] = 1;
        return $return;
    }

    /**
     * 悬赏问答详情之全部回答列表
     */
    public function bountryReplyList($input_arr){

        $group_id = $this->getGroIdByBountryInter($input_arr['interlocut_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($group_id);
            $this->verifyBountry($input_arr['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => 0,//没有正确返回值也返回1，根据data判断
                'msg' => $e->getMessage()
            ];
        }


        //首先取出已经采纳的回答
        $accept_answer = $this->acceptAnswer($input_arr['interlocut_id']);
        $where = [
            ['status','=',1],['interlocution_bounty_id','=',$input_arr['interlocut_id']]
        ];
        if($accept_answer){
            $input_arr['pagesize'] = $input_arr['pagesize'] -1;
            $where = [
                ['status','=',1],['interlocution_bounty_id','=',$input_arr['interlocut_id']],['id','<>',$accept_answer[0]['comment_interlocution_bounty_id']]
            ];
        }


        $bountryReplyList = CommentInterlocutionBounty::where($where)
            ->select('id as comment_interlocution_bounty_id','content','user_id','interlocution_bounty_id','create_time','type','likes')
            ->orderBy('create_time', 'desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get()->toArray();

        if($accept_answer){
            array_splice($bountryReplyList,0,0,$accept_answer);
        }

        $return_arr = $inter_arr = array();
        foreach($bountryReplyList as $k=>$v){
            $is_status = InterlocutionBounty::where('comment_id',$v['comment_interlocution_bounty_id'])->value('status');
            $inter_arr['accept_status'] = $is_status ? 1 : 0;
            $userInfo = User::where('id',$v['user_id'])->select('id','avatar','nickname','active','type')->first();
            $inter_arr['lv'] = $this->getLv(intval($userInfo['active']));
            $inter_arr['content'] = $v['content'];
            $inter_arr['type'] = $v['type'];
            $inter_arr['comment_interlocution_bounty_id'] = $v['comment_interlocution_bounty_id'];
            $inter_arr['create_time'] = $v['create_time'];
            $inter_arr['user_type'] = $userInfo['type'];
            $inter_arr['likes'] = $v['likes'];
            $inter_arr['user_id'] = $userInfo['id'];
            $inter_arr['avatar'] = $userInfo['avatar'] ? $userInfo['avatar'] : '';
            $inter_arr['nickname'] = $userInfo['nickname'] ? $userInfo['nickname'] : '';

            $is_own = $this->is_own($v['user_id']);
            $inter_arr['canDel'] = (!$is_own || $inter_arr['accept_status']) ? 0 : 1;
            $inter_arr['bountry_answer'] = 1;
            $return_arr[] = $inter_arr;
        }

        $last_arr['answer_list'] = $return_arr;
        $last_arr['answer_nums'] = InterlocutionBounty::where([['status','=',1],['id','=',$input_arr['interlocut_id']]])->value('answer_nums')?:0;
        return [
            'status' => 1,
            'data' => $last_arr
        ];
    }
    /**
     * 已经采纳的回答
     */
    public function acceptAnswer($interlocut_id) {
        $bountryInfo = InterlocutionBounty::where('id',$interlocut_id)->first();

        $bountryReply = CommentInterlocutionBounty::where([
            ['status','=',1],
            ['id','=',$bountryInfo['comment_id']],
        ])->select(
            'id as comment_interlocution_bounty_id',
            'content',
            'user_id',
            'interlocution_bounty_id',
            'create_time',
            'type',
            'likes'
        )->get()->toArray();

        return $bountryReply;
    }

    /**
     * 回答悬赏提问接口
     */
    public function bountryReply($input){
        $group = $this->getGroIdByBountryInter($input['interlocut_id']);//通过提问id获取分类id
        try {
            $this->virifyGroup($group);
            $this->verifyBountry($input['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $bountry_info = InterlocutionBounty::where([['id','=',$input['interlocut_id']]])->first();
        $input['create_time'] = time();
        DB::beginTransaction();
        $res = false;
        try {
            $data['user_id'] = $input['user_id'];
            $data['interlocution_bounty_id'] = $input['interlocut_id'];
            $data['group_id'] = $group;
            $data['content'] = $input['content'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            CommentInterlocutionBounty::create($data);//插入数据
            InterlocutionBounty::where('id', $input['interlocut_id'])->increment('answer_nums');//插入一级回答，则在提问表中一级回答数加1
            DB::commit();

            //添加推送
            $body = "有大师接了你的悬赏<".$bountry_info['title'].">，快看看TA是怎么说的！";
            $this->signedAuthorTui($bountry_info['user_id'], $body ,$bountry_info['id']);

            $res = true;     

        } catch (\Exception $e) {
            DB::rollBack();
        }
        $result['status'] = $res ? 1 : 0;
        $result['msg'] = $res ? '添加回答成功' : '添加回答失败';

        return $result;
    }

    /**
     * 采纳悬赏回答
     */
    public function acceptReply($input_arr){

        $attr = $this->getInterAndGroupIdByCons($input_arr['comment_interlocution_bounty_id']);//通过回答id获得对应的分类id和悬赏提问id
        try {
            $this->virifyGroup($attr['group_id']);
            $this->verifyBountry($attr['interlocution_bounty_id']);
            $this->verifyBountryAnswerStatus($input_arr['comment_interlocution_bounty_id']);
            $this->verifyBountryAcceptStatus($input_arr['comment_interlocution_bounty_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $interlocution_bounty_info = CommentInterlocutionBounty::where('id',$input_arr['comment_interlocution_bounty_id'])->first();
        //不可重复采纳
        $update_res = InterlocutionBounty::where([['id','=',$interlocution_bounty_info['interlocution_bounty_id']],['comment_id','=',0]])->update(['comment_id' => $input_arr['comment_interlocution_bounty_id']]);

/*        $bountry_info = InterlocutionBounty::where([['id','=',$attr['interlocution_bounty_id']]])->first();
        if($update_res){//采纳成功、添加推送
            $price = $bountry_info['price']/100;
            $body = "恭喜你！你对于".$bountry_info['title']."的回答已被提主采纳为最佳回答！悬赏金".$price."元已入账，速去查看";
            $this->signedAuthorTui($interlocution_bounty_info['user_id'], $body ,$bountry_info['id']);
        }*/
        $result['status'] = $update_res ? 1 : 0;      
        $result['msg'] = $update_res ? '采纳成功' : '已经有采纳';

        return $result;
    }

    /**
     * 对回答点赞
     */
    public function bountryLike($input_arr){

        $group_id = $this->getGroIdByBountryInter($input_arr['interlocution_bounty_id']);//通过回答id获得对应的分类id和悬赏提问id
        try {
            $this->virifyGroup($group_id);
            $this->verifyBountry($input_arr['interlocution_bounty_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $res = InterlocutionBounty::where('id', $input_arr['interlocution_bounty_id'])->increment('likes');
        $nums = InterlocutionBounty::where('id', $input_arr['interlocution_bounty_id'])->value('likes');
        return [
            'status' => $res ? 1 : 0,
            'nums' => $res ? $nums+1 : 0,
            'msg' => $res ? '点赞成功' : '点赞失败'
        ];
    }

    /*
     * 关注大师
     */
    public function focusSignedAuthor($input_arr){
        $focus_info = Focus::where([['master_id','=',$input_arr['master_id']],['user_id','=',$input_arr['user_id']]])->first();
        if($focus_info){
            return ['status'=>0,'msg'=>'已经关注'];
        }

        $data['user_id'] = $input_arr['user_id'];
        if($data['user_id'] > 0){
            $growth_service = new GrowthRuleService($data['user_id'],'focus');
            $growth_service->init();
        }
        $data['master_id'] = $input_arr['master_id'];
        $data['create_time'] = time();
        $res = Focus::insertGetId($data);

        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '关注成功' : '关注失败'
        ];
    }

    /**
     * 取消关注大师
     */
    public function cancelFocusSa($input_arr){
        $focus_info = Focus::where([['master_id','=',$input_arr['master_id']],['user_id','=',$input_arr['user_id']]])->first();
        if(!$focus_info){
            return ['status'=>0,'msg'=>'还没关注'];
        }

        $res = Focus::where([['master_id','=',$input_arr['master_id']],['user_id','=',$input_arr['user_id']]])->delete();
        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '取消关注成功' : '取消关注失败'
        ];
    }

    /**
     * 邀请大师
     */
    public function inviteSignedAuthor($input_param){
        $bountry_info = InterlocutionBounty::where([['id','=',$input_param['interlocboun_id']]])->first();

        if (!$bountry_info['status']) {
            return ['status'=>0,'msg'=>'悬赏提问不存在'];
        }

        if($bountry_info['user_id']!=$input_param['user_id']){
            return ['status'=>0,'msg'=>'没有权限邀请大师'];
        }

        $request_info = Request::where([['interlocboun_id','=',$input_param['interlocboun_id']]])->first();
        if($request_info){
            return ['status'=>0,'msg'=>'该悬赏提问已邀请大师'];
        }

        $res = Request::insertGetId(['interlocboun_id'=>$input_param['interlocboun_id'],'master_id'=>$input_param['master_id'],'create_time'=>time()]);

        if($res){//邀请成功、添加推送
            $price = $bountry_info['price']/100;
            $body = "赚赏金！有人邀请您回答他的悬赏<".$bountry_info['title'].">，赏金高达".$price."元马上去回答";
            $this->signedAuthorTui($input_param['master_id'], $body ,$input_param['interlocboun_id']);

            //计入系统消息
            $feed_data['user_id'] = $input_param['master_id'];
            $feed_data['type'] = 15;
            $feed_data['content'] = $body;
            $feed_data['create_time'] = time();
            $feed_data['feed_id'] = FeedBack::insertGetId($feed_data);
            unset($feed_data['user_id']);
            unset($feed_data['type']);
            FeedReply::create($feed_data);
        }
        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '邀请大师成功' : '邀请失败'
        ];
    }

}