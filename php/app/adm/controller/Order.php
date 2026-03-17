<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\facade\Cache;

class Order
{
    // 小程序配置：可在 .env 中覆盖
    private $miniAppId = '';
    private $miniAppSecret = '';
    // 到货确认订阅消息模板 ID（微信后台配置）
    private $arrivalTemplateId = '';
    // 到货确认模板字段名（示例：thing1/character_string2/time3）
    private $arrivalFieldTitle = '';
    private $arrivalFieldOrderNo = '';
    private $arrivalFieldTime = '';
    private $arrivalFieldTip = '';

    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
        $this->miniAppId = env('wechat.mini_appid', env('WECHAT_MINI_APPID', 'wxfcc20942c4074693'));
        $this->miniAppSecret = env('wechat.mini_secret', env('WECHAT_MINI_SECRET', 'fac8bbc449de6a99c4fa96ad8b6729e0'));
        $this->arrivalTemplateId = env('wechat.arrival_confirm_template_id', env('WECHAT_ARRIVAL_CONFIRM_TEMPLATE_ID', ''));
        $this->arrivalFieldTitle = env('wechat.arrival_field_title', env('WECHAT_ARRIVAL_FIELD_TITLE', 'thing1'));
        $this->arrivalFieldOrderNo = env('wechat.arrival_field_orderno', env('WECHAT_ARRIVAL_FIELD_ORDERNO', 'character_string2'));
        $this->arrivalFieldTime = env('wechat.arrival_field_time', env('WECHAT_ARRIVAL_FIELD_TIME', 'time3'));
        $this->arrivalFieldTip = env('wechat.arrival_field_tip', env('WECHAT_ARRIVAL_FIELD_TIP', 'thing4'));
    }

    public function index()
    {
        $orderNo = Request::get('orderNo', '');
        $status = Request::get('status', '');
        $arrivalConfirmStatus = Request::get('arrivalConfirmStatus', '');
        $nickName = Request::get('nickName', '');
        $phone = Request::get('phone', '');
        $productName = Request::get('productName', '');

        $where = [];
        if ($orderNo) {
            $where[] = ['orderNo', 'like', '%' . $orderNo . '%'];
        }

        $refundStatusFilter = '';
        if ($status !== '') {
            if (strpos($status, 'refund_') === 0) {
                $refundStatusFilter = substr($status, 7);
            } else {
                $where[] = ['status', '=', $status];
            }
        }

        if ($arrivalConfirmStatus !== '') {
            $where[] = ['arrivalConfirmStatus', '=', intval($arrivalConfirmStatus)];
        }

        $now = date('Y-m-d H:i:s');
        Db::name('order')
            ->where('arrivalConfirmStatus', 1)
            ->whereNotNull('arrivalConfirmDeadlineAt')
            ->where('arrivalConfirmDeadlineAt', '<', $now)
            ->update(['arrivalConfirmStatus' => 3]);

        $list = Db::name('order')
            ->alias('o')
            ->leftJoin('user u', 'o.userId = u.id')
            ->field('o.id, o.orderNo, o.totalPrice, o.product, o.address, o.remarks, o.status, o.arrivalConfirmStatus, o.arrivalNotifiedAt, o.arrivalConfirmDeadlineAt, o.arrivalConfirmedAt, o.createDate, o.payDate, o.freightName, o.freightCode, o.freightNo, o.freightTime, o.refundStatus, o.refundReason, o.refundAmount, o.refundApplyTime, o.refundTime, o.refundRemark, u.nickName, u.phone')
            ->where($where);

        if ($nickName) {
            $list = $list->where('u.nickName', 'like', '%' . $nickName . '%');
        }
        if ($phone) {
            $list = $list->where('u.phone', 'like', '%' . $phone . '%');
        }
        if ($productName) {
            $list = $list->where('o.product', 'like', '%' . $productName . '%');
        }
        if ($refundStatusFilter !== '') {
            $list = $list->where('o.refundStatus', '=', $refundStatusFilter);
        }

        $list = $list->order('o.id desc')->paginate(20, false, [
            'query' => request()->param(),
        ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        foreach ($list as &$val) {
            $refundStatus = $val['refundStatus'] ?? 0;
            $displayStatus = getOrderDisplayStatus($val['status'], $refundStatus);
            $val['statusText'] = $displayStatus['text'];
            $val['statusColor'] = $displayStatus['color'];

            $val['refundStatusText'] = refundStatus($refundStatus);
            $val['refundStatus'] = $refundStatus;

            $val['arrivalConfirmStatus'] = intval($val['arrivalConfirmStatus'] ?? 0);
            $val['arrivalConfirmStatusText'] = self::arrivalConfirmStatusText($val['arrivalConfirmStatus']);

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
        View::assign('arrivalConfirmStatus', $arrivalConfirmStatus);
        View::assign('nickName', $nickName);
        View::assign('phone', $phone);
        View::assign('productName', $productName);
        View::assign('orderStatus', orderStatus());
        View::assign('arrivalConfirmStatusMap', self::arrivalConfirmStatusMap());

        return View::fetch();
    }

    public static function arrivalConfirmStatusMap()
    {
        return [
            0 => '待通知',
            1 => '已通知待确认',
            2 => '已确认',
            3 => '超时未确认',
        ];
    }

    public static function arrivalConfirmStatusText($status)
    {
        $map = self::arrivalConfirmStatusMap();
        return $map[$status] ?? '未知状态';
    }
    public function markArrival()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $id = intval(Request::post('id', 0));
        $deadlineHours = intval(Request::post('deadlineHours', 72));

        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($deadlineHours <= 0) {
            $deadlineHours = 72;
        }
        if ($deadlineHours > 720) {
            $deadlineHours = 720;
        }

        $orderInfo = Db::name('order')->field('id, status, arrivalConfirmStatus')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if (intval($orderInfo['status']) !== 6) {
            return json(['code' => 0, 'msg' => '仅待收货订单可标记到货']);
        }
        if (intval($orderInfo['arrivalConfirmStatus']) === 2) {
            return json(['code' => 0, 'msg' => '该订单已确认到货']);
        }

        $deadlineAt = date('Y-m-d H:i:s', time() + $deadlineHours * 3600);
        $updateData = [
            'arrivalConfirmStatus' => 0,
            'arrivalNotifiedAt' => null,
            'arrivalConfirmDeadlineAt' => $deadlineAt,
            'arrivalConfirmedAt' => null,
            'arrivalConfirmSnapshot' => null,
            'arrivalConfirmRemark' => '',
        ];

        $res = Db::name('order')->where('id', $id)->update($updateData);
        if ($res === false) {
            return json(['code' => 0, 'msg' => '标记到货失败']);
        }

        $notifyRes = $this->sendArrivalConfirmNotify($id);
        if ($notifyRes['ok']) {
            return json([
                'code' => 1,
                'msg' => '标记到货成功，已发送确认通知',
                'data' => [
                    'arrivalConfirmStatus' => 1,
                    'arrivalConfirmStatusText' => self::arrivalConfirmStatusText(1),
                    'arrivalConfirmDeadlineAt' => $deadlineAt,
                    'notifyResult' => $notifyRes['msg'],
                ],
            ]);
        }

        return json([
            'code' => 1,
            'msg' => '标记到货成功，但通知发送失败：' . $notifyRes['msg'],
            'data' => [
                'arrivalConfirmStatus' => 0,
                'arrivalConfirmStatusText' => self::arrivalConfirmStatusText(0),
                'arrivalConfirmDeadlineAt' => $deadlineAt,
                'notifyResult' => $notifyRes['msg'],
            ],
        ]);
    }
    public function retryArrivalNotify()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = intval(Request::post('id', 0));
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        $orderInfo = Db::name('order')->field('id,status,arrivalConfirmStatus')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if (intval($orderInfo['status']) !== 6) {
            return json(['code' => 0, 'msg' => '仅待收货订单允许重发确认通知']);
        }
        if (intval($orderInfo['arrivalConfirmStatus']) === 2) {
            return json(['code' => 0, 'msg' => '该订单已确认到货，无需重发通知']);
        }

        $notifyRes = $this->sendArrivalConfirmNotify($id);
        if (!$notifyRes['ok']) {
            return json(['code' => 0, 'msg' => '重发失败：' . $notifyRes['msg']]);
        }
        return json(['code' => 1, 'msg' => '重发成功，用户将收到确认通知']);
    }

    private function sendArrivalConfirmNotify($orderId)
    {
        $orderInfo = Db::name('order')
            ->field('id, userId, orderNo, arrivalConfirmDeadlineAt')
            ->where('id', $orderId)
            ->find();
        if (!$orderInfo) {
            return ['ok' => false, 'msg' => '订单不存在'];
        }

        $userInfo = Db::name('user')->field('openId')->where('id', $orderInfo['userId'])->find();
        $openId = $userInfo['openId'] ?? '';
        if (!$openId) {
            return ['ok' => false, 'msg' => '用户缺少 openId'];
        }
        if (!$this->arrivalTemplateId) {
            return ['ok' => false, 'msg' => '未配置到货确认订阅模板 ID'];
        }

        $tokenRes = $this->getWechatAccessToken();
        if (!$tokenRes['ok']) {
            return $tokenRes;
        }

        $templateData = [];
        $titleKey = $this->normalizeTemplateFieldKey($this->arrivalFieldTitle);
        $orderNoKey = $this->normalizeTemplateFieldKey($this->arrivalFieldOrderNo);
        $timeKey = $this->normalizeTemplateFieldKey($this->arrivalFieldTime);
        $tipKey = $this->normalizeTemplateFieldKey($this->arrivalFieldTip);
        if ($titleKey) {
            $templateData[$titleKey] = ['value' => '预定商品已到货'];
        }
        if ($orderNoKey) {
            $templateData[$orderNoKey] = ['value' => $orderInfo['orderNo']];
        }
        if ($timeKey) {
            $templateData[$timeKey] = ['value' => date('Y-m-d H:i:s')];
        }
        if ($tipKey) {
            $templateData[$tipKey] = ['value' => '请尽快确认收货信息'];
        }
        if (empty($templateData)) {
            return ['ok' => false, 'msg' => '模板字段映射为空或格式错误，请配置 WECHAT_ARRIVAL_FIELD_*'];
        }
        $payload = [
            'touser' => $openId,
            'template_id' => $this->arrivalTemplateId,
            'page' => '/pages/order/detail?id=' . $orderInfo['id'],
            'data' => $templateData,
            'miniprogram_state' => 'formal',
        ];

        $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $tokenRes['token'];
        $resp = $this->httpPostJson($sendUrl, $payload);
        if (!$resp['ok']) {
            return $resp;
        }
        $resData = json_decode($resp['body'], true);
        if (!is_array($resData) || !isset($resData['errcode'])) {
            return ['ok' => false, 'msg' => '微信返回格式异常'];
        }
        if (intval($resData['errcode']) !== 0) {
            return ['ok' => false, 'msg' => '微信发送失败：' . ($resData['errmsg'] ?? 'unknown')];
        }

        Db::name('order')->where('id', $orderInfo['id'])->update([
            'arrivalConfirmStatus' => 1,
            'arrivalNotifiedAt' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'msg' => '发送成功'];
    }


    private function normalizeTemplateFieldKey($key)
    {
        $key = trim((string)$key);
        if ($key === '') {
            return '';
        }
        // 微信订阅消息字段名通常为 thing1/time2/character_string3 等格式
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            return $key;
        }
        return '';
    }

    private function getWechatAccessToken()
    {
        if (!$this->miniAppId || !$this->miniAppSecret) {
            return ['ok' => false, 'msg' => '缺少微信小程序配置'];
        }

        $cacheKey = 'wx_access_token_' . md5($this->miniAppId);
        $token = Cache::get($cacheKey);
        if ($token) {
            return ['ok' => true, 'token' => $token, 'msg' => 'ok'];
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential'
            . '&appid=' . urlencode($this->miniAppId)
            . '&secret=' . urlencode($this->miniAppSecret);
        $resp = $this->httpGet($url);
        if (!$resp['ok']) {
            return $resp;
        }

        $data = json_decode($resp['body'], true);
        if (!is_array($data) || empty($data['access_token'])) {
            return ['ok' => false, 'msg' => '获取 access_token 失败'];
        }
        $expiresIn = intval($data['expires_in'] ?? 7200);
        Cache::set($cacheKey, $data['access_token'], max(60, $expiresIn - 120));
        return ['ok' => true, 'token' => $data['access_token'], 'msg' => 'ok'];
    }

    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return ['ok' => false, 'msg' => 'HTTP 请求失败：' . $err];
        }
        return ['ok' => true, 'body' => $body, 'msg' => 'ok'];
    }

    private function httpPostJson($url, $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return ['ok' => false, 'msg' => 'HTTP 请求失败：' . $err];
        }
        return ['ok' => true, 'body' => $body, 'msg' => 'ok'];
    }
    public function ship()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            $freightName = $post['freightName'] ?? '';
            $freightCode = $post['freightCode'] ?? '';
            $freightNo = $post['freightNo'] ?? '';

            if (!$id) {
                return json(['msg' => '参数错误', 'code' => 0]);
            }
            if (!$freightName) {
                return json(['msg' => '请输入物流公司名称', 'code' => 0]);
            }
            if (!$freightNo) {
                return json(['msg' => '请输入物流单号', 'code' => 0]);
            }

            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                return json(['msg' => '订单信息不存在', 'code' => 0]);
            }
            if ($orderInfo['status'] != 2) {
                return json(['msg' => '订单状态不正确，仅可对待发货订单进行发货操作', 'code' => 0]);
            }

            $updateData = [
                'status' => 6,
                'freightName' => $freightName,
                'freightCode' => $freightCode,
                'freightNo' => $freightNo,
                'freightTime' => date('Y-m-d H:i:s'),
                'shipTime' => time(),
            ];

            $res = Db::name('order')->where('id', $id)->update($updateData);
            if ($res !== false) {
                return json(['msg' => '发货成功', 'code' => 1]);
            }
            return json(['msg' => '发货失败', 'code' => 0]);
        }

        $id = Request::get('id', 0);
        if (!$id) {
            return json(['msg' => '参数错误', 'code' => 0]);
        }

        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['msg' => '订单信息不存在', 'code' => 0]);
        }

        View::assign('orderInfo', $orderInfo);
        return View::fetch();
    }
    public function detail()
    {
        $id = Request::get('id', 0);
        if (!$id) {
            return json(['msg' => '参数错误', 'code' => 0]);
        }

        $orderInfo = Db::name('order')
            ->alias('o')
            ->leftJoin('user u', 'o.userId = u.id')
            ->field('o.*, u.nickName, u.phone as userPhone, u.avatar')
            ->where('o.id', $id)
            ->find();

        if (!$orderInfo) {
            return json(['msg' => '订单信息不存在', 'code' => 0]);
        }

        $userInfo = Db::name('user')->field('nickName, phone, avatar')->where('id', $orderInfo['userId'])->find();
        $orderInfo['nickName'] = isset($userInfo['nickName']) ? $userInfo['nickName'] : '-';
        $orderInfo['userPhone'] = isset($userInfo['phone']) ? $userInfo['phone'] : '-';
        $orderInfo['avatar'] = isset($userInfo['avatar']) ? $userInfo['avatar'] : '-';

        $refundStatus = $orderInfo['refundStatus'] ?? 0;
        $displayStatus = getOrderDisplayStatus($orderInfo['status'], $refundStatus);
        $orderInfo['statusText'] = $displayStatus['text'];
        $orderInfo['statusColor'] = $displayStatus['color'];
        $orderInfo['refundStatus'] = $refundStatus;

        $domain = Request::domain();
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

        if ($orderInfo['avatar'] && strpos($orderInfo['avatar'], 'http') === false && strpos($orderInfo['avatar'], 'data:image') === false) {
            $orderInfo['avatar'] = $domain . $orderInfo['avatar'];
        }

        View::assign('orderInfo', $orderInfo);
        return View::fetch();
    }
    public function refundApply()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            $refundReason = $post['refundReason'] ?? '';
            $refundAmount = $post['refundAmount'] ?? 0;

            if (!$id) {
                return json(['msg' => '参数错误', 'code' => 0]);
            }

            $orderInfo = Db::name('order')->where('id', $id)->find();
            if (!$orderInfo) {
                return json(['msg' => '订单信息不存在', 'code' => 0]);
            }

            if (!in_array($orderInfo['status'], [2, 6])) {
                return json(['msg' => '当前订单状态不允许申请退款', 'code' => 0]);
            }

            if ($orderInfo['refundStatus'] == 1) {
                return json(['msg' => '该订单已申请退款，请勿重复申请', 'code' => 0]);
            }

            $updateData = [
                'refundStatus' => 1,
                'refundReason' => $refundReason,
                'refundAmount' => $refundAmount ?: $orderInfo['totalPrice'],
                'refundApplyTime' => date('Y-m-d H:i:s'),
                'refundApplyTimeStamp' => time(),
            ];

            $res = Db::name('order')->where('id', $id)->update($updateData);
            if ($res !== false) {
                return json(['msg' => '申请退款提交成功', 'code' => 1]);
            }
            return json(['msg' => '申请退款提交失败', 'code' => 0]);
        }

        $id = Request::get('id', 0);
        if (!$id) {
            return json(['msg' => '参数错误', 'code' => 0]);
        }

        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['msg' => '订单信息不存在', 'code' => 0]);
        }

        View::assign('orderInfo', $orderInfo);
        return View::fetch();
    }
    public function refundAgree()
    {
        if (!Request::isPost()) {
            return json(['msg' => '请求方式错误', 'code' => 0]);
        }

        $post = Request::post();
        $id = $post['id'] ?? 0;
        $refundRemark = $post['refundRemark'] ?? '';

        if (!$id) {
            return json(['msg' => '参数错误', 'code' => 0]);
        }

        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['msg' => '订单信息不存在', 'code' => 0]);
        }

        if ($orderInfo['refundStatus'] != 1) {
            return json(['msg' => '当前订单未处于申请退款状态', 'code' => 0]);
        }

        $updateData = [
            'refundStatus' => 2,
            'refundRemark' => $refundRemark,
            'refundTime' => date('Y-m-d H:i:s'),
            'refundTimeStamp' => time(),
            'status' => 9,
        ];

        $res = Db::name('order')->where('id', $id)->update($updateData);
        if ($res !== false) {
            return json(['msg' => '退款已同意并完成', 'code' => 1]);
        }
        return json(['msg' => '退款同意失败', 'code' => 0]);
    }
    public function refundRefuse()
    {
        if (!Request::isPost()) {
            return json(['msg' => '请求方式错误', 'code' => 0]);
        }

        $post = Request::post();
        $id = $post['id'] ?? 0;
        $refundRemark = $post['refundRemark'] ?? '';

        if (!$id) {
            return json(['msg' => '参数错误', 'code' => 0]);
        }
        if (!$refundRemark) {
            return json(['msg' => '请输入拒绝原因', 'code' => 0]);
        }

        $orderInfo = Db::name('order')->where('id', $id)->find();
        if (!$orderInfo) {
            return json(['msg' => '订单信息不存在', 'code' => 0]);
        }

        if ($orderInfo['refundStatus'] != 1) {
            return json(['msg' => '当前订单未处于申请退款状态', 'code' => 0]);
        }

        $updateData = [
            'refundStatus' => 3,
            'refundRemark' => $refundRemark,
            'refundTime' => date('Y-m-d H:i:s'),
            'refundTimeStamp' => time(),
        ];

        $res = Db::name('order')->where('id', $id)->update($updateData);
        if ($res !== false) {
            return json(['msg' => '已拒绝退款申请', 'code' => 1]);
        }
        return json(['msg' => '拒绝退款失败', 'code' => 0]);
    }
}

