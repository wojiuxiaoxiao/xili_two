<?php
/**
 * 评论控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_3;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Http\Models\Program;
use App\Http\Models\User;
use App\Http\Models\Active;
use App\Http\Models\Article;
use App\Http\Models\Client;
use App\Http\Models\Comment;
use App\Http\Models\CommentActive;
use App\Http\Models\CommentArticle;
use App\Http\Controllers\Controller;
use App\Providers\GetuiServiceProvider;


class CommentController extends Controller
{
    /**
     * 一级评论列表
     */
    public function allCommentList(){

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $multi_id = Input::get('multi_id');
        $type = Input::get('type');
        $userid = ($this->userid) ? USERID : 0;

        $check_res=$this->checkThree($type,$multi_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $pcomment_list = array();
        $allComments = 0;
        switch ($type)//1 活动 2 文章 3 节目
        {
            case 1:
                $pcomment_list = CommentActive::where([['comment_active.active_id','=',$multi_id],['comment_active.rootid','=',0],['comment_active.status','=',1],['user.status','=',1]])
                    ->select('comment_active.content','comment_active.create_time','comment_active.type','comment_active.id','comment_active.likes','comment_active.user_id','comment_active.active_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_active.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($pcomment_list as $k=>$v){
                    $pcomment_list[$k]['comments'] = CommentActive::where([['rootid','=',$v['id']],['status','=',1]])->count();
                    $pcomment_list[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $pcomment_list[$k]['likes'] = $this->testMillion($v['likes']);
                }

                $allComments =  CommentActive::where([['comment_active.active_id','=',$multi_id],['comment_active.rootid','=',0],['comment_active.status','=',1]])->count();
                break;
            case 2:
                $pcomment_list = CommentArticle::where([['comment_article.article_id','=',$multi_id],['comment_article.rootid','=',0],['comment_article.status','=',1],['user.status','=',1]])
                    ->select('comment_article.content','comment_article.create_time','comment_article.type','comment_article.id','comment_article.likes','comment_article.user_id','comment_article.article_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_article.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($pcomment_list as $k=>$v){
                    $pcomment_list[$k]['comments'] = CommentArticle::where([['rootid','=',$v['id']],['status','=',1]])->count();
                    $pcomment_list[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $pcomment_list[$k]['likes'] = $this->testMillion($v['likes']);
                }

                $allComments =  CommentArticle::where([['comment_article.article_id','=',$multi_id],['comment_article.rootid','=',0],['comment_article.status','=',1]])->count();
                break;
            case 3:
                $pcomment_list = Comment::where([['comment.program_id','=',$multi_id],['comment.rootid','=',0],['comment.status','=',1],['user.status','=',1]])
                    ->select('comment.content','comment.create_time','comment.type','comment.id','comment.user_id','comment.likes','comment.program_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($pcomment_list as $k=>$v){
                    $pcomment_list[$k]['comments'] = Comment::where([['rootid','=',$v['id']],['status','=',1]])->count();
                    $pcomment_list[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $pcomment_list[$k]['likes'] = $this->testMillion($v['likes']);
                }

                $allComments = Comment::where([['comment.program_id','=',$multi_id],['comment.rootid','=',0],['comment.status','=',1]])->count();
                break;
        }

        $return['status'] = 1;
        $return['allComments'] = $allComments;
        $return['data'] = $pcomment_list ? $pcomment_list : null;
        extjson($return);
    }

    /**
     * 所有的评论详情
     */
    public function allCommentInfo(){

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $pmulti_id = Input::get('pmulti_id');
        $type = Input::get('type');

        $check_comment = $this->checkComment($type,$pmulti_id,'主评论不存在');
        if($check_comment['warm']['status']==0){
            extjson($check_comment['warm']);
        }

        $check_res = $this->checkCommentThree($type,$pmulti_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $userid = ($this->userid) ? USERID : 0;

        $comment = $comment_children = array();
        $counts = 0;

        switch ($type)//1 活动 2 文章 3节目
        {
            case 1:
                $comment = CommentActive::where([['comment_active.id','=',$pmulti_id],['comment_active.rootid','=',0],['comment_active.status','=',1]])
                    ->select('comment_active.content','comment_active.create_time','comment_active.id','comment_active.type','comment_active.likes','comment_active.user_id','comment_active.active_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_active.user_id', '=', 'user.id')
                    ->first();
                $comment['canDel'] = ($comment['user_id']==$userid) ? 1 : 0;

                $counts = CommentActive::where([['rootid','=',$pmulti_id],['status','=',1]])->count();

                $comment_children = CommentActive::where([['comment_active.rootid','=',$pmulti_id],['comment_active.status','=',1]])
                    ->select('comment_active.content','comment_active.pid','comment_active.create_time','comment_active.id','comment_active.type','comment_active.likes','comment_active.user_id','comment_active.active_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_active.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($comment_children as $k=>$v){
                    if($v['pid']!=$comment['id']){
                        $pname = CommentActive::where([['comment_active.status','=',1],['comment_active.id','=',$v['pid']]])
                            ->leftJoin('user', 'user.id', '=', 'comment_active.user_id')
                            ->select('user.nickname')
                            ->first();
                        $pname = $pname['nickname'];
                    }else{
                        $pname = '';
                    }
                    $comment_children[$k]['pname'] = $pname;
                    $comment_children[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $comment_children[$k]['likes'] = $this->testMillion($v['likes']);
                }
                break;
            case 2:

                $comment = CommentArticle::where([['comment_article.id','=',$pmulti_id],['comment_article.rootid','=',0],['comment_article.status','=',1]])
                    ->select('comment_article.content','comment_article.create_time','comment_article.id','comment_article.type','comment_article.likes','comment_article.user_id','comment_article.article_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_article.user_id', '=', 'user.id')
                    ->first();
                $comment['canDel'] = ($comment['user_id']==$userid) ? 1 : 0;

                $counts = CommentArticle::where([['rootid','=',$pmulti_id],['status','=',1]])->count();

                $comment_children = CommentArticle::where([['comment_article.rootid','=',$pmulti_id],['comment_article.status','=',1]])
                    ->select('comment_article.content','comment_article.pid','comment_article.create_time','comment_article.id','comment_article.type','comment_article.user_id','comment_article.likes','comment_article.article_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment_article.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($comment_children as $k=>$v){
                    if($v['pid']!=$comment['id']){
                        $pname = CommentArticle::where([['comment_article.status','=',1],['comment_article.id','=',$v['pid']]])
                            ->leftJoin('user', 'user.id', '=', 'comment_article.user_id')
                            ->select('user.nickname')
                            ->first();
                        $pname = $pname['nickname'];
                    }else{
                        $pname = '';
                    }
                    $comment_children[$k]['pname'] = $pname;
                    $comment_children[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $comment_children[$k]['likes'] = $this->testMillion($v['likes']);
                }
                break;

            case 3:
                $comment = Comment::where([['comment.id','=',$pmulti_id],['comment.rootid','=',0],['comment.status','=',1]])
                    ->select('comment.content','comment.create_time','comment.id','comment.type','comment.user_id','comment.rootid','comment.likes','comment.program_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment.user_id', '=', 'user.id')
                    ->first();
                $comment['canDel'] = ($comment['user_id']==$userid) ? 1 : 0;

                $counts = Comment::where([['rootid','=',$pmulti_id],['status','=',1]])->count();

                $comment_children = Comment::where([['comment.rootid','=',$pmulti_id],['comment.status','=',1]])
                    ->select('comment.content','comment.pid','comment.create_time','comment.id','comment.user_id','comment.type','comment.rootid','comment.likes','comment.program_id as multi_id','user.avatar','user.nickname')
                    ->leftJoin('user', 'comment.user_id', '=', 'user.id')
                    ->offset($start)
                    ->limit($pagesize)
                    ->orderBy('create_time', 'desc')
                    ->get();

                foreach($comment_children as $k=>$v){
                    if($v['pid']!=$comment['id']){
                        $pname = Comment::where([['comment.status','=',1],['comment.id','=',$v['pid']]])
                            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
                            ->select('user.nickname')
                            ->first();
                        $pname = $pname['nickname'];
                    }else{
                        $pname = '';
                    }
                    $comment_children[$k]['pname'] = $pname;
                    $comment_children[$k]['canDel'] = ($v['user_id']==$userid) ? 1 : 0;
                    $comment_children[$k]['likes'] = $this->testMillion($v['likes']);
                }
                break;
        }

        $return['comment'] = $comment ? $comment : array();
        $return['comment']['comments'] = $counts ? $counts : 0;
        $return['comment_children'] = $comment_children ? $comment_children : array();

        extInfo($return);
    }

    /**
     * 写评论
     */
    public function allPostComment(){
        $this->checkUser();

        $multi_id = Input::get('multi_id');
        $type = Input::get('type');
        $content = Input::get('content');
        //增加评论数
        $this->increTimes($type,$multi_id,1);

        $check_res=$this->checkThree($type,$multi_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $post_id = 0;
        $rootid = 0;

        $insert_id = 0;
        switch ($type)
        {
            case 1:
                $insert_id = CommentActive::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'nickname'=>NICKNMAE,'active_id'=>$multi_id,'pid'=>$post_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
            case 2:
                $insert_id = CommentArticle::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'nickname'=>NICKNMAE,'article_id'=>$multi_id,'pid'=>$post_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
            case 3:
                $insert_id = Comment::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'user_nickname'=>NICKNMAE,'program_id'=>$multi_id,'author_id'=>$check_res['data']['author_id'],'pid'=>$post_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
        }

        $return['status'] = $insert_id ? 1 : 0;
        $return['msg'] = $insert_id ? '评论成功' : '评论失败';
        extjson($return);
    }

    /**
     * 评论回复
     */
    public function allReplyComment(){

        $this->checkUser();

        $pmulti_id = Input::get('pmulti_id');//父级评论id
        $multi_id = Input::get('multi_id');
        $type = Input::get('type');
        $content = Input::get('content');

        $checkm_res = $this->checkComment($type,$pmulti_id);
        if($checkm_res['warm']['status']==0){
            extjson($checkm_res['warm']);
        }

        $check_root = $this->checkRootComment($type,$pmulti_id);
        if($check_root['warm']['status']==0){
            extjson($check_root['warm']);
        }

        $check_res=$this->checkThree($type,$multi_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $insert_id = 0;
        switch ($type)
        {
            case 1:
                $rootid = $checkm_res['data']['rootid'] ? $checkm_res['data']['rootid'] : $pmulti_id;
                $insert_id = CommentActive::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'nickname'=>NICKNMAE,'active_id'=>$multi_id,'pid'=>$pmulti_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
            case 2:
                $rootid = $checkm_res['data']['rootid'] ? $checkm_res['data']['rootid'] : $pmulti_id;
                $insert_id = CommentArticle::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'nickname'=>NICKNMAE,'article_id'=>$multi_id,'pid'=>$pmulti_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
            case 3:
                $rootid = $checkm_res['data']['rootid'] ? $checkm_res['data']['rootid'] : $pmulti_id;
                $insert_id = Comment::insertGetId(
                    ['content' => $content, 'user_id' => USERID,'user_nickname'=>NICKNMAE,'program_id'=>$multi_id,'author_id'=>$check_res['data']['author_id'],'pid'=>$pmulti_id,'rootid'=>$rootid,'create_time'=>time()]
                );
                break;
        }

        //推送给上级评论者
        $clentid = Client::where('user_id', $checkm_res['data']['user_id'])->value('clientId');

        if(isset($clentid)){
            $data['title'] = '十方云水';
            $data['body'] = '大事不好啦！有人居然这样评论了你的留言~快看下TA到底说了啥';
            $data['clientId'] = $clentid;
            $content = array(
                'action'=>'message',
                'multi_id'=>$pmulti_id,//多种评论的id
                'type'=>$type,
                'title'=>"十方云水",
                'content' => $data['body'],
            );
            $data['content'] = json_encode($content);
            GetuiServiceProvider::singlePush($data);
        }


        $return['status'] = $insert_id ? 1 : 0;
        $return['msg'] = $insert_id ? '回复成功' : '回复失败';
        extjson($return);
    }

    /**
     * 评论删除
     */
    public function allCommentDelete(){
        $this->checkUser();

        $pmulti_id = Input::get('pmulti_id');//评论id
        $type = Input::get('type');

        $checkm_res = $this->checkComment($type,$pmulti_id,"要删除的评论的不存在");
        if($checkm_res['warm']['status']==0){
            extjson($checkm_res['warm']);
        }
        if($type!=3){
            $this->decreTimes($type,$checkm_res['data']['multi_id'],1,$pmulti_id);
        }

        $check_root = $this->checkRootComment($type,$pmulti_id);
        if($check_root['warm']['status']==0){
            extjson($check_root['warm']);
        }

        $check_res=$this->checkCommentThree($type,$pmulti_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $res = TRUE;
        switch ($type)
        {
            case 1:
                $info = CommentActive::where([['id','=',$pmulti_id],['user_id','=',USERID],['status','=',1]])->select('id','user_id')->first();
                if(!$info){
                    extInfo([['status'=>0,'msg'=>'不是自己的评论，不能删除']]);
                }
                $res = CommentActive::where([['id','=',$pmulti_id]])->update(['status' => 0]);
                Active::where('id',$check_res['data']['id'])->where('status',1)->decrement('comment_nums',1);
                break;
            case 2:
                $info = CommentArticle::where([['id','=',$pmulti_id],['user_id','=',USERID],['status','=',1]])->select('id','user_id')->first();
                if(!$info){
                    extInfo([['status'=>0,'msg'=>'不是自己的评论，不能删除']]);
                }
                $res = CommentArticle::where([['id','=',$pmulti_id]])->update(['status' => 0]);
                Article::where('id',$check_res['data']['id'])->where('status',1)->decrement('comment_nums',1);
                break;
            case 3:
                $info = Comment::where([['id','=',$pmulti_id],['user_id','=',USERID],['status','=',1]])->select('id','user_id')->first();
                if(!$info){
                    extInfo([['status'=>0,'msg'=>'不是自己的评论，不能删除']]);
                }
                $res = Comment::where([['id','=',$pmulti_id]])->update(['status' => 0]);
                break;
        }

        $return['status'] = $res ? 1 : 0;
        $return['msg'] = $res ? '删除成功' : '删除失败';
        extjson($return);
    }

    /**
     * 评论点赞
     */
    public function pAllLikes(){
        $this->checkUser();
        $pmulti_id = Input::get('pmulti_id');//评论id
        $type = Input::get('type');

        $checkm_res = $this->checkComment($type,$pmulti_id,"评论不存在");
        if($checkm_res['warm']['status']==0){
            extjson($checkm_res['warm']);
        }

        $check_root = $this->checkRootComment($type,$pmulti_id);
        if($check_root['warm']['status']==0){
            extjson($check_root['warm']);
        }

        $check_res=$this->checkCommentThree($type,$pmulti_id);
        if($check_res['warm']['status']==0){
            extjson($check_res['warm']);
        }

        $update_res = TRUE;
        switch ($type)
        {
            case 1:
                $update_res = CommentActive::where([['status','=',1],['id','=',$pmulti_id]])->increment('likes');
                break;
            case 2:
                $update_res = CommentArticle::where([['status','=',1],['id','=',$pmulti_id]])->increment('likes');
                break;
            case 3:
                $update_res = Comment::where([['status','=',1],['id','=',$pmulti_id]])->increment('likes');
                break;
        }

        $return['status'] = $update_res ? 1 : 0;
        $return['nums'] = $update_res ? ($checkm_res['data']['likes'])+1 : 0;
        $return['msg'] = $update_res ? '' : '';
        extjson($return);
    }

}
