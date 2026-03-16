<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class User
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
        $nickName = Request::get('nickName', '');
        $phone = Request::get('phone', '');
        
        $where = [];
        
        if ($nickName) {
            $where[] = ['nickName', 'like', '%' . $nickName . '%'];
        }
        
        if ($phone) {
            $where[] = ['phone', 'like', '%' . $phone . '%'];
        }
        
        $list = Db::name('user')
            ->field('id, nickName, avatar, phone, snailShells, collectionCards, regDate, loginDate')
            ->where($where)
            ->order('id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        
        // 处理头像路径
        $domain = Request::domain();
        foreach ($list as &$val) {
            if ($val['avatar'] && strpos($val['avatar'], 'data:image') === false && strpos($val['avatar'], 'http') === false) {
                $val['avatar'] = $domain . $val['avatar'];
            }
            // 处理空值
            $val['nickName'] = $val['nickName'] ?? '-';
            $val['phone'] = $val['phone'] ?? '-';
            $val['snailShells'] = $val['snailShells'] ?? 0;
            $val['collectionCards'] = $val['collectionCards'] ?? 0;
            $val['regDate'] = $val['regDate'] ?? '-';
            $val['loginDate'] = $val['loginDate'] ?? '-';
        }

        View::assign('list', $list);
        View::assign('nickName', $nickName);
        View::assign('phone', $phone);

        return View::fetch();
    }
}



