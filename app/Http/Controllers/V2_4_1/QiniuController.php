<?php
/**
 * qiniu控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_4_1;

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
    /**
     * rollback_pic
     * 图片上传
     * @author by kexun
     * @access public
     * @since 1.0
     */
    public function rollback_pic()
    {
        $accessKey = config('qiniu.accessKey');
        $secretKey = config('qiniu.secretKey');
        $bucket = config('qiniu.bucketPic');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);
//        $authUrl = $auth->privateDownloadUrl("http://p1k6kz6ep.bkt.clouddn.com/Chrysanthemum.jpg");
        header("Content-type: application/json");
        echo json_encode(array('status'=> 1 ,'onlineUrl' => config('qiniu.onlineUrlPic'), 'utoken' => $token));
    }

    public function uploadInit(){
        if($_POST){
            dd($_POST);
        }
        return view('upload');
    }
}
