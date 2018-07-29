<?php

namespace App\Http\Provider\V2_4_1;

use App\Http\Models\Answer;
use App\Http\Models\CollectMulti;
use App\Http\Models\Interlocution;
use App\Http\Provider\CommonService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Models\User;
use App\Http\Models\CollectMulti as Collect;
use App\Http\Models\InterlocutionGroup as Group;

class InterlocutionService extends CommonService
{
    /**
     * 验证请求的参数
     * @param array $params 请求参数
     * @return object
     */
    public static function _validator(array $params)
    {
        $rules = [
            'group_id' => 'required',
            'anonymous' => 'required',
            'title' => 'required|max:20',
        ];

        $message = [
            'group_id.required' => '分类id必须传递',
            'anonymous.required' => '是否匿名状态必须传递',
            'title.max' => '标题不能超过20个字符',
            'title.required' => '标题不能为空',

        ];
        $validator = Validator::make($params, $rules, $message);
        return $validator;
    }

    /**
     * 获取分类
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _getGroup()
    {
        $res = Group::where('status', 1)
            ->where('shelves', 1)
            ->select('id', 'name')
            ->orderBy("sort")
            ->get();

        $result['status'] = $res ? 1 : 0;
        $result['data'] = $res ?: [];

        return $result;
    }

    /**
     * 提问列表
     *
     * @param int $group_id
     * @param array $page
     * @author zhuoshan
     * @return array
     */
    public function _index($group_id, $page)
    {
        try {
            $this->virifyGroup($group_id);
        } catch (\Exception $e) {
            return [
                'status' => 1,//没有数据也返回1，只是根据data判断是否成功
                'msg' => $e->getMessage(),
            ];
        }
        $lists = Interlocution::where('interlocution.status', 1)//未删除
            ->where('interlocution.violate', 1)//未违规
            ->where('interlocution.group_id', $group_id)
            ->leftJoin('user', 'interlocution.user_id', 'user.id')
            ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
            ->select(
                'user.avatar',
                'user.id as user_id',
                'user.nickname',
                'user.type as user_type',
                'user_profile.rankName',
                'user_profile.pic as profile_pic',
                'interlocution.id as interlocut_id',
                'interlocution.title',
                'interlocution.content',
                'interlocution.views',
                'interlocution.anonymous'
            )->orderByDesc('interlocution.create_time')
            ->offset($page['start'])
            ->limit($page['pagesize'])
            ->get()
            ->toArray();

        if ($lists) {
            foreach ($lists as $key => &$list) {
                $list['views'] = $this->testMillion($list['views']);
                $answer = Answer::where('interlocut_id', $list['interlocut_id'])
                    ->where('rootid', 0)
                    ->where('status', 1)
                    ->count();//现在先查询保持前期数据完整，以后可以直接使用answer_nums
                $list['answer_nums'] = $this->testMillion($answer);
                $list['title'] = $this->subTitle($list['title']);
                if (2 === $list['anonymous']) {
                    unset($lists[$key]['avatar']);
                    unset($lists[$key]['nickname']);
                    unset($lists[$key]['user_type']);
                    unset($lists[$key]['rankName']);
                    unset($lists[$key]['profile_pic']);
                    unset($lists[$key]['user_id']);
                }
            }
        }
        return [
            'status' => 1,
            'data' => $lists
        ];
    }

    /**
     * 添加提问
     * @author zhuoshan
     * @param array $input 请求的参数
     * @access public
     * @return array
     */
    public function _save(array $input)
    {
        try {
            $this->virifyGroup($input['group_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
        }

        $validator = self::_validator($input);
        if ($validator->passes()) {
            //验证通过
            $data['user_id'] = USERID;
            $data['group_id'] = $input['group_id'];
            $data['anonymous'] = $input['anonymous'];
            $data['title'] = $input['title'];
            $data['create_time'] = $data['update_time'] = time();
            $data['content'] = $input['content'];//对文本进行转义
            $data['pic'] = isset($input['pic']) ? $input['pic'] : '';
            $res = Interlocution::create($data);
            return [
                'status' => $res->id ? 1 : 0,
                'msg' => $res->id ? '提问成功' : '提问失败'
            ];
        } else {
            //验证不通过
            $error = $validator->errors()->toArray();
            $output = '';
            array_walk_recursive($error, function ($value) use (&$output) {
                $output = $value;
            });
            return [
                'status' => 0,
                'msg' => $output
            ];
        }
    }

    /**
     * 提问详情
     * @param array $input 获取提问详情传递的参数
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _detail(array $input)
    {
        $group = $this->getGroIdByInter($input['interlocut_id']);//通过提问id获取分类id
        try {
            $this->virifyGroup($group);
            $this->verifyQuest($input['interlocut_id']);
        } catch (\Exception $e) {
            //四种情况：分类下架、分类删除、提问删除、提问违规外层的status都为1，内层的status都为0
            return [
                'status' => 1,
                'msg' => $e->getMessage(),
                'data' => ['status' => 0]
            ];
        }

        $this->increViews($input['interlocut_id']);//递增阅读数

        $list = Interlocution::where('interlocution.id', $input['interlocut_id'])
            ->leftJoin('user', 'interlocution.user_id', 'user.id')
            ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
            ->select(
                'user.avatar',
                'user.nickname',
                'user.type as user_type',
                'user_profile.rankName',
                'user_profile.pic as profile_pic',
                'interlocution.title',
                'interlocution.user_id',
                'interlocution.content',
                'interlocution.pic',
                'interlocution.id as interlocut_id',
                'interlocution.create_time',
                'interlocution.views',
                'interlocution.anonymous'
            )->first()
            ->toArray();

        //分享的数据
        $list['share'] = [
            'pic' => config('yunshui.http_url') . '/img/share.png',
            'name' => $this->subTitle($list['title']),
            'summary'=>cutstr_html(html_entity_decode($list['content']), 20),
            'url'=>config('yunshui.http_url') . '/share?share_id=' . $input['interlocut_id'] . '&type=5',
        ];

        //操作数据
        $list['pic'] = $list['pic'] ? json_decode($list['pic'], true) : [];//图片字段变为array
        $list['views'] = $this->testMillion($list['views']);//格式化阅读数
        $list['title'] = $this->subTitle($list['title']);
        $answer = Answer::where('interlocut_id', $input['interlocut_id'])
                        ->where('rootid', 0)
                        ->where('status', 1)
                        ->count();//现在先查询保持前期数据完整，以后可以直接使用answer_nums
        $list['answer_nums'] = $this->testMillion($answer);//格式化回答数
        $list['canDel'] = isset($list['user_id']) ? $this->is_own($list['user_id']) : 0;//判断作者是否为自己
        $list['state'] = $this->isCollectExist($input['interlocut_id']);//判断是否收藏 0-未收藏 1-已收藏
        if (2 === $list['anonymous']) {
            unset($list['avatar']);
            unset($list['nickname']);
            unset($list['user_type']);
            unset($list['rankName']);
            unset($list['profile_pic']);
            unset($list['user_id']);
        }
        $return = [
            //正常情况下，内层外层的status都为1
            'status' => 1,
            'msg' => '',
            'data' => $list
        ];
        $return['data']['status'] = 1;
        return $return;
    }

    /**
     * 删除提问
     * @param array $input
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _delete($input)
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

        Interlocution::where('id', $input['interlocut_id'])->update(['status' => 0]);//更新删除状态
        if (null === $this->getQuest($input['interlocut_id'])) {
            return [
                'status' => 1,
                'msg' => '提问删除成功'
            ];
        } else {
            return [
                'status' => 0,
                'msg' => '提问删除失败'
            ];
        }
    }

    /**
     * 收藏问题
     * @param array $input 请求参数
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _collect($input)
    {
        $group = $this->getGroIdByInter($input['interlocut_id']);//通过提问id获得对应的分类id
        $title = $this->getTitleByInter($input['interlocut_id']);//根据提问id获取对应标题
        try {
            $this->virifyGroup($group);
            $this->verifyQuest($input['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        //判断要收藏的提问是否已存在
        $collect = $this->isCollectExist($input['interlocut_id']);
        if (1 === $collect) {
            return [
                'status' => 0,
                'msg' => '提问已收藏过，无须再次收藏'
            ];
        }
        DB::beginTransaction();
        $res = false;
        try {
            $insert = [
                'type' => 5,
                'multi_id' => $input['interlocut_id'],
                'user_id' => USERID,
                'title' => $title,
                'create_time' => time()
            ];
            Collect::create($insert);
            Interlocution::where('id', $input['interlocut_id'])->increment('collect_nums');
            DB::commit();
            $res = true;
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '收藏提问成功' : '收藏提问失败'
        ];
    }

    /**
     * 取消收藏
     * @param array $input
     * @return array
     */
    public function _ucollect($input)
    {
        $group = $this->getGroIdByInter($input['interlocut_id']);//通过提问id获得对应的分类id
        try {
            $this->virifyGroup($group);
            $this->verifyQuest($input['interlocut_id']);
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        $collect = $this->isCollectExist($input['interlocut_id']);
        if (0 === $collect) {
            return [
                'status' => 0,
                'msg' => '提问已取消收藏，无须再次取消'
            ];
        }
        DB::beginTransaction();
        $res = false;

        try {
            CollectMulti::where('type', 5)
                               ->where('user_id', USERID)
                               ->where('multi_id', $input['interlocut_id'])
                               ->where('status', 1)
                               ->update(['status' => 0]);
            Interlocution::where('id', $input['interlocut_id'])->decrement('collect_nums');
            DB::commit();
            $res = true;
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return [
            'status' => $res ? 1 : 0,
            'msg' => $res ? '取消收藏成功' : '取消收藏失败'
        ];

    }

    /**
     * 递增阅读数
     * @param int $interId 提问id
     */
    private function increViews($interId)
    {
        Interlocution::where('id', $interId)->increment('views');
    }

    /**
     * 判断收藏是否存在
     * @param int $interlocut_id
     * @return int
     */
    private function isCollectExist($interlocut_id)
    {
        if (defined('USERID')) {
            $collect = CollectMulti::where('type', 5)
                                   ->where('user_id', USERID)
                                   ->where('multi_id', $interlocut_id)
                                   ->where('status', 1)
                                   ->first();
        } else {
            return 0;
        }

        return is_null($collect) ? 0 : 1;
    }

    /**
     * 获取分享信息
     */
    private function getShareInfo()
    {

    }

    /**
     * 返回一条提问
     * @param int $insertId
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    private function getQuest($insertId)
    {
        return Interlocution::where('id', $insertId)->where('status', 1)->first();
    }
}
