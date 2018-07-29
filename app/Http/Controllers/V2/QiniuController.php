<?php
/**
 * qiniu控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
use App\Http\Controllers\Controller;

class QiniuController extends Controller
{
    public $access_key = '6uRB0cKNqK8Y-da8AlV7L5EvKt_bpeGXWcP9on3x';
    public $secret_key = 'hj0GN95V_b-wt5t06To7MEx2aE6UHHRC_QhuDvL6';
    public $bucket = 'shifangyunshui';

    /**
     * @服务器直传采用脚本定时任务上传
     */
    public  function radioUpload(){

        $auth = new Auth($this->access_key, $this->secret_key); // 构建鉴权对象
        $token = $auth->uploadToken($this->bucket); // 生成上传 Token
        $filePath = './img/jianghu.mp3'; // 要上传文件的本地路径
        $key = '笑傲江湖.mp3'; // 上传到七牛后保存的文件名
        $uploadMgr = new UploadManager();
        //list($ret, $err) = $uploadMgr->putFile($token, $key, $request->file('img'));
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        echo "\n====> putFile result: \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            var_dump($ret);
        }

    }


    public function uploadInit(){
        if($_POST){
            dd($_POST);
        }
        return view('upload');
    }
}
