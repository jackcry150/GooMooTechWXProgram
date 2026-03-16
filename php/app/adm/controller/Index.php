<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Index
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
        $departmentId = Session::get('department');
        $departmentInfo = Db::name('system_department')->where('id', $departmentId)->find();
        $role = $departmentInfo['role'];

        $list = Db::name('system_nav')->where('navId', 0)->whereIn('id', $role)->order('sort asc')->select()->toArray();

        foreach ($list as &$val) {
            $val['info'] = Db::name('system_nav')->where('navId', $val['id'])->whereIn('id', $role)->order('sort asc')->select()->toArray();
        }
        unset($val);
        View::assign('userName', Session::get('name'));
        View::assign('navlist', $list);
        return View::fetch();
    }

    public function welcome()
    {
        return View::fetch();
    }
}
