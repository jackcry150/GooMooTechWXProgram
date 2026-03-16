<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Album
{
    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $list = Db::name('album')->field('id, title, labels, image')->where('status', 1)->order('sort desc, id desc')->select()->toArray();

            $domain = Request::domain();
            foreach ($list as &$v) {
                $images = json_decode($v['image'], true);
                $v['image'] = $domain . $images[0];
                $v['labels'] = explode(',', $v['labels']);
            }

            $data['code'] = 200;
            $data['msg'] = '成功！';
            $data['data'] = $list;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function detail()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $id = Request::get('id');
            if (!$id) {
                return json($data);
            }
            $where = [
                'id' => $id,
                'status' => 1
            ];
            $info = Db::name('album')->field('id, title, image, labels, proportion, size, material, copyright, price, content, images, type')->where($where)->find();
            if (!$info) {
                return json($data);
            }

            $domain = Request::domain();
            $image = json_decode($info['image'], true);
            $info['image'] = $domain . $image[0];
            $info['labels'] = explode(',', $info['labels']);
            $info['content'] = explode(',', $info['content']);
            $images = json_decode($info['images'], true);
            $info['images'] = [];
            foreach ($images as $imageV) {
                $info['images'][] = $domain . $imageV;
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