<?php

namespace app\api\controller;

use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Banner
{
    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $type = Request::get('type', 1);
            $domain = Request::domain();
            $cacheKey = 'banner:list:' . current_app_code() . ':' . $type . ':' . md5($domain);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $data['code'] = 200;
                $data['msg'] = "成功！";
                $data['data'] = $cached;
                return json($data);
            }

            $adWhere = [
                'type' => $type,
                'status' => 1
            ];
            $query = Db::name('ad')->field('id, title, image, link')->where($adWhere);
            apply_app_code_scope($query, 'ad');
            $list = $query->order('sort desc, id desc')->select()->toArray();
            foreach ($list as &$v) {
                $v['image'] = $domain . $v['image'];
            }
            Cache::set($cacheKey, $list, 120);

            $data['code'] = 200;
            $data['msg'] = "成功！";
            $data['data'] = $list;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}
