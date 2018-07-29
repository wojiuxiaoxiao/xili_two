<?php
/**
 * 评论控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_1;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Http\Models\Program;
use App\Http\Models\Comment;
use App\Http\Models\User;
use App\Http\Controllers\Controller;

class CommentController extends Controller
{

    /**
     * 热门评论[取缓存数据]顶级的前5个数据
     * @author neekli
     * @since v1.0
     */
    public function hotComment(){
        $program_id = Input::get('program_id');

        $comment_list = Comment::where([['comment.status','=',1],['comment.program_id','=',$program_id]])
            ->select('user.avatar','user.nickname','comment.id','comment.create_time','comment.content','comment.pid')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->orderBy('comment.create_time', 'desc')
            ->limit(5)
            ->get()->toArray();

        foreach($comment_list as $k=>$comment){
            $nums = Redis::hget('commentlikes','cm'.$comment['id']);
            $comment_list[$k]['like_state'] = Redis::sismember ('user:comlike:state'.$comment['id'],$this->userid);
            $comment_list[$k]['nums'] = $nums ? $nums : 0;
            $comment_list[$k]['father'] = Comment::where([['comment.status','=',1],['comment.id','=',$comment['pid']],['comment.program_id','=',$program_id]])
                ->select('user.nickname','comment.content')
                ->leftJoin('user', 'user.id', '=', 'comment.user_id')
                ->orderBy('comment.create_time', 'desc')
                ->limit(1)->get()->toArray();
        }

        extInfo($comment_list);
    }

    /**
     * 节目评论列表
     * @author neekli
     * @since v1.0
     */
    public function commentList(){
        $program_id = Input::get('program_id');
        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $start = $pagesize*($page-1);

        $comment_list = Comment::where([['comment.status','=',1],['comment.program_id','=',$program_id]])
            ->select('user.avatar','user.nickname','comment.id','comment.create_time','comment.content','comment.pid')
            ->leftJoin('user', 'user.id', '=', 'comment.user_id')
            ->orderBy('comment.create_time', 'desc')
            ->offset($start)
            ->limit($pagesize)
            ->get()->toArray();

        foreach($comment_list as $k=>$comment){

            $nums = Redis::hget('commentlikes','cm'.$comment['id']);
            $comment_list[$k]['like_state'] = $this->userid>1 ? Redis::sismember ('user:comlike:state'.$comment['id'],USERID) : 0;
            $comment_list[$k]['nums'] = $nums ? $nums : 0;
            $comment_list[$k]['father'] = Comment::where([['comment.status','=',1],['comment.id','=',$comment['pid']],['comment.program_id','=',$program_id]])
                ->select('user.nickname','comment.content')
                ->leftJoin('user', 'user.id', '=', 'comment.user_id')
                ->orderBy('comment.create_time', 'desc')
                ->limit(1)->get()->toArray();
        }

        extInfo($comment_list);
    }

    /**
     * 写评论
     * @author neekli
     * @since v1.0
     */
    public function postComment(){
        $this->checkUser();

        $content = Input::get('content');
        $program_id = Input::get('program_id');

        $program_info = Program::where([['status','=',1],['id','=',$program_id]])->select('id','author_id')->first();
        if(!$program_info['id']){
            $return['status'] = 0;
            $return['msg'] = '要评论的节目不存在';
            extjson($return);
        }

        $post_id = 0;
        $rootid = 0;
        $insert_id = Comment::insertGetId(
            ['content' => $content, 'user_id' => USERID,'user_nickname'=>NICKNMAE,'program_id'=>$program_id,'author_id'=>$program_info['author_id'],'pid'=>$post_id,'rootid'=>$rootid,'create_time'=>time()]
        );
        //最新评论入缓存
        $user_info = User::where([['status','=',1],['id','=',USERID]])->select('avatar','nickname')->first();
        $newc_key = 'newcomment'.$program_id;
        $newc_value = [
            'id'=>$insert_id,
            'content'=>$content,
            'create_time'=>time(),
            'nickname'=>$user_info['nickname'],
            'avatar'=>$user_info['avatar'],
        ];
        Redis::lpush($newc_key,serialize($newc_value));
        Redis::ltrim($newc_key,0,4);

        //$return['data'] = $newc_value;
        $return['status'] = $insert_id ? 1 : 0;
        $return['msg'] = $insert_id ? '评论成功' : '评论失败';
        extjson($return);
    }

    /**
     * 回复评论
     * @author neekli
     * @since v1.0
     */
    public function replyComment(){
        $this->checkUser();

        $content = Input::get('content') ? Input::get('content') : "";
        $post_id = Input::get('post_id');//父级评论id
        $program_id = Input::get('program_id');

        $program_info = Program::where([['status','=',1],['id','=',$program_id]])->select('id','author_id')->first();
        if(!$program_info['id']){
            extOperate(false,'要评论的节目不存在');
        }

        $comment_info = Comment::where([['status','=',1],['id','=',$post_id]])->select('id','rootid')->first();
        if(!$comment_info['id'] || !$post_id){
            extOperate(false,'要回复的评论不存在');
        }
        $rootid = $comment_info['rootid'] ? $comment_info['rootid'] : $post_id;
        $post_id = $post_id ? $post_id : 0;

        $insert_id = Comment::insertGetId(
            ['content' => $content, 'user_id' => USERID,'user_nickname'=>NICKNMAE,'program_id'=>$program_id,'author_id'=>$program_info['author_id'],'pid'=>$post_id,'rootid'=>$rootid,'create_time'=>time()]
        );

        $return['status'] = $insert_id ? 1 : 0;
        $return['msg'] = $insert_id ? '回复成功' : '回复失败';
        extjson($return);
    }

    /**
     * 评论点赞
     */
    public function commentLike(){
        $this->checkUser();
        $post_id = Input::get('post_id');

        if (Redis::sismember('user:comlike:state' . $post_id, USERID)) {
            //点赞数量返回
            $nums = Redis::hget('commentlikes','cm'.$post_id);
            $return['status'] = 1;
            $return['msg'] = "您已经点赞过了！";
            $return['nums'] = $nums ? $nums : 0;
            extjson($return);
        }
        $update_res = Comment::where([['status','=',1],['id','=',$post_id]])->increment('likes');

        $nums = Redis::hget('commentlikes','cm'.$post_id);

        //点赞次数入缓存
        if($nums){
            Redis::hset('commentlikes','cm'.$post_id,$nums+1);
        }else{
            Redis::hset('commentlikes','cm'.$post_id,1);
        }

        //对某个评论点赞
        Redis::sadd('user:comlike:state'.$post_id,USERID);
        //点赞数量返回
        $nums = Redis::hget('commentlikes','cm'.$post_id);

        $return['status'] = $update_res ? 1 : 0;
        $return['msg'] = $update_res ? '点赞成功' : '操作失败';
        $return['nums'] = $nums ? $nums : 0;
        extjson($return);
    }
}
