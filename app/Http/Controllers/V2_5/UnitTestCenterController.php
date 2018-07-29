<?php
/**
 * 单元测试控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_5;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class UnitTestCenterController extends Controller
{
    public function aesTest(){

//        $redis = new Redis();
//        $redis->connect('10.118.30.198', 6379);
//        $redis->auth("rryz,aqfh");

        Redis::set('ceshi',6666);
        $res = Redis::expire('ceshi',1800);
        dd($res);
    }

}
