<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Collect
{

    public function edit()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId'],
                'productId' => Request::post('id')
            ];
            $collect = Request::post('collect');
            $count = Db::name('collect')->where($where)->count();
            $res = false;
            $msg = '';
            $collectCount = 0;
            if ($collect === 0) {
                if ($count >= 1) {
                    return json($data);
                }
                $res = Db::name('collect')->insert($where);
                $msg = '添加';
                $collectCount = 1;
            } elseif ($collect === 1) {
                if ($count < 1) {
                    return json($data);
                }
                $res = Db::name('collect')->where($where)->delete();
                $msg = '取消';
                $collectCount = 0;
            }
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = $msg . '收藏成功';
                $data['data'] = $collectCount;
            } else {
                $data['code'] = 100;
                $data['msg'] = $msg . '收藏失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function count()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }

            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId']
            ];
            $count = Db::name('collect')->where($where)->count();

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = [
                'count' => $count
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();
            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $where = [
                'c.userId' => $userToken['userId'],
            ];

            $list = Db::name('collect')->alias('c')
                ->field('c.id, p.id as productId, p.title, p.subtitle, p.type, p.image, p.price, p.type')
                ->join('product p', 'p.id = c.productId')
                ->where($where)
                ->order('c.id desc')
                ->select()->toArray();
            $domain = Request::domain();
            foreach ($list as &$v) {
                $images = json_decode($v['image'], true);
                $img = [];
                foreach ($images as $image) {
                    $img[] = $domain . $image;
                }
                $v['image'] = $img[0];
                $v['type'] = productType($v['type']);
            }

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $list;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function cancel()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $collectId = Request::post('id');
            if (empty($collectId)) {
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId'],
                'id' => $collectId
            ];
            $res = Db::name('collect')->where($where)->delete();
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = '取消收藏成功';
            } else {
                $data['code'] = 100;
                $data['msg'] = '取消收藏失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function cancelAll()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $collect = Request::post();
            if (count($collect) == 0) {
                return json($data);
            }

            $collectIds = array_column($collect, 'id');
            $res = Db::name('collect')->delete($collectIds);
            if ($res) {

                $where = [
                    'c.userId' => $userToken['userId'],
                ];

                $list = Db::name('collect')->alias('c')
                    ->field('c.id, p.id as productId, p.title, p.subtitle, p.type, p.image, p.price')
                    ->join('product p', 'p.id = c.productId')
                    ->where($where)
                    ->order('c.id desc')
                    ->select()->toArray();
                $domain = Request::domain();
                foreach ($list as &$v) {
                    $images = json_decode($v['image'], true);
                    $img = [];
                    foreach ($images as $image) {
                        $img[] = $domain . $image;
                    }
                    $v['image'] = $img[0];
                }

                $data['code'] = 200;
                $data['msg'] = '取消收藏成功';
                $data['data'] = $list;
            } else {
                $data['code'] = 100;
                $data['msg'] = '取消收藏失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}