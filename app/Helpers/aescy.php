<?php

/**
 * aes
 */
require_once(app_path() . '/' . 'Libs/aes/MCrypt.php');
function aesCrypt($str){
    $encryption = new MCrypt();
    return $encryption->encrypt_test($str);
}

