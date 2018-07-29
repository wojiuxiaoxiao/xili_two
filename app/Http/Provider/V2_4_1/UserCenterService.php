<?php

namespace App\Http\Provider\V2_4_1;

use App\Http\Models\Collect;
use App\Http\Models\User;
use App\Http\Models\Interlocution;
use App\Http\Models\Answer;
use App\Http\Models\CollectMulti;
use App\Http\Provider\CommonService;


class UserCenterService extends CommonService
{


    /**
     * 节目收藏列表
     * @author neek
     * @since v2_4
     */
    public function program_c_list($input_arr){

        $collect_list = Collect::where([['program.status','=',1],['collect.status','=',1],['collect.user_id','=',$input_arr['userid']]])
            ->select('program.id','program.name','program.radio_pic','program.radio_url','program.column_name','program.burning_time','program.type','program.status as del_status')
            ->leftJoin('program', 'program.id', '=', 'collect.program_id')
            ->orderBy('collect.create_time','desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get()->toArray();

        foreach($collect_list as $k=>$v){
            $collect_list[$k]['type'] = $input_arr['type'];
        }

        return $collect_list;
    }

    /**
     * 文章收藏列表
     * @author neek
     * @since v2_4
     */
    public function article_c_list($input_arr){

        $collect_list = CollectMulti::where([['article.status','=',1],['collect_multi.status','=',1],['collect_multi.user_id','=',$input_arr['userid']],['collect_multi.type','=',$input_arr['type']]])
            ->select('article.id','article.title','article.pic','article.views','article.author','article.pic_style','article.comment_nums')
            ->leftJoin('article', 'article.id', '=', 'collect_multi.multi_id')
            ->orderBy('collect_multi.create_time','desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get()->toArray();

        foreach($collect_list as $k=>$v){
            $collect_list[$k]['type'] = $input_arr['type'];
            $collect_list[$k]['views'] = testMillion($collect_list[$k]['views']);
        }

        return $collect_list;
    }

    /**
     * 问答收藏列表
     * @author neek
     * @since v2_4
     */
    public function answer_c_list($input_arr){
        $collect_list = CollectMulti::where([['interlocution.violate','=',1],['interlocution.status','=',1],['collect_multi.status','=',1],['collect_multi.user_id','=',$input_arr['userid']],['collect_multi.type','=',$input_arr['type']],['interlocution_group.status','=',1],['interlocution_group.shelves','=',1]])
            ->select('user.avatar','user.nickname','user.type as user_type','user.id as user_id','interlocution.id as interlocut_id','interlocution.anonymous','interlocution.title','interlocution.content','interlocution.answer_nums','interlocution.views','collect_multi.type')
            ->leftJoin('interlocution', 'interlocution.id', '=', 'collect_multi.multi_id')
            ->leftJoin('user', 'user.id', '=', 'interlocution.user_id')
            ->rightJoin('interlocution_group', 'interlocution_group.id', '=', 'interlocution.group_id')
            ->orderBy('collect_multi.create_time','desc')
            ->offset($input_arr['start'])
            ->limit($input_arr['pagesize'])
            ->get()->toArray();

        foreach($collect_list as $k=>$v){
            if (2 === $v['anonymous']) {
                unset($collect_list[$k]['avatar']);
                unset($collect_list[$k]['nickname']);
            }
            $collect_list[$k]['type'] = $input_arr['type'];
            $collect_list[$k]['views'] = testMillion($collect_list[$k]['views']);
        }

        return $collect_list;
    }

    /**
     * 个人中心提问列表
     * @param array $input 获取列表传递的参数
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _askList($input)
    {
        $where = [
            'interlocution.status' => 1,//判断提问是否已删除
            'interlocution.user_id' => $input['user_id'],
            'interlocution.violate' => 1,//判断提问是否已违规
            'interlocution_group.status' => 1,//判断提问关联的分类是否已删除
            'interlocution_group.shelves' => 1//判断提问关联的分类是否下架
        ];
        $is_own = $this->is_own($input['user_id']);
        if (0 === $is_own) $where['interlocution.anonymous'] = 1;//如果不是查看自己的提问列表，则只显示正常的，隐藏匿名提问的

        $lists = Interlocution::where($where)
            ->leftJoin('user', 'interlocution.user_id', 'user.id')
            ->leftJoin('user_profile', 'user.profile_id', 'user_profile.id')
            ->rightJoin('interlocution_group', 'interlocution.group_id', 'interlocution_group.id')
            ->select(
                'user.avatar',
                'user.nickname',
                'user.type as user_type',
                'user_profile.rankName',
                'user_profile.pic as profile_pic',
                'interlocution.user_id',
                'interlocution.id as interlocut_id',
                'interlocution.title',
                'interlocution.views',
                'interlocution.content',
                'interlocution.anonymous'
            )->offset($input['start'])
            ->limit($input['pagesize'])
            ->orderByDesc('interlocution.create_time')
            ->get()
            ->toArray();

        if (false != $lists) {
            foreach ($lists as $key => &$list) {
                $answer = Answer::where('interlocut_id', $list['interlocut_id'])
                    ->where('rootid', 0)
                    ->where('status', 1)
                    ->count();//现在先查询保持前期数据完整，以后可以直接使用answer_nums
                $list['views'] = $this->testMillion($list['views']);
                $list['content'] = $this->entityDecode($list['content']);//对输出文本进行反转义
                $list['title'] = $this->subTitle($list['title']);
                $list['answer_nums'] = $this->testMillion($answer);
                if (2 === $list['anonymous']) {
                    unset($lists[$key]['avatar']);
                    unset($lists[$key]['nickname']);
                    unset($lists[$key]['user_type']);
                    unset($lists[$key]['rankName']);
                    unset($lists[$key]['profile_pic']);
                    unset($lists[$key]['user_id']);
                }

            }
            unset($list);
//            $lists['is_own'] = $is_own;
        }
        return [
            'status' => 1,//即使没值返回空数组，状态仍为1
            'data' => $lists,
            'user' => $this->getUserInfoInUserCenter($input['user_id'])//获取头部的用户信息
        ];
    }

    /**
     * 个人中心回答列表
     * @param array $input 获取列表传递的参数
     * @author zhuoshan
     * @access public
     * @return array
     */
    public function _answerList($input)
    {
        $where = [
            'comment_interlocution.status' => 1,//回答是否删除
            'comment_interlocution.user_id' => $input['user_id'],
            'comment_interlocution.rootid' => 0,//rootid为0是回答
            'interlocution.status' => 1,//判断回答关联的提问是否已删除
            'interlocution.violate' => 1,//判断回答关联的提问是否违规
            'interlocution_group.status' => 1,//判断回答关联的提问的分类是否已删除
            'interlocution_group.shelves' => 1//判断回答关联的提问的分类是否下架
        ];
        $lists = Answer::where($where)
            ->leftJoin('user', 'comment_interlocution.user_id', 'user.id')
            ->rightJoin('interlocution', 'comment_interlocution.interlocut_id', 'interlocution.id')
            ->rightJoin('interlocution_group', 'interlocution.group_id', 'interlocution_group.id')
            ->select(
                'user.avatar',
                'user.nickname',
                'user.type as user_type',
                'comment_interlocution.id',
                'interlocution.title',
                'comment_interlocution.content',
                'comment_interlocution.likes',
                'comment_interlocution.create_time',
                'comment_interlocution.user_id',
                'interlocution.answer_nums',
                'comment_interlocution.interlocut_id',
                'comment_interlocution.type'
            )->offset($input['start'])
            ->limit($input['pagesize'])
            ->orderByDesc('comment_interlocution.create_time')
            ->get()
            ->toArray();

        if ($lists) {
            foreach ($lists as &$list) {
                $list['likes'] = $this->testMillion($list['likes']);
                $list['answer_nums'] = $this->testMillion($list['answer_nums']);
                $list['title'] = $this->subTitle($list['title']);
                $list['content'] = $this->entityDecode($list['content']);//对输出文本进行反转义
                $list['canDel'] = $this->is_own($list['user_id']);
                $list['comments'] = $this->replyCount($list['id'], $list['id']);//获取回答的一级评论数
            }
        }
//        $lists['is_own'] = $this->is_own($input['user_id']);//是否本人 1-是 0-不是

        return [
            'status' => 1,//即使没值返回空数组，状态仍为1
            'data' => $lists,
            'user' => $this->getUserInfoInUserCenter($input['user_id'])//获取头部的用户信息
        ];
    }

    /**
     * 个人中心回答和提问列表头部的数据
     * @param int $user_id
     * @return array
     */
    private function getUserInfoInUserCenter($user_id)
    {
        $user = User::where('id', $user_id)
                    ->where('status', 1)
                    ->select('avatar', 'id', 'type as user_type', 'nickname', 'signature')
                    ->first();

        return $user ? $user->toArray() : [];
    }

    /**
     * 我的成长
     */
    public function myGrowth($user_id){
        $user = User::where('id', $user_id)
            ->where('status', 1)
            ->select('avatar', 'id', 'type as user_type', 'nickname','active','create_time')
            ->first();
        if($user){
            $today_start = strtotime(date("Y-m-d",time()));
            $sign_time_start = strtotime(date("Y-m-d",$user['create_time']));
            $day_diff = ($today_start-$sign_time_start)/(3600*24);

            $user['lv'] = $this->getLv($user['active']);
            $user['days'] = '今天你加入十方云水'.$day_diff.'天啦';
        }

        return $user ? $user->toArray() : [];
    }

}
