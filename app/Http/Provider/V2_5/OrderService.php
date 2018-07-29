<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/14
 * Time: 11:06
 */

namespace App\Http\Provider\V2_5;

use App\Http\Models\InterlocutionBounty;
use App\Http\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Http\Provider\CommonService;

class OrderService extends CommonService
{

    private $alipay_status=1;
    private $wxpay_status=1;

   /**
    * 1待支付 2进行中 3已完成 4已关闭
    * 订单列表
    */
    public function myOrderList($input_arr){

        switch ($input_arr['type'])
        {
            case 1:
                $whereIn = [1];
                break;
            case 2:
                $whereIn = [3,5,7];
                break;
            case 3:
                $whereIn = [4];
                break;
            case 4:
                $whereIn = [2,6,8,9];
                break;
        }
        $order_list = Order::where([['status','=',1],['user_id','=',$input_arr['user_id']]])
            ->whereIn('order_status', $whereIn)
            ->select('out_trade_no','create_time','price','order_status','user_id','goods_id')
            ->orderBy('create_time', 'desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get();

        $paystr_arr = [
            //待支付
            1=>'未支付',

            //进行中
            3=>'进行中',
            5=>'退款中',//进行中
            7=>'退款中',

            //已完成
            4=>'已采纳',

            //已关闭
            2=>'已取消',
            6=>'已退款',
            8=>'已关闭',
            9=>'已关闭',
        ];

        $type_arr = [1=>1,3=>2,5=>2,7=>2,4=>3,2=>4,6=>4,8=>4,9=>4,];
        foreach($order_list as $k=>$v){
            $order_list[$k]['price'] = number_format($v['price']/100,2);
            $order_list[$k]['title'] = '悬赏问答';
            $order_list[$k]['trade_status'] = $v['order_status'];//数据库中真实订单状态返回出去
            $order_list[$k]['order_desc'] = $paystr_arr[$v['order_status']];
            $order_list[$k]['order_status'] = $type_arr[$v['order_status']];
            $order_list[$k]['alipay_status'] = $this->alipay_status;
            $order_list[$k]['wxpay_status'] = $this->wxpay_status;
        }

        return $order_list;
    }

    /**
     * 取消订单
     */
    public function cancelOrder($input_arr){
        try {
            $this->veryfyCancelOrder($input_arr['user_id'],$input_arr['out_trade_no']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $update_res = Order::where([['user_id','=',$input_arr['user_id']],['out_trade_no','=',$input_arr['out_trade_no']]])->update(['order_status' => 2,'close_reason'=>'取消支付，已关闭']);
        $result['status'] = $update_res ? 1 : 0;
        $result['msg'] = $update_res ? '成功取消订单' : '取消订单失败';

        return $result;
    }

    /**
     * 验证取消订单
     */
    public function veryfyCancelOrder($user_id,$out_trade_no){
        $verify = Order::where([['user_id','=',$user_id],['out_trade_no','=',$out_trade_no],['status','=',1]])->first();
        if (null === $verify) {
            throw new \Exception('订单已被删除', 0);
        } else if (1 != $verify->order_status) {
            throw new \Exception('订单不在待支付状态，不可取消', 0);
        }
    }

    /**
     * 删除订单
     */
    public function deleteOrder($input_arr){
        try {
            $this->veryfyDeleteOrder($input_arr['user_id'],$input_arr['out_trade_no']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $update_res = Order::where([['user_id','=',$input_arr['user_id']],['out_trade_no','=',$input_arr['out_trade_no']]])->update(['status' => 0]);
        $result['status'] = $update_res ? 1 : 0;
        $result['msg'] = $update_res ? '删除订单' : '删除失败';

        return $result;
    }

    /**
     * 验证删除订单
     */
    public function veryfyDeleteOrder($user_id,$out_trade_no){
        $verify = Order::where([['user_id','=',$user_id],['out_trade_no','=',$out_trade_no],['status','=',1]])->first();
        if (null === $verify) {
            throw new \Exception('订单已被删除', 0);
        } else if (!in_array($verify->order_status,[2,6,8,9])) {
            throw new \Exception('订单不在待关闭状态，不可删除', 0);
        }
    }

    /**
     * 订单详情
     */
    public function orderInfo($input_arr){

        $order_info = Order::where([['order.user_id','=',$input_arr['user_id']],['order.out_trade_no','=',$input_arr['out_trade_no']],['order.status','=',1]])
            ->select(
                'interlocution_bounty.title',
                'interlocution_bounty.id as interlocution_bounty_id',
                'order.price',
                'order.out_trade_no',
                'order.create_time',
                'order.trade_time',
                'order.order_status',
                'order.refund_reason',
                'order.close_reason',
                'order.refund_time'
            )
            ->leftJoin('interlocution_bounty', 'interlocution_bounty.id', '=', 'order.goods_id')
            ->first();
        if(!$order_info){
            return array('status'=>0,'msg'=>'订单已删除');
        }

        $return_arr = array();
        if($order_info['order_status']==1){//未支付
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['surples_time'] = $this->surplesTime($order_info['create_time']);
            $return_arr['type'] = 1;
        }elseif(in_array($order_info['order_status'],[3])){//进行中
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];
            $return_arr['type'] = 2;
        }elseif($order_info['order_status']==5){//退款中
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];
            $return_arr['refund_reason'] = $order_info['refund_reason'];
            $return_arr['type'] = 3;
        }elseif($order_info['order_status']==4){//已完成
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];
            $return_arr['type'] = 4;
        }elseif($order_info['order_status']==6){//轻度违规关闭$order_info['order_status']==6
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];  
            $return_arr['refund_reason'] = $order_info['refund_reason'];
            $return_arr['refund_time'] = $order_info['refund_time'];
//            $return_arr['close_reason'] = $order_info['close_reason'];//轻度违规，不显示关闭原因
            $return_arr['type'] = 5;
        }elseif($order_info['order_status']==9){//重度违规关闭
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];
            $return_arr['close_reason'] = $order_info['close_reason'];
            $return_arr['type'] = 5;
        }elseif($order_info['order_status']==8){//其他关闭状态
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['trade_time'] = $order_info['trade_time'];
            $return_arr['type'] = 6;
        }elseif($order_info['order_status']==2){//取消订单
            $return_arr = $this->baseOrderInfo($order_info);
            $return_arr['type'] = 6;
        }
        $return_arr['alipay_status'] = $this->alipay_status;
        $return_arr['wxpay_status'] = $this->wxpay_status;

        $result['status'] = $return_arr ? 1 : 0;
        $result['data'] = $return_arr ? $return_arr : null;
        return $result;
    }

    /**
     * 判断剩余支付时间
     */
    public function surplesTime($surples_time){
        $diff_time =  ($surples_time+3600*24)-time();
        if($diff_time<0){      
            return 0;
        }
        return $diff_time;
    }

    public function baseOrderInfo($order_info){
        $paystr_arr = [
            //待支付
            1=>'未支付',
            //进行中
            3=>'进行中',
            5=>'退款中',//进行中
            7=>'退款中',
            //已完成
            4=>'已采纳',//已完成
            //已关闭
            2=>'已取消',//已关闭
            6=>'已退款',//已关闭
            8=>'已关闭',
            9=>'已关闭',
        ];
        $return_arr = array();
        $return_arr['price'] = number_format($order_info['price']/100,2);
        $return_arr['out_trade_no'] = $order_info['out_trade_no'];
        $return_arr['create_time'] = $order_info['create_time'];
        $return_arr['title'] = $order_info['title'];   
        $return_arr['order_status'] = $order_info['order_status'];
        $return_arr['order_desc'] = $paystr_arr[$order_info['order_status']];
        $return_arr['interlocution_bounty_id'] = $order_info['interlocution_bounty_id'];

        return $return_arr;
    }

    /**
     * 验证订单是否正常
     */
    public function veryfyOrderInfo($user_id,$out_trade_no){
        $verify = Order::where([['user_id','=',$user_id],['out_trade_no','=',$out_trade_no],['status','=',0]])->first();
        if (null === $verify) {
            throw new \Exception('订单已被删除', 0);
        }
    }


    /**
     * 预览回答
     */
    public function previewBountryAnswer($interlocution_bounty_id){
        $info = InterlocutionBounty::where([['interlocution_bounty.id','=',$interlocution_bounty_id]])
            ->select('user.avatar','user.nickname','user.type as user_type','user.active','interlocution_bounty.title',
                'interlocution_bounty.content','interlocution_bounty.price','interlocution_bounty.pic')
            ->leftJoin('user', 'user.id', '=', 'interlocution_bounty.user_id')
            ->first();

        if($info){
            $info['price'] = number_format($info['price']/100,2);
            $info['lv'] = $this->getLv(intval($info['active']));
            $info['pic'] =  $info['pic'] ? json_decode($info['pic'], true) : [];//图片字段变为array

        }
        return $info;
    }



}