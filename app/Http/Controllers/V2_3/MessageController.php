<?php
/**
 * 消息控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.3
 * @since       2.3
 */

namespace App\Http\Controllers\V2_3;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

use App\Http\Models\Comment;
use App\Http\Models\Article;
use App\Http\Models\Active;
use App\Http\Models\Program;
use App\Http\Models\User;
use App\Http\Models\FeedReply;
use App\Http\Models\CommentActive;
use App\Http\Models\CommentArticle;

use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * 系统消息
     * @author neekli
     * @since v2.3
     */
    public function systemNotice(){
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $this->checkUser();
        $where = array(
            ['feed_reply.status','=',1],
            ['feed_reply.app_status','=',1],
            ['feed_back.user_id','=',USERID],
            ['feed_back.status','=',1],
        );
        $list = FeedReply::where($where)->select('feed_reply.content','feed_reply.create_time','feed_back.id')
            ->leftJoin('feed_back', 'feed_reply.feed_id', '=', 'feed_back.id')
            ->orderBy('feed_reply.create_time','desc')
            ->offset($start)
            ->limit($pagesize)
            ->get();

        extInfo($list);
    }

    /**
     * 评论回复消息[弃用]
     * @author neekli
     * @since v2.3
     */
    public function topicNotice(){
        $this->checkUser();

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $programList = $this->programMessage();
        $articleList = $this->articleMessage();
        $activityList = $this->activityMessage();
        foreach($articleList as $kr=>$vr){
            array_push($programList,$vr);
        }
        foreach($activityList as $kc=>$vc){
            array_push($programList,$vc);
        }

        $fieldArr = array();
        foreach ($programList as $k => $v) {
            $fieldArr[$k] = $v['create_time'];
        }

        array_multisort($fieldArr, SORT_DESC, $programList);
        $res = array_splice($programList,$start,$pagesize);

        extInfo($res);
    }

    /**
     * 节目评论消息
     * @author neekli
     * @since v2.3
     */
    public function programMessage(){
        $this->checkUser();
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $type = User::where([['status','=',1],['id','=',USERID]])->value('type');
        if($type==2){//如果是大师  直接评论观南的节目 和 回复评论观南的评论  （作者是观南 rootid=0  or  找出观南的评论id 评论的父级id是观南的评论id ）
            $comment_list = Comment::where([['comment.author_id','=',USERID],['rootid','=',0]])->orWhereIn('pid', Comment::where([['user_id','=',USERID]])->pluck('id'))
                ->leftJoin('program', 'program.id', '=', 'comment.program_id')
                ->leftJoin('user', 'user.id', '=', 'comment.user_id')
                ->select('user.avatar','comment.id','comment.user_nickname as nickname','comment.content','comment.rootid','comment.pid','comment.create_time','comment.type','program.name','program.id as program_id')
                ->orderBy('create_time', 'desc')
                ->offset($start)
                ->limit($pagesize)
                ->get();

            foreach($comment_list as $k=>$comment){//如果该评论是子评论的话  找出该评论的父评论   找出该评论的节目
                if ($comment['rootid'] != 0) {
                    $comment_father= Comment::where([['id','=',$comment['pid']]])->select('content','user_nickname')->orderBy('create_time', 'desc')->first();
                    $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
                }
                $program_info = Program::where([['id','=',$comment['program_id']]])->select('id','name','showup_time','radio_url','radio_pic','burning_time','column_name')->first();
                $comment_list[$k]['info'] = $program_info ? $program_info->toArray() : null;
            }
            
            $return['data'] = $comment_list ? $comment_list : array();
            $return['status'] = 1;
            $return['msg'] = '';
            extjson($return);
        }

        $comment_list = Comment::whereIn('pid', Comment::where([['comment.user_id','=',USERID]])->pluck('id'))
            ->select('user.avatar','user.nickname','comment.id','comment.create_time','comment.rootid','comment.content','comment.pid','comment.program_id','comment.type')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->orderBy('create_time', 'desc')
            ->get()->toArray();

        $return = array();
        foreach($comment_list as $k=>$comment){//找出评论的父评论且是用户的评论
            $comment_father= Comment::where([['id','=',$comment['pid']],['user_id','=',USERID]])->select('content','user_nickname')->orderBy('comment.create_time', 'desc')->first();
            $program_info = Program::where([['id','=',$comment['program_id']]])->select('id','name','showup_time','radio_url','radio_pic','burning_time','column_name')->first();
            $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
            $comment_list[$k]['info'] = $program_info ? $program_info->toArray() : null;
            if($comment_list[$k]['father']){
                $return[$k] = $comment_list[$k];
            }
        }
        $res = array_splice($return,$start,$pagesize);

        $tmp['data'] = $res ? $res : array();
        $tmp['status'] = 1;
        $tmp['msg'] = '';
        extjson($tmp);
    }

    /**
     * 文章消息
     * @author neekli
     * @since v2.3
     */
    public function articleMessage(){

        $this->checkUser();
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $comment_list = CommentArticle::whereIn('pid', CommentArticle::where([['comment_article.user_id','=',USERID]])->pluck('id'))
            ->select('user.avatar','user.nickname','comment_article.id','comment_article.create_time','comment_article.rootid','comment_article.content','comment_article.pid','comment_article.article_id','comment_article.type')
            ->leftJoin('user', 'user.id', '=', 'comment_article.user_id')
            ->orderBy('comment_article.create_time', 'desc')
//            ->offset($start)
//            ->limit($pagesize)
            ->get()->toArray();

        $return = array();
        foreach($comment_list as $k=>$comment){//找出评论的父评论且是用户的评论

            $comment_father= CommentArticle::where([['id','=',$comment['pid']],['user_id','=',USERID]])->select('content','nickname')->orderBy('create_time', 'desc')->first();
            $article_info = Article::where([['id','=',$comment['article_id']]])->select('id','title','pic')->first();
            $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
            $comment_list[$k]['info'] = $article_info ? $article_info->toArray() : null;
            if($comment_list[$k]['father']){
                $return[$k] = $comment_list[$k];
            }
        }
        $res = array_splice($return,$start,$pagesize);

        $tmp['data'] = $res ? $res : array();
        $tmp['status'] = 1;
        $tmp['msg'] = '';
        extjson($tmp);
    }


    /**
     * 活动消息
     * @author neekli
     * @since v2.3
     */
    public function activityMessage(){
        $this->checkUser();

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $comment_list = CommentActive::whereIn('pid', CommentActive::where([['comment_active.user_id','=',USERID]])->pluck('id'))
            ->select('user.avatar','user.nickname','comment_active.id','comment_active.create_time','comment_active.rootid','comment_active.content','comment_active.pid','comment_active.active_id','comment_active.type')
            ->leftJoin('user', 'user.id', '=', 'comment_active.user_id')
            ->orderBy('comment_active.create_time', 'desc')
//            ->offset($start)
//            ->limit($pagesize)
            ->get()->toArray();

        $return = array();
        foreach($comment_list as $k=>$comment){//找出评论的父评论且是用户的评论

            $comment_father= CommentActive::where([['id','=',$comment['pid']],['user_id','=',USERID]])->select('content','nickname')->orderBy('create_time', 'desc')->first();
            $article_info = Active::where([['id','=',$comment['active_id']]])->select('id','title','pic')->first();
            $comment_list[$k]['father'] = $comment_father ? $comment_father->toArray() : array();
            $comment_list[$k]['info'] = $article_info ? $article_info->toArray() : null;
            if($comment_list[$k]['father']){
                $return[$k] = $comment_list[$k];
            }
        }
        $res = array_splice($return,$start,$pagesize);

        $tmp['data'] = $res ? $res : array();
        $tmp['status'] = 1;
        $tmp['msg'] = '';
        extjson($tmp);
    }


    /**
     * 删除系统消息
     * @author neekli
     * @since v2.3
     */
    public function deleteSystemMessage(){

        $system_ids = Input::get('system_ids');
        $system_ids = json_decode($system_ids);
        try {
            FeedReply::where([['status','=',1],['app_status','=',1]])->whereIn('feed_id', $system_ids)->update(['app_status' => 0]);
            extOperate(1,'','删除成功');
        } catch (Exception $e) {

        } finally {
            extOperate(0);
        }
    }

    /**
     * 删除评论回复消息[弃用]
     * @author neekli
     * @since v2.3
     */
    public function deleteTopicMessage(){
        $multi_id = Input::get('multi_id');
        $type = Input::get('type');

        $res = $this->checkComment($type,$multi_id,1);
        if($res['warm']['status']==0){
            extInfo($res['warm']);
        }

        $res = TRUE;
        switch ($type)
        {
            case 1:
                $res = CommentActive::where([['id','=',$multi_id]])->update(['app_status' => 0]);
                break;
            case 2:
                $res = CommentArticle::where([['id','=',$multi_id]])->update(['app_status' => 0]);
                break;
            case 3:
                $res = Comment::where([['id','=',$multi_id]])->update(['app_status' => 0]);
                break;
        }

        $return['status'] = $res ? 1 : 0;
        $return['msg'] = $res ? '删除成功' : '删除失败';
        extjson($return);
    }

    /**
     * 清空反馈消息红点提示
     * @author neekli
     * @since v2.3
     */
    public function clearFeedMessage(){

        try {
            $where = array(
                ['feed_reply.status','=',1],
                ['feed_reply.read_status','=',0],
                ['feed_back.user_id','=',USERID],
            );
            $list = FeedReply::where($where)->select('feed_back.id')
                ->leftJoin('feed_back', 'feed_reply.feed_id', '=', 'feed_back.id')
                ->pluck('id');

            FeedReply::where([['status','=',1],['read_status','=','0']])
                ->whereIn('feed_id', $list)
                ->update(['read_status' => 1]);

            extOperate(1);
        } catch (Exception $e) {

        } finally {
            extOperate(0);
        }
    }


    /**
     * 文章或者内容信息的红点提示
     * @author neekli
     * @since v2.3
     */
    public function redHint(){
        $this->checkUser();

        //判断红点
        $res1 = Comment::where([['comment.status','=',1],['read_status','=','0']])
            ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
            ->count();
        $res2 = CommentActive::where([['comment_active.status','=',1],['comment_active.read_status','=','0']])
            ->whereIn('pid', CommentActive::where([['comment_active.status','=',1],['comment_active.user_id','=',USERID]])->pluck('id'))
            ->count();
        $res3 = CommentArticle::where([['comment_article.status','=',1],['comment_article.read_status','=','0']])
            ->whereIn('pid', CommentArticle::where([['comment_article.status','=',1],['comment_article.user_id','=',USERID]])->pluck('id'))
            ->count();

        $where = array(
            ['feed_reply.status','=',1],
            ['feed_reply.read_status','=',0],
            ['feed_back.user_id','=',USERID],
        );
        $res4 = FeedReply::where($where)->leftJoin('feed_back', 'feed_reply.feed_id', '=', 'feed_back.id')->count();

        $tmp['program_c_status'] = $res1 ? 1 : 0;
        $tmp['active_c_status'] = $res2 ? 1 : 0;
        $tmp['article_c_status'] = $res3 ? 1 : 0;
        $tmp['feed_status'] = $res4 ? 1 : 0;
        $tmp['msg'] = '';
        $tmp['status'] = 1;
        extjson($tmp);
    }


    /**
     * 请空红点提示[评论相关]
     * @type 1:活动 2:文章 3:节目
     * @author neekli
     * @since v1.0
     */
    public function clearRedHint(){
        $this->checkUser();
        $type = Input::get('type');

        switch($type){
            case 1:
                try {
                    CommentActive::where([['comment_active.status','=',1],['comment_active.read_status','=','0']])
                        ->whereIn('pid', CommentActive::where([['comment_active.status','=',1],['user_id','=',USERID]])->pluck('id'))
                        ->update(['comment_active.read_status' => 1]);
                    extOperate(1);
                } catch (Exception $e) {

                } finally {
                    extOperate(0);
                }
                break;
            case 2:
                try {
                    CommentArticle::where([['comment_article.status','=',1],['comment_article.read_status','=','0']])
                        ->whereIn('pid', CommentArticle::where([['comment_article.status','=',1],['user_id','=',USERID]])->pluck('id'))
                        ->update(['comment_article.read_status' => 1]);
                    extOperate(1);
                } catch (Exception $e) {

                } finally {
                    extOperate(0);
                }
                break;
            case 3:
                try {
                    if (TYPE == 2) {
                        Comment::where([['status','=',1],['read_status','=','0'],['author_id','=',USERID]])->update(['read_status' => 1]);
                        Comment::where([['comment.status','=',1],['comment.read_status','=','0']])
                            ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                            ->update(['comment.read_status' => 1]);
                    } else {
                        Comment::where([['comment.status','=',1],['comment.read_status','=','0']])
                            ->whereIn('pid', Comment::where([['comment.status','=',1],['user_id','=',USERID]])->pluck('id'))
                            ->update(['comment.read_status' => 1]);
                    }
                    extOperate(1);
                } catch (Exception $e) {
                } finally {
                    extOperate(0);
                }
                break;
        }

    }

}
