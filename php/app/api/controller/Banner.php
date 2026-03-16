<?php

namespace app\api\controller;

use Exception;
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

            $adWhere = [
                'type' => $type,
                'status' => 1
            ];
            $list = Db::name('ad')->field('id, title, image, link')->where($adWhere)->order('sort desc, id desc')->select()->toArray();
            $domain = Request::domain();
            foreach ($list as &$v) {
                $v['image'] = $domain . $v['image'];
            }

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