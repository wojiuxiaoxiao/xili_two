<?php
/**
 * 支付控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.5
 * @since       2.5
 */
namespace App\Http\Controllers\V2_5;

use App\Http\Models\Bill;
use App\Http\Models\Transfer;
use App\Http\Models\User;
use App\Libs\alipay\alipay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GetuiController;
use App\Http\Models\Banner;
use  App\Http\Models\Order;
use  App\Libs\wxpay\wechatAppPay;
use App\Http\Models\CollectMulti;
use Illuminate\Support\Facades\DB;


class PayController extends Controller
{

    private $wxpay_body='悬赏微信下单';
    private $wxpay_trade_type='APP';

    private $alipay_body='悬赏支付宝下单';
    private $alipay_su='悬赏问答';

    /**
     *  去支付接口
     */
    public function goPrepay(){
        $pay_type = Input::get("pay_type");//支付类型:是支付宝还是微信 1：支付宝 2：微信
        $out_trade_no = Input::get("out_trade_no");
        $order_info = Order::where([['order_status','=',1],['status','=',1],['out_trade_no','=',$out_trade_no]])->first();
        if(!$order_info){
            return array('status'=>0,'msg'=>'非法订单');
        }

        if($pay_type==1){
            $alipay = new alipay();
            $params = array();
            $params['order_body'] = $this->alipay_body;
            $params['order_su'] = $this->alipay_su;
            $params['order_no'] =$out_trade_no;
            $params['order_amount']=number_format($order_info['price']/100,2);
            $data = $alipay->unifiedOrder($params);

            $return['data']['orderString'] = $data;
            $return['status'] = 1;
            extjson($return);
        }elseif($pay_type==2){
            $WcPay = new wechatAppPay();
            $params = array();
            $params['body'] = $this->wxpay_body;
            $params['out_trade_no'] = $out_trade_no;
            $params['total_fee'] = (int)$order_info['price'];
            $params['trade_type'] = $this->wxpay_trade_type;
            $data = $WcPay->unifiedOrder($params);
        }


        if($data['return_code'] == 'SUCCESS') {
            $return['status'] = 1;
            $return['data'] = $WcPay->getAppPayParams($data['prepay_id']);
            extjson($return);
        } else{
            $return['status'] = 0;
            $return['msg'] = $WcPay->error_code($data['return_code']);
            extjson($return);
        }

    }


    /**
     * 提现接口
     * @author neekli
     * @since v2.5
     */
    public function withDraw(){
        $this->checkUser();
        $user_id = USERID;
        $withdraw_money = Input::get("withdraw_money");

        //判断余额是否充足
        $user_info = User::where('id',$user_id)->first();
        $user_balance = $user_info['account'];
        if(!$user_info['payNumb']){
            extjson(['status'=>0,'msg'=>'没有提现账户，请联系客服设置']);
        }

        if($user_balance<$withdraw_money*100){
            extjson(['status'=>0,'msg'=>'余额不足']);
        }

        $res = false;
         DB::beginTransaction();
            try {
                //减去提现的账户余额
                User::where('id',$user_id)->decrement('account', $withdraw_money*100);

                $number = $this->getNumberId();
                //生成账单
                $user_balance = $user_balance-($withdraw_money*100);
                $bill_data['bill_no'] = $number;//账单编号
                $bill_data['user_id'] = $user_id;
                $bill_data['type'] = 3;
                $bill_data['pay_type'] = 1;
                $bill_data['price'] = $withdraw_money*100;
                $bill_data['account'] = $user_balance;
                $bill_data['status'] = 0;
                $bill_data['out_trade_no'] = '';//订单编号
                $bill_data['create_time'] = time();
                Bill::insertGetId($bill_data);

                //生成提现记录
                $transfer_data['user_id'] = $user_id;
                $transfer_data['payNumb'] = $user_info['payNumb'];
                $transfer_data['price'] = $withdraw_money*100;
                $transfer_data['out_trade_no'] = $number;//提现编号
                $transfer_data['create_time'] = time();
                Transfer::insertGetId($transfer_data);

                DB::commit();
                $res = true;
            } catch (\Exception $e) {
                DB::rollBack();
            }

        $return = [
            'status' =>$res ? 1 : 0,
            'msg' =>$res ? '提现成功' : '提现失败'
        ];
        extjson($return);
    }

    /**
     * 提现进度
     */
    public function withDrawProgress(){
        $this->checkUser();
        $user_id = USERID;
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $progress_list = Bill::where([['user_id','=',$user_id],['type','=',3]])
            ->select('create_time','pay_type','price','audit_user_id','status','bill_no')
            ->orderBy('create_time','desc')
            ->offset($start)
            ->limit($pagesize)
            ->get();

        $billdesc_arr = [0=>'提现中',1=>'提现成功',2=>'提现失败'];
        foreach($progress_list as $k=>$v){
            $fail_reason = Transfer::where('out_trade_no',$v['bill_no'])->value('not_pass_reason');
            $progress_list[$k]['bill_desc'] = $billdesc_arr[$v['status']];
            $progress_list[$k]['price'] = number_format($v['price']/100,2);
            if($v['status'] == 2) {
                $progress_list[$k]['fail_reason'] = '失败原因：'.$fail_reason;
            }
        }

        extInfo($progress_list);
    }


    /**
     * 生成订单号
     */
    public function getNumberId()
    {
        $orderid = date('YmdHis').mt_rand(100000, 999999);
        return $orderid;
    }

    /**
     * 核对订单防止恶意篡改数据
     */
    public function orderQueryStatus(){

        $intput_para['out_trade_no'] = Input::get('out_trade_no');
        $type = Input::get('type');//1支付宝 2微信

        $order_info = Order::where('out_trade_no' ,$intput_para['out_trade_no'])->first();
        $order_price = ($order_info['price'])/100;
        if($type==1){
            $aliPay = new alipay();
            $query_res = $aliPay->orderQuery($intput_para);//后续处理
            if($query_res->alipay_trade_query_response->total_amount==$order_price && $query_res->alipay_trade_query_response->msg=='Success'){
                extjson(array('status'=>1,'data'=>['errormsg'=>'支付成功','order_status'=>1]));
            }else{
                $order_data['order_status'] = 9;
                $order_data['close_reason'] = '异常订单';
                Order::where('out_trade_no',$intput_para['out_trade_no'])->update($order_data);

                extjson(array('status'=>1,'data'=>['errormsg'=>'非法订单','order_status'=>0]));
            }
        }elseif($type==2){
            $WcPay = new wechatAppPay();
            $query_res = $WcPay->orderQuery($intput_para);
            if($query_res['result_code']=='SUCCESS' && $query_res['total_fee']==$order_info['price']){
                extjson(array('status'=>1,'data'=>['errormsg'=>'支付成功','order_status'=>1]));
            }else{
                $order_data['order_status'] = 9;
                $order_data['close_reason'] = '异常订单';
                Order::where('out_trade_no',$intput_para['out_trade_no'])->update($order_data);

                extjson(array('status'=>1,'data'=>['errormsg'=>'非法订单','order_status'=>0]));
            }
        }

    }

}
