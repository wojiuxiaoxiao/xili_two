<?php
/**
 * 首页控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V1;

use App\Http\Models\CommentInterlocutionBounty;
use App\Http\Models\Goods;
use App\Http\Models\InterlocutionBounty;
use App\Http\Models\Order;
use App\Http\Models\Bill;
use App\Http\Models\User;
use App\Http\Models\Program;
use App\Http\Models\Attention;

use App\Http\Provider\CommonService;
use App\Http\Provider\GrowthRuleService;
use App\Libs\alipay\alipay;
use App\Libs\wxpay\wechatAppPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;


class IndexController extends Controller
{

    /**
     *  首页数据初始化
     * @author neek li
     * @since v1.0
     */
    public function index()
    {

        $time = Input::get('time');
        $start_time = strtotime(date('Y-m-d 00:00:00', $time));
        $end_time = strtotime(date('Y-m-d 23:59:59', $time));

        $where = array(
            ['program.status', '=', 1],
            ['program.type', '!=', 2],
            ['program.showup_time', '>', $start_time],
            ['program.showup_time', '<', $end_time],
            ['program.radio_origin_url', '<>', ''],
        );

        $program_list = Program::where($where)
            ->select('id', 'showup_time', 'burning_time', 'column_name', 'name', 'author_name', 'create_time', 'radio_url', 'radio_pic', 'type')
            ->orderBy('showup_time', 'desc')
            ->get();

        //图片,先取缓存无数据再取服务器图片
        $today_time = strtotime(date('Ymd', time()));

        $solar_pics = Redis::hget('solar_terms',$today_time);
        if(!$solar_pics){
            $d = date('d',time())%12+1;
            $solar_pic = 'img/terms20180109/terms'.$d.'.jpg';
            $small_solar_pic = 'img/terms20180109/small_terms'.$d.'.jpg';
        }else{
            $pic = explode('=',$solar_pics);
            $solar_pic = $pic[0];
            $small_solar_pic = explode($solar_pic.'=',$solar_pics)[1];
        }

        $return = array();
        $return['data'] = $program_list ? $program_list : '';
        $return['status'] = 1;
        $return['solar_pic'] = $solar_pic;

        extjson($return);
    }

    /**
     * 商品点击量累加接口
     * @author neekli
     * @since v1.0
     */
    public function goodClickNums()
    {
        $good_id = Input::get('good_id');

        //点击次数递增
        $update_res = Goods::where([['status', '=', 1], ['id', '=', $good_id]])->increment('click_nums');
        extOperate($update_res);
    }

    /**
     * 跳转至官网首页
     * @author kexun
     * @since v1.0
     */
    public function location()
    {
//        Header("Location: http://www.sfys365.com/");
        Header("Location: http://www.mengjingcm.com/");
        exit;
    }

    /**
     * 微信支付公共回调接口
     * @author neekli
     * @version all
     */
    public function callBack(){
        $WcPay = new wechatAppPay();
        $callback_res = $WcPay->getNotifyData();

        //查询订单,防止虚假回调
        $query_res = $WcPay->orderQuery($callback_res);//后续处理
        if($query_res['out_trade_no']!=$callback_res['out_trade_no']){
            file_put_contents('./outputXIADANG.log',$query_res['out_trade_no'].'非法订单',FILE_APPEND);
            return array('status'=>0,'msg'=>'非法订单');
        }

        if($callback_res['result_code']=='SUCCESS'){
            //查看订单是否合法
            $order_info = Order::where([['order_status','=',1],['status','=',1],['out_trade_no','=',$callback_res['out_trade_no']]])->first();
            if(!$order_info){
                file_put_contents('./outputXIADANG.log',$query_res['out_trade_no'].'非法订单',FILE_APPEND);
                return array('status'=>0,'msg'=>'非法订单');
            }

            //累加积分
            $growth_service = new GrowthRuleService($order_info['user_id'],'boutry');
            $growth_service->init();

            //发推送
            $bountry_info = InterlocutionBounty::where([['id','=',$order_info['goods_id']]])->first();
            $master_id = \App\Http\Models\Request::where([['interlocboun_id','=',$order_info['goods_id']]])->value('master_id');
            $price = $order_info['price']/100;
            $body = "赚赏金！有人邀请您回答他的悬赏<".$bountry_info['title'].">，赏金高达".$price."元马上去回答";
            $commenServic = new CommonService();
            $commenServic->signedAuthorTui($master_id, $body ,$order_info['goods_id']);

            DB::beginTransaction();
            try {
                InterlocutionBounty::where('id',$order_info['goods_id'])->update(['status' => 1]);

                //修改订单状态，增加订单交易号
                $bill_data = $order_data = array();
                $order_data['trade_no'] = $callback_res['transaction_id'];//订单交易号
                $order_data['trade_time'] = time();
                $order_data['order_status'] = 3;
                Order::where('out_trade_no',$callback_res['out_trade_no'])->update($order_data);

                //生成账单
                $user_balance = User::where('id',$order_info['user_id'])->value('account');
                $bill_data['bill_no'] = $this->getNumberId();//账单编号
                $bill_data['user_id'] = $order_info['user_id'];
                $bill_data['type'] = 2;
                $bill_data['pay_type'] = 2;
                $bill_data['price'] = $order_info['price'];
                //$bill_data['account'] = $user_balance + $order_info['price'];
                $bill_data['out_trade_no'] = $callback_res['out_trade_no'];//订单编号
                $bill_data['create_time'] = time();
                Bill::insertGetId($bill_data);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }

        }

    }

    /**
     * 支付宝公共回调接口
     * @author kexun
     * @version all
     */
    public function alipayCallBack()
    {
        $callback_data = $_POST;
        $aliPay = new alipay();
        //查询订单,防止虚假回调
        $query_res = $aliPay->orderQuery($callback_data);//后续处理
        if($query_res->alipay_trade_query_response->msg!='Success'){
            file_put_contents('./outputXIADANG.log',$query_res['out_trade_no'].'非法订单',FILE_APPEND);
            return array('status'=>0,'msg'=>'非法订单');
        }

        if($callback_data['trade_status']=='TRADE_SUCCESS'){
            //查看订单是否合法
            $order_info = Order::where([['order_status','=',1],['status','=',1],['out_trade_no','=',$callback_data['out_trade_no']]])->first();
            if(!$order_info){
                file_put_contents('./outputXIADANG3.log',$query_res['out_trade_no'].'非法订单',FILE_APPEND);
                return array('status'=>0,'msg'=>'非法订单');
            }

            //累加积分
            $growth_service = new GrowthRuleService($order_info['user_id'],'boutry');
            $growth_service->init();

            //发推送
            $bountry_info = InterlocutionBounty::where([['id','=',$order_info['goods_id']]])->first();
            $master_id = \App\Http\Models\Request::where([['interlocboun_id','=',$order_info['goods_id']]])->value('master_id');
            $price = $order_info['price']/100;
            $body = "赚赏金！有人邀请您回答他的悬赏<".$bountry_info['title'].">，赏金高达".$price."元马上去回答";
            $commenServic = new CommonService();
            $commenServic->signedAuthorTui($master_id, $body ,$order_info['goods_id']);

            DB::beginTransaction();
            try {
                InterlocutionBounty::where('id',$order_info['goods_id'])->update(['status' => 1]);  

                //修改订单状态，增加订单交易号
                $bill_data = $order_data = array();
                $order_data['trade_no'] = $callback_data['trade_no'];//订单交易号
                $order_data['trade_time'] = time();
                $order_data['order_status'] = 3;
                Order::where('out_trade_no',$callback_data['out_trade_no'])->update($order_data);

                //生成账单
                $user_balance = User::where('id',$order_info['user_id'])->value('account');
                $bill_data['bill_no'] = $this->getNumberId();//账单编号
                $bill_data['user_id'] = $order_info['user_id'];
                $bill_data['type'] = 2;
                $bill_data['pay_type'] = 1;
                $bill_data['price'] = $order_info['price'];
//                $bill_data['account'] = $user_balance + $order_info['price'];  //普通用户消费是直接消费没走余额
                $bill_data['out_trade_no'] = $callback_data['out_trade_no'];//订单编号
                $bill_data['create_time'] = time();
                Bill::insertGetId($bill_data);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

    }

    /**
     * 生成订单号
     */
    public function getNumberId()
    {
        $orderid = date('YmdHis').mt_rand(100000, 999999);
        return $orderid;
    }
}

//$data = array (
//    'appid' => 'wxb3f642c82275f277',
//    'bank_type' => 'CFT',
//    'cash_fee' => '1',
//    'fee_type' => 'CNY',
//    'is_subscribe' => 'N',
//    'mch_id' => '1506987141',
//    'nonce_str' => 'wEU12NNT7QO4ZanEPJa6H6zlXyYzENAQ',
//    'openid' => 'olcmJ1rMePwRCvs8OXOwZTp-oA1U',
//    'out_trade_no' => '20180621165200542807',
//    'result_code' => 'SUCCESS',
//    'return_code' => 'SUCCESS',
//    'sign' => '739C99D4E8673C5C722365E50010C480',
//    'time_end' => '20180621165210',
//    'total_fee' => '1',
//    'trade_type' => 'APP',
//    'transaction_id' => '4200000126201806213189310754',
//);