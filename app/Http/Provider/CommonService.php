<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/15
 * Time: 14:36
 */

namespace App\Http\Provider;


use App\Http\Models\Client;
use App\Providers\GetuiServiceProvider;
use Illuminate\Support\Facades\Input;
use App\Http\Models\Answer;
use App\Http\Models\UserProfile;
use App\Http\Models\Interlocution;
use App\Http\Models\InterlocutionBounty;
use App\Http\Models\CommentInterlocutionBounty;
use App\Http\Models\InterlocutionGroup as Group;

//只判断分类的情况：发表提问~
//判断分类和提问的情况：提问详情~、回答列表~、添加回答~、收藏提问~、删除提问~
//判断分类、提问和回答的情况：回答详情~、对回答添加回复~、对回答或是评论进行删除~、对回答点赞~
class CommonService
{

    /**
     * 通过提问id获取分类id
     *
     * @param int $interlcut_id 提问id
     * @author zhuoshan
     * @access public
     * @return int
     */
    public function getGroIdByInter($interlcut_id)
    {
        return Interlocution::where('id', $interlcut_id)->value('group_id');
    }

    /**
     * 通过提问id获取提问标题
     *
     * @param int $interlocut_id
     * @access public
     * @return string
     */
    public function getTitleByInter($interlocut_id)
    {
        return Interlocution::where('id', $interlocut_id)->value('title');
    }

    /**
     * 根据回答id获取提问id和分类id
     * @param int $answer_id 回答id
     * @return array
     */
    public function getInterAndGroupIdByAns($answer_id)
    {
        return Answer::where('id', $answer_id)->select('interlocut_id', 'group_id')->first()->toArray();
    }

    /**
     * 判断分类是否下架或者已删除
     * @param int $group_id 分组id
     * @author zhuoshan
     * @access protected
     * @throws \Exception
     */
    public function virifyGroup($group_id)
    {
        $verify = Group::where('id', $group_id)
            ->where('status', 1)
            ->first();
        if (null === $verify) {
            throw new \Exception('此分类已经删除', 0);
        } else if (2 === $verify->shelves) {
            throw new \Exception('此分类已经下架', 0);
        }
    }

    /**
     * 判断提问是否违规或者已删除
     * @param int $interlocut_id 提问id
     * @author zhuoshan
     * @access protected
     * @throws \Exception
     */
    public function verifyQuest($interlocut_id)
    {
        $verify = Interlocution::where('id', $interlocut_id)
                               ->where('status', 1)
                               ->select('violate')
                               ->first();

        if (null === $verify) {
            throw new \Exception('提问已被删除', 0);
        } else if (2 === $verify->violate) {
            throw new \Exception('提问违规', 0);
        }
    }


    /**
     * 判断回答是否已被删除
     * @param int $answer_id 回答或者评论id
     * @author zhuoshan
     * @access public
     * @throws \Exception
     */
    public function verifyAnswerStatus($answer_id)
    {
        $verify = Answer::where('id', $answer_id)
            ->where('status', 1)
            ->where('rootid', 0)
            ->first();

//        有rootid且rootid不为0，说明不是回答
        if (isset($verify->rootid) && 0 !== $verify->rootid) {
            $ans = Answer::where('id', $verify->rootid)
                           ->where('status', 1)
                           ->first();

            if (null === $ans) {
                throw new \Exception('回答已被删除', 0);
            }
        }

        if (null === $verify) {
            throw new \Exception('回答已被删除', 0);
        }
    }

    /**
     * 判断数组是否达到了万
     * @param int $data
     * @return string|int
     */
    protected function testMillion($data){

        $changeM = $data/10000;
        $res = $changeM >= 1 ? substr(sprintf('%.2f', $changeM), 0, -1) . '万' : $data;
        return $res;
    }

    /**
     * 判断是否本人
     * @param int $user_id
     * @return int 1-是本人  0-不是本人
     */
    protected function is_own($user_id)
    {
        return defined('USERID') ? (USERID == $user_id ? 1 : 0) : 0;
    }

    /**
     * 获取回答的评论数
     * 显示所有评论，包括评论的子评论
     * rootid永远是回答id
     *
     * @param int $pid 父评论id
     * @param int $rootid 根评论id，这里是回答id
     * @return int
     */
    protected function replyCount($pid, $rootid)
    {
        $count = Answer::where('status', 1)
                       ->where('rootid', $rootid)
//                       ->where('pid', $pid)
                       ->count();

        return $count;
    }

    /**
     * 对文本进行转义
     * @param string $content
     * @return string
     */
    public function entityDecode($content)
    {
        return html_entity_decode($content);
    }

    /**
     * 对文本进行转义
     *
     * @param $content
     * @return string
     */
    public function entity($content)
    {
        return htmlentities($content);
    }

    /**
     * 标题截取
     *
     * @param string $title
     * @return string
     */
    public function subTitle($title)
    {
        if (mb_strlen($title) > 20) {
            $title = mb_substr($title, 0, 20) . '......';
        }
        return $title;
    }

    /**
     * 生成订单号
     */
    public function getNumberId()
    {
        $orderid = date('YmdHis').mt_rand(100000, 999999);
        return $orderid;
    }

    /**
     * 根据提交参数返回第几页和偏移量
     */
    public function getPageStart(){
        $input_arr = array();

        $page = Input::get('page') ?: 1;
        $pagesize = Input::get('pagesize') ?: config('yunshui.pagesize');
        $input_arr['start'] = $pagesize*($page-1);
        $input_arr['pagesize'] = $pagesize;

        return $input_arr;
    }

    /**
     * 获取用户等级
     * @param $code
     * @return mixed
     */
    public function getLv($code){
        $lv = UserProfile::where([['score_end','>=',$code]])->orderBy('score_end','asc')->select('rankName')->first();
        return $lv['rankName'];
    }

    /**
     * ########################################
     *        悬赏部分公共判断方法
     * ########################################
     */


    /**
     * 通过悬赏提问id获取分类id
     *
     * @param int $interlcut_id 提问id
     * @author neekli
     * @access public
     * @return int
     */
    public function getGroIdByBountryInter($interlcut_id)
    {
        return InterlocutionBounty::where('id', $interlcut_id)->value('group_id');
    }

    /**
     * 根据回答id获取悬赏提问id和分类id
     * @param int $answer_id 回答id
     * @return array
     */
    public function getInterAndGroupIdByCons($comment_interlocution_bounty_id)
    {
        return CommentInterlocutionBounty::where('id', $comment_interlocution_bounty_id)->select('interlocution_bounty_id', 'group_id')->first()->toArray();
    }

    /**
     * 判断悬赏提问是否违规或者已删除
     * @param int $interlocut_id 提问id
     * @author neekli
     * @access protected
     * @throws \Exception
     */
    public function verifyBountry($interlocut_id)
    {
        $verify = InterlocutionBounty::where('id', $interlocut_id)
            ->where('status', 1)
            ->select('violate')
            ->first();

        if (null === $verify) {
            throw new \Exception('悬赏提问不存在', 0);
        } else if (2 === $verify->violate || 3 === $verify->violate) {
            throw new \Exception('提问违规', 0);
        }
    }

    /**
     * 判断悬赏回答是否已被删除
     * @param int $answer_id 回答id
     * @author neekli
     * @access public
     * @throws \Exception
     */
    public function verifyBountryAnswerStatus($comment_interlocution_bounty_id)
    {
        $verify = CommentInterlocutionBounty::where('id', $comment_interlocution_bounty_id)
            ->where('status', 1)
            ->first();

        if (null === $verify) {
            throw new \Exception('回答已被删除', 0);
        }
    }

    /**
     * 当前回答是否已经被采纳
     */
    public function verifyBountryAcceptStatus($comment_interlocution_bounty_id){
        $verify = InterlocutionBounty::where('comment_id', $comment_interlocution_bounty_id)
            ->first();

        if ($verify) {
            throw new \Exception('该回答已被采纳', 0);
        }
    }


    /*
     * 悬赏推送
     * 推送给大师信息[当被邀请或者回答被采纳]
     */
    public function signedAuthorTui($master_id ,$body,$multi_id,$answer_type=null)
    {    
        $clentid = Client::where('user_id', $master_id)->value('clientId');

        if (isset($clentid)) {
            $data['title'] = '十方云水';
            $data['body'] = $body;
            $data['clientId'] = $clentid;
            $content = array(
                'action' => 'interlocution_bounty',
                'multi_id' => $multi_id,
                'type' => 6,
                'answer_type' => $answer_type,
                'title' => "十方云水",
                'content' => $data['body'],
            );
            $data['content'] = json_encode($content);
            GetuiServiceProvider::singlePush($data);
            return 888;
        }
    }

    /**
     * 验证提问是否过期无人回答
     * @param int $interlocut_id 提问id
     * @throws \Exception
     */
    public function verifyBountyHasAnswer($interlocut_id)
    {
        $delInterlocut = InterlocutionBounty::where('interlocution_bounty.id', $interlocut_id)
                                            ->where('interlocution_bounty.status', 0)
                                            ->whereIn('order.order_status', [5, 6, 7])
                                            ->join('order', 'interlocution_bounty.id', 'order.goods_id')
                                            ->first();
        if ($delInterlocut) {
            throw new \Exception('提问过期无人回答', 0);
        }
    }


}
