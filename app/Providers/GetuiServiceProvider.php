<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Input;
require_once app_path() . "/Libs/getui/push.php";

class GetuiServiceProvider extends ServiceProvider
{
    private static $push = [];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * @param $push
     * @isOffline 是否离线
     * @ExpireTime 离线时间
     * @Type 透传类型
     * @body body体 必传参数
     * @content 内容 必传参数
     * @title 标题 必传参数
     * @return bool
     */
    public static function singlePush($push)
    {
        $push['isOffline'] = $push['isOffline'] ?? config('getui.isOffline');
        $push['ExpireTime'] = $push['ExpireTime'] ?? config('getui.ExpireTime');
        $push['Type'] = $push['Type'] ?? config('getui.Type');

        $result = pushMessageToSingle($push);

        return $result['result'] == 'ok' ? true :false;
    }


}
/*单推调用案例
use App\Providers\GetuiServiceProvider;
$data['content'] = '十方云水内容';
$data['title'] = 'hello biaoti';
$data['body'] = 'hello body';
$res = GetuiServiceProvider::singlePush($data);
var_dump($res);
*/