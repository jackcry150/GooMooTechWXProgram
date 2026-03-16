<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class News
{
    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    /**
     * 资讯列表
     */
    public function index()
    {
        $list = Db::name('news')->order('id asc')->select()->toArray();

        View::assign('list', $list);
        return View::fetch();
    }

    /**
     * 编辑资讯内容
     */
    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = intval($post['id'] ?? 0);
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            // 仅允许修改标题和内容，防止修改 code / isSystem
            $update = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
            ];

            $res = Db::name('news')->where('id', $id)->update($update);
            if ($res !== false) {
                $data['msg'] = '修改成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '修改失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            $id = intval(Request::get('id', 0));
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $info = Db::name('news')->where('id', $id)->find();
            if (!$info) {
                $data['msg'] = '记录不存在！';
                $data['code'] = 0;
                return json($data);
            }

            View::assign('info', $info);
            return View::fetch();
        }
    }
}

