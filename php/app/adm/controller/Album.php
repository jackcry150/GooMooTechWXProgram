<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Album
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
        $title = Request::get('title');
        $status = Request::get('status');
        $where = [];

        if ($title) {
            $searchWhere = [
                [
                    'title',
                    'like',
                    '%' . $title . '%'
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

        $list = Db::name('album')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        foreach ($list as &$val) {
            $val['status'] = status($val['status']);
            $image = json_decode($val['image'], true);
            $val['image'] = $image[0];
        }

        View::assign('list', $list);

        View::assign('title', $title);
        View::assign('status', $status);
        View::assign('statusType', status());

        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('album')->insert($post);
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

            View::assign('statusType', status());

            return View::fetch();
        }
    }


    public function del()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('album')->delete($post);
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
            $res = Db::name('album')->where('id', $post['id'])->update($post);
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
            $info = Db::name('album')->where('id', $id)->find();
            View::assign('info', $info);

            View::assign('statusType', status());

            return View::fetch();
        }
    }
}