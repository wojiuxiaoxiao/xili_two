<?php
/**
 * Banner控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_5;

use App\Libs\alipay\alipay;
use App\Libs\wxpay\wechatAppPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GetuiController;
use App\Http\Models\Banner;
use App\Http\Models\CollectMulti;


class BannerController extends Controller
{

    /**
     *  banner轮播图
     *  @author neek li
     *  @since V2_5
     */
    public function bannerList(){

        $banner_list = Banner::where([['status','=',1]])->select('banner_pic','type','multi_id')->get();

        extInfo($banner_list);
    }

    /**
     *  banner详情即活动详情
     */
    public function bannerInfo(){
        $WcPay = new wechatAppPay();
        $params = array();
        $params['body'] = '测试4';
        $params['out_trade_no'] = '1234567893';
        $params['total_fee'] = 1;
        $params['trade_type'] = 'APP';
        $data = $WcPay->unifiedOrder($params);
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

    public function orderQuery(){
        $WcPay = new wechatAppPay();
        $out_trade_no = Input::get('out_trade_no');
        $data = $WcPay->orderQuery($out_trade_no);
        $return['status'] = 1;
        $return['data'] = $data;
        extjson($return);
    }

    public function orderRefund()
    {
        $WcPay = new wechatAppPay();
        $params['out_trade_no'] ='20180625155512797243';  //订单号
        $params['out_refund_no'] ='1201806251555127972430'; //退款订单号
        $params['total_fee'] =2; //商品金额
        $params['refund_fee'] =1; //退款金额
        $params['refund_desc'] = '2121212';  //退款描述
        $data = $WcPay->orderRefund($params);
//"return_code": "SUCCESS",
//"return_msg": "OK",
//"appid": "wxb3f642c82275f277",
//"mch_id": "1506987141",
//"nonce_str": "YOaSDWVspdxLGAPq",
//"sign": "DC416F91A32449ED0E1F80A6DA36F466",
//"result_code": "FAIL",
//"err_code": "NOTENOUGH",
//"err_code_des": "基本账户余额不足，请充值后重新发起",
//"err_msg": "用户帐号余额不足"
        $return['status'] = 1;
        $return['data'] = $data;
        extjson($return);
    }

    /*支付*/
    public function alipay()
    {
        $alipay = new alipay();
        $params = array();
        $params['order_body'] = '我是测试数据';
        $params['order_su'] = 'App支付测试';
        $params['order_no'] =1234567891;
        $params['order_amount']=0.02;
        $return['data'] = $alipay->unifiedOrder($params);
        $return['status'] = 1;
        extjson($return);
    }

    /*查询*/
    public function ali_orderQuery()
    {
        $alipay = new alipay();
        $params = array();
        $params['out_trade_no'] =1234567890;
        $return['data'] = $alipay->orderQuery($params);
        $return['status'] = 1;
        extjson($return);
    }
    /*退款*/
    public function ali_refund()
    {
        $alipay = new alipay();
        $params = array();
        $params['out_trade_no'] =1234567891;
        $params['refund_amount'] =0.01;
        $params['refund_reason'] ='部分退款';
        $params['out_request_no'] =112345678910;
        $return['data'] = $alipay->orderRefund($params);
        $return['status'] = 1;
        extjson($return);
    }

}
