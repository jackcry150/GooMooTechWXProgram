<?php
// 应用公共文件

/**
 * 加密
 * @param $data
 * @return false|string
 */
function encrypt($data)
{
    $key = 'W1R3T5G7J9O5L6N3';
    return openssl_encrypt($data, 'AES-128-ECB', $key, 0, '');
}


/**
 * 解密
 * @param $data
 * @return false|string
 */
function decrypt($data)
{
    $key = 'W1R3T5G7J9O5L6N3';
    return openssl_decrypt($data, 'AES-128-ECB', $key, 0, '');
}

/**
 * @param $key
 * @return array|array[]
 */
function adType($key = '')
{
    $data = [
        1 => '首页banner',
        2 => '加入官方社群海报'

    ];
    if ($key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}

function status($key = '')
{
    $data = [
        1 => '开启',
        2 => '关闭'
    ];
    if ($key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}

function productType($key = '')
{
    $data = [
        1 => '在售',
        2 => '预售',
//        3 => '补款',
//        4 => '再贩'
    ];
    if ($key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}


function productMode($key = '')
{
    $data = [
        1 => '否',
        2 => '是'
    ];
    if ($key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}

function serverType($key = '')
{
    $data = [
        1 => '微信官方社群',
        2 => '官方账号',
        3 => '微信客服'
    ];
    if ($key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}

/**
 * 获取订单状态配置
 * 统一管理订单状态：状态码、名称、颜色、描述
 * @return array
 */
function getOrderStatusConfig()
{
    return [
        1 => [
            'name' => '待支付',
            'color' => '#dc0000',
            'desc' => '订单已创建，等待用户支付'
        ],
        2 => [
            'name' => '待发货',
            'color' => '#1ccf00',
            'desc' => '用户已支付，等待商家发货'
        ],
        3 => [
            'name' => '支付失败',
            'color' => '#cccccc',
            'desc' => '支付过程中发生错误'
        ],
        4 => [
            'name' => '取消支付',
            'color' => '#cccccc',
            'desc' => '用户主动取消订单'
        ],
        5 => [
            'name' => '删除订单',
            'color' => '#cccccc',
            'desc' => '订单已被删除'
        ],
        6 => [
            'name' => '待收货',
            'color' => '#1ccf00',
            'desc' => '商家已发货，等待用户确认收货'
        ],
        7 => [
            'name' => '订单完成',
            'color' => '#1ccf00',
            'desc' => '订单已完成'
        ],
        8 => [
            'name' => '已预定',
            'color' => '#1ccf00',
            'desc' => '预售订单已创建，等待支付定金'
        ],
        9 => [
            'name' => '退款成功',
            'color' => '#f39c12',
            'desc' => '订单已退款'
        ],
        10 => [
            'name' => '已付定金待付尾款',
            'color' => '#f39c12',
            'desc' => '预售订单定金已支付，等待支付尾款'
        ],
    ];
}

/**
 * 获取退款状态配置
 * @return array
 */
function getRefundStatusConfig()
{
    return [
        0 => [
            'name' => '未申请',
            'color' => '#999999',
            'desc' => '未申请退款'
        ],
        1 => [
            'name' => '申请退款中',
            'color' => '#f39c12',
            'desc' => '用户已申请退款，等待商家处理'
        ],
        2 => [
            'name' => '同意退款',
            'color' => '#1ccf00',
            'desc' => '商家已同意退款'
        ],
        3 => [
            'name' => '拒绝退款',
            'color' => '#dc0000',
            'desc' => '商家已拒绝退款'
        ],
    ];
}

/**
 * 获取订单状态文本
 * @param string|int $key 状态码，不传则返回所有状态
 * @return string|array
 */
function orderStatus($key = '')
{
    $config = getOrderStatusConfig();
    if ($key !== '' && $key !== null) {
        return isset($config[$key]) ? $config[$key]['name'] : '';
    }
    // 返回简化的 key => name 数组
    $data = [];
    foreach ($config as $k => $v) {
        $data[$k] = $v['name'];
    }
    return $data;
}

/**
 * 获取订单状态颜色
 * @param string|int $key 状态码，不传则返回所有状态颜色
 * @return string|array
 */
function orderStatusClass($key = '')
{
    $config = getOrderStatusConfig();
    if ($key !== '' && $key !== null) {
        return isset($config[$key]) ? $config[$key]['color'] : '';
    }
    // 返回简化的 key => color 数组
    $data = [];
    foreach ($config as $k => $v) {
        $data[$k] = $v['color'];
    }
    return $data;
}

/**
 * 获取订单显示状态（结合退款状态）
 * @param int $status 订单状态
 * @param int $refundStatus 退款状态
 * @return array ['text' => '状态文本', 'color' => '状态颜色']
 */
function getOrderDisplayStatus($status, $refundStatus = 0)
{
    // 如果有退款状态，优先显示退款相关状态
    $refundConfig = getRefundStatusConfig();
    if ($refundStatus > 0 && isset($refundConfig[$refundStatus])) {
        return [
            'text' => $refundConfig[$refundStatus]['name'],
            'color' => $refundConfig[$refundStatus]['color']
        ];
    }

    // 否则显示订单状态
    return [
        'text' => orderStatus($status),
        'color' => orderStatusClass($status)
    ];
}

/**
 * 获取退款状态文本
 * @param string|int $key 状态码，不传则返回所有状态
 * @return string|array
 */
function refundStatus($key = '')
{
    $config = getRefundStatusConfig();
    if ($key !== '' && $key !== null) {
        return isset($config[$key]) ? $config[$key]['name'] : '';
    }
    // 返回简化的 key => name 数组
    $data = [];
    foreach ($config as $k => $v) {
        $data[$k] = $v['name'];
    }
    return $data;
}

/**
 * 判断订单是否可以取消
 * @param int $status 订单状态
 * @return bool
 */
function canCancelOrder($status)
{
    return in_array($status, [1, 8]);
}

/**
 * 判断订单是否可以支付
 * @param int $status 订单状态
 * @return bool
 */
function canPayOrder($status)
{
    return in_array($status, [1, 8, 10]);
}

/**
 * 判断订单是否可以发货
 * @param int $status 订单状态
 * @return bool
 */
function canShipOrder($status)
{
    return $status == 1;
}

/**
 * 判断订单是否可以申请退款
 * @param int $status 订单状态
 * @param int $refundStatus 退款状态
 * @return bool
 */
function canRefundOrder($status, $refundStatus = 0)
{
    return in_array($status, [1, 6]) && $refundStatus == 0;
}
