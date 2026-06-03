<?php

namespace app\api\controller;

use Exception;
use Guanjiapo;
use Huifu;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Order
{
    private function orderTradeField()
    {
        return table_has_column('order', 'tradeNo') ? 'tradeNo' : 'orderNo';
    }

    private function loadProductsMap(array $productIds)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $list = Db::name('product')->where('id', 'in', $productIds)->select()->toArray();
        $map = [];
        foreach ($list as $item) {
            $map[intval($item['id'])] = $item;
        }
        return $map;
    }

    private function expandOrderProducts($productRaw, $domain, array $productMap = [])
    {
        $productList = is_array($productRaw) ? $productRaw : json_decode($productRaw ?? '[]', true);
        if (!is_array($productList)) {
            return [];
        }

        $expanded = [];
        foreach ($productList as $item) {
            if (isset($item['title']) || isset($item['productCode'])) {
                if (isset($item['image']) && strpos($item['image'], 'http') === false) {
                    $item['image'] = $domain . $item['image'];
                }
                $expanded[] = $item;
                continue;
            }

            $productId = intval($item['productId'] ?? 0);
            if (!$productId || !isset($productMap[$productId])) {
                continue;
            }

            $productInfo = $productMap[$productId];
            $images = json_decode($productInfo['image'] ?? '[]', true);
            $img0 = is_array($images) && !empty($images) ? $images[0] : '';
            $expanded[] = [
                'productId' => $productId,
                'productCode' => $productInfo['productId'] ?? '',
                'title' => $productInfo['title'] ?? '',
                'subtitle' => $productInfo['subtitle'] ?? '',
                'image' => $img0 ? (strpos($img0, 'http') === 0 ? $img0 : $domain . $img0) : '',
                'price' => intval($productInfo['type'] ?? 1) == 2 ? ($productInfo['deposit'] ?? 0) : ($productInfo['price'] ?? 0),
                'version' => $item['version'] ?? '',
                'quantity' => intval($item['quantity'] ?? 0),
                'type' => intval($productInfo['type'] ?? 1),
                'deposit' => $productInfo['deposit'] ?? 0,
                'balance' => floatval($productInfo['price'] ?? 0) - floatval($productInfo['deposit'] ?? 0),
            ];
        }

        return $expanded;
    }

    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId']
            ];
            $status = Request::get('status');
            if ($status !== '0') {
                if (strval($status) === '1') {
                    $where[] = ['status', 'in', [1, 8]];
                } elseif (strval($status) === '8') {
                    $where[] = ['status', 'in', [8, 10]];
                } else {
                    $where['status'] = $status;
                }
            } else {
                // 全部订单时排除已删除的
                $where[] = ['status', '<>', 5];
            }
            $list = Db::name('order')->field('id, totalPrice, product, orderNo, status')->where($where)->order('id desc')->select()->toArray();
            $domain = Request::domain();
            $productIds = [];
            foreach ($list as $item) {
                $productList = json_decode($item['product'] ?? '[]', true);
                if (!is_array($productList)) {
                    continue;
                }
                foreach ($productList as $productItem) {
                    if (!isset($productItem['title']) && !empty($productItem['productId'])) {
                        $productIds[] = intval($productItem['productId']);
                    }
                }
            }
            $productMap = $this->loadProductsMap($productIds);
            foreach ($list as &$v) {
                $v['statusClass'] = 'color:' . orderStatusClass($v['status']);
                $v['statusVal'] = orderStatus($v['status']);
                $v['product'] = $this->expandOrderProducts($v['product'] ?? '[]', $domain, $productMap);
            }

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $list;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function cancel()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = Request::post('id');
            if (empty($id)) {
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];
            $orderInfo = Db::name('order')->field('status, product')->where($orderWhere)->find();
            if (!$orderInfo) {
                return json($data);
            }
            // 只有待支付（1）和已预定（8）状态的订单可以取消
            if (!in_array($orderInfo['status'], [1, 8])) {
                $data['msg'] = '只能取消待支付或已预定的订单';
                return json($data);
            }

            // 订单取消（支付前取消，库存未扣减，无需恢复）
            Db::startTrans();
            try {
                // 更新订单状态为取消
                $orderUpdate = [
                    'status' => 4
                ];
                $res = Db::name('order')->where('id', $id)->update($orderUpdate);

                if ($res) {
                    Db::commit();
                    $data['code'] = 200;
                    $data['msg'] = '取消成功';
                    $data['data'] = [
                        'status' => 4,
                        'statusVal' => orderStatus(4),
                        'statusClass' => orderStatusClass(4),
                    ];
                    return json($data);
                } else {
                    Db::rollback();
                    $data['msg'] = '取消失败';
                    return json($data);
                }
            } catch (Exception $e) {
                Db::rollback();
                $data['msg'] = '取消失败：' . $e->getMessage();
                return json($data);
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    /**
     * 确认收货，并奖励猫饼（一元一猫饼）
     */
    public function confirmReceipt()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();
            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = Request::post('id');
            if (empty($id)) {
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];
            $orderInfo = Db::name('order')->field('id, userId, totalPrice, status, address, arrivalConfirmStatus')->where($orderWhere)->find();
            if (!$orderInfo) {
                return json($data);
            }
            // 只有待收货（6）状态的订单可以确认收货
            if ($orderInfo['status'] != 6) {
                $data['msg'] = '当前订单状态不允许确认收货';
                return json($data);
            }

            $totalPrice = floatval($orderInfo['totalPrice'] ?? 0);
            $snailShells = intval($totalPrice); // 一元一猫饼

            Db::startTrans();
            try {
                Db::name('order')->where($orderWhere)->update([
                    'status' => 7, // 订单完成
                    'arrivalConfirmStatus' => 2,
                    'arrivalConfirmedAt' => date('Y-m-d H:i:s'),
                    'arrivalConfirmSnapshot' => $orderInfo['address'] ?? '',
                    'arrivalConfirmRemark' => 'user confirm receipt',
                ]);
                if ($snailShells > 0) {
                    Db::name('user')->where('id', $orderInfo['userId'])->inc('snailShells', $snailShells)->update();
                }
                Db::commit();
                $data['code'] = 200;
                $data['msg'] = '确认收货成功' . ($snailShells > 0 ? '，获得' . $snailShells . '猫饼' : '');
                $data['data'] = ['snailShells' => $snailShells];
                return json($data);
            } catch (Exception $e) {
                Db::rollback();
                $data['msg'] = '确认收货失败：' . $e->getMessage();
                return json($data);
            }
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function confirmArrivalInfo()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();
            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }

            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = intval(Request::post('id', 0));
            $name = trim((string) Request::post('name', ''));
            $phone = trim((string) Request::post('phone', ''));
            $province = trim((string) Request::post('province', ''));
            $city = trim((string) Request::post('city', ''));
            $area = trim((string) Request::post('area', Request::post('region', '')));
            $detail = trim((string) Request::post('detail', ''));
            $remark = trim((string) Request::post('remark', ''));

            if ($id <= 0) {
                $data['msg'] = '订单参数错误';
                return json($data);
            }
            if ($name === '' || $phone === '' || $detail === '') {
                $data['msg'] = '请填写完整收货信息';
                return json($data);
            }
            if (!preg_match('/^1\d{10}$/', $phone)) {
                $data['msg'] = '手机号格式不正确';
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];
            $orderInfo = Db::name('order')
                ->field('id, status, address, arrivalConfirmStatus')
                ->where($orderWhere)
                ->find();
            if (!$orderInfo) {
                $data['msg'] = '订单不存在';
                return json($data);
            }
            if (intval($orderInfo['status']) !== 6) {
                $data['msg'] = '当前订单状态不允许确认收货信息';
                return json($data);
            }

            $oldAddress = json_decode($orderInfo['address'] ?? '', true);
            if (!is_array($oldAddress)) {
                $oldAddress = [];
            }
            $newAddress = [
                'name' => $name,
                'phone' => $phone,
                'province' => $province,
                'city' => $city,
                'area' => $area,
                'region' => $area,
                'detail' => $detail,
            ];
            if (isset($oldAddress['id'])) {
                $newAddress['id'] = $oldAddress['id'];
            }

            Db::name('order')->where($orderWhere)->update([
                'address' => json_encode($newAddress, JSON_UNESCAPED_UNICODE),
                'arrivalConfirmStatus' => 2,
                'arrivalConfirmedAt' => date('Y-m-d H:i:s'),
                'arrivalConfirmSnapshot' => json_encode($newAddress, JSON_UNESCAPED_UNICODE),
                'arrivalConfirmRemark' => $remark !== '' ? $remark : 'user confirm arrival info',
            ]);

            $data['code'] = 200;
            $data['msg'] = '收货信息确认成功';
            $data['data'] = [
                'arrivalConfirmStatus' => 2,
                'addressInfo' => $newAddress,
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function pay()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = Request::post('id');
            $payType = Request::post('payType', 'full'); // 'deposit' 定金支付, 'balance' 尾款支付, 'full' 全额支付

            if (empty($id)) {
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];

            // 获取订单详细信息，包括定金尾款信息
            $tradeField = $this->orderTradeField();
            $orderInfo = Db::name('order')->field("id, {$tradeField} as tradeNo, totalPrice, product, address, orderDetails, status, depositAmount, depositPaid, balanceAmount, balancePaid, balanceDueTimeStamp")->where
                ($orderWhere)->find();

            if (!$orderInfo) {
                $data['msg'] = '订单不存在';
                return json($data);
            }

            $isPresale = ($orderInfo['depositAmount'] > 0 || $orderInfo['balanceAmount'] > 0);
            $currentTime = time();
            $productList = json_decode($orderInfo['product'], true);
            if (!is_array($productList) || empty($productList)) {
                $data['msg'] = '订单商品异常';
                return json($data);
            }

            // 支付时校验：库存、限购、价格（从数据库读取当前商品信息）
            $domain = Request::domain();
            $productNames = [];
            $recalcDeposit = 0;
            $recalcBalance = 0;
            $recalcTotal = 0;
            $splitTotal = 0;
            $acctInfos = [];
            foreach ($productList as $p) {
                $productId = intval($p['productId'] ?? 0);
                $quantity = intval($p['quantity'] ?? 0);
                $version = trim(strval($p['version'] ?? ''));
                if (!$productId || $quantity <= 0) continue;

                $productInfo = Db::name('product')->where('id', $productId)->find();
                if (!$productInfo) {
                    $data['msg'] = '商品已下架或不存在';
                    return json($data);
                }
                $productNames[] = ($productInfo['subtitle'] ?? '') . ($productInfo['title'] ?? '');

                $productType = intval($productInfo['type'] ?? 1);
                $price = floatval($productInfo['price'] ?? 0);
                $deposit = floatval($productInfo['deposit'] ?? 0);

                // 库存校验（非预售）
                if ($productType != 2) {
                    $stock = intval($productInfo['stock'] ?? 0);
                    if ($stock < $quantity) {
                        $data['msg'] = '商品"' . ($productInfo['title'] ?? '') . '"库存不足，当前库存：' . $stock;
                        return json($data);
                    }
                }

                // 限购校验
                $limitStock = intval($productInfo['limitStock'] ?? 0);
                if ($limitStock > 0) {
                    $userPurchased = Db::name('order')
                        ->where('userId', $userToken['userId'])
                        ->whereIn('status', [2, 6, 7])
                        ->where('product', 'like', '%"productId":' . $productId . '%')
                        ->select()->toArray();
                    $purchasedQty = 0;
                    foreach ($userPurchased as $o) {
                        $ops = json_decode($o['product'], true);
                        if (is_array($ops)) {
                            foreach ($ops as $op) {
                                if (isset($op['productId']) && $op['productId'] == $productId &&
                                    (!isset($op['version']) || $op['version'] == $version)) {
                                    $purchasedQty += intval($op['quantity'] ?? 0);
                                }
                            }
                        }
                    }
                    if ($purchasedQty + $quantity > $limitStock) {
                        $data['msg'] = '商品"' . ($productInfo['title'] ?? '') . '"限购' . $limitStock . '件';
                        return json($data);
                    }
                }

                if ($productType == 2) {
                    $payMoney = bcmul(($price - $deposit), $quantity, 2);
                    $recalcDeposit += $deposit * $quantity;
                    $recalcBalance += $payMoney;
                } else {
                    $payMoney = bcmul($price, $quantity, 2);
                    $recalcTotal += $payMoney;
                }

                if ($productInfo['splitRatio'] > 0 && $productInfo['splitReceiverId'] != '') {
                    // 账户分润
                    $splitMoney = number_format(($payMoney * $productInfo['splitRatio'] / 100), 2, '.', '');
                    $acctInfos[] = [
                        'div_amt' => $splitMoney,
                        'huifu_id' => $productInfo['splitReceiverId'],
                    ];
                    $splitTotal += $splitMoney;
                }
            }

            // 价格校验：预售定金/尾款须与当前商品价格一致
            $storedDeposit = floatval($orderInfo['depositAmount'] ?? 0);
            $storedBalance = floatval($orderInfo['balanceAmount'] ?? 0);
            if (abs($recalcDeposit - $storedDeposit) > 0.01 || abs($recalcBalance - $storedBalance) > 0.01) {
                $data['msg'] = '商品价格已变动，请重新下单';
                return json($data);
            }

            $orderName = implode('、', array_slice($productNames, 0, 3));
            if (count($productNames) > 3) $orderName .= '等';

            $payAmount = 0;

            // 定金支付
            if ($payType === 'deposit') {
                if (!$isPresale) {
                    $data['msg'] = '该订单不是预售订单，无法支付定金';
                    return json($data);
                }
                if ($orderInfo['depositPaid'] == 1) {
                    $data['msg'] = '定金已支付，无需重复支付';
                    return json($data);
                }
                // 已预定（8）状态的订单可以支付定金
                if ($orderInfo['status'] != 8) {
                    $data['msg'] = '订单状态不正确，无法支付定金';
                    return json($data);
                }

                $payAmount = floatval($orderInfo['depositAmount']);
                $orderName = '定金-' . $orderName;
                $orderTradeType = 1;
            } // 尾款支付
            elseif ($payType === 'balance') {
                if (!$isPresale) {
                    $data['msg'] = '该订单不是预售订单，无法支付尾款';
                    return json($data);
                }
                if ($orderInfo['depositPaid'] != 1) {
                    $data['msg'] = '请先支付定金';
                    return json($data);
                }
                if ($orderInfo['balancePaid'] == 1) {
                    $data['msg'] = '尾款已支付，无需重复支付';
                    return json($data);
                }
                // 已付定金待付尾款（10）状态的订单可以支付尾款
                if ($orderInfo['status'] != 10) {
                    $data['msg'] = '订单状态不正确，无法支付尾款';
                    return json($data);
                }

                $payAmount = floatval($orderInfo['balanceAmount']);
                $orderName = '尾款-' . $orderName;
                $orderTradeType = 1;
            } // 全额支付（普通订单或一次性支付全款）
            else {
                // 如果是预售订单且未付定金，需要先付定金
                if ($isPresale && $orderInfo['depositPaid'] != 1) {
                    $data['msg'] = '预售订单请先支付定金';
                    return json($data);
                }
                // 如果是预售订单且已付定金未付尾款，则支付尾款
                if ($isPresale && $orderInfo['depositPaid'] == 1 && $orderInfo['balancePaid'] != 1) {
                    $payAmount = floatval($orderInfo['balanceAmount']);
                    $orderName = '尾款-' . $orderName;
                    $payType = 'balance'; // 切换为尾款支付
                } else {
                    // 普通订单全额支付
                    if (!in_array($orderInfo['status'], [1, 8])) {
                        $data['msg'] = '订单状态不正确，无法支付';
                        return json($data);
                    }
                    $payAmount = floatval($orderInfo['totalPrice']);
                }
                $orderTradeType = 0;
            }

            if ($payAmount <= 0) {
                $data['msg'] = '支付金额错误';
                return json($data);
            }

            try {

                $tradeNo = date('YmdHis') . $userToken['userId'] . rand(1000, 9999);
                $params = [
                    'tradeNo' => $tradeNo,
                    'goodsDesc' => strlen($orderName) > 100 ? substr($orderName, 0, 100) . '...' : $orderName,
                    'tradeType' => 'T_MINIAPP',
                    'transAmt' => number_format($payAmount, 2, '.', ''),
//                    'notifyUrl' => Request::domain() . '/Api/Notify',
                    'notifyUrl' => 'https://api.goomooplay.com/api/order/notify',
                    'openId' => $userToken['openId'] ?? '',
                    'profit' => number_format(($payAmount - $splitTotal), 2, '.', ''),
                ];

                $paymenyt = new Huifu();
                $paymenytJson = $paymenyt->create($params, $acctInfos);
//                $paymenytResult = Cache::get('paymenyt');
                $paymenytResult = json_decode($paymenytJson, true);

                if (isset($paymenytResult) && isset($paymenytResult['data']['resp_code']) && $paymenytResult['data']['resp_code'] == '00000100') {
                    $payInfo = json_decode($paymenytResult['data']['pay_info'], true);

                    $orderWhere = [
                        'id' => $id,
                    ];
                    $userUpdate = [
                        'payType' => $payType
                    ];
                    if (table_has_column('order', 'tradeNo')) {
                        $userUpdate['tradeNo'] = $tradeNo;
                    }
                    Db::name('order')->where($orderWhere)->update($userUpdate);

                    $data['code'] = 200;
                    $data['msg'] = '提交成功';
                    $data['data'] = [
                        'payment' => $payInfo,
                        'orderNo' => $tradeNo,
                    ];
                } else {
                    $data['msg'] = '拉起失败，请重新支付--' . $paymenytJson;
                }
            } catch (Exception $paymentException) {
                $data['msg'] = '支付接口异常：' . $paymentException->getMessage();
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function create()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            // 只接收地址ID和产品ID（含版本、数量），支付成功前不存产品详情
            $product = Request::post('product');
            $addressInput = Request::post('address');
            $addressId = is_array($addressInput) ? ($addressInput['id'] ?? 0) : $addressInput;
            if (empty($product) || empty($addressId)) {
                $data['msg'] = '请选择地址和商品';
                return json($data);
            }
            $remarks = Request::post('remarks', '');
            $shippingFee = floatval(Request::post('shippingFee', 0));

            $addressWhere = [
                'userId' => $userToken['userId'],
                'id' => $addressId
            ];
            $addressInfo = Db::name('address')->field('id, name, phone, province, city, region, detail')->where($addressWhere)->find();
            if (!$addressInfo) {
                $data['msg'] = '地址不存在';
                return json($data);
            }

            Db::startTrans();
            try {
                $totalPrice = 0;
                $depositAmount = 0;
                $balanceAmount = 0;
                $orderNo = date('YmdHis') . $userToken['userId'] . rand(1000, 9999);
                $cartIds = [];
                $presaleEndTime = 0;
                $hasPresale = false;
                $productMinimal = []; // 只存 productId, version, quantity
                $orderDetails = [];

                foreach ($product as $k => $v) {
                    $productId = intval($v['productId'] ?? 0);
                    $quantity = intval($v['quantity'] ?? 0);
                    $version = trim(strval($v['version'] ?? ''));

                    if (!$productId || $quantity <= 0) {
                        throw new Exception('商品信息不完整');
                    }

                    $productInfo = Db::name('product')->field('productId, title, type, deposit, price, endTime')->where('id', $productId)->lock(true)->find();
                    if (!$productInfo) {
                        throw new Exception('商品不存在');
                    }

                    if ($productInfo['type'] == 2) {
                        $hasPresale = true;
                        $deposit = floatval($productInfo['deposit'] ?? 0);
                        $productPrice = floatval($productInfo['price'] ?? 0);
                        $depositAmount += $deposit * $quantity;
                        $balanceAmount += ($productPrice - $deposit) * $quantity;
                        $endT = !empty($productInfo['endTime']) ? strtotime($productInfo['endTime']) : 0;
                        if ($endT > $presaleEndTime) $presaleEndTime = $endT;
                    } else {
                        $totalPrice += floatval($productInfo['price'] ?? 0) * $quantity;
                    }

                    $productMinimal[] = ['productId' => $productId, 'version' => $version, 'quantity' => $quantity];
                    if (!empty($v['id'])) $cartIds[] = $v['id']; // 购物车 id，用于支付成功后删除

                    $orderDetails[$k] = [
                        'ptypeid' => $productInfo['productId'],
                        'productname' => $productInfo['title'],
                        'oid' => $orderNo,
                        'platformpropertiesname' => '',
                        'tradeoriginalprice' => $productInfo['price'],
                        'preferentialtotal' => 0,
                        'qty' => $quantity,
                        'refundstatus' => 0,
                    ];
                }

                if ($hasPresale) {
                    $orderTotalPrice = $depositAmount + $totalPrice + $shippingFee;
                } else {
                    $orderTotalPrice = $totalPrice + $shippingFee;
                }

                $orderTradeType = $productInfo['type'] == 2 ? 1 : 0;
                self::subOrder($orderNo, 1, $orderTradeType, $orderTotalPrice, $orderDetails, $addressInfo);

                $initialStatus = $hasPresale ? 8 : 1;

                $orderInsert = [
                    'userId' => $userToken['userId'],
                    'totalPrice' => $orderTotalPrice,
                    'product' => json_encode($productMinimal), // 支付前只存产品ID
                    'address' => json_encode($addressInfo),
                    'orderDetails' => json_encode($orderDetails),
                    'remarks' => $remarks,
                    'createTime' => time(),
                    'createDate' => date('Y-m-d H:i:s'),
                    'createIp' => Request::ip(),
                    'orderNo' => $orderNo,
                    'status' => $initialStatus,
                    'apiStatus' => 0,
                    'apiMsg' => '订单已创建，等待支付',
                ];
                if (table_has_column('order', 'tradeNo')) {
                    $orderInsert['tradeNo'] = $orderNo;
                }

                if ($hasPresale) {
                    $orderInsert['depositAmount'] = $depositAmount;
                    $orderInsert['depositPaid'] = 0;
                    $orderInsert['balanceAmount'] = $balanceAmount;
                    $orderInsert['balancePaid'] = 0;
                    if ($presaleEndTime > 0) {
                        $balanceDueTimeStamp = $presaleEndTime + (7 * 24 * 3600);
                        $orderInsert['balanceDueTimeStamp'] = $balanceDueTimeStamp;
                        $orderInsert['balanceDueTime'] = date('Y-m-d H:i:s', $balanceDueTimeStamp);
                    }
                }

                $orderId = Db::name('order')->insertGetId($orderInsert);

                if (!empty($cartIds)) {
                    Db::name('cart')->where('id', 'in', $cartIds)->delete();
                }

                Db::commit();
                $data['code'] = 200;
                $data['msg'] = '提交成功';
                $data['data'] = [
                    'orderId' => $orderId,
                    'orderNo' => $orderNo
                ];
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                $data['code'] = 100;
                $data['msg'] = "提交失败" . $e->getMessage();
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    function subOrder($orderNo, $tradestatus, $orderTradeType, $totalPrice, $orderDetails, $address)
    {
        $token = Cache::get('token');
        if (!is_array($token) || empty($token['auth_token'])) {
            return [
                'ok' => false,
                'msg' => '管家婆授权 token 不存在或已失效'
            ];
        }
        if (!is_array($address)) {
            return [
                'ok' => false,
                'msg' => '收货地址数据异常'
            ];
        }
        $orderData = [
            'tradeid' => $orderNo,
            'tradestatus' => $tradestatus,
            'tradecreatetime' => date('Y-m-d H:i:s', time()),
            'tradetype' => $orderTradeType, // 0=普通，1=预售
            'refundstatus' => 0,
            'tradetotal' => $totalPrice,
            'total' => $totalPrice,
            'preferentialtotal' => 0,
            'orderdetails' => $orderDetails,
            'eshopbuyer' => [
                'customerreceiver' => $address['name'],
                'customerreceivermobile' => $address['phone'],
                'customerreceiverprovince' => $address['province'],
                'customerreceivercity' => $address['city'],
                'customerreceiverdistrict' => $address['region'],
                'customerreceiveraddress' => $address['detail'],
            ]
        ];

        $Guanjiapo = new Guanjiapo();
        $order = $Guanjiapo->order($token, $orderData);
        return $order;
    }

    /**
     * 删除订单（仅支持已取消的订单）
     */
    public function delete()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();
            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = Request::post('id');
            if (empty($id)) {
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];
            $orderInfo = Db::name('order')->field('status')->where($orderWhere)->find();
            if (!$orderInfo) {
                return json($data);
            }
            // 只有已取消（4）状态的订单可以删除
            if ($orderInfo['status'] != 4) {
                $data['msg'] = '只能删除已取消的订单';
                return json($data);
            }

            $res = Db::name('order')->where($orderWhere)->update(['status' => 5]); // 5=删除订单
            if ($res !== false) {
                $data['code'] = 200;
                $data['msg'] = '删除成功';
                return json($data);
            }
            $data['msg'] = '删除失败';
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function notify()
    {
        $input = file_get_contents('php://input');
        parse_str($input, $output);
        if (isset($output) && $output['resp_code'] == '00000000') {
            $resData = json_decode($output['resp_data'], true);
            if (isset($resData) && $resData['resp_code'] == '00000000') {
                $tradeField = $this->orderTradeField();
                $transAmt = round(floatval($resData['trans_amt'] ?? 0), 2);
                $dateString = $resData['end_time'];
                $formattedDateString = substr($dateString, 0, 4) . '-' . substr($dateString, 4, 2) . '-' . substr($dateString, 6, 2) . ' ' . substr($dateString, 8, 2) . ':' . substr($dateString, 10, 2) . ':' . substr($dateString, 12, 2);
                $formattedDateTime = strtotime($formattedDateString);

                Db::startTrans();
                try {
                    $orderWhere = [
                        $tradeField => $resData['mer_ord_id'],
                    ];
                    $orderInfo = Db::name('order')
                        ->field('id, status, orderNo, totalPrice, depositAmount, depositPaid, balanceAmount, balancePaid, product, payType, orderDetails, address')
                        ->where($orderWhere)
                        ->lock(true)
                        ->find();

                    if (!$orderInfo) {
                        Db::rollback();
                        $key = 'notifyresData' . time() . rand(100000, 999999);
                        $content = '没有查询到订单，' . $output['resp_data'];
                        Cache::set($key, $content);
                        return 'FAIL';
                    }

                    $matchOrder = false;
                    $isDuplicateNotify = false;
                    $updateData = [];
                    $needSync = false;
                    $orderTradeType = 0;
                    $shouldDeductStock = false;

                    if ($orderInfo['payType'] === 'deposit') {
                        $matchOrder = intval($orderInfo['status']) === 8
                            && intval($orderInfo['depositPaid']) === 0
                            && $transAmt * 100 == round(floatval($orderInfo['depositAmount'] ?? 0), 2) * 100;
                        $isDuplicateNotify = intval($orderInfo['depositPaid']) === 1
                            && $transAmt * 100 == round(floatval($orderInfo['depositAmount'] ?? 0), 2) * 100;

                        if ($matchOrder) {
                            $updateData['depositPaid'] = 1;
                            $updateData['depositPayTime'] = $formattedDateString;
                            $updateData['depositPayTimeStamp'] = $formattedDateTime;
                            $updateData['status'] = $resData['trans_stat'] == 'S' ? 10 : 8;
                            $updateData['payNo'] = $resData['out_trans_id'] ? $resData['out_trans_id'] : $resData['hf_seq_id'];
                            $shouldDeductStock = $resData['trans_stat'] == 'S';
                        }
                    } elseif ($orderInfo['payType'] === 'balance') {
                        $matchOrder = intval($orderInfo['status']) === 10
                            && intval($orderInfo['depositPaid']) === 1
                            && intval($orderInfo['balancePaid']) === 0
                            && $transAmt * 100 == round(floatval($orderInfo['balanceAmount'] ?? 0), 2) * 100;
                        $isDuplicateNotify = intval($orderInfo['balancePaid']) === 1
                            && $transAmt * 100 == round(floatval($orderInfo['balanceAmount'] ?? 0), 2) * 100;

                        if ($matchOrder) {
                            $updateData['balancePaid'] = 1;
                            $updateData['balancePayTime'] = $formattedDateString;
                            $updateData['balancePayTimeStamp'] = $formattedDateTime;
                            $updateData['status'] = $resData['trans_stat'] == 'S' ? 2 : 10;
                            $updateData['payNo'] = $resData['out_trans_id'] ? $resData['out_trans_id'] : $resData['hf_seq_id'];
                            $updateData['payDate'] = $formattedDateString;
                            $updateData['payTime'] = $formattedDateTime;
                            $needSync = $resData['trans_stat'] == 'S';
                            $orderTradeType = 1;
                        }
                    } else {
                        $isPresale = isset($orderInfo['depositAmount']) && floatval($orderInfo['depositAmount']) > 0;
                        if ($isPresale) {
                            $matchOrder = intval($orderInfo['status']) === 8
                                && intval($orderInfo['depositPaid']) === 0
                                && $transAmt * 100 == round(floatval($orderInfo['depositAmount'] ?? 0), 2) * 100;
                            $isDuplicateNotify = intval($orderInfo['depositPaid']) === 1
                                && $transAmt * 100 == round(floatval($orderInfo['depositAmount'] ?? 0), 2) * 100;

                            if ($matchOrder) {
                                $updateData['depositPaid'] = 1;
                                $updateData['depositPayTime'] = $formattedDateString;
                                $updateData['depositPayTimeStamp'] = $formattedDateTime;
                                $updateData['status'] = $resData['trans_stat'] == 'S' ? 10 : 8;
                                $updateData['payNo'] = $resData['out_trans_id'] ? $resData['out_trans_id'] : $resData['hf_seq_id'];
                                $shouldDeductStock = $resData['trans_stat'] == 'S';
                            }
                        } else {
                            $matchOrder = intval($orderInfo['status']) === 1
                                && $transAmt * 100 == round(floatval($orderInfo['totalPrice'] ?? 0), 2) * 100;
                            $isDuplicateNotify = in_array(intval($orderInfo['status']), [2, 6, 7], true)
                                && $transAmt * 100 == round(floatval($orderInfo['totalPrice'] ?? 0), 2) * 100;

                            if ($matchOrder) {
                                $updateData['status'] = $resData['trans_stat'] == 'S' ? 2 : 3;
                                $updateData['payNo'] = $resData['out_trans_id'] ? $resData['out_trans_id'] : $resData['hf_seq_id'];
                                $updateData['payDate'] = $formattedDateString;
                                $updateData['payTime'] = $formattedDateTime;
                                $needSync = $resData['trans_stat'] == 'S';
                                $shouldDeductStock = $resData['trans_stat'] == 'S';
                            }
                        }
                    }

                    if ($isDuplicateNotify) {
                        Db::commit();
                        return 'SUCCESS';
                    }

                    if (!$matchOrder) {
                        Db::rollback();
                        $key = 'notifyresData' . time() . rand(100000, 999999);
                        $content = '没有查询到订单或者订单状态不对获取金额不对，' . $output['resp_data'];
                        Cache::set($key, $content);
                        return 'FAIL';
                    }

                    if (!empty($updateData)) {
                        Db::name('order')->where('id', $orderInfo['id'])->update($updateData);
                    }

                    if ($shouldDeductStock) {
                        $productList = json_decode($orderInfo['product'], true);
                        if (is_array($productList)) {
                            foreach ($productList as $su) {
                                $productId = intval($su['productId'] ?? 0);
                                $quantity = intval($su['quantity'] ?? 0);
                                if ($productId > 0 && $quantity > 0) {
                                    Db::name('product')->where('id', $productId)->dec('stock', $quantity)->update();
                                }
                            }
                        }
                    }

                    Db::commit();

                    if ($needSync) {
                        try {
                            self::subOrder(
                                $orderInfo['orderNo'],
                                2,
                                $orderTradeType,
                                $orderInfo['totalPrice'],
                                json_decode($orderInfo['orderDetails'], true),
                                json_decode($orderInfo['address'], true)
                            );
                        } catch (Exception $syncException) {
                            $key = 'notifySyncFail' . time() . rand(100000, 999999);
                            $content = '支付成功后同步外部订单失败，orderNo=' . $orderInfo['orderNo'] . '，' . $syncException->getMessage();
                            Cache::set($key, $content);
                        }
                    }

                    return 'SUCCESS';
                } catch (Exception $e) {
                    Db::rollback();

                    $key = 'notifyresData' . time() . rand(100000, 999999);
                    $content = '更是数据库失败，' . $e->getMessage();
                    Cache::set($key, $content);

                    return 'FAIL';
                }
            } else {
                $key = 'notifyresData' . time() . rand(100000, 999999);
                $content = '回调网关返回码不是00000000，' . $input;
                Cache::set($key, $content);
            }
        } else {
            $key = 'notifyresData' . time() . rand(100000, 999999);
            $content = '回调解析不对或者网关返回码不是00000000，' . $input;
            Cache::set($key, $content);
        }
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $id = Request::get('id');
            if (empty($id)) {
                return json($data);
            }

            $orderWhere = [
                'userId' => $userToken['userId'],
                'id' => $id,
            ];
            $orderInfo = Db::name('order')
                ->field('id, orderNo, totalPrice, product, address, remarks, status, createDate, payDate, freightName, freightCode, freightNo, freightTime, refundStatus, refundReason, refundAmount, refundRemark, refundApplyTime, refundTime, depositAmount, depositPaid, depositPayTime, depositPayTimeStamp, balanceAmount, balancePaid, balancePayTime, balancePayTimeStamp, balanceDueTime, balanceDueTimeStamp')
                ->where($orderWhere)
                ->find();

            if (!$orderInfo) {
                $data['msg'] = '订单不存在';
                return json($data);
            }

            $domain = Request::domain();

            // 处理订单状态
            $refundStatus = $orderInfo['refundStatus'] ?? 0;
            $displayStatus = getOrderDisplayStatus($orderInfo['status'], $refundStatus);

            // 判断是否为预售订单
            $isPresale = ($orderInfo['depositAmount'] > 0 || $orderInfo['balanceAmount'] > 0);
            $currentTime = time();

            // 判断是否可以支付定金
            $canPayDeposit = false;
            if ($isPresale && $orderInfo['depositPaid'] == 0 && $orderInfo['status'] == 8) {
                $canPayDeposit = true;
            }

            // 判断是否可以支付尾款
            $canPayBalance = false;
            if ($isPresale && $orderInfo['depositPaid'] == 1 && $orderInfo['balancePaid'] == 0 && $orderInfo['status'] == 10) {
                // 检查尾款是否到期（如果设置了到期时间且未过期，或者没有设置到期时间）
                if ($orderInfo['balanceDueTimeStamp'] == 0 || $currentTime <= $orderInfo['balanceDueTimeStamp']) {
                    $canPayBalance = true;
                }
            }

            $productIds = [];
            $productList = json_decode($orderInfo['product'] ?? '[]', true);
            if (is_array($productList)) {
                foreach ($productList as $productItem) {
                    if (!isset($productItem['title']) && !empty($productItem['productId'])) {
                        $productIds[] = intval($productItem['productId']);
                    }
                }
            }
            $productMap = $this->loadProductsMap($productIds);
            $products = $this->expandOrderProducts($orderInfo['product'] ?? '[]', $domain, $productMap);

            // 处理地址信息
            $addressInfo = [];
            if ($orderInfo['address']) {
                $addr = json_decode($orderInfo['address'], true);
                if (is_array($addr)) {
                    $addressInfo = $addr;
                }
            }

            $orderInfo['statusText'] = $displayStatus['text'];
            $orderInfo['statusColor'] = $displayStatus['color'];
            $orderInfo['refundStatusText'] = refundStatus($refundStatus);
            $orderInfo['productList'] = $products;
            $orderInfo['addressInfo'] = $addressInfo;
            $orderInfo['paymentMethod'] = '微信支付'; // 默认支付方式

            // 添加预售相关字段
            $orderInfo['isPresale'] = $isPresale;
            $orderInfo['canPayDeposit'] = $canPayDeposit;
            $orderInfo['canPayBalance'] = $canPayBalance;

            // 格式化金额
            $orderInfo['depositAmount'] = floatval($orderInfo['depositAmount'] ?? 0);
            $orderInfo['balanceAmount'] = floatval($orderInfo['balanceAmount'] ?? 0);

            // 尾款到期时间戳（前端倒计时使用）
            if ($orderInfo['balanceDueTimeStamp'] > 0) {
                $orderInfo['balanceDueTimeStamp'] = intval($orderInfo['balanceDueTimeStamp']);
            } else {
                $orderInfo['balanceDueTimeStamp'] = 0;
            }

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $orderInfo;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}
