<?php
/**
 * Article控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.2
 * @since       2.2
 */
namespace App\Http\Controllers\V2_4_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Http\Models\Article;
use App\Http\Models\Active;
use App\Http\Models\CommentActive;
use App\Http\Models\CommentArticle;
use App\Http\Models\CollectMulti;


class ArticleController extends Controller
{
    public $hot_article_nums = 3;

    /**
     *  精选文章
     *  @author neek li
     *  @since v2.4
     */
    public function siftArticle(){
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $article_list = Article::where([['status','=',1]])
            ->select('id','title','pic','author','views','likes','pic_style','comment_nums')
            ->orderBy('update_time', 'desc')
            ->offset($start)
            ->limit($pagesize)
            ->get();

        foreach($article_list as $k=>$v){
            $article_list[$k]['type'] = 2;
            $article_list[$k]['views'] = $this->testMillion($v['views']);
            $article_list[$k]['likes'] = $this->testMillion($v['likes']);
            $article_list[$k]['comment_nums'] = $this->testMillion($v['comment_nums']);
        }

        extInfo($article_list);
    }

    /**
     *  热门文章
     *  @author neek li
     *  @since v2.4
     */
    public function hotArticle(){

        $article_list = Article::where([['status','=',1]])
            ->select('id','title','pic','author','views','likes','comment_nums','pic_style')
            ->orderBy('views', 'desc')
            ->limit($this->hot_article_nums)
            ->get();

        foreach($article_list as $k=>$v){
            $article_list[$k]['type'] = 2;
            $article_list[$k]['views'] = $this->testMillion($v['views']);
            $article_list[$k]['likes'] = $this->testMillion($v['likes']);
            $article_list[$k]['comment_nums'] = $this->testMillion($v['comment_nums']);
        }
        extInfo($article_list);
    }

    /**
     *  搜索文章
     *  @author neek li
     *  @since v2.4
     */
    public function articleSearch(){   

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $keyword = Input::get('key_word');//先用like模糊查找 后期扩展成sphinx
        $where = array(
            ['status','=',1],
            ['title','like','%'.$keyword.'%'],
        );

        $article_list = Article::where($where)->select('id','title','pic','author','views','likes','comment_nums','pic_style')
            ->orderBy('update_time', 'desc')
            ->offset($start)
            ->limit($pagesize)
            ->get();

        foreach($article_list as $k=>$v){
            $article_list[$k]['type'] = 2;
            $article_list[$k]['views'] = $this->testMillion($v['views']);
            $article_list[$k]['likes'] = $this->testMillion($v['likes']);
            $article_list[$k]['comment_nums'] = $this->testMillion($v['comment_nums']);
        }
        extInfo($article_list);
    }

    /**
     * 文章或者活动详情
     * @author neek li
     * @since v2.4
     */
    public function ArAcInof(){    
        $multi_id = Input::get('multi_id');
        $type = Input::get('type');

        $res=$this->checkThree($type,$multi_id);
        if($res['warm']['status']==0){
            extInfo($res['warm']);
        }

        $where = array(
            ['status','=',1],
            ['id','=',$multi_id],
        );
        //递增阅读数
        $this->increTimes($type,$multi_id,3);

        $return_info = array();
        if($type==1){//活动
            $return_info = Active::where($where)
                ->select('id','pic','title','update_time','content','imgsJson','views','likes','status','comment_nums as pcomments','share_img','share_title','share_intro')
                ->first();
            $return_info['views'] = $this->testMillion($return_info['views']);
            $return_info['likes'] = $this->testMillion($return_info['likes']);
            $return_info['pcomments'] = $this->testMillion($return_info['pcomments']);
            $return_info['type'] = 1;
            $return_info['collectStatus'] = $this->checkCollect($multi_id,$type);
        }elseif($type==2){//文章
            $return_info = Article::where($where)
                ->select('id','pic','title','author','update_time','content','imgsJson','views','likes','status','comment_nums as pcomments','share_img','share_title','share_intro')
                ->first();

            $return_info['type'] = 2;
            $return_info['views'] = $this->testMillion($return_info['views']);
            $return_info['likes'] = $this->testMillion($return_info['likes']);
            $return_info['pcomments'] = $this->testMillion($return_info['pcomments']);
            $return_info['collectStatus'] = $this->checkCollect($multi_id,$type);
        }

        $share = [   
            'pic'=>$return_info['share_img'] ?: config('yunshui.http_url').'/img/share.png',
            'name'=>$return_info['share_title'] ?: $return_info['title'],
            'summary'=>$return_info['share_intro'] ? cutstr_html(html_entity_decode($return_info['share_intro']),50) : cutstr_html(html_entity_decode($return_info['content']),50),
            'url'=>config('yunshui.http_url').'/share?share_id='.$multi_id.'&type='.$type,
        ];

        $return_info['share'] =  $share;

        $return_info['imgsJson'] = json_decode($return_info['imgsJson']);

        $return_info['content'] = htmlspecialchars_decode($return_info['content']);


        extInfo($return_info);
    }

    /**
     * check是否收藏
     */
    public function checkCollect($multi_id,$type){

        $userid = ($this->userid) ? USERID : 0;
        $res = 0;
        if($userid){
            $res = CollectMulti::where([['status','=',1],['type','=',$type],['multi_id','=',$multi_id],['user_id','=',$userid]])->first();
        }
        $res = $res ? 1 : 0;
        return $res;
    }

    public function checkArticle($article_id){
        $article_info = Article::where([['id','=',$article_id],['status','=',1]])->select('id','likes')->first();
        $return['data'] = $article_info ? $article_info : array();
        $return['warm']['status'] = $article_info ? 1 : 0;
        $return['warm']['msg'] = $article_info ? '' : '文章不存在';

        return $return;
    }

    /**
     * 文章或者活动收藏
     * @author neek li
     * @since v2.4
     */
    public function ArAcCollect(){
        $this->checkUser();

        $multi_id = Input::get('multi_id');
        $type = Input::get('type');
        //递增文章或者活动的收藏数
        $this->increTimes($type,$multi_id,4);

        $check_res=$this->checkThree($type,$multi_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $user_info = CollectMulti::where([['multi_id','=',$multi_id],['type','=',$type],['user_id','=',USERID]])->first();
        if($user_info['status']){
            extOperate(false,'用户已经收藏');
        }

        if($user_info){
            $update_res = CollectMulti::where([['multi_id','=',$multi_id],['type','=',$type],['user_id','=',USERID]])->update(['status' => 1]);
            extOperate($update_res,'收藏失败');
        }

        if(!$user_info){
            $insert_res = CollectMulti::insert(['user_id' => USERID,'multi_id'=>$multi_id ,'type'=>$type,'status' => 1,'create_time'=>time()]);
            extOperate($insert_res,'收藏失败');
        }
    }

    /**
     * 文章或者活动取消收藏
     * @author neekli
     * @since v2.4
     */
    public function cancelArAcCollect(){
        $this->checkUser();
        $multi_id = Input::get('multi_id');
//        $type = Input::get('type');
//
//        $check_res=$this->checkThree($type,$multi_id);
//        if($check_res['warm']['status']==0){
//            extInfo($check_res['warm']);
//        }
        $this->decreTimes(2,$multi_id,3);

        $user_info = CollectMulti::where([['multi_id','=',$multi_id],['user_id','=',USERID]])->first();

        if(!$user_info){
            extOperate(false,'用户未收藏，不能取消收藏');
        }

        if($user_info['status']==0){
            extOperate(false,'用户已是取消关注状态');
        }

        if($user_info['status']){
            $update_res = CollectMulti::where(['user_id' => USERID,'multi_id'=>$multi_id])->update(['status' => 0]);
            extOperate($update_res);
        }
    }

    /**
     * 文章、活动点赞
     */
    public function allLikes(){
        $multi_id = Input::get('multi_id');
        $type = Input::get('type');

        $check_res=$this->checkThree($type,$multi_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $update_res = TRUE;
        switch ($type)
        {
            case 1:
                $update_res = Active::where([['status','=',1],['id','=',$multi_id]])->increment('likes');
                break;
            case 2:
                $update_res = Article::where([['status','=',1],['id','=',$multi_id]])->increment('likes');
                break;
        }


        $return['status'] = $update_res ? 1 : 0;
        $return['msg'] = $update_res ? '点赞成功' : '操作失败';
        $return['nums'] = $update_res ? ($check_res['data']['likes'])+1 : 0;
        extjson($return);
    }

}
