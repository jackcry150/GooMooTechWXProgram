<?php

namespace app\api\controller;

use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Server
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

            $list = Cache::get('server');
            $newsList = [];
            $domain = Request::domain();

            if ($list) {
                foreach ($list as $v) {
                    if ($v['type'] == $type) {
                        $images = json_decode($v['image'], true);
                        $img = [];
                        foreach ($images as $image) {
                            $img[] = $domain . $image;
                        }
                        $v['image'] = $img;
                        unset($v['type']);
                        $newsList[] = $v;
                    }
                }
            } else {
                $where = [
                    'type' => $type,
                    'status' => 1
                ];
                $newsList = Db::name('server')
                    ->field('id, type, title, image, link, corpId')
                    ->where($where)
                    ->order('sort desc, id desc')
                    ->select()
                    ->toArray();
                foreach ($newsList as &$v) {
                    $images = json_decode($v['image'], true);
                    $img = [];
                    foreach ($images as $image) {
                        $img[] = $domain . $image;
                    }
                    $v['image'] = $img;
                    unset($v['type']);
                }
            }

            $data['code'] = 200;
            $data['msg'] = "成功！";
            $data['data'] = $newsList;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}