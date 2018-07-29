<?php
/**
 * 账单控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.5
 * @since       2.5
 */

namespace App\Http\Controllers\V2_5;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Http\Provider\V2_5\BillService;


class BillController extends Controller
{

    private $billService = null;

    public function __construct(BillService $billService)
    {
        parent::__construct();
        $this->billService = $billService;
    }

  /**
   * 账单记录[普通用户]
   * @author neekli
   * @since v2.5
   */
    public function billList(){
        $this->checkUser();
        $input_arr = $this->getPageStart();
        $input_arr['user_id'] = USERID;

        $bill_list = $this->billService->billList($input_arr);
        extInfo($bill_list);
    }

    /**
     * 交易记录[大师身份]
     * @author neekli
     * @since v2.5
     */
    public function signedAuthorDeal(){
        $this->checkUser();
        $input_arr = $this->getPageStart();
        $input_arr['user_id'] = USERID;

        $deal_list = $this->billService->signedAuthorDeal($input_arr);
        extInfo($deal_list);
    }

}
