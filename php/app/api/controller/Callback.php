<?php

namespace app\api\controller;

use think\facade\Cache;
use think\facade\Db;

class Callback
{


    public function index()
    {
        $input = file_get_contents('php://input');
        Cache::set('index', $input);
    }

    public function message()
    {
        $input = file_get_contents('php://input');
        $message = json_decode($input, true);
        if ($message && isset($message['tradeid']) && $message['freightName']) {

            $orderDetail = $message['eshopSaleOrderDetail'];
            foreach ($orderDetail as $k => $v) {
                $orderWhere = [
                    'orderNo' => $v['oid'],
                    'status' => 2
                ];
                $orderUpdate = [
                    'status' => 6,
                    'freightName' => $message['freightName'],
                    'freightCode' => $message['freightCode'],
                    'freightNo' => $message['freightNo'],
                    'freightTime' => date('Y-m-d H:i:s', strtotime($message['modifyTime'])),
                ];
                Db::name('order')->where($orderWhere)->update($orderUpdate);
            }
        }
    }
}