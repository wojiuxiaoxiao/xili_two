<?php
/**
 * 单元测试控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_4_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class FuwuController extends Controller
{
    public function aesTest(){
        dd('fuwu');
    }  

}
