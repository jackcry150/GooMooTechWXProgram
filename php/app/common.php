<?php
// 应用公共文件

use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

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

function lotteryRewardType($key = '')
{
    $data = [
        1 => '谢谢参与',
        2 => '猫饼',
        3 => '收藏卡',
        4 => '实物奖品',
    ];
    if ($key !== '' && $key !== null) {
        return isset($data[$key]) ? $data[$key] : '';
    }
    return $data;
}

function lotteryStockText($stock)
{
    if ((int)$stock < 0) {
        return '不限';
    }
    return (string) max(0, (int) $stock);
}

function app_code_options()
{
    static $options = null;
    if ($options !== null) {
        return $options;
    }

    $options = [
        'goomoo' => 'GooMoo',
        'hasuki' => 'Hasuki',
        'common' => '通用',
    ];

    try {
        if (table_has_column('setting', 'app_code')) {
            $codes = Db::name('setting')
                ->whereNotNull('app_code')
                ->where('app_code', '<>', '')
                ->column('app_code');
            foreach ($codes as $code) {
                $code = strtolower(trim((string) $code));
                if ($code !== '' && !isset($options[$code])) {
                    $options[$code] = strtoupper($code);
                }
            }
        }
    } catch (\Throwable $e) {
        // 数据库初始化前使用默认小程序选项。
    }

    return $options;
}

function normalize_app_code_value($appCode = '')
{
    $appCode = strtolower(trim((string) $appCode));
    $appCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $appCode);
    $options = app_code_options();
    if (!$appCode || !isset($options[$appCode])) {
        return 'goomoo';
    }
    return $appCode;
}

function app_code_text($appCode = '')
{
    $options = app_code_options();
    return $options[$appCode] ?? ($appCode ?: '未设置');
}

function current_app_code()
{
    static $appCode = null;
    if ($appCode !== null) {
        return $appCode;
    }

    $candidates = [
        trim((string) Request::header('X-App-Code', '')),
        trim((string) Request::param('appCode', '')),
        trim((string) Request::param('app_code', '')),
    ];

    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            $appCode = normalize_app_code_value($candidate);
            return $appCode;
        }
    }

    $host = strtolower((string) Request::host());
    if (strpos($host, 'hasuki') !== false) {
        $appCode = 'hasuki';
        return $appCode;
    }

    $appCode = 'goomoo';
    return $appCode;
}

function table_has_app_code($table)
{
    static $tableColumnMap = [];
    if (array_key_exists($table, $tableColumnMap)) {
        return $tableColumnMap[$table];
    }

    $defaultConnection = Config::get('database.default', 'mysql');
    $prefix = Config::get('database.connections.' . $defaultConnection . '.prefix', '');
    $tableName = $prefix . $table;
    $columns = Db::query("SHOW COLUMNS FROM `" . $tableName . "` LIKE 'app_code'");
    $exists = !empty($columns);
    $tableColumnMap[$table] = $exists;
    return $exists;
}

function build_app_code_priority_order($appCode = '')
{
    $appCode = $appCode ?: current_app_code();
    $appCode = addslashes($appCode);
    return "CASE 
        WHEN app_code = '{$appCode}' THEN 0
        WHEN app_code = 'common' THEN 1
        WHEN app_code = '' OR app_code IS NULL THEN 2
        ELSE 3
    END";
}

function apply_app_code_scope($query, $table, $appCode = '', $includeShared = true)
{
    if (!table_has_app_code($table)) {
        return $query;
    }

    $appCode = $appCode ?: current_app_code();
    $query->where(function ($subQuery) use ($appCode, $includeShared) {
        $subQuery->where('app_code', $appCode);
        if ($includeShared) {
            $subQuery->whereOr('app_code', 'common')
                ->whereOr('app_code', '')
                ->whereOrRaw('app_code IS NULL');
        }
    });

    return $query;
}

function find_brand_setting()
{
    $appCode = current_app_code();
    if (!table_has_app_code('setting')) {
        return Db::name('setting')->where('id', 1)->find();
    }

    $query = Db::name('setting');
    apply_app_code_scope($query, 'setting', $appCode, true);
    return $query->orderRaw(build_app_code_priority_order($appCode))
        ->order('id asc')
        ->find();
}

function app_code_cache_targets($appCode = '')
{
    if ($appCode === 'common') {
        return array_keys(app_code_options());
    }
    return [normalize_app_code_value($appCode ?: current_app_code())];
}

function table_has_column($table, $column)
{
    static $tableColumnMap = [];
    $cacheKey = $table . ':' . $column;
    if (array_key_exists($cacheKey, $tableColumnMap)) {
        return $tableColumnMap[$cacheKey];
    }

    $defaultConnection = Config::get('database.default', 'mysql');
    $prefix = Config::get('database.connections.' . $defaultConnection . '.prefix', '');
    $tableName = $prefix . $table;
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
    $columns = Db::query("SHOW COLUMNS FROM `" . $tableName . "` LIKE '" . $column . "'");
    $exists = !empty($columns);
    $tableColumnMap[$cacheKey] = $exists;
    return $exists;
}

function config_bool_value($value, $default = false)
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

function brand_payment_config($appCode = '')
{
    $appCode = normalize_app_code_value($appCode ?: current_app_code());
    $notifyUrl = env('HUIFU_NOTIFY_URL', '');
    if (!$notifyUrl) {
        $notifyUrl = rtrim(Request::domain(), '/') . '/api/order/notify';
    }

    $config = [
        'appCode' => $appCode,
        'wechatMiniAppId' => env('wechat.mini_appid', env('WECHAT_MINI_APPID', '')),
        'wechatMiniSecret' => env('wechat.mini_secret', env('WECHAT_MINI_SECRET', '')),
        'huifuMerchantId' => env('HUIFU_MERCHANT_ID', ''),
        'huifuPrivateKey' => env('HUIFU_PRIVATE_KEY', ''),
        'huifuNotifyUrl' => $notifyUrl,
        'paymentSplitEnabled' => config_bool_value(env('PAYMENT_SPLIT_ENABLED', false), false),
    ];

    if (table_has_app_code('setting')) {
        $query = Db::name('setting');
        apply_app_code_scope($query, 'setting', $appCode, true);
        $setting = $query->orderRaw(build_app_code_priority_order($appCode))
            ->order('id asc')
            ->find();
    } else {
        $setting = Db::name('setting')->where('id', 1)->find();
    }

    if (!$setting) {
        return $config;
    }

    $fieldMap = [
        'wechatMiniAppId' => 'wechatMiniAppId',
        'wechatMiniSecret' => 'wechatMiniSecret',
        'huifuMerchantId' => 'huifuMerchantId',
        'huifuPrivateKey' => 'huifuPrivateKey',
        'huifuNotifyUrl' => 'huifuNotifyUrl',
    ];
    foreach ($fieldMap as $column => $key) {
        if (array_key_exists($column, $setting) && trim((string) $setting[$column]) !== '') {
            $config[$key] = trim((string) $setting[$column]);
        }
    }
    if (array_key_exists('paymentSplitEnabled', $setting)) {
        $config['paymentSplitEnabled'] = config_bool_value($setting['paymentSplitEnabled'], $config['paymentSplitEnabled']);
    }

    return $config;
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
