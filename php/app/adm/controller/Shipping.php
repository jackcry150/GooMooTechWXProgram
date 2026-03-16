<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Shipping
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
     * 运费模板列表
     */
    public function index()
    {
        $name = Request::get('name', '');
        
        $where = [];
        
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        
        $list = Db::name('shipping_template')
            ->where($where)
            ->order('id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        View::assign('list', $list);
        View::assign('name', $name);

        return View::fetch();
    }
    
    /**
     * 添加运费模板
     */
    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            
            if (empty($post['name'])) {
                $data['msg'] = '模板名称不能为空！';
                $data['code'] = 0;
                return json($data);
            }
            
            if (empty($post['type']) || !in_array($post['type'], [1, 2])) {
                $data['msg'] = '计费方式不正确！';
                $data['code'] = 0;
                return json($data);
            }
            
            // 处理计费规则
            if ($post['type'] == 1) {
                // 按件计费
                if ($post['firstPiece'] < 1) {
                    $data['msg'] = '首件数不能为空！';
                    $data['code'] = 0;
                    return json($data);
                }
                $post['firstPiece'] = intval($post['firstPiece']);
                $post['firstFee'] = floatval($post['firstFee']);
                $post['continuePiece'] = intval($post['continuePiece'] ?? 1);
                $post['continueFee'] = floatval($post['continueFee'] ?? 0);
            } else {
                // 按重量计费
                if ($post['firstWeight'] < 1) {
                    $data['msg'] = '首重和首重费用不能为空！';
                    $data['code'] = 0;
                    return json($data);
                }
                $post['firstWeight'] = floatval($post['firstWeight']);
                $post['firstFee'] = floatval($post['firstFee']);
                $post['continueWeight'] = floatval($post['continueWeight'] ?? 1);
                $post['continueFee'] = floatval($post['continueFee'] ?? 0);
            }
            
            $post['createTime'] = time();
            $post['createDate'] = date('Y-m-d H:i:s');
            $post['updateTime'] = time();
            $post['updateDate'] = date('Y-m-d H:i:s');
            
            $res = Db::name('shipping_template')->insert($post);
            if ($res) {
                $data['msg'] = '添加成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '添加失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }
    
    /**
     * 编辑运费模板
     */
    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;

            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }
            
            if (empty($post['name'])) {
                $data['msg'] = '模板名称不能为空！';
                $data['code'] = 0;
                return json($data);
            }
            
            if (empty($post['type']) || !in_array($post['type'], [1, 2])) {
                $data['msg'] = '计费方式不正确！';
                $data['code'] = 0;
                return json($data);
            }

            // 处理计费规则
            if ($post['type'] == 1) {
                // 按件计费
                if (empty($post['firstPiece']) || empty($post['firstFee'])) {
                    $data['msg'] = '首件数和首件费用不能为空！';
                    $data['code'] = 0;
                    return json($data);
                }
                $post['firstPiece'] = intval($post['firstPiece']);
                $post['firstFee'] = floatval($post['firstFee']);
                $post['continuePiece'] = intval($post['continuePiece'] ?? 1);
                $post['continueFee'] = floatval($post['continueFee'] ?? 0);
            } else {
                // 按重量计费
                if (empty($post['firstWeight']) || empty($post['firstFeeWeight'])) {
                    $data['msg'] = '首重和首重费用不能为空！';
                    $data['code'] = 0;
                    return json($data);
                }
                $post['firstWeight'] = floatval($post['firstWeight']);
                $post['firstFeeWeight'] = floatval($post['firstFeeWeight']);
                $post['continueWeight'] = floatval($post['continueWeight'] ?? 1);
                $post['continueFee'] = floatval($post['continueFee'] ?? 0);
            }

            unset($post['id']);
            $post['updateTime'] = time();
            $post['updateDate'] = date('Y-m-d H:i:s');

            $res = Db::name('shipping_template')->where('id', $id)->update($post);
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
            $id = Request::get('id');
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }
            
            $info = Db::name('shipping_template')->where('id', $id)->find();
            if (!$info) {
                $data['msg'] = '运费模板不存在！';
                $data['code'] = 0;
                return json($data);
            }
            
            View::assign('info', $info);
            return View::fetch();
        }
    }
    
    /**
     * 删除运费模板
     */
    public function del()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }
            
            // 检查是否有产品在使用此模板
            $productCount = Db::name('product')->where('shippingTemplateId', $id)->count();
            if ($productCount > 0) {
                $data['msg'] = '该运费模板正在被' . $productCount . '个产品使用，无法删除！';
                $data['code'] = 0;
                return json($data);
            }
            
            $res = Db::name('shipping_template')->where('id', $id)->delete();
            if ($res) {
                $data['msg'] = '删除成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '删除失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }
}





