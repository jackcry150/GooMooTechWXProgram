<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Gallery
{
    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $title = Request::get('title', '');
            $category = Request::get('category', '');
            $page = Request::get('page', 1);
            $limit = Request::get('limit', 20);

            $where = [];
            $where[] = ['status', '=', 1];
            
            if ($title) {
                $where[] = ['title', 'like', '%' . $title . '%'];
            }
            if ($category) {
                $where[] = ['category', '=', $category];
            }

            $list = Db::name('gallery')
                ->where($where)
                ->order('sort desc, id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            $domain = Request::domain();
            foreach ($list as &$v) {
                $v['url'] = $domain . $v['url'];
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
}







