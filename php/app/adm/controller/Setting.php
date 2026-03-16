<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Setting
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
        $info = Db::name('setting')->where('id', 1)->find();
        View::assign('info', $info);

        return View::fetch();
    }



    function updateCacheSetting()
    {
        $info = Db::name('setting')->where('id', 1)->find();
        Cache::set('setting', $info);
    }


    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('setting')->where('id', $post['id'])->update($post);
            if ($res) {
                self::updateCacheSetting();
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        }
    }
}