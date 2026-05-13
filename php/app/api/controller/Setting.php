<?php

namespace app\api\controller;

use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Setting
{

    public function info()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $cacheKey = 'setting:' . current_app_code();
            $info = Cache::get($cacheKey);
            if (!$info) {
                $info = find_brand_setting();
                Cache::set($cacheKey, $info);
            }

            $data['code'] = 200;
            $data['msg'] = "成功！";
            $data['data'] = $info;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

}
