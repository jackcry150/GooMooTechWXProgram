<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Order
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
        $orderNo = Request::get('orderNo', '');
        $status = Request::get('status', '');
        $nickName = Request::get('nickName', '');
        $phone = Request::get('phone', '');
        $productName = Request::get('productName', ''); // 产品名称搜索

        $where = [];

        if ($orderNo) {
            $where[] = ['orderNo', 'like', '%' . $orderNo . '%'];
        }

        // 处理退款状态搜索
        $refundStatusFilter = '';
        if ($status !== '') {
            if (strpos($status, 'refund_') === 0) {
                // 退款状态搜索
                $refundStatusFilter = substr($status, 7); // 获取退款状态值
            } else {
                // 普通订单状态搜索
                $where[] = ['status', '=', $status];
            }
        }

        // 如果搜索昵称或手机号，需要关联用户表
        $list = Db::name('order')
            ->alias('o')
            ->leftJoin('user u', 'o.userId = u.id')
            ->field('o.id, o.orderNo, o.totalPrice, o.product, o.address, o.remarks, o.status, o.createDate, o.payDate, o.freightName, o.freightCode, o.freightNo, o.freightTime, o.refundStatus, o.refundReason, o.refundAmount, o.refundApplyTime, o.refundTime, o.refundRemark, u.nickName, u.phone')
            ->where($where);

        if ($nickName) {
            $list = $list->where('u.nickName', 'like', '%' . $nickName . '%');
        }

        if ($phone) {
            $list = $list->where('u.phone', 'like', '%' . $phone . '%');
        }

        // 产品名称搜索：在订单的product JSON字段中搜索
        if ($productName) {
            $list = $list->where('o.product', 'like', '%' . $productName . '%');
        }

        // 退款状态搜索
        if ($refundStatusFilter !== '') {
            $list = $list->where('o.refundStatus', '=', $refundStatusFilter);
        }

        $list = $list->order('o.id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        // 处理数据
        $domain = Request::domain();
        foreach ($list as &$val) {
            // 处理订单状态（结合退款状态）
            $refundStatus = $val['refundStatus'] ?? 0;
            $displayStatus = getOrderDisplayStatus($val['status'], $refundStatus);
            $val['statusText'] = $displayStatus['text'];
            $val['statusColor'] = $displayStatus['color'];

            // 处理退款状态
            $val['refundStatusText'] = refundStatus($refundStatus);
            $val['refundStatus'] = $refundStatus;

            // 处理商品信息
            if ($val['product']) {
                $products = json_decode($val['product'], true);
                foreach ($products as &$product) {
                    $productInfo = Db::name('product')->field('title, subtitle')->where('id', $product['productId'])->find();
                    $product['title'] = $productInfo['title'];
                    $product['subtitle'] = $productInfo['subtitle'];
                }
                $val['productList'] = $products;

            } else {
                $val['productList'] = [];
            }

            // 处理地址信息
            if ($val['address']) {
                $address = json_decode($val['address'], true);
                if (is_array($address)) {
                    $val['addressInfo'] = $address;
                } else {
                    $val['addressInfo'] = [];
                }
            } else {
                $val['addressInfo'] = [];
            }
        }

        View::assign('list', $list);
        View::assign('orderNo', $orderNo);
        View::assign('status', $status);
        View::assign('nickName', $nickName);
        View::assign('phone', $phone);
        View::assign('productName', $productName);
        View::assign('orderStatus', orderStatus());

        return View::fetch();
    }

    /**
     * 发货
     */
    public function ship()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            $freightName = $post['freightName'] ?? '';
            $freightCode = $post['freightCode'] ?? '';
            $freightNo = $post['freightNo'] ?? '';

            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            if (!$freightName) {
                $data['msg'] = '请填写物流公司！';
                $data['code'] = 0;
                return json($data);
            }

            if (!$freightNo) {
                $data['msg'] = '请填写物流单号！';
                $data['code'] = 0;
                return json($data);
            }

            // 检查订单状态
            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                $data['msg'] = '订单不存在！';
                $data['code'] = 0;
                return json($data);
            }

            if ($orderInfo['status'] != 2) {
                $data['msg'] = '订单状态不正确，只能对"待发货"订单进行发货操作！';
                $data['code'] = 0;
                return json($data);
            }

            // 更新订单状态为待收货，并填写物流信息
            $updateData = [
                'status' => 6, // 待收货
                'freightName' => $freightName,
                'freightCode' => $freightCode,
                'freightNo' => $freightNo,
                'freightTime' => date('Y-m-d H:i:s'),
                'shipTime' => time()
            ];

            $res = Db::name('order')->where('id', $id)->update($updateData);
            if ($res !== false) {
                $data['msg'] = '发货成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '发货失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            $id = Request::get('id', 0);
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                $data['msg'] = '订单不存在！';
                $data['code'] = 0;
                return json($data);
            }

            View::assign('orderInfo', $orderInfo);
            return View::fetch();
        }
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $id = Request::get('id', 0);
        if (!$id) {
            $data['msg'] = '参数错误！';
            $data['code'] = 0;
            return json($data);
        }

        // 获取订单信息
        $orderInfo = Db::name('order')
            ->alias('o')
            ->leftJoin('user u', 'o.userId = u.id')
            ->field('o.*, u.nickName, u.phone as userPhone, u.avatar')
            ->where('o.id', $id)
            ->find();

        if (!$orderInfo) {
            $data['msg'] = '订单不存在！';
            $data['code'] = 0;
            return json($data);
        }

        // 用户信息：兼容 join 字段及 MySQL 小写键，join 无数据时单独查用户表
        $userInfo = [];
        $userInfo = Db::name('user')->field('nickName, phone, avatar')->where('id', $orderInfo['userId'])->find();

        $orderInfo['nickName'] =isset($userInfo['nickName']) ? $userInfo['nickName'] : '-';
        $orderInfo['userPhone'] =isset($userInfo['phone']) ? $userInfo['phone'] : '-';
        $orderInfo['avatar'] =isset($userInfo['avatar']) ? $userInfo['avatar'] : '-';

        // 处理订单状态（结合退款状态）
        $refundStatus = $orderInfo['refundStatus'] ?? 0;
        $displayStatus = getOrderDisplayStatus($orderInfo['status'], $refundStatus);
        $orderInfo['statusText'] = $displayStatus['text'];
        $orderInfo['statusColor'] = $displayStatus['color'];
        $orderInfo['refundStatus'] = $refundStatus;

        $domain = Request::domain();
        // 处理商品信息
        if ($orderInfo['product']) {
            $products = json_decode($orderInfo['product'], true);
            foreach ($products as &$product) {
                $productInfo = Db::name('product')->field('title, subtitle, price')->where('id', $product['productId'])->find();
                $product['title'] = $productInfo['title'];
                $product['price'] = $productInfo['price'];
            }
            $orderInfo['productList'] = $products;

        } else {
            $orderInfo['productList'] = [];
        }

        // 处理地址信息
        if ($orderInfo['address']) {
            $address = json_decode($orderInfo['address'], true);
            if (is_array($address)) {
                $orderInfo['addressInfo'] = $address;
            } else {
                $orderInfo['addressInfo'] = [];
            }
        } else {
            $orderInfo['addressInfo'] = [];
        }

        // 处理头像 URL
        if ($orderInfo['avatar'] && strpos($orderInfo['avatar'], 'http') === false && strpos($orderInfo['avatar'], 'data:image') === false) {
            $orderInfo['avatar'] = $domain . $orderInfo['avatar'];
        }

        View::assign('orderInfo', $orderInfo);
        return View::fetch();
    }

    /**
     * 申请退款
     */
    public function refundApply()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            $refundReason = $post['refundReason'] ?? '';
            $refundAmount = $post['refundAmount'] ?? 0;

            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            // 检查订单状态
            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                $data['msg'] = '订单不存在！';
                $data['code'] = 0;
                return json($data);
            }

            // 只有待发货(2)、待收货(6)状态的订单可以申请退款
            if (!in_array($orderInfo['status'], [2, 6])) {
                $data['msg'] = '当前订单状态不允许申请退款！';
                $data['code'] = 0;
                return json($data);
            }

            // 检查是否已经申请退款
            if ($orderInfo['refundStatus'] == 1) {
                $data['msg'] = '该订单已申请退款，请勿重复申请！';
                $data['code'] = 0;
                return json($data);
            }

            // 更新订单退款信息
            $updateData = [
                'refundStatus' => 1, // 申请退款中
                'refundReason' => $refundReason,
                'refundAmount' => $refundAmount ?: $orderInfo['totalPrice'],
                'refundApplyTime' => date('Y-m-d H:i:s'),
                'refundApplyTimeStamp' => time()
            ];

            $res = Db::name('order')->where('id', $id)->update($updateData);
            if ($res !== false) {
                $data['msg'] = '退款申请提交成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '退款申请提交失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            $id = Request::get('id', 0);
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                $data['msg'] = '订单不存在！';
                $data['code'] = 0;
                return json($data);
            }

            View::assign('orderInfo', $orderInfo);
            return View::fetch();
        }
    }

    /**
     * 同意退款
     */
    public function refundAgree()
    {
        if (!Request::isPost()) {
            $data['msg'] = '请求方式错误！';
            $data['code'] = 0;
            return json($data);
        }

        $post = Request::post();
        $id = $post['id'] ?? 0;
        $refundRemark = $post['refundRemark'] ?? '';

        if (!$id) {
            $data['msg'] = '参数错误！';
            $data['code'] = 0;
            return json($data);
        }

        // 检查订单状态
        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            $data['msg'] = '订单不存在！';
            $data['code'] = 0;
            return json($data);
        }

        if ($orderInfo['refundStatus'] != 1) {
            $data['msg'] = '该订单未申请退款！';
            $data['code'] = 0;
            return json($data);
        }

        // 更新订单退款状态
        $updateData = [
            'refundStatus' => 2, // 同意退款
            'refundRemark' => $refundRemark,
            'refundTime' => date('Y-m-d H:i:s'),
            'refundTimeStamp' => time(),
            'status' => 9 // 退款成功状态
        ];

        $res = Db::name('order')->where('id', $id)->update($updateData);
        if ($res !== false) {
            $data['msg'] = '退款处理成功！';
            $data['code'] = 1;
            return json($data);
        } else {
            $data['msg'] = '退款处理失败！';
            $data['code'] = 0;
            return json($data);
        }
    }

    /**
     * 拒绝退款
     */
    public function refundRefuse()
    {
        if (!Request::isPost()) {
            $data['msg'] = '请求方式错误！';
            $data['code'] = 0;
            return json($data);
        }

        $post = Request::post();
        $id = $post['id'] ?? 0;
        $refundRemark = $post['refundRemark'] ?? '';

        if (!$id) {
            $data['msg'] = '参数错误！';
            $data['code'] = 0;
            return json($data);
        }

        if (!$refundRemark) {
            $data['msg'] = '请填写拒绝原因！';
            $data['code'] = 0;
            return json($data);
        }

        // 检查订单状态
        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            $data['msg'] = '订单不存在！';
            $data['code'] = 0;
            return json($data);
        }

        if ($orderInfo['refundStatus'] != 1) {
            $data['msg'] = '该订单未申请退款！';
            $data['code'] = 0;
            return json($data);
        }

        // 更新订单退款状态
        $updateData = [
            'refundStatus' => 3, // 拒绝退款
            'refundRemark' => $refundRemark,
            'refundTime' => date('Y-m-d H:i:s'),
            'refundTimeStamp' => time()
        ];

        $res = Db::name('order')->where('id', $id)->update($updateData);
        if ($res !== false) {
            $data['msg'] = '拒绝退款成功！';
            $data['code'] = 1;
            return json($data);
        } else {
            $data['msg'] = '拒绝退款失败！';
            $data['code'] = 0;
            return json($data);
        }
    }
}

