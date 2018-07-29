<?php

namespace App\Http\Controllers\V2_5;

use App\Http\Provider\GrowthRuleService;
use App\Http\Provider\V2_5\UserCenterService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Http\Provider\V2_5\InterlocutionService;
use App\Http\Controllers\V2_5\UserCenterController;

class InterlocutionController extends Controller
{
    private $interLocutionService = null;

    /**
     * 获取服务
     * InterlocutionController constructor.
     * @param InterlocutionService $interLocutionService
     */
    public function __construct(InterlocutionService $interLocutionService)
    {
        parent::__construct();
        $this->interLocutionService = $interLocutionService;
    }

    /**
     * 获取分类
     */
    public function getGroup()
    {
        $result = $this->interLocutionService->_getGroup();
        extjson($result);
    }

    /**
     * 提问列表
     * @author zhuoshan
     * @access public
     */
    public function index()
    {
        $group_id = Input::get('group_id');
        $page = $this->getPageStart();//获取分页
        $result = $this->interLocutionService->_index($group_id, $page);
        extjson($result);
    }

    /**
     * 提问详情
     * @author zhuoshan
     * @access public
     */
    public function detail()
    {
        $input = Input::all();
        $result = $this->interLocutionService->_detail($input);
        extjson($result);
    }

    /**
     * 添加提问
     * @author zhuoshan
     * @access public
     */
    public function save()
    {
        $this->checkUser();
        $input = Input::all();//获取所有参数
        $result = $this->interLocutionService->_save($input);

        //累加积分
        $growth_service = new GrowthRuleService(USERID,'pose');
        $growth_service->init();

        extjson($result);
    }

    /**
     * 删除提问
     * 需要传递分类id和提问id，用于判断分类和提问是否还存在
     * @author zhuoshan
     * @access public
     */
    public function delete()
    {
        $input = Input::all();
        $result = $this->interLocutionService->_delete($input);
        extjson($result);
    }

    /**
     * 收藏提问
     * @author zhuoshan
     * @access public
     */
    public function collect()
    {
        $this->checkUser();
        $input = Input::all();
        $result = $this->interLocutionService->_collect($input);

        //累加积分
        $growth_service = new GrowthRuleService(USERID,'collect');
        $growth_service->init();

        extjson($result);
    }

    /**
     * 取消收藏
     * @author zhuoshan
     * @access public
     */
    public function ucollect()
    {
        $this->checkUser();
        $input = Input::all();
        $result = $this->interLocutionService->_ucollect($input);
        return $result;
    }

}
