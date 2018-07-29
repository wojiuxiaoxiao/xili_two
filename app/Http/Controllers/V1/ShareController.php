<?php
/**
 * 分享控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V1;

use App\Http\Models\Answer;
use App\Http\Models\CommentInterlocution;
use App\Http\Models\CommentInterlocutionBounty;
use App\Http\Models\Focus;
use App\Http\Models\Interlocution;
use App\Http\Models\InterlocutionBounty;
use App\Http\Models\User;
use App\Http\Provider\GrowthRuleService;
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
use App\Http\Provider\V1\ShareProvider;
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
        } else if (6 == $param['type']) {
            $table = 'bounty';
        }else if (7 == $param['type']) {
            $table = 'master';
        } else {
            $return['status'] = 0;
            $return['msg'] = '传入的type不正确';
            return $return;
        }

        if (6 == $param['type']) {
            //悬赏问答
            $data['body']['bounty'] = $this->retBounty($param['share_id']);
            $data['body']['answer'] = $this->retBountyAnswer($param['share_id']);

        } else if (7 == $param['type']) {
            //此type代表大师回答分享，和前面的分享类型不同，所以区分开来
            $user_id = isset($param['user_id']) ? $param['user_id'] : null;//如果没传递user_id，则user_id为null
            $data['body'] = $this->getMasterPage($param['share_id'], $user_id);

        } else {
            $data['body'] = $this->retBodyByPostData($param);//返回body数据
            if (!empty($data['body'])) {
                $data['comment'] = $this->retCommentByPostData($param);//返回评论数据，没有则默认为空数组[]
//            return $data;

            }
        }

        return view('home.share.' . $table, [
            'body' => $data['body'],
            'comment' => isset($data['comment']) ? $data['comment'] : [],
            'hot' => isset($data['hot']) ? $data['hot'] : []
        ]);

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
            //活动
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
            //文章
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
            //节目
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
            //问答
            //判断分享的问答是否违规、删除或者分类是否已下架、已删除
            $share = new ShareProvider();
            try {
                $group = $share->getGroIdByInter($param['share_id']);
                $share->virifyGroup($group);
                $share->verifyQuest($param['share_id']);
            } catch (\Exception $e) {
                return [
                    'status' => $e->getCode(),
                    'msg' => $e->getMessage()
                ];
            }

            $where = [
                'interlocution.status' => 1,
                'interlocution.id' => $param['share_id']
            ];
            $data = Interlocution::query()
                                 ->where($where)
                                 ->leftJoin('user', 'interlocution.user_id', 'user.id')
                                 ->select('interlocution.id', 'interlocution.title', 'interlocution.content', 'interlocution.pic', 'interlocution.create_time', 'interlocution.views', 'user.id as user_id', 'user.avatar', 'user.nickname')
                                 ->first();

            if ($data) {
                $data = $data->toArray();
                //匿名用户使用默认图片和默认名称
                if (1 === $this->isAnonymous($param['share_id'])) {
                    $data['avatar'] = null;
                    $data['nickname'] = '匿名用户';
                } else if(null === $data['avatar']) {
                    $data['avatar'] = '';
                }
                $data['pic'] = !empty($data['pic']) ? json_decode($data['pic'], true) : [];
                $data['views'] = $this->testMillion($data['views']);
                $data['content'] = str_replace("\n", "<br>", $data['content']);
                $data['is_choice'] = $this->hasChoiceAnswer($data['id']);//是否有精选
                $choice = 1 === $data['is_choice'] ? 2 : 1;//精选为2，全部为1
                $data['answers'] = $this->getAnswers($data['id'], $choice);//获取回答数
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
                    $array['reply'] = CommentActive::query()->where(['status' => 1, 'rootid' => $array['id']])->count();
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
                    $array['reply'] = CommentArticle::query()->where(['status' => 1, 'rootid' => $array['id']])->count();
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
                    $array['reply'] = Comment::query()->where(['status' => 1, 'rootid' => $array['id']])->count();
                }
                unset($array);
            }

        } else if (5 == $param['type']) {
            $where = [
                'comment_interlocution.interlocut_id' => $param['share_id'],
                'comment_interlocution.rootid' => 0,
                'comment_interlocution.choice' => 2,
                'comment_interlocution.status' => 1,
            ];
            //获取精选
            $data = $this->getInterAnswers($where);//精选根据设为精选时间进行排序

            //没有精选，查询全部回答
            if (!$data) {
                $where['comment_interlocution.choice'] = 1;
                $order = 'comment_interlocution.create_time';
                $data = $this->getInterAnswers($where, $order);//全部回答根据回答添加时间排序

            }
            //有精选或者全部回答
            if ($data) {
                foreach ($data as &$array) {
                    $array['reply'] = $this->getComments($array['id']);
                    $array['likes'] = $this->testMillion($array['likes']);
                    $array['time'] = $this->dateTransform($array['create_time']);
                }
            }

        }else {
            return false;
        }

        if (false == $data) $data = [];

        return $data;
    }

    /**
     * 返回悬赏问答
     * @param int $share_id 分享的悬赏问答id
     * @return array
     */
    private function retBounty($share_id)
    {
        $share = new ShareProvider();
        try {
            $group = $share->getGroIdByBountryInter($share_id);
            $share->virifyGroup($group);
            $share->verifyBountyHasAnswer($share_id);
            $share->verifyBountry($share_id);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        $data = InterlocutionBounty::where('interlocution_bounty.id', $share_id)
                    ->leftJoin('user', 'interlocution_bounty.user_id', 'user.id')
                    ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
                    ->select(
                        'user.avatar',
                        'user.nickname',
                        'user_profile.rankName',
                        'interlocution_bounty.anonymous',
                        'interlocution_bounty.title',
                        'interlocution_bounty.comment_id',
                        'interlocution_bounty.content',
                        'interlocution_bounty.create_time',
                        'interlocution_bounty.price',
                        'interlocution_bounty.pic',
                        'interlocution_bounty.views',
                        'interlocution_bounty.answer_nums')
                    ->first()
                    ->toArray();

        //匿名用户使用默认图片和默认名称
        if (2 == $data['anonymous']) {
            $data['avatar'] = config('yunshui.http_url') . '/img/master-avatar.png';
            $data['nickname'] = '匿名用户';
        } else {
            $data['avatar'] = $data['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
        }

        $data['pic'] = !empty($data['pic']) ? json_decode($data['pic'], true) : [];
        $data['create_time'] = date('Y-m-d', $data['create_time']);
        $data['price'] = $data['price'] / 100;//金额以分为单位，整数保存
        $data['views'] = $this->testMillion($data['views']);

        return $data;

    }

    /**
     * 返回悬赏问答的回答
     * @param int $share_id 分享的悬赏问答id
     * @return array
     */
    private function retBountyAnswer($share_id)
    {
        $data = CommentInterlocutionBounty::where('comment_interlocution_bounty.interlocution_bounty_id', $share_id)
                    ->where('comment_interlocution_bounty.status', 1)
                    ->where('user.type', 2)
                    ->leftJoin('user', 'comment_interlocution_bounty.user_id', 'user.id')
                    ->select(
                        'user.avatar',
                        'user.nickname',
                        'comment_interlocution_bounty.id',
                        'comment_interlocution_bounty.content',
                        'comment_interlocution_bounty.likes',
                        'comment_interlocution_bounty.create_time')
                    ->orderByDesc('comment_interlocution_bounty.create_time')
                    ->limit(3)
                    ->get();

        if ($data) {
            $data = $data->toArray();
            foreach ($data as &$item) {
                $item['avatar'] = $item['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
                $item['create_time'] = $this->dateTransform($item['create_time']);
                $item['likes'] = $this->testMillion($item['likes']);
            }

            return $data;
        }

    }

    /**
     * 获取大师个人回答页面
     * @param int $share_id 大师用户id
     * @param int $user_id 普通用户id
     * @return array
     */
    private function getMasterPage($share_id, $user_id)
    {
        $normal = $this->getNormalComment($share_id);
        $bounty = $this->getBountyComment($share_id);
        //大师个人信息
        $data['profile'] = $this->retMasterProfile($share_id, $user_id);
        $data['profile']['answer_count'] = (int)(count($normal) + count($bounty));//获取总共的回答数

        //获取回答
        $data['answer'] = $this->retMasterAnswers($normal, $bounty);

        return $data;
    }

    /**
     * 返回大师个人信息
     * @param int $share_id 分享的大师id
     * @param int|null $user_id 分享的用户id，根据与share_id是否相同，判断页面展示是否关注还是粉丝数
     * @return array
     */
    private function retMasterProfile($share_id, $user_id)
    {
        $where = ['user.id' => $share_id, 'user.type' => 2, 'user.status' => 1];
        $data = User::where($where)
                    ->join('signed_author', 'user.id', 'signed_author.user_id')
                    ->select(
                        'user.nickname',
                        'user.avatar',
                        'user.signature',
                        'user.type',
                        'signed_author.detail')
                    ->first();
        if ($data) {
            $data = $data->toArray();
            $data['avatar'] = $data['avatar'] ?: config('yunshui.http_url') . '/img/avatar.png';
            $data['signature'] = $data['signature'] ?: '资深风水大师';
            $data['detail'] = $data['detail'] ?: '我们专注于传递中国传统文化，弘扬正统易学之道，品尝国学经典。';
            if (null !== $user_id && $share_id == $user_id) {
                //大师id和用户id相同，即大师自己分享，页面展示粉丝数
                $data['fans'] = Focus::where('master_id', $share_id)->count();
            } else {
                //页面展示是否关注
                $focus = Focus::where('user_id', $user_id)
                                           ->where('master_id', $share_id)
                                           ->first();
                $data['is_focus'] = $focus ? 1 : 0;
            }
        }
        return $data;

    }

    /**
     * 返回大师的回答（包括悬赏问答和普通问答）
     * @param array $normal 普通回答
     * @param array $bounty 悬赏回答
     * @return array
     */
    private function retMasterAnswers($normal, $bounty)
    {
        //将取得的两个数组merge，并截取前3个数组
        $all_answer = array_merge($normal, $bounty);
        $create_time = array_column($all_answer, 'create_time');
        array_multisort($create_time, SORT_DESC, $all_answer);
        $answers = array_slice($all_answer, 0, 3);
        return $answers;

    }

    /**
     * 获取普通回答
     * @param int $user_id 大师id
     * @return array
     */
    private function getNormalComment($user_id)
    {
        $normal_where = [
            'comment_interlocution.user_id' => $user_id,
            'comment_interlocution.status' => 1,
            'comment_interlocution.rootid' => 0,
            'comment_interlocution.pid' => 0,
            'interlocution.status' => 1,
            'interlocution.violate' => 1,
            'interlocution_group.shelves' => 1,
            'interlocution_group.status' => 1
        ];

        //获取普通的回答
        $normal = CommentInterlocution::where($normal_where)
                                      ->join('interlocution', 'comment_interlocution.interlocut_id', 'interlocution.id')
                                      ->join('interlocution_group', 'comment_interlocution.group_id', 'interlocution_group.id')
                                      ->select(
                                          'interlocution.title',
                                          'comment_interlocution.content',
                                          'comment_interlocution.likes',
                                          'comment_interlocution.create_time'
                                      )->orderByDesc('comment_interlocution.create_time')
                                      ->get()
                                      ->toArray();

        return $normal;
    }

    /**
     * 获取悬赏回答
     * @param int $user_id 大师id
     * @return array
     */
    private function getBountyComment($user_id)
    {
        $bounty_where = [
            'comment_interlocution_bounty.user_id' => $user_id,
            'comment_interlocution_bounty.status' => 1,
            'interlocution_bounty.violate' => 1,
            'interlocution_bounty.status' => 1,
            'interlocution_group.shelves' => 1,
            'interlocution_group.status' => 1
        ];

        //获取悬赏提问的回答
        $bounty = CommentInterlocutionBounty::where($bounty_where)
                                            ->join('interlocution_bounty', 'comment_interlocution_bounty.interlocution_bounty_id', 'interlocution_bounty.id')
                                            ->join('interlocution_group', 'comment_interlocution_bounty.group_id', 'interlocution_group.id')
                                            ->select(
                                                'interlocution_bounty.title',
                                                'interlocution_bounty.comment_id',
                                                'interlocution_bounty.price',
                                                'comment_interlocution_bounty.id',
                                                'comment_interlocution_bounty.content',
                                                'comment_interlocution_bounty.likes',
                                                'comment_interlocution_bounty.create_time'
                                            )->orderByDesc('comment_interlocution_bounty.create_time')
                                            ->get()
                                            ->toArray();

        return $bounty;
    }

    /**
     * 获取回答
     *
     * @param array $where sql查询的where语句
     * @param string $order sql的order语句
     * @return array 返回查询到的回答
     */
    private function getInterAnswers($where, $order = 'comment_interlocution.choice_time')
    {
        $data = Answer::where($where)
                      ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
                      ->select('user.avatar', 'user.nickname', 'comment_interlocution.id', 'comment_interlocution.content', 'comment_interlocution.likes', 'comment_interlocution.create_time')
                      ->orderByDesc($order)
                      ->limit(3)
                      ->get()
                      ->toArray();

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
     * @param int $time 评论时间,时间戳
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
     * 查看是否有精选回答
     *
     * @param int $interlocut_id
     * @return int 0-没有精选 1-有精选
     */
    private function hasChoiceAnswer($interlocut_id)
    {
        $choice = Answer::where('interlocut_id', $interlocut_id)
                        ->where('choice', 2)
                        ->where('status', 1)
                        ->get()
                        ->toArray();

        return false == $choice ? 0 : 1;
    }

    /**
     * 获取回答数
     *
     * @param int $interlocut_id
     * @param int $choice 1-全部回答 2精选回答
     * @return int
     */
    private function getAnswers($interlocut_id, $choice = 1)
    {
        $count = Answer::where('interlocut_id', $interlocut_id)
            ->where('status', 1)
            ->where('rootid', 0)
            ->where('choice', $choice)
            ->count();

        return $count;
    }

    /**
     * 获取回答所有评论
     *
     * @param int $answer_id
     * @return int
     */
    private function getComments($answer_id)
    {
        $count = Answer::where('rootid', $answer_id)
                       ->where('status', 1)
                       ->count();

        return $count;
    }

    /**
     * 判断提问用户是否匿名
     *
     * @param int $interlocut_id
     * @return int
     */
    private function isAnonymous($interlocut_id)
    {
        $status = Interlocution::where('id', $interlocut_id)->value('anonymous');

        return 1 === $status ? 0 : 1;//如果anonymous为1，则返回0，不为1（即为2），则返回1，此时匿名
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
