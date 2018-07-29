<?php
/**
 * 跳板控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;

class SpringBoardController extends Controller
{
    /**
     * 扫码后的中转页面
     * @author neekli
     * @since v1.0
     */
    public function wechatJump(){
        $ip = client_ip();
        Redis::set($ip,Input::get('id'));
        Redis::expire($ip,1800);

        //判断是安卓还是ios，然后跳转到对应的appstore
        $down_url = '';
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $down_url = 'https://itunes.apple.com/us/app/%E5%8D%81%E6%96%B9%E4%BA%91%E6%B0%B4/id1332982959?l=zh&ls=1&mt=8';
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $down_url = config('yunshui.http_admin_url').'download/android.apk';
        }

        return view('home.jump.index',['down_url' => $down_url]);//后期修改成对应的中转页面
    }
}
