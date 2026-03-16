<?php

namespace app\api\controller;

use think\facade\Cache;
use think\facade\Request;

class Guanjiapo
{

    public function authToken()
    {
        $get = Request::get();
        if(isset($get)){
            $Guanjiapo = new \Guanjiapo();
            $token = $Guanjiapo->getToken($get);
            Cache::set('token', $token);
            var_dump($token);
            echo 'Authorization Successful';
            exit;
        }

        echo 'Authorization False';
        exit;
    }

    public function token()
    {

        $authCode = Cache::get('token');
        var_dump($authCode);
        exit;
        if ($authCode) {
            $Guanjiapo = new \Guanjiapo();
            $token = $Guanjiapo->getToken($authCode);
            var_dump($token);
            exit;
        }
    }

    public function goodsList()
    {
        $token = Cache::get('token');
        $Guanjiapo = new \Guanjiapo();
        $goodsList = $Guanjiapo->goodsList($token);

        var_dump($goodsList);
        exit;
    }

    public function order()
    {
        $token = Cache::get('token');
        $Guanjiapo = new \Guanjiapo();
        $order = $Guanjiapo->order($token);

        var_dump($order);
        exit;
    }

}