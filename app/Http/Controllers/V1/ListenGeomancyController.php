<?php
/**
 * 听风水控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Models\Program;
use App\Http\Models\Column;
use App\Http\Models\Goods;
use App\Http\Models\Collect;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ListenGeomancyController extends Controller
{
    /**
     * 节目搜索
     * @author neekli
     * @since v1.0
     */
    public function programSearch(){
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $keyword = Input::get('keyword');//先用like模糊查找 后期扩展成sphinx
        $where = array(
            ['type','=',0],
            ['status','=',1],
            ['name','like','%'.$keyword.'%'],
            ['radio_origin_url','<>',''],
        );
        $program_list = Program::where($where)->select('id','name','column_name','burning_time','radio_pic','radio_url')
            ->offset($start)
            ->limit($pagesize)
            ->orderBy('update_time', 'desc')
            ->get();

        extInfo($program_list);
    }

    /**
     * 热门节目
     * @author neekli
     * @since v1.0
     */
    public function hotProgram(){

        $where = array(
            ['status', '=', 1],
            ['type', '=', 0],
            ['radio_origin_url','<>',''],
        );
        $program_list = Program::where($where)
            ->select('id','name','column_name','burning_time','radio_pic','radio_url')
            ->orderBy('play_nums', 'desc')
            ->limit(3)
            ->get();

        extInfo($program_list);
    }

    /**
     * 节目详情
     * @author neekli
     * @since v1.0
     */
    public function columnInfo(){

        //$column_id = Input::get('column_id');
        $column_id = Column::where('status',1)->limit(1)->value('id');
        $where = array(
            ['column.status','=',1],
            ['column.id','=',$column_id],
        );
        $program_info = Column::where($where)
            ->select('signed_author.detail as author_detail','column.detail as column_detail','column.background_pic','column.name','column.title')
            ->leftJoin('signed_author', 'column.user_id', '=', 'signed_author.user_id')
            ->first();

        $return['status'] = 1;
        $return['data'] = $program_info ? $program_info : null;
        extjson($return);
    }    

    /**
     * 节目待播放列表上面图片数据[后期要根据栏目]
     * @author neekli
     * @since v1.0
     */
    public function programListhead(){

        $where = array(
            ['status','=',1],
            ['id','=',1],
        );
        $first_info = Column::where($where)->select('background_pic','name')->first();

        extInfo($first_info);
    }

    /**
     * 节目播放列表
     * @author neekli
     * @since v1.0
     */
    public function programList(){

        $page = Input::get('page') ?: 1;
        //$column_id = Input::get('column_id');
        $column_id = Column::where('status',1)->limit(1)->value('id');

        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $where = array(
            ['type','=',0],
            ['status','=',1],
            ['column_id','=',$column_id],
            ['radio_origin_url','<>',''],
        );
        $program_list = Program::where($where)
            ->select('id','name','showup_time','radio_url','radio_pic','burning_time','column_name')
            ->offset($start)
            ->limit($pagesize)
            ->orderBy('showup_time','desc')
            ->get();

        extInfo($program_list);
    }

    /**
     * 节目播放页面
     * @author neekli
     * @since v1.0
     */
    public function playRadio(){
        $program_id = Input::get('program_id');

        //判断是否可以听并且说明原因
        $this->isPlay($program_id);

        $where = array(
            ['status','=',1],
            ['id','=',$program_id],
        );

        $program_info = Program::where($where)
            ->select('id', 'radio_pic', 'radio_url', 'name', 'showup_time', 'burning_time', 'play_nums', 'column_name', 'status as del_status', 'type')
            ->first();

        $collect = 0;
        if($this->userid>0){
            $collect = Collect::where([['status','=',1],['user_id','=',USERID],['program_id','=',$program_id]])->value('status');;
        }

        $good_list = Goods::where([['status','=',1],['program_id','=',$program_info['id']]])
            ->select('id','name','taobao_url','pic')
            ->get();

        $program_info['state'] =  $collect ? 1 : 0;
        $program_info['goods'] =  $good_list;
        $summary = $program_info['name'] ? '#十方云水#快来听《'.$program_info['column_name'].'》的节目'.'“'.$program_info['name'].'”' : '我们专注于传递中国传统文化，弘扬正统易学之道，品味国学经典';
        $program_info['share'] =  [
            'radio_pic'=>$program_info['radio_pic'],
            'name'=>$program_info['name'],
            'summary'=>$summary,
            'url'=>config('yunshui.http_url').'shareRadio?share_id='.$program_id,
        ];

        $return['status'] = 1;

        $return['playtype'] = 1;
        $return['data'] = $program_info ? $program_info : '';
        $return['msg'] = $program_info ? '' : '加载数据失败';
        extjson($return);
    }


    /**
     * 节目分享次数累加
     */
    public function shareNums(){
        $program_id = Input::get('program_id');

        //分享次数递增
        $update_res = Program::where([['status','=',1],['id','=',$program_id]])->increment('share_nums');
        extOperate($update_res);
    }

    /**
     * 节目下载次数累加
     * @author neekli
     * since v1.0
     */
    public function downloadNums(){
        $program_id = Input::get('program_id');

        //下载次数递增
        $update_res = Program::where([['status','=',1],['id','=',$program_id]])->increment('download_nums');
        extOperate($update_res);
    }

    /**
     * 节目播放次数累加
     * @author neekli
     * @since v1.0
     */
    public function playNums(){
        $program_id = Input::get('program_id');

        //下载次数递增
        $update_res = Program::where([['status','=',1],['id','=',$program_id]])->increment('play_nums');
        extOperate($update_res);
    }

    /**
     * 节目收藏
     * @author neekli
     * @since v1.0
     */
    public function collectProgram(){
        $this->checkUser();
        $program_id = Input::get('program_id');

        $program_info = Program::where([['status','=',1],['id','=',$program_id]])->select('id','name')->first();
        if(!$program_info){
           extOperate(false,'节目不存在');
        }

        $user_info = Collect::where([['program_id','=',$program_id],['user_id','=',USERID]])->first();
        if($user_info['status']){
            extOperate(false,'用户已经收藏');
        }

        if($user_info){
            $update_res = Collect::where([['program_id','=',$program_id],['user_id','=',$user_info['user_id']]])->update(['status' => 1]);
            extOperate($update_res,'收藏失败');
        }

        if(!$user_info){
            $insert_res = Collect::insert(['user_id' => USERID,'program_id'=>$program_id ,'program_name'=>$program_info['name'],'status' => 1,'create_time'=>time()]);
            extOperate($insert_res,'收藏失败');
        }
    }

    /**
     * 取消收藏
     * @author neekli
     * @since v1.0
     */
    public function cancelCollect(){
        $this->checkUser();
        $program_id = Input::get('program_id');

        $user_info = Collect::where([['program_id','=',$program_id],['user_id','=',USERID]])->first();
        if(!$user_info){
            extOperate(false,'用户未收藏，不能取消收藏');
        }

        if($user_info['status']==0){
            extOperate(false,'用户已是取消关注状态');
        }

        if($user_info['status']){
            $update_res = Collect::where(['user_id' => USERID,'program_id'=>$program_id ])->update(['status' => 0]);
            extOperate($update_res);
        }
    }

    /**
     * 关注次数累加
     * @author neekli
     * @since v1.0
     */
    public function attentionNums(){
        $program_id = Input::get('program_id');

        //关注次数递增
        $update_res = Program::where([['status','=',1],['id','=',$program_id]])->increment('attention_nums');
        extOperate($update_res);
    }

    /**
     * isPlay
     * 是否可以播放   //单独调用这个接口，status = 0,1表示成功和失败，  其他接口调用这个函数，status均为1
     * @author by kexun
     * @access public
     * @since 1.0
     */
    public function isPlay(){
        $Program_id = func_get_args();
        $program_id = $Program_id ? $Program_id :Input::get('program_id');

        $program_info = Program::where('id',$program_id)
            ->select('status as del_status','type')
            ->first();

        if (!$program_info) {
            $return['status'] = $Program_id ? 1:0;
            $return['msg'] = '该节目不存在！';
            $return['playtype'] = 0;
            extjson($return);
        }

        //判断是否可以听并且说明原因
        if ($program_info['del_status'] == 0 || $program_info['type'] != 0){
            if ($program_info['del_status'] == 0) {
                $return['status'] = $Program_id ? 1:0;
                $return['msg'] = '该节目已删除！';
                $return['playtype'] = 0;
                extjson($return);
            }
            if ($program_info['type'] == 1) {
                $return['status'] = $Program_id ? 1:0;
                $return['msg'] = '该节目还未上架！';
                $return['playtype'] = 0;
                extjson($return);
            }
            if ($program_info['type'] == 2) {
                $return['status'] = $Program_id ? 1:0;
                $return['msg'] = '该节目已下架！';
                $return['playtype'] = 0;
                extjson($return);
            }
        }
        $return['status'] = 1;
        $return['playtype'] = 1;
        $Program_id ? '':extjson($return);

    }

}
