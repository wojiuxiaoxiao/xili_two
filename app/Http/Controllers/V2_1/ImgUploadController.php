<?php
/**
 * 图片统一处理控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */
namespace App\Http\Controllers\V2_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;

class ImgUploadController extends Controller
{
    /**
     * 单图多图统一上传方法
     * @author neekli
     * @since v1.0
     */
    function imgMuchUpload()
    {

        //$this->checkUser();
        $type = Input::get('type');//默认common 1 头像  2反馈
        switch ($type) {
            case 1:
                $joint_url = 'avatar';
                break;
            case 2:
                $joint_url = 'feedback';
                break;
            default:
                $joint_url = 'common';
        }

        $upload_res = false;
        $Pic = array();
        foreach ($_FILES as $img_name => $single_img) {
            if ($single_img['name'] && $single_img['error'] == 0 && $single_img['size'] > 0) {
                $input = array(
                    'fileArr' => $_FILES,
                    'savePath' => 'img/' . $joint_url,
                    'imgName' => $img_name
                );
                $upload_res = uploadImg($input);
                $Pic[] = $upload_res ? 'img/' . $joint_url . '/' . $upload_res['uploadNme'][0] : '';
            }
            //只要一张不成功，全部失败
            extOperate($upload_res);
        }

        extOperate($Pic);
    }

    /**
     * 单图统一上传方法
     * @author neekli
     * @since v1.0
     */
    function imgUpload_locl()
    {
        //$this->checkUser();
        $type = Input::get('type');//默认common 1 头像  2反馈
        switch ($type) {
            case 1:
                $joint_url = 'avatar';
                break;
            case 2:
                $joint_url = 'feedback';
                break;
            default:
                $joint_url = 'common';
        }

        $upload_res = false;
        $Picnum = count($_FILES);
        $Pic = $Picnum > 1 ? array() : '';

        foreach ($_FILES as $img_name => $single_img) {
            if ($single_img['name'] && $single_img['error'] == 0 && $single_img['size'] > 0) {
                $input = array(
                    'fileArr' => $_FILES,
                    'savePath' => 'img/' . $joint_url,
                    'imgName' => $img_name
                );
                $upload_res = uploadImg($input);
                if ($Picnum > 1) {
                    $Pic[] = $upload_res ? 'img/' . $joint_url . '/' . $upload_res['uploadNme'][0] : '';
                } else {
                    $Pic = $upload_res ? 'img/' . $joint_url . '/' . $upload_res['uploadNme'][0] : '';
                }

            }
            //只要一张不成功，全部失败
            extOperate($upload_res);
        }

        extOperate($Pic);
    }

    /**
     * 图片处理接口
     * @author neekli
     * @since v1.0
     */
    function imgUpload()
    {
        $type = Input::get('type');//默认common 5 头像  6反馈
        if ($_FILES['pic']['name'] && $_FILES['pic']['error'] == 0 && $_FILES['pic']['size'] > 0) {
            $obj = new \CurlFile($_FILES['pic']['tmp_name']);
            $obj->setMimeType($_FILES['pic']['type']);
            $post['pic'] = $obj;
            $post['type'] = $type;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8100/uploadImg");//上传类
            $info = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($info, true);
            extjson($res);
        }
        extjson(array('status'=>0,'data'=>''));
    }
}
