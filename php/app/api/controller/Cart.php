<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Cart
{
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

            // 只接收产品ID、版本、数量，商品详情从数据库读取
            $productId = intval(Request::post('productId', 0));
            $version = Request::post('version', '');
            $quantity = intval(Request::post('quantity', 1));
            if (is_array($version)) {
                $version = implode('', $version);
            }
            $version = trim(strval($version));

            if (empty($productId) || empty($version) || $quantity <= 0) {
                $data['msg'] = '参数错误';
                return json($data);
            }

            // 获取商品信息（productCode、库存、限购等均从数据库读取）
            $productInfo = Db::name('product')->where('id', $productId)->lock(true)->find();
            if (!$productInfo) {
                $data['msg'] = '商品不存在';
                return json($data);
            }

            $productCode = strval($productInfo['productId'] ?? ''); // 管家婆商品ID
            $insert = [
                'userId' => intval($userToken['userId']),
                'productId' => intval($productId),
                'productCode' => $productCode,
                'version' => strval($version),
                'quantity' => intval($quantity),
            ];

            // 非预售商品检查库存
            if ($productInfo['type'] != 2) {
                if ($productInfo['stock'] <= 0) {
                    $data['msg'] = '商品已售罄';
                    return json($data);
                }
            }

            // 获取购物车中已有的数量
            $cartWhere = [
                'userId' => $insert['userId'],
                'productId' => $insert['productId'],
                'version' => $insert['version']
            ];
            $cartInfo = Db::name('cart')->where($cartWhere)->find();
            $currentCartQuantity = $cartInfo ? intval($cartInfo['quantity']) : 0;

            // 计算总购买数量（购物车数量 + 本次数量）
            $totalQuantity = $currentCartQuantity + $insert['quantity'];
            // 检查限购
            if ($productInfo['limitStock'] > 0) {
                // 统计用户已支付的订单中该商品的数量（从JSON字段查询）

                $userPurchasedOrders = Db::name('order')
                    ->where('userId', $insert['userId'])
                    ->whereIn('status', [2, 6, 7])
                    ->where('product', 'like', '%"productId":' . $insert['productId'] . '%')
                    ->select()
                    ->toArray();

                $purchasedQuantity = 0;
                foreach ($userPurchasedOrders as $order) {
                    $orderProducts = json_decode($order['product'], true);
                    if (is_array($orderProducts)) {
                        foreach ($orderProducts as $op) {
                            if (isset($op['productId']) && $op['productId'] == $insert['productId']) {
                                // 如果版本一致，才计算数量
                                if (!isset($op['version']) || $op['version'] == $insert['version']) {
                                    $purchasedQuantity += intval($op['quantity'] ?? 0);
                                }
                            }
                        }
                    }
                }

                // 已购买数量 + 购物车数量 + 本次数量 > 限购数量
                if ($purchasedQuantity + $totalQuantity > $productInfo['limitStock']) {
                    $availableQuantity = $productInfo['limitStock'] - $purchasedQuantity - $currentCartQuantity;
                    if ($availableQuantity <= 0) {
                        $data['msg'] = '您已达到限购数量，无法继续添加';
                        return json($data);
                    } else {
                        $data['msg'] = "限购{$productInfo['limitStock']}件，您还可购买{$availableQuantity}件";
                        return json($data);
                    }
                }
            }

            // 非预售商品检查库存
            if ($productInfo['type'] != 2) {
                if ($totalQuantity > $productInfo['stock']) {
                    $availableQuantity = $productInfo['stock'] - $currentCartQuantity;
                    if ($availableQuantity <= 0) {
                        $data['msg'] = '库存不足，无法继续添加';
                        return json($data);
                    } else {
                        $data['msg'] = "库存不足，最多可添加{$availableQuantity}件";
                        return json($data);
                    }
                }
            }
            // 添加到购物车
            $where = [
                'userId' => $insert['userId'],
                'productId' => $insert['productId'],
                'version' => $insert['version'],
            ];
            $info = Db::name('cart')->field('id, quantity')->where($where)->find();

            if (!$info) {
                // 添加创建时间和IP（只添加购物车表存在的字段）
                $insert['createTime'] = intval(time());
                $insert['createDate'] = strval(date('Y-m-d H:i:s'));
                $ip = Request::ip();
                // 确保IP是字符串，不是数组
                if (is_array($ip)) {
                    $ip = implode(',', $ip);
                }
                $insert['createIp'] = strval($ip);

                // 最终检查：确保所有值都是标量值（字符串或数字），创建新数组避免修改原数组
                $cleanInsert = [];
                foreach ($insert as $key => $value) {
                    if (is_array($value)) {
                        // 如果是数组，转换为JSON字符串（但购物车表不应该有数组字段）
                        $cleanInsert[$key] = json_encode($value);
                    } elseif (is_object($value)) {
                        $cleanInsert[$key] = strval($value);
                    } elseif (is_scalar($value)) {
                        // 标量值（字符串、数字、布尔值、null）直接使用
                        $cleanInsert[$key] = $value;
                    } else {
                        // 其他类型转为字符串
                        $cleanInsert[$key] = strval($value);
                    }
                }

                $res = Db::name('cart')->insert($cleanInsert);
            } else {
                $where = [
                    'id' => $info['id'],
                ];
                $update = [
                    'quantity' => $totalQuantity
                ];
                $res = Db::name('cart')->where($where)->update($update);
            }

            if ($res) {
                $where = [
                    'userId' => $userToken['userId']
                ];
                $count = Db::name('cart')->where($where)->count();

                $data['code'] = 200;
                $data['msg'] = '加入购物车成功';
                $data['data'] = [
                    'count' => $count
                ];
            } else {
                $data['code'] = 100;
                $data['msg'] = "加入购物车失败";
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function count()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                $data['code'] = 200;
                $data['msg'] = '成功';
                $data['data'] = [
                    'count' => 0,
                    'collect' => 0
                ];
                return json($data);
            }

            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                $data['code'] = 200;
                $data['msg'] = '成功';
                $data['data'] = [
                    'count' => 0,
                    'collect' => 0
                ];
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId']
            ];
            $count = Db::name('cart')->where($where)->count();

            $productId = Request::get('id');
            $collectWhere = [
                'userId' => $userToken['userId'],
                'productId' => $productId,
            ];
            $collect = Db::name('collect')->where($collectWhere)->count();

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = [
                'count' => $count,
                'collect' => $collect
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
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

            $list = Db::name('cart')->field('c.id, c.productId, c.productCode, p.title, p.subtitle, p.image, p.price, p.type, p.shippingTemplateId, c.version, c.quantity')
                ->alias('c')
                ->join('product p', 'p.id = c.productId')
                ->where($where)
                ->select()->toArray();

            // 获取运费模板信息
            $templateIds = array_filter(array_column($list, 'shippingTemplateId'));
            $templates = [];
            if (!empty($templateIds)) {
                $templates = Db::name('shipping_template')->where('id', 'in', $templateIds)->select()->toArray();
                $templates = array_column($templates, null, 'id');
            }

            foreach ($list as &$v) {
                if (isset($v['shippingTemplateId']) && $v['shippingTemplateId'] > 0 && isset($templates[$v['shippingTemplateId']])) {
                    $v['shippingTemplate'] = $templates[$v['shippingTemplateId']];
                }
            }
            $domain = Request::domain();
            foreach ($list as &$v) {
                $images = json_decode($v['image'], true);
                $v['image'] = $domain . $images[0];
                // 确保有selected字段（前端需要）
                $v['selected'] = $v['selected'] ?? true;
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

            $cartId = Request::post('id');
            if (empty($cartId)) {
                return json($data);
            }

            $where = [
                'userId' => $userToken['userId'],
                'id' => $cartId
            ];
            $res = Db::name('cart')->where($where)->delete();
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = '移除购物车成功';
            } else {
                $data['code'] = 100;
                $data['msg'] = '移除购物车失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function cancelAll()
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

            $cart = Request::post();
            if (count($cart) == 0) {
                return json($data);
            }

            $cartIds = array_column($cart, 'id');
            $res = Db::name('cart')->delete($cartIds);
            if ($res) {

                $where = [
                    'userId' => $userToken['userId']
                ];

                $list = Db::name('cart')->field('c.id, c.productId, p.title, p.subtitle, p.image, p.price, p.type, c.version, c.quantity')
                    ->alias('c')
                    ->join('product p', 'p.id = c.productId')
                    ->where($where)
                    ->select()->toArray();
                $domain = Request::domain();
                foreach ($list as &$v) {
                    $images = json_decode($v['image'], true);
                    $v['image'] = $domain . $images[0];
                }

                $data['code'] = 200;
                $data['msg'] = '移除购物车成功';
                $data['data'] = $list;
            } else {
                $data['code'] = 100;
                $data['msg'] = '移除购物车失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function quantity()
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

            $id = intval(Request::post('id'));
            $quantity = intval(Request::post('quantity'));
            if (empty($id) || $quantity <= 0) {
                $data['msg'] = '参数错误';
                return json($data);
            }
            $where = ['userId' => $userToken['userId'], 'id' => $id];
            $cartItem = Db::name('cart')->where($where)->find();
            if (!$cartItem) {
                $data['msg'] = '购物车项不存在';
                return json($data);
            }

            $productId = intval($cartItem['productId']);
            $version = strval($cartItem['version'] ?? '');
            $productInfo = Db::name('product')->where('id', $productId)->find();
            if (!$productInfo) {
                $data['msg'] = '商品不存在';
                return json($data);
            }

            // 同一商品同版本在其他购物车项中的数量（不含当前项）
            $otherCartQty = Db::name('cart')
                ->where('userId', $userToken['userId'])
                ->where('productId', $productId)
                ->where('version', $version)
                ->where('id', '<>', $id)
                ->sum('quantity');
            $totalCartQty = intval($otherCartQty) + $quantity;

            // 限购校验
            if ($productInfo['limitStock'] > 0) {
                $userPurchasedOrders = Db::name('order')
                    ->where('userId', $userToken['userId'])
                    ->whereIn('status', [2, 6, 7])
                    ->where('product', 'like', '%"productId":' . $productId . '%')
                    ->select()->toArray();
                $purchasedQuantity = 0;
                foreach ($userPurchasedOrders as $order) {
                    $orderProducts = json_decode($order['product'], true);
                    if (is_array($orderProducts)) {
                        foreach ($orderProducts as $op) {
                            if (isset($op['productId']) && $op['productId'] == $productId &&
                                (!isset($op['version']) || $op['version'] == $version)) {
                                $purchasedQuantity += intval($op['quantity'] ?? 0);
                            }
                        }
                    }
                }
                if ($purchasedQuantity + $totalCartQty > $productInfo['limitStock']) {
                    $available = $productInfo['limitStock'] - $purchasedQuantity - intval($otherCartQty);
                    $data['msg'] = $available <= 0 ? '您已达到限购数量，无法继续添加' : "限购{$productInfo['limitStock']}件，您还可购买{$available}件";
                    return json($data);
                }
            }

            // 库存校验（非预售）
            if ($productInfo['type'] != 2) {
                if ($totalCartQty > $productInfo['stock']) {
                    $available = $productInfo['stock'] - intval($otherCartQty);
                    $data['msg'] = $available <= 0 ? '库存不足，无法继续添加' : "库存不足，最多可购买{$available}件";
                    return json($data);
                }
            }

            $update = ['quantity' => $quantity];
            $res = Db::name('cart')->where($where)->update($update);
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = '成功';
            } else {
                $data['code'] = 100;
                $data['msg'] = '更新失败';
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}