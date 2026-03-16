<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Ad
{

    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    public function index()
    {
        $type = Request::get('type');
        $name = Request::get('name');
        $status = Request::get('status');
        $where = [];
        if ($type) {
            $searchWhere = [
                [
                    'type',
                    '=',
                    $type
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($name) {
            $searchWhere = [
                [
                    'name',
                    'like',
                    '%' . $name . '%'
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($status) {
            $searchWhere = [
                [
                    'status',
                    '=',
                    $status
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        $list = Db::name('ad')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        foreach ($list as &$val) {
            $val['type'] = adType($val['type']);
            $val['status'] = status($val['status']);
        }

        View::assign('list', $list);

        View::assign('type', $type);
        View::assign('adType', adType());
        View::assign('name', $name);
        View::assign('status', $status);
        View::assign('statusType', status());

        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('ad')->insert($post);
            if ($res) {
                $data['msg'] = "添加成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "添加失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            View::assign('adType', adType());
            View::assign('statusType', status());

            return View::fetch();
        }
    }

    public function del()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('ad')->delete($post);
            if ($res) {
                $data['msg'] = "成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('ad')->where('id', $post['id'])->update($post);
            if ($res) {
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $id = Request::get('id');
            $info = Db::name('ad')->where('id', $id)->find();
            
            // 处理图片路径，确保能正确显示
            if (!empty($info['image'])) {
                // 如果图片路径不是完整URL，添加域名前缀用于显示
                if (strpos($info['image'], 'http') === false && strpos($info['image'], 'data:image') === false) {
                    $info['imageDisplay'] = Request::domain() . $info['image'];
                } else {
                    $info['imageDisplay'] = $info['image'];
                }
            } else {
                $info['imageDisplay'] = '';
            }
            
            View::assign('info', $info);

            View::assign('adType', adType());
            View::assign('statusType', status());

            return View::fetch();
        }
    }
}