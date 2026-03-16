<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class System
{

    protected $systemUserId = '';

    public function __construct()
    {
        $this->systemUserId = Session::get('systemUserId');
        if (!$this->systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    public function department()
    {
        $list = Db::name('system_department')->select();
        View::assign('list', $list);
        return View::fetch();
    }

    public function departmentAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('system_department')->insert($post);
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
            return View::fetch();
        }
    }

    public function departmentEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('system_department')->where('id', $post['id'])->update($post);
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
            $info = Db::name('system_department')->where('id', $id)->find();
            View::assign('info', $info);
            View::assign('id', $id);

            return View::fetch();
        }
    }

    public function user()
    {
        $name = Request::get('name');
        $department = Request::get('department');
        $where = [];
        if ($name) {
            $where['u.name'] = $name;
        }
        if ($department) {
            $where['u.department'] = $department;
        }
        $list = Db::name('system_admin')->field('u.*, d.name as departmentName')
            ->alias('u')
            ->leftJoin('system_department d', 'd.id = u.department')
            ->where($where)
            ->order('u.id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        $departmentList = Db::name('system_department')->select();
        View::assign('departmentList', $departmentList);

        View::assign('department', $department);
        View::assign('name', $name);
        View::assign('list', $list);

        return View::fetch();
    }

    public function userAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $userInfo = Db::name('system_admin')->field('id')
                ->where('phone', $post['phone'])
                ->find();
            if ($userInfo) {
                $data['msg'] = "手机号注册过，无需重复注册！";
                $data['code'] = 0;
                return json($data);
            }

            if ($post['password'] != $post['password2']) {
                $data['msg'] = "两次密码不一致！";
                $data['code'] = 0;
                return json($data);
            }

            $post['password'] = md5($post['password2']);
            unset($post['password2']);
            $post['addTime'] = time();
            $res = Db::name('system_admin')->insert($post);
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

            $list = Db::name('system_department')->select();

            View::assign('list', $list);
            return View::fetch();
        }
    }

    public function userEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();

            $userInfo = Db::name('system_admin')->field('id')
                ->where('phone', $post['phone'])
                ->where('id', '<>', $post['id'])
                ->find();
            if ($userInfo) {
                $data['msg'] = "手机号与其他重复，不能修改！";
                $data['code'] = 0;
                return json($data);
            }

            $res = Db::name('system_admin')->where('id', $post['id'])->update($post);
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
            $id = input('id');

            $info = Db::name('system_admin')->where('id', $id)->find();
            View::assign('info', $info);

            $list = Db::name('system_department')->select();
            View::assign('list', $list);
            View::assign('id', $id);
            return View::fetch();
        }
    }

    public function userPassword()
    {
        if (Request::isPost()) {
            $post = Request::post();

            if ($post['password'] != $post['password2']) {
                $data['msg'] = "两次密码不一致！";
                $data['code'] = 0;
                return json($data);
            }
            $password['password'] = md5(md5($post['password2']));

            $res = Db::name('system_admin')->where('id', $post['id'])->update($password);
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
            View::assign('id', $id);
            return View::fetch();
        }
    }

    public function userDel()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('system_admin')->where('id', $post['id'])->delete();
            if ($res) {
                $data['msg'] = "删除成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "删除失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $list = Db::name('system_department')->select();

            View::assign('list', $list);
            return View::fetch();
        }
    }

    public function nav()
    {
        $list = Db::name('system_nav')->where('navId', 0)->order('sort asc')->select()->toArray();

        foreach ($list as &$val) {
            $val['info'] = Db::name('system_nav')->where('navId', $val['id'])->order('sort asc')->select()->toArray();
        }

        View::assign('list', $list);
        return View::fetch();
    }

    public function navAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('system_nav')->insert($post);
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

            $list = Db::name('system_nav')->field('id, name')->where('navId', 0)->select();
            View::assign('list', $list);
            return View::fetch();
        }
    }

    public function navEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();

            $res = Db::name('system_nav')->where('id', $post['id'])->update($post);
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

            $info = Db::name('system_nav')->where('id', $id)->find();
            View::assign('info', $info);

            $list = Db::name('system_nav')->field('id, name')->where('navId', 0)->select();
            View::assign('list', $list);

            View::assign('id', $id);
            return View::fetch();
        }
    }

    public function jurisdiction()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('system_department')->where('id', $post['id'])->update($post);
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
            $info = Db::name('system_department')->where('id', $id)->find();
            $list = Db::name('system_nav')->where('navId', 0)->select()->toArray();

            foreach ($list as &$val) {
                $val['info'] = Db::name('system_nav')->where('navId', $val['id'])->select()->toArray();
            }
            View::assign('info', $info);
            View::assign('list', $list);
            View::assign('id', $id);
            return View::fetch();
        }
    }
}