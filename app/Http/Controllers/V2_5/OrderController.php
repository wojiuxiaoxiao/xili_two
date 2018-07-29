<?php
/**
 * 账单控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.5
 * @since       2.5
 */

namespace App\Http\Controllers\V2_5;

use App\Http\Provider\V2_5\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;


class OrderController extends Controller
{

    private $orderService = null;

    public function __construct(OrderService $orderService)
    {
        parent::__construct();
        $this->orderService = $orderService;
    }
    /**
     * type 1待支付 2进行中 3已完成 4已关闭
     * 我的订单列表
     * @author neekli
     * @since v2.5
     */
    public function myOrderList(){
        $this->checkUser();
        $input_arr = $this->getPageStart();
        $input_arr['type'] = Input::get('type');
        $input_arr['user_id'] = USERID;

        $order_list = $this->orderService->myOrderList($input_arr);
        extInfo($order_list);
    }

    /**
     * 取消订单接口
     * @author neekli
     * @since v2.5
     */
    public function cancelOrder(){
        $this->checkUser();
        $input_arr['out_trade_no'] = Input::get('out_trade_no');
        $input_arr['user_id'] = USERID;

        $result = $this->orderService->cancelOrder($input_arr);
        extjson($result);
    }

    /**
     * 删除订单接口
     * @author neekli
     * @since v2.5
     */
    public function deleteOrder(){
        $this->checkUser();
        $input_arr['out_trade_no'] = Input::get('out_trade_no');
        $input_arr['user_id'] = USERID;

        $result = $this->orderService->deleteOrder($input_arr);
        extjson($result);
    }

    /**
     * 订单详情接口
     * @author neekli
     * @since v2.5
     */
    public function orderInfo(){
        $this->checkUser();
        $input_arr['out_trade_no'] = Input::get('out_trade_no');
        $input_arr['user_id'] = USERID;

        $order_info = $this->orderService->orderInfo($input_arr);
        extjson($order_info);
    }

    /**
     * 预览回答
     * @author neekli
     * @since v2.5
     */
    public function previewBountryAnswer(){
        $input_arr['interlocution_bounty_id'] = Input::get('interlocution_bounty_id');
        $preview_order = $this->orderService->previewBountryAnswer($input_arr);

        extInfo($preview_order);
    }


}
