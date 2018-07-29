<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/5/14
 * Time: 11:03
 */

namespace App\Http\Controllers\V2_4_1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Http\Provider\V2_4\AnswerService;

class AnswerController extends Controller
{

    private $answerService = null;

    public function __construct(AnswerService $answerService)
    {
        parent::__construct();
        $this->answerService = $answerService;
    }

    /**
     * 回答列表
     * @author zhuoshan
     * @access public
     */
    public function index()
    {
        $input = $this->getPageStart();//获取分页
        $input['interlocut_id'] = Input::get('interlocut_id');
        $result = $this->answerService->_index($input);
        extjson($result);
    }

    /**
     * 对问题进行回答
     * @author zhuoshan
     * @access public
     */
    public function save()
    {
        $this->checkUser();
        $input = Input::all();
        $result = $this->answerService->_save($input);
        extjson($result);
    }

    /**
     * 回答详情
     * @author zhuoshan
     * @access public
     */
    public function detail()
    {
        $input = $this->getPageStart();//包括start和pagesize
        $input['answer_id'] = Input::get('answer_id');
        $result = $this->answerService->_detail($input);
        extjson($result);
    }

    /**
     * 回答添加回复
     * @author zhuoshan
     * @access public
     */
    public function saveComment()
    {
        $this->checkUser();
        $input = Input::all();
        $result = $this->answerService->_saveComment($input);
        extjson($result);
    }

    /**
     * 删除回答
     * @author zhuoshan
     * @access public
     */
    public function delete()
    {
        $input = Input::all();
        $result = $this->answerService->_delete($input);
        extjson($result);
    }

    /**
     * 对回答点赞
     * @author zhuoshan
     * @access public
     */
    public function like()
    {
        $this->checkUser();
        $input = Input::all();
        $result = $this->answerService->_like($input);
        extjson($result);
    }
}