<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use think\facade\Request;

class Login
{

    public function index()
    {
        $systemUserId = Session::get('systemUserId');
        if ($systemUserId) {
            header('Location: /adm');
            exit;
        }
        return View::fetch();
    }

    public function login()
    {
        if (Request::isPost()) {

            $name = Request::post('name');
            $password = Request::post('password');
            if ($name && $password) {
                $where = [
                    'sa.name' => $name,
                    'sa.password' => md5(md5($password))
                ];
                $info = Db::name('system_admin')->field('sa.id, sa.name, sa.department')
                    ->alias('sa')
                    ->join('system_department sd', 'sd.id = sa.department')
                    ->where($where)
                    ->find();
                if ($info) {
                    Session::set('systemUserId', $info['id']);
                    Session::set('name', $info['name']);
                    Session::set('department', $info['department']);

                    $data['msg'] = "登录成功！";
                    $data['code'] = 1;
                    return json($data);
                } else {
                    $data['msg'] = "登录失败！";
                    $data['code'] = 0;
                    return json($data);
                }
            }
        }
        header('Location: /adm');
    }

    public function logout()
    {
        Session::delete('systemUserId');
        Session::delete('systemUserName');
        Session::delete('department');
        header('Location: /adm/login');
    }
}