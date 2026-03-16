<?php


$appKey = '54106586826718458121037253533071';
$appSecret = 'WY02AiBKlqzingGB6sOcYpSyaQmlrMGt';
$signKey = 'c9ae941c10a2c82343bb20bd01bef3eb';
$authCode = 'authCodeauthCode';

$appKey = '68943923115886070418838901844741';
$appSecret = 'ONxYDyNaCoyTzsp83JoQ3YYuMPHxk3j7';
$signKey = 'lezitiancheng';


function getToken($auth, $appKey, $appSecret, $signKey)
{
//    $url = 'http://apigateway.wsgjp.com.cn/api/token';
    $url = 'http://d7safe.mygjp.com.cn:8026/api/token';

    $header = [
        'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
    ];

//    $encryptionParam = [
//        'TimeStamp' => date('Y-m-d H:i:s', time()),
//        'GrantType' => 'auth_token',
//        'AuthParam' => trim($auth)
//    ];

    $encryptionParam = [
        'TimeStamp' => '2016-10-01 10:24:40',
        'GrantType' => 'auth_token',
        'AuthParam' => 'mKu64PrYYPGSEsLYrOEzBDngpKypgndJ'
    ];

    $encryptionJson = json_encode($encryptionParam, JSON_UNESCAPED_SLASHES);
    $encryptionKey = $appSecret;
    $encryptionIv = mb_substr(trim($appSecret), 5, 16);//获取偏移量
    $param['appkey'] = $appKey;

    echo 'jsonParam =' . $encryptionJson . '</br>';
    echo 'appsecret = ' . $encryptionKey . '</br>';
    echo 'iv = ' . $encryptionIv . '</br>';
    echo 'p = ' . aesEncrypt($encryptionJson, $encryptionKey, $encryptionIv) . '</br>';
    echo 'p2 = ' . aesEncrypt2($encryptionJson, $encryptionKey, $encryptionIv);
    exit;
    $param['p'] = aesEncrypt($encryptionJson, $encryptionKey, $encryptionIv);
    $param['signkey'] = $signKey;
    $param['sign'] = hash('sha256', json_encode($param));
    unset($param['signkey']);

    $res = postCurl($url, $header, http_build_query($param));

    var_dump($res);
    exit;

}

function aesEncrypt2($encryptStr, $encryptKey, $localIV)
{

    $encryptStr = trim($encryptStr);
    $cipherAlgo = 'AES-256-CBC';
    // PKCS7 填充
//    $blockSize = openssl_cipher_iv_length($cipherAlgo); // IV length is usually block size
//    $pad = $blockSize - (strlen($encryptStr) % $blockSize);
//    $encryptStr .= str_repeat(chr($pad), $pad);

    $encrypted = openssl_encrypt($encryptStr, $cipherAlgo, $encryptKey, OPENSSL_RAW_DATA, $localIV);

    return base64_encode($encrypted);
}

function aesEncrypt($encryptStr, $encryptKey, $localIV)
{
    /*
        $encryptStr = trim($encryptStr);
        $cipherAlgo = 'AES-256-CBC';
        // PKCS7 填充
        $blockSize = openssl_cipher_iv_length($cipherAlgo); // IV length is usually block size
        $pad = $blockSize - (strlen($encryptStr) % $blockSize);
        $encryptStr .= str_repeat(chr($pad), $pad);

        $encrypted = openssl_encrypt($encryptStr, $cipherAlgo, $encryptKey, OPENSSL_RAW_DATA, $localIV);

        return base64_encode($encrypted);
    */

    $encryptStr = trim($encryptStr);

    //Open module
    $module = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);

    //print "module = $module <br/>" ;

    @mcrypt_generic_init($module, $encryptKey, $localIV);

    //Padding
    $block = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $pad = $block - (strlen($encryptStr) % $block); //Compute how many characters need to pad
    $encryptStr .= str_repeat(chr($pad), $pad); // After pad, the str length must be equal to block or its integer multiples
    //encrypt
    $encrypted = @mcrypt_generic($module, $encryptStr);
    //Close
    @mcrypt_generic_deinit($module);
    @mcrypt_module_close($module);
    return base64_encode($encrypted);

}

function postCurl($url, $headers, $data)
{
    var_dump($url, $headers, $data);
    // 初使化init方法
    $curl = curl_init();
    // 指定URL
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    // 设定请求后返回结果
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // 声明使用POST方式来进行发送
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // 发送什么数据呢
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    // 忽略证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //忽略header头信息
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置超时时间
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    // 发送请求
    $result = curl_exec($curl);
    // 关闭curl
    curl_close($curl);
    // 返回数据
    return $result;
}

$post = $_POST;
if (isset($post['appkey']) && isset($post['auth_code']) && isset($post['keyword'])) {
    if ($post['appkey'] == $appKey && $post['keyword'] == $authCode) {
        $result = getToken($post['auth_code'], $appKey, $appSecret, $signKey);
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        exit;
    }
}