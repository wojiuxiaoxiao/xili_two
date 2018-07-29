<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/14
 * Time: 11:06
 */

namespace App\Http\Provider\V2_4;

use App\Http\Models\Answer;
use App\Http\Models\Client;
use App\Http\Models\Interlocution;
use App\Http\Models\User;
use App\Http\Provider\CommonService;
use App\Providers\GetuiServiceProvider;
use Illuminate\Support\Facades\DB;

class AnswerService extends CommonService
{
    /**
     * 回答列表
     * @param array $input
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _index($input)
    {
        $group = $this->getGroIdByInter($input['interlocut_id']);//通过提问id获取分类id
        try {
            $this->virifyGroup($group);
            $this->verifyQuest($input['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => 0,//没有正确返回值也返回1，根据data判断
                'msg' => $e->getMessage()
            ];
        }
        $list['choice'] = $this->getAnswerList($input, 2);//获取精选回答
        $list['normal'] = $this->getAnswerList($input, 1);//获取普通回答
        $list['choice_count'] = $this->answerCount($input['interlocut_id'], 2);//获取精选数
        $list['normal_count'] = $this->answerCount($input['interlocut_id'], 1);//获取普通回答数
        $list['answer_all'] = $this->answerCount($input['interlocut_id']);//获取所有的回答数
        return [
            'status' => 1,
            'data' => $list
        ];
    }

    /**
     * 回答详情
     * @param array $input
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _detail($input)
    {
        $attr = $this->getInterAndGroupIdByAns($input['answer_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($attr['group_id']);
            $this->verifyQuest($attr['interlocut_id']);
            $this->verifyAnswerStatus($input['answer_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        $where = [
            'comment_interlocution.id' => $input['answer_id'],
            'comment_interlocution.rootid' => 0,
            'comment_interlocution.pid' => 0
        ];
        //获取回答详情
        $list['answer'] = Answer::where($where)
            ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
            ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
            ->rightJoin('interlocution', 'comment_interlocution.interlocut_id', 'interlocution.id')
            ->select(
                'user.avatar',
                'user.nickname',
                'user.type as user_type',
                'user_profile.rankName',
                'user_profile.pic as profile_pic',
                'interlocution.title',
                'interlocution.answer_nums',
                'interlocution.id as multi_id',
                'comment_interlocution.user_id',
                'comment_interlocution.id',
                'comment_interlocution.content',
                'comment_interlocution.create_time',
                'comment_interlocution.likes'
            )->first()
            ->toArray();

        $list['answer']['comments'] = $this->replyCount($input['answer_id'], $input['answer_id']);//获取回答的一级评论数
        $list['answer']['likes'] = $this->testMillion($list['answer']['likes']);//格式化点赞数
        $list['answer']['title'] = $this->subTitle($list['answer']['title']);
        $list['answer']['canDel'] = $this->is_own($list['answer']['user_id']);//回答是否可删除
        $list['answer']['type'] = 5;


        $list['answer_child'] = Answer::where('comment_interlocution.status', 1)
            ->where('comment_interlocution.rootid', $input['answer_id'])
            ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
            ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
            ->select(
                'user.avatar',
                'user.nickname',
                'user.type as user_type',
                'user_profile.rankName',
                'user_profile.pic as profile_pic',
                'comment_interlocution.interlocut_id as multi_id',
                'comment_interlocution.content',
                'comment_interlocution.likes',
                'comment_interlocution.create_time',
                'comment_interlocution.id',
                'comment_interlocution.user_id',
                'comment_interlocution.pid'
            )->orderByDesc('comment_interlocution.create_time')
            ->offset($input['start'])
            ->limit($input['pagesize'])
            ->get()
            ->toArray();
        if ($list['answer_child']) {
            foreach ($list['answer_child'] as $key => &$item) {
                $item['likes'] = $this->testMillion($item['likes']);
                $item['pname'] = $this->getPname($item['pid'], $input['answer_id']);
                $item['canDel'] = $this->is_own($item['user_id']);//评论是否可删除
                $item['type'] = 5;
            }
            unset($item);
        }
        return [
            'status' => 1,
            'data' => $list
        ];
    }

    /**
     * 添加问题的回答
     * @param array $input 问题回答传参
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _save($input)
    {
        $group = $this->getGroIdByInter($input['interlocut_id']);//通过提问id获取分类id
        try {
            $this->virifyGroup($group);
            $this->verifyQuest($input['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $input['create_time'] = time();
        DB::beginTransaction();
        $res = false;
        try {
            $data['user_id'] = USERID;
            $data['interlocut_id'] = $input['interlocut_id'];
            $data['group_id'] = $group;
            $data['content'] = $input['content'];//对文本进行转义
            $data['create_time'] = time();
            Answer::create($data);//插入数据
            Interlocution::where('id', $input['interlocut_id'])->increment('answer_nums');//插入一级回答，则在提问表中一级回答数加1
            DB::commit();
            $res = true;

            //获取提问者的clientId
            $inter = Interlocution::find($input['interlocut_id'], ['user_id', 'title']);
            $clentid = Client::where('user_id', $inter->user_id)->value('clientId');

            //添加回答推送给提问者
            $answer_type = 2;
            if(isset($clentid)){
                $data['title'] = '十方云水';
                $data['body'] = '你的提问<' . $inter->title . '>有高人回答啦！看TA怎么解决你的问题>>';
                $data['clientId'] = $clentid;
                $content = array(
                    'action'=>'message',
                    'multi_id' => $input['interlocut_id'],//提问id
                    'type' => 5,
                    'answer_type' => $answer_type,
                    'title'=>"十方云水",
                    'content' => $data['body'],
                );
                $data['content'] = json_encode($content);
                GetuiServiceProvider::singlePush($data);
            }

        } catch (\Exception $e) {
            DB::rollBack();
        }
        $result['status'] = $res ? 1 : 0;
        $result['msg'] = $res ? '添加回答成功' : '添加回答失败';

        return $result;
    }

    /**
     * 对回答或是评论进行点赞
     * @param array $input 点赞回答传递的参数
     * @return array
     */
    public function _like($input)
    {
        $attr = $this->getInterAndGroupIdByAns($input['answer_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($attr['group_id']);
            $this->verifyQuest($attr['interlocut_id']);
            $this->verifyAnswerStatus($input['answer_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $res = Answer::where('id', $input['answer_id'])->increment('likes');
        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '点赞成功' : '点赞失败'
        ];
    }

    /**
     * 对回答进行回复
     * @param array $input
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _saveComment($input)
    {
        $attr = $this->getInterAndGroupIdByAns($input['answer_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($attr['group_id']);
            $this->verifyQuest($attr['interlocut_id']);
            $this->verifyAnswerStatus($input['answer_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        $data['rootid'] = $input['answer_id'];
        $data['user_id'] = USERID;
        $data['pid'] = $input['pid'];
        $data['content'] = $input['content'];//对文本进行转义
        $data['interlocut_id'] = $attr['interlocut_id'];
        $data['group_id'] = $attr['group_id'];
        $data['create_time'] = time();
        $res = Answer::create($data);

        $result['status'] = $res ? 1 : 0;
        $result['msg'] = $res ? '添加回复成功' : '添加回复失败';
        return $result;
    }

    /**
     * 删除回答或者回复
     * @param array $input
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _delete($input)
    {
        $attr = $this->getInterAndGroupIdByAns($input['answer_id']);//通过回答id获得对应的分类id和提问id
        try {
            $this->virifyGroup($attr['group_id']);
            $this->verifyQuest($attr['interlocut_id']);
            $this->verifyAnswerStatus($input['answer_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $res = false;
        DB::beginTransaction();
        try {
            Answer::where('id', $input['answer_id'])->update(['status' => 0]);//更新回复的状态
            $root = Answer::where('id', $input['answer_id'])->value('rootid');
            if (0 === $root) {
                Interlocution::where('status', 1)
                             ->where('id', $attr['interlocut_id'])
                             ->decrement('answer_nums');
            }
            DB::commit();
            $res = true;
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return [
            'status' => 0 === $res ? 1 : 0,
            'msg' => 0 === $res ? '回复已被删除成功' : '回复删除失败'
        ];
    }

    /**
     * 获取回答数，分精选、非精选和全部回答
     * @param int $interlocut_id 提问id
     * @param int|null $choice 是否精选：null-全部 1-普通 2-精选
     * @return int
     */
    private function answerCount($interlocut_id, $choice = null)
    {
        $where = [
            'interlocut_id' => $interlocut_id,
            'status' => 1,
            'rootid' => 0,
            'pid' => 0
        ];
        if (null !== $choice) $where['choice'] = $choice;
        $count = Answer::where($where)->count();
        return $count;
    }

    /**
     * 获取精选和全部回答列表
     * @param array $input
     * @param int $choice 判断是否精选 1-不是 2-是
     * @author zhuoshan
     * @access private
     * @return array
     */
    private function getAnswerList($input, $choice = 1)
    {
        //设置排序的字段
        switch ($choice) {
            case 1:
                $order = 'comment_interlocution.create_time';
                break;
            case 2:
                $order = 'comment_interlocution.choice_time';
                break;
        }

        $where = [
            'comment_interlocution.interlocut_id' => $input['interlocut_id'],
            'comment_interlocution.status' => 1,//未删除
            'comment_interlocution.choice' => $choice,
            'comment_interlocution.rootid' => 0,
            'comment_interlocution.pid' => 0
        ];

        $lists = Answer::where($where)
                       ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
                       ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
                       ->select(
                           'user.avatar',
                           'user.nickname',
                           'user.type as user_type',
                           'user_profile.rankName',
                           'user_profile.pic as profile_pic',
                           'comment_interlocution.content',
                           'comment_interlocution.likes',
                           'comment_interlocution.create_time',
                           'comment_interlocution.id',
                           'comment_interlocution.user_id'
                       )->offset($input['start'])
                       ->limit($input['pagesize'])
                       ->orderByDesc($order)
                       ->get()
                       ->toArray();

        if ($lists) {
            foreach ($lists as &$list) {
                $list['likes'] = $this->testMillion($list['likes']);//格式化点赞数
                $list['comments'] = $this->replyCount($list['id'], $list['id']);//获取回答的评论数
                $list['canDel'] = $this->is_own($list['user_id']);//判断是否可删除
                $list['multi_id'] = $input['interlocut_id'];
                $list['type'] = 5;
//                unset($lists[$key]['user_id']);
            }
            unset($list);
        }

        return $lists;
    }

    /**
     * 获取回答详情的pname
     * @param int $pid 父评论id
     * @param int $rootid
     * @return string
     */
    private function getPname($pid, $rootid)
    {
        if ($pid != $rootid) {
            $puid = Answer::where('id', $pid)->where('status', 1)->value('user_id');
            if ($puid) {
                return User::where('id', $puid)->value('nickname');
            }
        }
        return '';
    }

    /**
     * 递归判断父评论是否已删除
     *
     * @param array $comment 要判断的评论
     */
    private function filterComByRec($comment)
    {

    }
}