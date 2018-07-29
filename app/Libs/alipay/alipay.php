<?php
/**
 * Created by PhpStorm.
 * User: XUN
 * Date: 2018/6/22
 * Time: 9:30
 */
namespace App\Libs\alipay;
require_once(app_path().'/Libs/alipay/aop/AopClient.php');
require_once(app_path().'/Libs/alipay/aop/SignData.php');
require_once(app_path().'/Libs/alipay/aop/request/AlipayTradeAppPayRequest.php');
require_once(app_path().'/Libs/alipay/aop/request/AlipayTradeRefundRequest.php');
require_once(app_path().'/Libs/alipay/aop/request/AlipayTradeQueryRequest.php');

class alipay
{
    //接口API URL前缀
    const API_URL_PREFIX = '';

    //支付宝网关（固定）
    private $URL;

    //开发者appid
    private $APP_ID;

    //开发者应用私钥，由开发者自己生成
    private $APP_PRIVATE_KEY;

    //参数返回格式，只支持json
    private $FORMAT = 'json';

    //请求和签名使用的字符编码格式，支持GBK和UTF-8
    private $CHARSET = 'UTF-8';

    //支付宝公钥，由支付宝生成
    private $ALIPAY_PUBLIC_KEY;

    //商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
    private $SIGN_TYPE = 'RSA2';

    //支付结果回调通知地址
    private $notify_url;

    //所有参数
    private $params = array();

    public function __construct()
    {
        $this->URL = config('pay.AL_URL');
        $this->APP_ID = config('pay.AL_APP_ID');
        $this->APP_PRIVATE_KEY = file_get_contents(app_path()."/Libs/alipay/secret/app_private_key.txt");
        $this->ALIPAY_PUBLIC_KEY = file_get_contents(app_path()."/Libs/alipay/secret/alipay_public_key.txt");;
        $this->notify_url = config('pay.AL_notify_url');
    }
    /*
     *下单
     */
    public function unifiedOrder($params)
    {
        $aop = new \AopClient();
        $this->setParameter($aop);

        $bizcontent = json_encode([
            'body'=>$params['order_body'],
            'subject'=>$params['order_su'],
            'out_trade_no'=>$params['order_no'],//此订单号为商户唯一订单号
            'total_amount'=> $params['order_amount'],//保留两位小数
            'product_code'=>'QUICK_MSECURITY_PAY'
        ]);
        //**沙箱测试支付宝结束
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        //支付宝回调
        $request->setNotifyUrl($this->notify_url);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        return $response;
    }

    /*
     * 查询
     */
    public function orderQuery($params)
    {
        $aop = new \AopClient ();
        $this->setParameter($aop);
        $request = new \AlipayTradeQueryRequest ();
        isset($params['out_trade_no']) ? $this->params['out_trade_no'] = $params['out_trade_no'] : $this->params['trade_no'] = $params['trade_no'];

        $this->params = json_encode($this->params);
        $request->setBizContent($this->params);
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return $result;
        } else {
            return $resultCode;
        }
    }

    /*
     * 退款
     */
    public function orderRefund($params)
    {
        $aop = new \AopClient ();
        $this->setParameter($aop);
        $request = new \AlipayTradeRefundRequest();
        //退款的商户订单号或者支付宝交易号
        isset($params['out_trade_no']) ? $this->params['out_trade_no'] = $params['out_trade_no'] : $this->params['trade_no'] = $params['trade_no'];

        $this->params['refund_amount'] = $params['refund_amount'];//需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
        $this->params['refund_reason'] = $params['refund_reason'];//退款的原因说明
        $this->params['out_request_no'] = $params['out_request_no'];//标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
        $this->params = json_encode($this->params);
        $request->setBizContent($this->params);
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return $result;
        } else {
            return $resultCode;
        }
    }

    /*
     * 设置公共参数
     */

    public function setParameter($aop)
    {
        $aop->gatewayUrl = $this->URL;
        $aop->appId = $this->APP_ID; //开发者appid
        $aop->rsaPrivateKey = $this->APP_PRIVATE_KEY; //填写工具生成的商户应用私钥
        $aop->alipayrsaPublicKey=$this->ALIPAY_PUBLIC_KEY; //填写从支付宝开放后台查看的支付宝公钥
        $aop->apiVersion = '1.0';
        $aop->signType = $this->SIGN_TYPE;
        $aop->postCharset=$this->CHARSET;
        $aop->format= $this->FORMAT;
    }


}