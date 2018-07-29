<?php
/**
 * cutstr_html
 * 返回清除html后的字符串
 * @author by kexun
 * @param string $string 字符串
 * @param int $length 长度
 * @param string $ellipsis ...
 * @access public static
 * @since 1.0
 * @return $n 长度$n
 */
if (!function_exists('cutstr_html')) {
    function cutstr_html($string, $length = 0, $ellipsis = '',$len=50)
    {
        $string = strip_tags($string);
        $string = preg_replace('/\n/is', '', $string);
        $string = preg_replace('/ |　/is', '', $string);
        $string = preg_replace("/\r/", '', $string);
        $string = preg_replace('/&nbsp;/is', '', $string);
        $string = preg_replace('/&ensp;/is', '', $string);
        $string = preg_replace('/&amp;/is', '', $string);
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $string);
        if (is_array($string) && !empty($string[0])) {
            if (is_numeric($length) && $length) {
                $string = join('', array_slice($string[0], 0, $length)) . $ellipsis;
            } else {
                $string = implode('', $string[0]);
            }
        } else {
            $string = '';
        }

        return mb_substr ($string,0,$len);
    }
}


/**
 * aes加密
 */
function en_crypt($str){
    require_once(app_path() . '/' . 'Libs/aes/MCrypt.php');
    $encryption = new MCrypt();
    return $encryption->en_crypt($str);
}

/**
 * aes解密
 */
function de_crypt($str){
    require_once(app_path() . '/' . 'Libs/aes/MCrypt.php');
    $encryption = new MCrypt();
    return $encryption->de_crypt($str);
}

/**
 * created by neek li
 * @param $inputArr
 */
function uploadImg($inputArr){

//    $input = array(
//        'fileArr'=>$_FILES,
//        'imgName'=>'uploadinput',
//        'maxSize'=>0,
//        'overWrite'=>0,
//        'savePath'=>'shangchuan',
//        'thumb'=>1,
//        'thumbWidth'=>10,
//        'thumbHeight'=>10
//    );
//    uploadImg($input);
    $fileArr = $inputArr['fileArr'];//必填参数,$_FILES
    $imgName = $inputArr['imgName'];//必填参数,表单中input的名字
    $savePath = (isset($inputArr['savePath']) && !empty($inputArr['savePath'])) ? $inputArr['savePath'] : "img/upload";
    $maxSize = (isset($inputArr['maxSize']) && !empty($inputArr['maxSize'])) ? $inputArr['maxSize'] : 0;//文件大小限制kb
    $overWrite = (isset($inputArr['overWrite']) && !empty($inputArr['overWrite'])) ? $inputArr['overWrite'] : 0;//0 不允许  1 允许
    $thumb = (isset($inputArr['thumb']) && !empty($inputArr['thumb'])) ? $inputArr['thumb'] : 0;//是否生成缩率图 0否 1是
    $thumbWidth = (isset($inputArr['thumbWidth']) && !empty($inputArr['thumbWidth'])) ? $inputArr['thumbWidth'] : 130;//缩率图宽,默认130
    $thumbHeight = (isset($inputArr['thumbHeight']) && !empty($inputArr['thumbHeight'])) ? $inputArr['thumbHeight'] : 130;//缩率图高 默认130

    if($fileArr[$imgName]['name'] <> ""){
        //包含上传文件类
        require_once(app_path() . '/' . 'Libs/image/upload.php');
        //创建目录
        makeDirectory($savePath);
        //允许的文件类型
        $fileFormat = array('gif','jpg','jpge','png','jpeg');
        //初始化上传类
        $f = new Upload( $savePath, $fileFormat, $maxSize, $overWrite);

        if($thumb){
            $f->setThumb(1,$thumbWidth,$thumbHeight);
        }else{
            $f->setThumb(0);
        }

        //后面的0表示不更改文件名，若为1，则由系统生成随机文件名
        if (!$f->run($imgName,1)){
            $returnArr['status'] = 10001;
            $returnArr['msg'] = '图片上传失败';
            return $returnArr;//上传失败
            //通过$f->errmsg()只能得到最后一个出错的信息，
            //详细的信息在$f->getInfo()中可以得到。
            //echo   $f->errmsg()."<br>\n";
        }else{

            $uploadName = array();
            foreach($f->getInfo() as $v){
                $uploadName[] = $v['saveName'];
            }
            $returnArr['status'] = 200;
            $returnArr['msg'] = '图片上传成功';
            $returnArr['uploadNme'] = $uploadName;
            return $returnArr;
        }
    }
}

function makeDirectory($directoryName) {

    $directoryName = str_replace("\\","/",$directoryName);
    $dirNames = explode('/', $directoryName);
    $total = count($dirNames);
    $temp = '';
    for($i=0; $i<$total; $i++) {
        $temp .= $dirNames[$i].'/';
        if (!is_dir($temp)) {
            $oldmask = umask(0);
            if (!mkdir($temp, 0777)) exit("不能建立目录 $temp");
            umask($oldmask);
        }
    }
    return true;
}

/**
 * created by neek li
 * json输出
 * @param $return
 */
function extjson($return){
    header("Content-type: application/json");
    $string = json_encode($return,JSON_UNESCAPED_UNICODE);
    echo $string;

    //logOutput($string);
    exit;
}

/**
 * created by neek li
 * 操作结果json输出
 * @param $return
 */
function extOperate($bool,$false_msg='',$true_msg=''){
    header("Content-type: application/json");
    $return['status'] = $bool ? 1 : 0;
    $return['msg'] = $bool ? $true_msg : $false_msg;
    $string = json_encode($return, JSON_UNESCAPED_UNICODE);
    echo $string;

    //logOutput($string);
    exit;
}

/**
 * created by neek li
 * 查询信息json输出
 * @param $return
 */
function extInfo($data){
    header("Content-type: application/json");

    $return['data'] = $data ? $data : array();
    $return['status'] = 1;
    $string = json_encode($return, JSON_UNESCAPED_UNICODE);
    echo $string;

    //logOutput($string);
    exit;
}

/**
 * created by neek li
 * 入参日志
 */
function logInput(){
    $uri = $_SERVER['REQUEST_URI'];
    $postStr = serialize($_POST);
    $str = "\r\n\r\n".date("Y-m-d G:i:s")."\r\n";
    $str .= "\r\n".$uri."\r\n";
    $str .= "\r\n".$postStr."\r\n";
    $str .= "\r\n==================================================================================================\r\n";

    file_put_contents('./input.log',var_export($str,true),FILE_APPEND);
}

/**
 * created by neek li
 * 出参日志
 * @param $strJson
 */
function logOutput($strJson){
    $str = "\r\n\r\n".date("Y-m-d G:i:s")."\r\n";
    $str .= "\r\n".$strJson."\r\n";
    $str .= "\r\n==================================================================================================\r\n";

    file_put_contents('./output.log',var_export($str,true),FILE_APPEND);
}

/**
 * created by neek li
 * @param $string 明文 或 密文
 * @param string $operation  DECODE表示解密,其它表示加密
 * @param int $expiry
 * @param string $key 密匙
 * @return string 密文有效期
 */
function authcode($string, $operation = 'DECODE', $expiry = 0,$key = '%_dm%$s&shifangyunshui') {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥
    if($expiry<1){
        $expiry = 86400*30;
    }
    $ckey_length = 16;
    // 密匙
    $key = md5($key);
    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }

}

/**
 * 返回Ip
 */
function client_ip(){
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $onlineip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $onlineip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $onlineip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
    $onlineip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
    return $onlineip;
}

/**
 * 输出sql
 */
function lastSql(){
    \DB::listen(function ($query) {
        echo 'SQL语句执行：'.$query->sql.'，参数：'.json_encode($query->bindings).',耗时：'.$query->time.'ms';
    });
}

function s2a($str)
{
    $arr = array();
    parse_str($str, $arr);
    return $arr;
}

function a2s($arr)
{
    $str = "";
    foreach ($arr as $key => $value)
    {
        if (is_array($value))
        {
            foreach ($value as $value2)
            {
                $str .= urlencode($key) . "[]=" . urlencode($value2) . "&";
            }
        }
        else
        {
            $str .= urlencode($key) . "=" . urlencode($value) . "&";
        }
    }
    return $str;
}

/**
 * 判断数组是否达到了万
 */
function testMillion($data){

    $changeM = $data/10000;
    $res = $changeM >= 1 ? sprintf("%.1f", $changeM).'万' : $data;
    return $res;
}

function postData($url,$param=array()){
    $o="";
    foreach ($param as $k=>$v)
    {
        $o.= "$k=".urlencode($v)."&";
    }
    $get_data=substr($o,0,-1);
    if(strstr($url,"?")){
        $url.='&'.$get_data;
    }
    else {
        $url.='?'.$get_data;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1 );
    curl_setopt($ch,CURLOPT_TIMEOUT,2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $get_data);
    $result = curl_exec($ch);
    return $result;
}