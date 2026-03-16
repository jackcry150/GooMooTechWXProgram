<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class News
{
    /**
     * 获取单条资讯详情
     * 支持通过 code 或 id 获取
     * code 约定：
     *  - about：公司简介
     *  - after_sale：售后说明
     *  - service_agreement：服务协议
     */
    public function detail()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $code = trim(Request::get('code', ''));
            $id = intval(Request::get('id', 0));

            if (!$code && !$id) {
                $data['msg'] = '参数错误';
                return json($data);
            }

            if ($code) {
                $where = ['code' => $code];
            } else {
                $where = ['id' => $id];
            }

            $info = Db::name('news')
                ->field('id, code, title, content')
                ->where($where)
                ->find();

            if (!$info) {
                $data['msg'] = '记录不存在';
                return json($data);
            }

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $info;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}

