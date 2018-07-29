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
use App\Http\Provider\GrowthRuleService;


class AddScoreController extends Controller
{

    /**
     * type 1分享 2关注 3听音频
     * 分享和关注累加积分
     * @authro neekli
     * @since v2.5
     */
   public function shareAndAttention(){

       $type = Input::get('type');
       switch($type){
           case 1:
               $Inject = 'share';
               break;
           case 2:
               $Inject = 'focus';
               break;
           case 3:
               $Inject = 'listen';
               break;
       }

       if($this->userid>0) {
           $growth_service = new GrowthRuleService($this->userid,$Inject);
           $growth_service->init();
       }

       extjson(['status'=>1]);
   }
}
