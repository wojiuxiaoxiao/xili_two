<?php
/**
 * 首页控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_1_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Models\Program;
use App\Http\Models\Goods;
use App\Http\Models\Attention;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;


class IndexController extends Controller
{

    /**
     *  首页数据初始化
     *  @author neek li
     *  @since v1.0
     */
    public function index(){
        $time = Input::get('time');
        $start_time = strtotime(date('Y-m-d 00:00:00',$time));
        $end_time = strtotime(date('Y-m-d 23:59:59',$time));

        $where = array(
            ['program.status','=',1],
            ['program.type','!=',2],
            ['program.showup_time','>',$start_time],
            ['program.showup_time','<',$end_time],
            ['program.radio_origin_url','<>',''],
        );

        $program_list = Program::where($where)
            ->select('id','showup_time','burning_time','column_name','name','author_name','create_time','radio_url','radio_pic','type')
            ->orderBy('showup_time', 'desc')
            ->get();

        //图片,先取缓存无数据再取服务器图片
        $today_time = strtotime(date('Ymd',time()));
        $solar_pic = Redis::hget('solar_terms',$today_time);
        if(!$solar_pic){
            $d = date('d',time())%12+1;
            $solar_pic = 'img/terms20180109/terms'.$d.'.jpg';
            $small_solar_pic = 'img/terms20180109/small_terms'.$d.'.jpg';
        }else{
            $pic = explode('=',$solar_pic);
            $solar_pic = $pic[0];
            $small_solar_pic = $pic[1];
        }

        $return = array();
        $return['data'] = $program_list ? $program_list : '';
        $return['status'] = 1;
        $return['solar_pic'] = $solar_pic;
        $return['small_solar_pic'] = $small_solar_pic;

        extjson($return);
    }

    /**
     * 关注接口【暂时弃用】
     * @author neekli
     * @since v1.0
     */
    public function attentionProgram(){
        $this->checkUser();
        $program_id = Input::get('program_id');

        $program_info = Program::where([['status','=',1],['id','=',$program_id]])->select('id','name')->first();
        if(!$program_info){
            $return['status'] = 0;
            $return['msg'] = '节目不存在';
            extjson($return);
        }

        $user_info = Attention::where([['program_id','=',$program_id],['user_id','=',USERID]])->first();
        if($user_info['status']){
            $return['status'] = 0;
            $return['msg'] = '用户已经关注';
            extjson($return);
        }

        if($user_info){
            $update_res = Attention::where([['program_id','=',$program_id],['user_id','=',$user_info['user_id']]])->update(['status' => 1]);
            $return['status'] = $update_res ? 1 : 0;
            $return['msg'] = $update_res ? '操作成功' : '操作失败';
            extjson($return);
        }

        if(!$user_info){
            $insert_res = Attention::insert(['user_id' => USERID,'program_id'=>$program_id ,'program_name'=>$program_info['name'],'status' => 1,'create_time'=>time()]);
            $return['status'] = $insert_res ? 1 : 0;
            $return['msg'] = $insert_res ? '操作成功' : '操作失败';
            extjson($return);
        }
    }

    /**
     * 取消关注接口【暂时弃用】
     * @author neekli
     * @since v1.0
     */
    public function cancelAttention(){
        $this->checkUser();
        $program_id = Input::get('program_id');

        $user_info = Attention::where([['program_id','=',$program_id],['user_id','=',USERID]])->first();
        if(!$user_info){
            $return['status'] = 0;
            $return['msg'] = '用户未关注，不能取消关注';
            extjson($return);
        }

        if($user_info['status']==0){
            $return['status'] = 0;
            $return['msg'] = '用户已是取消关注状态';
            extjson($return);
        }

        if($user_info['status']){
            $update_res = Attention::where(['user_id' => USERID,'program_id'=>$program_id ])->update(['status' => 0]);
            $return['status'] = $update_res ? 1 : 0;
            $return['msg'] = $update_res ? '操作成功' : '操作失败';
            extjson($return);
        }

    }

    /**
     * 商品点击量累加接口
     * @author neekli
     * @since v1.0
     */
    public function goodClickNums(){
        $good_id = Input::get('good_id');

        //点击次数递增
        $update_res = Goods::where([['status','=',1],['id','=',$good_id]])->increment('click_nums');
        extOperate($update_res);
    }

    /**
     * 跳转至官网首页
     * @author kexun
     * @since v1.0
     */
    public function location()
    {
        Header("Location: http://www.sfys365.com/");
        exit;
    }
}
