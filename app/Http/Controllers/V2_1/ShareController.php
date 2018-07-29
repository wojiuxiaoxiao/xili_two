<?php
/**
 * 分享控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_1;

use App\Http\Models\Answer;
use App\Http\Models\Interlocution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Http\Models\Program;
use App\Http\Models\Active;
use App\Http\Models\Article;
use App\Http\Models\Comment;
use App\Http\Models\CommentArticle;
use App\Http\Models\CommentActive;
use Carbon\Carbon;

class ShareController extends Controller
{
    /**
     * 分享页面
     * 两个参数 1-share_id分享对象的id  2-type
     * 根据传入的type判断分享页面 1-活动 2-文章 3-节目
     *
     * @author neekli
     * @since v1.0
     */
    public function share()
    {
        $param = Input::all();
        if (1 == $param['type']) {
            $table = 'active';
        } else if (2 == $param['type']) {
            $table = 'article';
        } else if (3 == $param['type']) {
            $table = 'program';
            $data['hot'] = $this->retHotByPostData($param['share_id']);//返回热门节目
            $data['hot']['count'] = count($data['hot']);
        } else if (5 == $param['type']) {
            $table = 'interlocution';
        }else {
            $return['status'] = 0;
            $return['msg'] = '传入的type不正确';
            return $return;
        }
        $data['body'] = $this->retBodyByPostData($param);//返回body数据
//        return $data;
        if (!empty($data['body'])) {
            $data['comment'] = $this->retCommentByPostData($param);//返回评论数据，没有则默认为空数组[]
//            return $data;
            return view('home.share.' . $table, [
                'body' => $data['body'],
                'comment' => isset($data['comment']) ? $data['comment'] : [],
                'hot' => isset($data['hot']) ? $data['hot'] : []
            ]);
        } else {
            //如果没有数据访问无数据模板
            return view('home.share.' . $table, ['body' => []]);
        }

    }

    /**
     * 根据get数据返回主页面
     *
     * @param array $param get数据
     * @return array|boolean
     */
    private function retBodyByPostData($param)
    {
        $where = ['status' => 1,'id' => $param['share_id']];
        if (1 == $param['type']) {
            $data = Active::query()
                          ->where($where)
                          ->select('title', 'likes', 'views', 'contentY', 'update_time')
                          ->first();
            if ($data) {
                $data = $data->toArray();
                $data['contentY'] = $data['contentY'] ? html_entity_decode($data['contentY']) : '';
                $data['likes'] = $this->testMillion($data['likes']);
                $data['views'] = $this->testMillion($data['views']);
                $data['comment_count'] = CommentActive::query()->where(['status' => 1, 'pid' => 0, 'active_id' => $param['share_id']])->count();
            }

        } else if (2 == $param['type']) {
            $data = Article::query()
                           ->where($where)
                           ->select( 'title', 'author', 'likes', 'views', 'contentY', 'update_time')
                           ->first();
            if ($data) {
                $data = $data->toArray();
                $data['contentY'] = $data['contentY'] ? html_entity_decode($data['contentY']) : '';
                $data['likes'] = $this->testMillion($data['likes']);
                $data['views'] = $this->testMillion($data['views']);
                $data['comment_count'] = CommentArticle::query()->where(['status' => 1, 'pid' => 0, 'article_id' => $param['share_id']])->count();
            }

        } else if (3 == $param['type']) {
            $data = Program::query()
                           ->where($where)
                           ->select('id', 'name','radio_url','radio_pic','burning_time', 'type')
                           ->first();
            if ($data) {
                $data = $data->toArray();//如果查询没有数据，使用toArray()会报错
                $data['burning_time'] = $this->mediaTimeTrans($data['burning_time']);
                $data['radio_pic'] = $data['radio_pic'] ?: config('yunshui.http_url') . '/img/fengmian_default.jpg';
                $data['comment_count'] = Comment::query()->where(['status' => 1, 'pid' => 0, 'program_id' => $param['share_id']])->count();
            }

        } else if (5 == $param['type']) {
            $where = [
                'interlocution.status' => 1,
                'interlocution.id' => $param['share_id']
            ];
            $data = Interlocution::query()
                                 ->where($where)
                                 ->leftJoin('user', 'interlocution.user_id', 'user.id')
                                 ->select('interlocution.id', 'interlocution.title', 'interlocution.content', 'user.id as user_id', 'user.avatar', 'user.nickname')
                                 ->first();

            if ($data) {
                $data->toArray();
            }
        }else {
            return false;
        }

        if (false == $data) $data = [];

        return $data;
    }

    /**
     * 根据个推参数返回评论数据
     *
     * @param array $param get传入的参数
     * @return
     */
    private function retCommentByPostData($param)
    {
        if (1 == $param['type']) {
            //按添加时间降序排序
            $data = CommentActive::query()
                                 ->where('comment_active.status', 1)
                                 ->where('comment_active.active_id', $param['share_id'])
                                 ->where('pid', 0)
                                 ->leftJoin('user', 'comment_active.user_id', '=', 'user.id')
                                 ->select('user.avatar', 'comment_active.nickname', 'comment_active.content', 'comment_active.create_time', 'comment_active.likes', 'comment_active.id')
                                 ->orderByDesc('comment_active.create_time')
                                 ->limit(3)
                                 ->get();
            if ($data) {
                $data = $data->toArray();
                foreach ($data as &$array) {
                    $array['avatar'] = $array['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
                    $array['likes'] = $this->testMillion($array['likes']);
                    $array['time'] = $this->dateTransform($array['create_time']);
                    $array['create_time'] = date('Y-m-d H:i:s', $array['create_time']);
                    $array['reply'] = CommentActive::query()->where(['status' => 1, 'pid' => $array['id']])->count();
                }
                unset($array);
            }

        } else if (2 == $param['type']) {
            //按添加时间降序排序
            $data = CommentArticle::query()
                                  ->where('comment_article.status', 1)
                                  ->where('comment_article.article_id', $param['share_id'])
                                  ->where('pid', 0)
                                  ->leftJoin('user', 'comment_article.user_id', '=', 'user.id')
                                  ->select('user.avatar', 'comment_article.nickname', 'comment_article.content', 'comment_article.create_time', 'comment_article.likes', 'comment_article.id')
                                  ->orderByDesc('comment_article.create_time')
                                  ->limit(3)
                                  ->get();
            if ($data) {
                $data = $data->toArray();
                foreach ($data as &$array) {
                    $array['avatar'] = $array['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
                    $array['likes'] = $this->testMillion($array['likes']);
                    $array['time'] = $this->dateTransform($array['create_time']);
                    $array['create_time'] = date('Y-m-d H:i:s', $array['create_time']);
                    $array['reply'] = CommentArticle::query()->where(['status' => 1, 'pid' => $array['id']])->count();
                }
                unset($array);
            }

        } else if (3 == $param['type']) {
            //按点赞数降序排序
            $data = Comment::query()
                           ->where('comment.status', 1)
                           ->where('comment.program_id', $param['share_id'])
                           ->where('pid', 0)
                           ->leftJoin('user', 'comment.user_id', '=', 'user.id')
                           ->select('user.avatar', 'comment.user_nickname as nickname', 'comment.content', 'comment.create_time', 'comment.likes', 'comment.id')
                           ->orderByDesc('comment.create_time')
                           ->limit(3)
                           ->get();
            if ($data) {
                $data = $data->toArray();
                foreach ($data as &$array) {
                    $array['avatar'] = $array['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
                    $array['likes'] = $this->testMillion($array['likes']);
                    $array['time'] = $this->dateTransform($array['create_time']);
                    $array['create_time'] = date('Y-m-d H:i:s', $array['create_time']);
                    $array['reply'] = Comment::query()->where(['status' => 1, 'pid' => $array['id']])->count();
                }
                unset($array);
            }

        } else if (5 == $param['type']) {
            $where = [
                'comment_interlocution.interlocut.id' => $param['share_id'],
                'comment_interlocution.choice' => 2,
                'comment_interlocution.status' => 1
            ];
            //获取精选
            $data = Answer::where($where)
                            ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
                            ->select('user.avatar', 'user.nickname', 'comment_interlocution.id', 'comment_interlocution.content', 'comment_interlocution.likes', 'comment_interlocution.create_time')
                            ->orderByDesc('comment_interlocution.choice_time')
                            ->limit(3)
                            ->get()
                            ->toArray();

            if (!$data) {
                //没有精选，获取全部回答
                $where['comment_interlocution.choice'] = 1;
                $data = Answer::where($where)
                              ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
                              ->select('user.avatar', 'user.nickname', 'comment_interlocution.id', 'comment_interlocution.content', 'comment_interlocution.likes', 'comment_interlocution.create_time')
                              ->orderByDesc('comment_interlocution.create_time')
                              ->limit(3)
                              ->get()
                              ->toArray();

            }

            foreach ($data as &$array) {
                $array['reply'] = $this->getComments($array['id']);
                $array['likes'] = $this->testMillion($array['likes']);
                $array['time'] = $this->dateTransform($array['create_time']);
            }
            $data['count'] = count($data);

        }else {
            return false;
        }

        if (false == $data) $data = [];

        return $data;
    }

    /**
     * 获取分享的本节目大师底下热门节目
     * 如果该大师只有创建过本节目，那么对应的热门节目即该分享节目本身
     * 根据播放量排序
     *
     * @return array
     */
    private function retHotByPostData($id)
    {
        $author = Program::query()->where('id', $id)->value('author_id');
        $data = Program::query()
                       ->where('status', 1)
                       ->where('author_id', $author)
                       ->where('type',0)
                       ->orderByDesc('play_nums')
                       ->select('id', 'name', 'column_name', 'burning_time', 'radio_pic')
                       ->limit(3)
                       ->get()
                       ->toArray();

        return $data;
    }

    /**
     * 时间转换
     * 距现在：
     * > 60秒 ：1分钟前
     * > 60分钟 ：1~24小时前
     * > 24小时 ：1~31天前
     * > 1月 ：1~12月前
     * > 1年 ：1~*年前
     *
     * @param int $time 评论时间
     * @return string  返回时间string，形式为：1分钟前
     */
    private function dateTransform($time)
    {
        Carbon::setLocale('zh');
        $dt = Carbon::createFromTimestamp($time);
        return $dt->diffForHumans();
    }

    /**
     * 播放时长
     * 将 hour : min : sec 格式转换为 min : sec 格式
     * @param string $time 播放时长，类似 01:10:20
     * @return string 格式为 min : sec
     */
    private function mediaTimeTrans($time)
    {
        $times = explode(':', $time);//将time转换为数组
        $times[1] = $times[0] == 0 ? $times[1] : (int) $times[1] + (int) ($times[0] * 60);

        return (string) ($times[1] . ':' . $times[2]);
    }

    /**
     * 获取回答所有评论
     *
     * @param int $answer_id
     * @return int
     */
    private function getComments($answer_id)
    {
        $count = Answer::where('rootid', $answer_id)->where('status', 1)->count();

        return $count;
    }

    /**
     * 分享页面
     * @author neekli
     * @since v1.0
     */
    public function shareRadio(){
        $share_id = Input::get('share_id');
        $where = array(
            ['id', '=', $share_id],
//            ['status', '=', 1],
//            ['radio_origin_url','<>',''],
        );
        $program = Program::where($where)
                          ->select('name','radio_url','radio_pic','burning_time')
                          ->first();
        if(!$program['radio_pic']){
            $program['radio_pic'] = config('yunshui.http_url').'img/fengmian_default.jpg';
        }

        //判断是安卓还是ios，然后跳转到对应的appstore
        $program['down_url'] = '';
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $program['down_url'] = 'https://itunes.apple.com/us/app/%E5%8D%81%E6%96%B9%E4%BA%91%E6%B0%B4/id1332982959?l=zh&ls=1&mt=8';
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $program['down_url'] = config('yunshui.http_admin_url').'download/android.apk';
        }

        return view('home.share.index',['program' => $program]);
    }
}
