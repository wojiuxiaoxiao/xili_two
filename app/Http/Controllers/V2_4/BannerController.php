<?php
/**
 * Banner控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_4;

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
     *  @since v2.4
     */
    public function bannerList(){

        $banner_list = Banner::where([['status','=',1]])->select('banner_pic','type','multi_id')->get();

        extInfo($banner_list);
    }

    /**
     *  banner详情即活动详情
     */
    public function bannerInfo(){
        echo 'banner_info';
    }



}
