<?php
/**
 * 二维码控制器
 * @author      neek<ixingqiye@163.com>
 * @version     1.0
 * @since       1.0
 */

namespace App\Http\Controllers\V2_4;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;

class QrcodeController extends Controller
{
    /**
     *createCode
     * 创建二维码
     * @author by kexun
     * @access public
     * @since 2.4
     */
    public function  createCode()
    {
        require_once(app_path().'/Libs/phpqrcode/qrlib.php');
        \QRcode::png(url('callBackCode'),'QRcode/code.jpg');
    }
    /**
     *acceptCode
     *识别二维码
     * @author by kexun
     * @access public
     * @since 2.4
     */
    public function callBackCode()
    {
        $device = Input::get('device');
        $source = Input::get('source');
    }
}
