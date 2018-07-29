<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/14
 * Time: 11:06
 */

namespace App\Http\Provider\V2_5;

use App\Http\Models\Bill;
use App\Http\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Provider\CommonService;

class BillService extends CommonService
{
    /**
     * 普通用户
     * @param $input_arr
     * @return mixed
     */
    public function billList($input_arr){
        $bill_list = Bill::where([['status','=',1],['user_id','=',$input_arr['user_id']],['type','<>',4 ]])
            ->select('create_time','bill_no','out_trade_no','price','pay_type','type')
            ->orderBy('create_time', 'desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get();

        $bill_desc = array(
            1=>'退款',
            2=>'悬赏问答，付款成功',
            3=>'提现',
        );
        foreach($bill_list as $k=>$v){
            $operator = $v['type']==1 ? '+' : '-';
            $bill_list[$k]['price'] = $operator.(number_format($v['price']/100,2));
            $bill_list[$k]['bill_desc'] = $bill_desc[$v['type']];
        }

        return $bill_list;
    }

    /**
     * 交易记录
     */
    public function signedAuthorDeal($input_arr){
        $bill_list = Bill::where([['status','=',1],['user_id','=',$input_arr['user_id']],['type','>',2]])
            ->select('create_time','bill_no','price','type')
            ->orderBy('create_time', 'desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get();

        $bill_desc = array(
            3=>'提现',
            4=>'悬赏问答',
        );
        foreach($bill_list as $k=>$v){
            $operator = $v['type']==4 ? '+' : '-';
            $bill_list[$k]['price'] = $operator.(number_format($v['price']/100,2));
            $bill_list[$k]['bill_desc'] = $bill_desc[$v['type']];
        }

        $my_balance = User::where('id',$input_arr['user_id'])->value('account');
        $return['deal_list'] = $bill_list ? $bill_list : null;
        $return['balance'] = number_format($my_balance/100,2);
        return $return;
    }

}