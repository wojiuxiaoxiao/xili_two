<?php
/**
 * 悬赏问答控制器
 * @author      neek<ixingqiye@163.com>
 * @version     2.5
 * @since       2.5
 */
namespace App\Http\Controllers\V2_5;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Http\Provider\V2_5\BountyService;
use App\Http\Provider\GrowthRuleService;

class BountyController extends Controller
{

    private $bountyService = null;

    public function __construct(BountyService $bountyService)
    {
        parent::__construct();
        $this->bountyService = $bountyService;
    }

    /**
     *  添加悬赏
     *  @author neek li
     *  @since V2_5
     */
    public function addBounty(){

        $this->checkUser();
        $input_param = Input::all();
        $input_param['user_id'] = USERID;
        $result = $this->bountyService->addbounty($input_param);

        extjson($result);
    }

    /**
     * 邀请大师列表
     * type 1:推荐 2:关注
     * @author neekli
     * @since V2_5
     */
    public function chooseInviter(){
        $this->checkUser();
        $input_param = $this->getPageStart();
        $input_param['type'] = Input::get("type");
        $input_param['user_id'] = USERID;
        $inveters = $this->bountyService->inviters($input_param);
        extInfo($inveters);
    }

    /**
     * 邀请大师
     * @author neekli
     * @since v2.5
     */
    public function inviteSignedAuthor(){
        $this->checkUser();
        $input_param['master_id'] = Input::get("master_id");
        $input_param['interlocboun_id'] = Input::get("interlocboun_id");
        $input_param['user_id'] = USERID;

        $result = $this->bountyService->inviteSignedAuthor($input_param);
        extjson($result);
    }

    /**
     * 悬赏列表
     * @author neekli
     * @since v2.5
     */
    public function bountryList(){
        $input_arr = $this->getPageStart();
        $input_arr['type'] = Input::get('type');

        $bountry_list = $this->bountyService->bountryList($input_arr);
        extInfo($bountry_list);
    }

    /**
     * 悬赏问答详情
     * @author neekli
     * @since v2.5
     */
    public function boutryInfo(){
        $input_arr['interlocut_id'] = Input::get('interlocut_id');
        $bountry_info = $this->bountyService->boutryInfo($input_arr);

        extjson($bountry_info);
    }

    /**
     * 悬赏问答详情之全部回答列表
     * @author neekli
     * @since v2.5
     */

    public function bountryReplyList(){
        $input_arr = $this->getPageStart();
        $input_arr['interlocut_id'] = Input::get('interlocut_id');
        $bountryReplyList = $this->bountyService->bountryReplyList($input_arr);

        extjson($bountryReplyList);
    }

    /**
     * 回答悬赏提问接口
     * @author neekli
     * @since v2.5
     */
    public function bountryReply(){
        $this->checkUser();
        $input = Input::all();
        $input['user_id'] = USERID;
        $result = $this->bountyService->bountryReply($input);

        extjson($result);
    }

    /**
     * 悬赏回答采纳接口
     * @author neekli
     * @since v2.5
     */
    public function acceptReply(){
        $this->checkUser();
        $input_arr['comment_interlocution_bounty_id'] = Input::get('comment_interlocution_bounty_id');
        $result = $this->bountyService->acceptReply($input_arr);

        //累加积分
        $growth_service = new GrowthRuleService(USERID,'accept');
        $growth_service->init();

        extjson($result);
    }

    /**
     * 悬赏回答点赞
     * @author neekli
     * @since v2.5
     */
    public function bountryLike(){
        $input_arr['interlocution_bounty_id'] = Input::get('interlocution_bounty_id');
        $result = $this->bountyService->bountryLike($input_arr);
        if($this->userid>0){
            $growth_service = new GrowthRuleService($this->userid,'like');
            $growth_service->init();
        }

        extjson($result);
    }

    /**
     * 关注大师接口
     * @author neekli
     * @since v2.5
     */
    public function focusSignedAuthor(){
        $this->checkUser();
        $input_arr['master_id'] = Input::get('user_id');
        $input_arr['user_id'] = USERID;
        $result = $this->bountyService->focusSignedAuthor($input_arr);

        extjson($result);
    }

    /**
     * 取消关注大师接口
     * @author neekli
     * @since v2.5
     */
    public function cancelFocusSa(){

        $this->checkUser();
        $input_arr['master_id'] = Input::get('user_id');
        $input_arr['user_id'] = USERID;
        $result = $this->bountyService->cancelFocusSa($input_arr);

        extjson($result);
    }

}
