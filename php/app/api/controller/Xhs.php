<?php

namespace app\api\controller;

use Exception;
use app\common\service\XhsOrderSyncService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Xhs
{
    public function oauth()
    {
        $path = strtolower(Request::pathinfo());
        if (strpos($path, 'refresh') !== false) {
            return $this->refreshToken();
        }

        return $this->oauthCallback();
    }

    /**
     * 小红书 OAuth 授权回调。
     *
     * 小红书会把 code/state 等参数回传到：
     * https://api.goomooplay.com/api/xhs/oauth/callback
     */
    public function oauthCallback()
    {
        $params = Request::param();
        $payload = [
            'code' => $params['code'] ?? '',
            'state' => $params['state'] ?? '',
            'shopId' => $params['shop_id'] ?? ($params['shopId'] ?? ''),
            'sellerId' => $params['seller_id'] ?? ($params['sellerId'] ?? ''),
            'raw' => $params,
            'ip' => Request::ip(),
            'time' => date('Y-m-d H:i:s'),
        ];

        Cache::set('xhs_oauth_callback_latest', $payload, 86400);

        if ($payload['code'] === '') {
            return $this->jsonResponse(100, 'missing code', [
                'received' => true,
                'hasCode' => false,
                'time' => $payload['time'],
            ]);
        }

        try {
            $result = $this->requestGateway('oauth.getAccessToken', [
                'code' => $payload['code'],
            ]);
        } catch (Exception $e) {
            Cache::set('xhs_oauth_token_error_latest', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ], 86400);

            return $this->jsonResponse(100, 'exchange token failed: ' . $e->getMessage(), [
                'received' => true,
                'hasCode' => true,
                'hasAccessToken' => false,
                'time' => $payload['time'],
            ]);
        }

        if (empty($result['success']) || empty($result['data']) || empty($result['data']['accessToken'])) {
            Cache::set('xhs_oauth_token_error_latest', [
                'response' => $this->maskTokenResponse($result),
                'time' => date('Y-m-d H:i:s'),
            ], 86400);

            return $this->jsonResponse(100, $result['error_msg'] ?? ($result['msg'] ?? 'exchange token failed'), [
                'received' => true,
                'hasCode' => true,
                'hasAccessToken' => false,
                'errorCode' => $result['error_code'] ?? null,
                'time' => $payload['time'],
            ]);
        }

        $token = $result['data'];
        $this->saveToken($token, $result);

        return $this->jsonResponse(200, 'success', [
            'received' => true,
            'hasCode' => true,
            'hasAccessToken' => true,
            'sellerId' => $token['sellerId'] ?? '',
            'sellerName' => $token['sellerName'] ?? '',
            'accessTokenExpiresAt' => $this->formatTokenTime($token['accessTokenExpiresAt'] ?? 0),
            'refreshTokenExpiresAt' => $this->formatTokenTime($token['refreshTokenExpiresAt'] ?? 0),
            'time' => $payload['time'],
        ]);
    }

    /**
     * 手动/定时刷新小红书 accessToken。
     *
     * 可通过：https://api.goomooplay.com/api/xhs/oauth/refresh 触发。
     */
    public function refreshToken()
    {
        if (!$this->validateRefreshKey()) {
            return $this->jsonResponse(403, 'forbidden', [
                'refreshed' => false,
                'time' => date('Y-m-d H:i:s'),
            ]);
        }

        $storedToken = $this->getStoredToken();
        if (empty($storedToken['refreshToken'])) {
            return $this->jsonResponse(100, 'refreshToken missing', [
                'refreshed' => false,
                'time' => date('Y-m-d H:i:s'),
            ]);
        }

        try {
            $result = $this->requestGateway('oauth.refreshToken', [
                'refreshToken' => $storedToken['refreshToken'],
            ]);
        } catch (Exception $e) {
            Cache::set('xhs_oauth_refresh_error_latest', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ], 86400);

            return $this->jsonResponse(100, 'refresh token failed: ' . $e->getMessage(), [
                'refreshed' => false,
                'time' => date('Y-m-d H:i:s'),
            ]);
        }

        if (empty($result['success']) || empty($result['data']) || empty($result['data']['accessToken'])) {
            Cache::set('xhs_oauth_refresh_error_latest', [
                'response' => $this->maskTokenResponse($result),
                'time' => date('Y-m-d H:i:s'),
            ], 86400);

            return $this->jsonResponse(100, $result['error_msg'] ?? ($result['msg'] ?? 'refresh token failed'), [
                'refreshed' => false,
                'errorCode' => $result['error_code'] ?? null,
                'time' => date('Y-m-d H:i:s'),
            ]);
        }

        $token = $this->mergeTokenPayload($result['data'], $storedToken);
        $this->saveToken($token, $result);

        return $this->jsonResponse(200, 'success', [
            'refreshed' => true,
            'sellerId' => $token['sellerId'] ?? '',
            'sellerName' => $token['sellerName'] ?? '',
            'accessTokenExpiresAt' => $this->formatTokenTime($token['accessTokenExpiresAt'] ?? 0),
            'refreshTokenExpiresAt' => $this->formatTokenTime($token['refreshTokenExpiresAt'] ?? 0),
            'time' => date('Y-m-d H:i:s'),
        ]);
    }



    public function order($action = '')
    {
        $path = strtolower(Request::pathinfo());
        if ($action === '') {
            $parts = explode('/', trim($path, '/'));
            $action = end($parts) ?: '';
        }

        if ($action === 'status') {
            return $this->bindStatus();
        }
        if ($action === 'bind') {
            return $this->bindOrder();
        }
        if ($action === 'review') {
            return $this->reviewBind();
        }
        if ($action === 'sync') {
            return $this->syncOrders();
        }

        return $this->jsonResponse(404, 'xhs order action not found');
    }

    public function bindOrder()
    {
        if (!Request::isPost()) {
            return $this->jsonResponse(100, 'invalid request');
        }
        if (!$this->tableExists('xhs_user_bind') || !$this->tableExists('xhs_order_sync')) {
            return $this->jsonResponse(100, '小红书同步数据表未初始化');
        }

        $userToken = $this->currentUserToken();
        if (!$userToken) {
            return $this->jsonResponse(401, '请先登录');
        }

        $orderId = trim((string) Request::post('orderId', ''));
        $phone = trim((string) Request::post('phone', ''));
        if ($orderId === '' || $phone === '') {
            return $this->jsonResponse(100, '请填写小红书订单号和手机号');
        }

        $service = new XhsOrderSyncService();
        $user = Db::name('user')->field('id, phone')->where('id', intval($userToken['userId']))->find();
        if (!$user || !$service->phoneMatches($user['phone'] ?? '', $phone)) {
            return $this->jsonResponse(100, '手机号与当前小程序账号不一致');
        }

        $storedToken = $this->getStoredToken();
        if (empty($storedToken['accessToken'])) {
            return $this->jsonResponse(100, '小红书店铺授权未完成');
        }

        try {
            $detail = $this->getXhsOrderDetail($orderId, $storedToken['accessToken']);
        } catch (Exception $e) {
            return $this->jsonResponse(100, '查询小红书订单失败：' . $e->getMessage());
        }

        if (!$service->canBindOrder($detail)) {
            return $this->jsonResponse(100, '仅支持待发货且无售后的订单首次绑定');
        }

        try {
            $receiver = $this->getXhsReceiverInfo($detail, $storedToken['accessToken']);
        } catch (Exception $e) {
            return $this->jsonResponse(100, '查询收件人手机号失败：' . $e->getMessage());
        }


        $xhsOpenId = (string) ($detail['xhsOpenId'] ?? '');
        if ($xhsOpenId === '') {
            return $this->jsonResponse(100, '小红书订单缺少用户标识');
        }

        $existingBind = Db::name('xhs_user_bind')->where('xhsOpenId', $xhsOpenId)->find();
        if ($existingBind && intval($existingBind['userId']) !== intval($user['id'])) {
            return $this->jsonResponse(100, '该小红书账号已提交审核或绑定其他小程序用户');
        }

        Db::startTrans();
        try {
            $bindData = [
                'userId' => intval($user['id']),
                'phone' => $service->normalizePhone($phone),
                'xhsOpenId' => $xhsOpenId,
                'firstOrderId' => $orderId,
                'sellerId' => (string) ($storedToken['sellerId'] ?? ''),
                'shopId' => (string) ($detail['shopId'] ?? ''),
                'shopName' => (string) ($detail['shopName'] ?? ''),
                'status' => ($existingBind && intval($existingBind['status'] ?? 0) === 1) ? 1 : 0,
                'updateTime' => date('Y-m-d H:i:s'),
            ];
            if ($existingBind) {
                Db::name('xhs_user_bind')->where('id', $existingBind['id'])->update($bindData);
            } else {
                $bindData['bindTime'] = date('Y-m-d H:i:s');
                Db::name('xhs_user_bind')->insert($bindData);
            }

            $this->saveXhsOrderSync($detail, intval($user['id']), 0, 0, 0);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return $this->jsonResponse(100, '绑定失败：' . $e->getMessage());
        }

        return $this->jsonResponse(200, '已提交审核，审核通过后订单完成会自动到账', [
            'orderId' => $orderId,
            'xhsOpenId' => $xhsOpenId,
            'reviewStatus' => ($existingBind && intval($existingBind['status'] ?? 0) === 1) ? 'approved' : 'pending',
        ]);
    }

    public function bindStatus()
    {
        $userToken = $this->currentUserToken();
        if (!$userToken) {
            return $this->jsonResponse(401, '请先登录');
        }
        if (!$this->tableExists('xhs_user_bind')) {
            return $this->jsonResponse(200, 'success', ['bound' => false, 'reviewStatus' => 'none']);
        }

        $bind = Db::name('xhs_user_bind')->where('userId', intval($userToken['userId']))->orderRaw('status = 1 desc, status = 0 desc, id desc')->find();
        $user = Db::name('user')->field('phone')->where('id', intval($userToken['userId']))->find();
        $phone = (string) ($user['phone'] ?? '');
        $status = $bind ? intval($bind['status'] ?? 0) : null;
        $reviewStatus = $bind ? ($status === 1 ? 'approved' : ($status === 0 ? 'pending' : 'rejected')) : 'none';

        return $this->jsonResponse(200, 'success', [
            'bound' => $status === 1,
            'pending' => $status === 0,
            'reviewStatus' => $reviewStatus,
            'phone' => $phone,
            'maskedPhone' => $this->maskPhone($phone),
            'firstOrderId' => $bind['firstOrderId'] ?? '',
            'bindTime' => $bind['bindTime'] ?? '',
            'updateTime' => $bind['updateTime'] ?? '',
        ]);
    }

    public function reviewBind()
    {
        if (!Request::isPost()) {
            return $this->jsonResponse(100, 'invalid request');
        }
        if (!$this->validateRefreshKey()) {
            return $this->jsonResponse(403, 'forbidden');
        }
        if (!$this->tableExists('xhs_user_bind')) {
            return $this->jsonResponse(100, '小红书同步数据表未初始化');
        }

        $orderId = trim((string) Request::post('orderId', Request::param('orderId', '')));
        $bindId = intval(Request::post('bindId', Request::param('bindId', 0)));
        $action = strtolower(trim((string) Request::post('action', Request::param('action', 'approve'))));
        $status = in_array($action, ['reject', 'rejected', 'deny'], true) ? -1 : 1;

        if ($bindId <= 0 && $orderId === '') {
            return $this->jsonResponse(100, '请提供 bindId 或 orderId');
        }

        $query = Db::name('xhs_user_bind');
        if ($bindId > 0) {
            $query->where('id', $bindId);
        } else {
            $query->where('firstOrderId', $orderId);
        }
        $bind = $query->find();
        if (!$bind) {
            return $this->jsonResponse(100, '未找到待审核绑定记录');
        }

        $update = [
            'status' => $status,
            'updateTime' => date('Y-m-d H:i:s'),
        ];
        if ($status === 1 && empty($bind['bindTime'])) {
            $update['bindTime'] = date('Y-m-d H:i:s');
        }
        Db::name('xhs_user_bind')->where('id', intval($bind['id']))->update($update);

        return $this->jsonResponse(200, $status === 1 ? '审核已通过' : '审核已拒绝', [
            'bindId' => intval($bind['id']),
            'orderId' => $bind['firstOrderId'] ?? '',
            'userId' => intval($bind['userId'] ?? 0),
            'reviewStatus' => $status === 1 ? 'approved' : 'rejected',
        ]);
    }

    public function syncOrders()
    {
        if (!$this->validateRefreshKey()) {
            return $this->jsonResponse(403, 'forbidden', ['synced' => false]);
        }
        if (!$this->tableExists('xhs_user_bind') || !$this->tableExists('xhs_order_sync')) {
            return $this->jsonResponse(100, '小红书同步数据表未初始化', ['synced' => false]);
        }

        $storedToken = $this->getStoredToken();
        if (empty($storedToken['accessToken'])) {
            return $this->jsonResponse(100, 'accessToken missing', ['synced' => false]);
        }

        $orderId = trim((string) Request::param('orderId', ''));
        try {
            if ($orderId !== '') {
                $result = $this->syncOneOrder($orderId, $storedToken['accessToken']);
                return $this->jsonResponse(200, 'success', ['synced' => true, 'orders' => [$result]]);
            }

            $endTime = intval(Request::param('endTime', time()));
            $startTime = intval(Request::param('startTime', $endTime - 25 * 60));
            if ($endTime - $startTime > 30 * 60) {
                $startTime = $endTime - 30 * 60;
            }

            $orders = $this->fetchXhsOrderList($startTime, $endTime, $storedToken['accessToken']);
            $results = [];
            foreach ($orders as $order) {
                if (!empty($order['orderId'])) {
                    $results[] = $this->syncOneOrder($order['orderId'], $storedToken['accessToken']);
                }
            }

            return $this->jsonResponse(200, 'success', [
                'synced' => true,
                'count' => count($results),
                'orders' => $results,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ]);
        } catch (Exception $e) {
            Cache::set('xhs_order_sync_error_latest', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ], 86400);
            return $this->jsonResponse(100, '同步失败：' . $e->getMessage(), ['synced' => false]);
        }
    }

    private function requestGateway($method, array $businessParams = [], $accessToken = '')
    {
        $config = $this->loadConfig();
        if (empty($config['app_id']) || empty($config['app_secret']) || empty($config['endpoint'])) {
            throw new Exception('xhs config missing');
        }

        $timestamp = (string) time();
        $body = array_merge([
            'appId' => $config['app_id'],
            'timestamp' => $timestamp,
            'version' => $config['version'] ?? '2.0',
            'method' => $method,
            'sign' => $this->buildSign($method, $timestamp, $config),
        ], $businessParams);

        if ($accessToken !== '') {
            $body['accessToken'] = $accessToken;
        }

        $response = $this->postJson($config['endpoint'], $body, 15);
        if (!$response['ok']) {
            throw new Exception('http ' . $response['httpCode'] . ' ' . $response['error']);
        }

        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded)) {
            throw new Exception('invalid json response');
        }

        return $decoded;
    }

    private function buildSign($method, $timestamp, array $config)
    {
        $version = $config['version'] ?? '2.0';
        $plain = $method . '?appId=' . $config['app_id'] . '&timestamp=' . $timestamp . '&version=' . $version . $config['app_secret'];
        return md5($plain);
    }

    private function postJson($url, array $payload, $timeout)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json;charset=utf-8'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HEADER => false,
        ]);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        curl_close($curl);

        return [
            'ok' => $error === '' && $httpCode >= 200 && $httpCode < 300 && is_string($body),
            'body' => is_string($body) ? $body : '',
            'error' => $error,
            'httpCode' => $httpCode,
        ];
    }

    private function saveToken(array $token, array $rawResponse)
    {
        $cachePayload = [
            'sellerId' => $token['sellerId'] ?? '',
            'sellerName' => $token['sellerName'] ?? '',
            'accessToken' => $token['accessToken'] ?? '',
            'accessTokenExpiresAt' => $token['accessTokenExpiresAt'] ?? 0,
            'refreshToken' => $token['refreshToken'] ?? '',
            'refreshTokenExpiresAt' => $token['refreshTokenExpiresAt'] ?? 0,
            'rawResponse' => $rawResponse,
            'updateTime' => date('Y-m-d H:i:s'),
        ];
        Cache::set('xhs_oauth_token_latest', $cachePayload, 86400 * 30);

        if (!$this->tableExists('xhs_oauth_token')) {
            return;
        }

        $sellerId = (string) ($token['sellerId'] ?? '');
        $data = [
            'sellerId' => $sellerId,
            'sellerName' => (string) ($token['sellerName'] ?? ''),
            'accessToken' => (string) ($token['accessToken'] ?? ''),
            'accessTokenExpiresAt' => $this->formatTokenTime($token['accessTokenExpiresAt'] ?? 0),
            'refreshToken' => (string) ($token['refreshToken'] ?? ''),
            'refreshTokenExpiresAt' => $this->formatTokenTime($token['refreshTokenExpiresAt'] ?? 0),
            'rawResponse' => json_encode($rawResponse, JSON_UNESCAPED_UNICODE),
            'updateTime' => date('Y-m-d H:i:s'),
        ];

        $exists = $sellerId !== '' ? Db::name('xhs_oauth_token')->where('sellerId', $sellerId)->find() : null;
        if ($exists) {
            Db::name('xhs_oauth_token')->where('id', $exists['id'])->update($data);
            return;
        }

        $data['createTime'] = date('Y-m-d H:i:s');
        Db::name('xhs_oauth_token')->insert($data);
    }

    private function validateRefreshKey()
    {
        $config = $this->loadConfig();
        $refreshKey = (string) ($config['refresh_key'] ?? '');
        if ($refreshKey === '') {
            return true;
        }

        $givenKey = (string) Request::param('key', Request::header('X-Xhs-Refresh-Key', ''));
        return hash_equals($refreshKey, $givenKey);
    }

    private function getStoredToken()
    {
        if ($this->tableExists('xhs_oauth_token')) {
            $row = Db::name('xhs_oauth_token')->order('updateTime desc, id desc')->find();
            if ($row) {
                return $row;
            }
        }

        $cached = Cache::get('xhs_oauth_token_latest');
        return is_array($cached) ? $cached : [];
    }

    private function mergeTokenPayload(array $token, array $fallback)
    {
        foreach (['sellerId', 'sellerName', 'refreshToken', 'refreshTokenExpiresAt'] as $key) {
            if (empty($token[$key]) && !empty($fallback[$key])) {
                $token[$key] = $fallback[$key];
            }
        }

        return $token;
    }

    private function loadConfig()
    {
        $configDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        foreach (['xhs.local.php', 'xhs.php'] as $fileName) {
            $file = $configDir . $fileName;
            if (is_file($file)) {
                $config = include $file;
                return is_array($config) ? $config : [];
            }
        }

        return [];
    }

    private function tableExists($name)
    {
        static $cache = [];
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        try {
            $prefix = (string) (Db::getConfig('prefix') ?? '');
            $candidates = array_unique(array_filter([
                $prefix . $name,
                'mp_' . $name,
                $name,
            ]));

            foreach ($candidates as $tableName) {
                $safeTableName = addslashes($tableName);
                $result = Db::query("SHOW TABLES LIKE '" . $safeTableName . "'");
                if (!empty($result)) {
                    $cache[$name] = true;
                    return true;
                }
            }

            $cache[$name] = false;
            return false;
        } catch (Exception $e) {
            $cache[$name] = false;
            return false;
        }
    }

    private function formatTokenTime($value)
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return $value;
        }

        $time = intval($value);
        if ($time <= 0) {
            return null;
        }

        if ($time > 9999999999) {
            $time = intval($time / 1000);
        }

        return date('Y-m-d H:i:s', $time);
    }

    private function maskTokenResponse(array $response)
    {
        if (isset($response['data']) && is_array($response['data'])) {
            foreach (['accessToken', 'refreshToken'] as $key) {
                if (!empty($response['data'][$key])) {
                    $response['data'][$key] = substr($response['data'][$key], 0, 6) . '***';
                }
            }
        }

        return $response;
    }


    private function currentUserToken()
    {
        $headers = Request::header();
        $token = $headers['authorization'] ?? ($headers['Authorization'] ?? '');
        if ($token === '') {
            return null;
        }

        $userToken = json_decode(decrypt(base64_decode($token)), true);
        return is_array($userToken) && isset($userToken['userId']) ? $userToken : null;
    }

    private function getXhsOrderDetail($orderId, $accessToken)
    {
        $result = $this->requestGateway('order.getOrderDetail', [
            'orderId' => $orderId,
        ], $accessToken);
        $data = $this->gatewayData($result);
        if (empty($data['orderId'])) {
            throw new Exception($result['error_msg'] ?? ($result['msg'] ?? 'order detail missing'));
        }

        return $data;
    }

    private function getXhsReceiverInfo(array $detail, $accessToken)
    {
        $orderId = (string) ($detail['orderId'] ?? '');
        $openAddressId = (string) ($detail['openAddressId'] ?? '');
        if ($orderId === '' || $openAddressId === '') {
            throw new Exception('openAddressId missing');
        }

        $result = $this->requestGateway('order.getOrderReceiverInfo', [
            'receiverQueries' => [[
                'orderId' => $orderId,
                'openAddressId' => $openAddressId,
            ]],
            'isReturn' => intval($detail['orderType'] ?? 0) === 5,
        ], $accessToken);
        $data = $this->gatewayData($result);
        $infos = $data['receiverInfos'] ?? [];
        if (!is_array($infos) || empty($infos[0])) {
            throw new Exception($result['error_msg'] ?? ($result['msg'] ?? 'receiver info missing'));
        }

        return $infos[0];
    }

    private function resolveXhsReceiverPhone($orderId, array $receiver, $accessToken)
    {
        $receiverPhone = (string) ($receiver['receiverPhone'] ?? '');
        if ($receiverPhone === '') {
            throw new Exception('receiverPhone missing');
        }

        if (!$this->isXhsEncryptedValue($receiverPhone)) {
            return $receiverPhone;
        }

        $result = $this->requestGateway('data.batchDecrypt', [
            'baseInfos' => [[
                'dataTag' => (string) $orderId,
                'encryptedData' => $receiverPhone,
            ]],
            'actionType' => '1',
            'appUserId' => '2',
        ], $accessToken);
        $data = $this->gatewayData($result);
        $list = $data['dataInfoList'] ?? [];
        if (!is_array($list) || empty($list[0]) || !is_array($list[0])) {
            throw new Exception($result['error_msg'] ?? ($result['msg'] ?? 'decrypt response missing'));
        }

        $info = $list[0];
        $errorCode = (string) ($info['errorCode'] ?? '');
        if ($errorCode !== '' && $errorCode !== '0') {
            throw new Exception($errorCode . ' ' . (string) ($info['errorMsg'] ?? 'decrypt failed'));
        }

        $decrypted = (string) ($info['decryptedData'] ?? '');
        if ($decrypted === '') {
            throw new Exception('decryptedData missing');
        }

        Cache::set('xhs_receiver_phone_decrypt_latest', [
            'orderId' => $orderId,
            'encryptedPhone' => $receiverPhone,
            'decryptedPhone' => $decrypted,
            'decryptInfo' => $info,
            'time' => date('Y-m-d H:i:s'),
        ], 86400);

        return $decrypted;
    }

    private function isXhsEncryptedValue($value)
    {
        return preg_match('/^#.+#\\d+##$/', trim((string) $value)) === 1;
    }

    private function fetchXhsOrderList($startTime, $endTime, $accessToken)
    {
        $orders = [];
        $pageNo = 1;
        $maxPageNo = 1;
        do {
            $result = $this->requestGateway('order.getOrderList', [
                'startTime' => intval($startTime),
                'endTime' => intval($endTime),
                'timeType' => 2,
                'orderStatus' => 0,
                'pageNo' => $pageNo,
                'pageSize' => 100,
            ], $accessToken);
            $data = $this->gatewayData($result);
            $list = $data['orderList'] ?? [];
            if (is_array($list)) {
                foreach ($list as $item) {
                    if (is_array($item)) {
                        $orders[] = $item;
                    }
                }
            }
            $maxPageNo = max(1, intval($data['maxPageNo'] ?? 1));
            $pageNo++;
        } while ($pageNo <= $maxPageNo && $pageNo <= 10);

        return $orders;
    }

    private function gatewayData(array $result)
    {
        if (isset($result['success']) && empty($result['success'])) {
            throw new Exception($result['error_msg'] ?? ($result['msg'] ?? 'xhs api failed'));
        }
        if (isset($result['data']) && is_array($result['data'])) {
            return $result['data'];
        }

        return $result;
    }

    private function syncOneOrder($orderId, $accessToken)
    {
        $service = new XhsOrderSyncService();
        $detail = $this->getXhsOrderDetail($orderId, $accessToken);
        $xhsOpenId = (string) ($detail['xhsOpenId'] ?? '');
        $bind = $xhsOpenId !== '' ? Db::name('xhs_user_bind')->where('xhsOpenId', $xhsOpenId)->where('status', 1)->find() : null;
        $userId = $bind ? intval($bind['userId']) : 0;

        $existing = Db::name('xhs_order_sync')->where('orderId', $orderId)->find();
        if (!$bind) {
            $this->saveXhsOrderSync($detail, 0, 0, 0, 0);
            return ['orderId' => $orderId, 'status' => 'unbound'];
        }
        if ($existing && intval($existing['pointsStatus'] ?? 0) === 1) {
            $this->saveXhsOrderSync($detail, $userId, intval($existing['earnedShells'] ?? 0), 1, 0);
            return ['orderId' => $orderId, 'status' => 'already_rewarded', 'earnedShells' => intval($existing['earnedShells'] ?? 0)];
        }
        if (!$service->canRewardOrder($detail)) {
            $this->saveXhsOrderSync($detail, $userId, 0, 0, 0);
            return ['orderId' => $orderId, 'status' => 'pending'];
        }

        $earnedShells = $service->calculateEarnedShells($this->getXhsTotalPayAmount($detail));
        if ($earnedShells <= 0) {
            $this->saveXhsOrderSync($detail, $userId, 0, 0, 0);
            return ['orderId' => $orderId, 'status' => 'no_reward'];
        }

        $this->rewardXhsOrder($detail, $userId, $earnedShells);
        return ['orderId' => $orderId, 'status' => 'rewarded', 'earnedShells' => $earnedShells];
    }

    private function getXhsTotalPayAmount(array $detail)
    {
        foreach (['totalPayAmount', 'totalPaidAmount', 'payAmount', 'totalAmount'] as $field) {
            if (isset($detail[$field]) && intval($detail[$field]) > 0) {
                return intval($detail[$field]);
            }
        }

        $total = 0;
        $skuList = $detail['skuList'] ?? ($detail['skuInfos'] ?? []);
        if (is_array($skuList)) {
            foreach ($skuList as $sku) {
                if (!is_array($sku)) {
                    continue;
                }
                foreach (['totalPaidAmount', 'totalPayAmount', 'payAmount', 'paidAmount'] as $field) {
                    if (isset($sku[$field]) && intval($sku[$field]) > 0) {
                        $total += intval($sku[$field]);
                        break;
                    }
                }
            }
        }

        return $total;
    }
    private function rewardXhsOrder(array $detail, $userId, $earnedShells)
    {
        Db::startTrans();
        try {
            $orderId = (string) ($detail['orderId'] ?? '');
            $existing = Db::name('xhs_order_sync')->where('orderId', $orderId)->lock(true)->find();
            if ($existing && intval($existing['pointsStatus'] ?? 0) === 1) {
                Db::commit();
                return;
            }

            $user = Db::name('user')->where('id', intval($userId))->lock(true)->find();
            if (!$user) {
                throw new Exception('user missing');
            }
            $beforeShells = intval($user['snailShells'] ?? 0);
            $afterShells = $beforeShells + intval($earnedShells);
            Db::name('user')->where('id', intval($userId))->inc('snailShells', intval($earnedShells))->update();
            $this->insertSnailShellRecord($userId, 0, $orderId, 'xhs_order_reward', $earnedShells, $beforeShells, $afterShells, '小红书订单完成赠送积分');
            $this->saveXhsOrderSync($detail, intval($userId), intval($earnedShells), 1, 1);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function saveXhsOrderSync(array $detail, $userId, $earnedShells, $pointsStatus, $rewardNow)
    {
        $orderId = (string) ($detail['orderId'] ?? '');
        if ($orderId === '') {
            return;
        }

        $data = [
            'orderId' => $orderId,
            'xhsOpenId' => (string) ($detail['xhsOpenId'] ?? ''),
            'userId' => intval($userId),
            'shopId' => (string) ($detail['shopId'] ?? ''),
            'shopName' => (string) ($detail['shopName'] ?? ''),
            'orderStatus' => intval($detail['orderStatus'] ?? 0),
            'orderAfterSalesStatus' => intval($detail['orderAfterSalesStatus'] ?? 0),
            'cancelStatus' => intval($detail['cancelStatus'] ?? 0),
            'totalPayAmount' => $this->getXhsTotalPayAmount($detail),
            'earnedShells' => intval($earnedShells),
            'pointsStatus' => intval($pointsStatus),
            'rawDetail' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'lastSyncTime' => date('Y-m-d H:i:s'),
            'updateTime' => date('Y-m-d H:i:s'),
        ];
        if ($rewardNow) {
            $data['rewardTime'] = date('Y-m-d H:i:s');
        }

        $existing = Db::name('xhs_order_sync')->where('orderId', $orderId)->find();
        if ($existing) {
            Db::name('xhs_order_sync')->where('id', $existing['id'])->update($data);
            return;
        }

        $data['createTime'] = date('Y-m-d H:i:s');
        Db::name('xhs_order_sync')->insert($data);
    }

    private function insertSnailShellRecord($userId, $orderId, $orderNo, $scene, $changeShells, $beforeShells, $afterShells, $remark = '')
    {
        if (!$this->tableExists('snail_shell_record')) {
            return;
        }
        Db::name('snail_shell_record')->insert([
            'userId' => intval($userId),
            'orderId' => intval($orderId),
            'orderNo' => strval($orderNo),
            'scene' => $scene,
            'changeShells' => intval($changeShells),
            'beforeShells' => intval($beforeShells),
            'afterShells' => intval($afterShells),
            'remark' => $remark,
            'createDate' => date('Y-m-d H:i:s'),
            'createTime' => time(),
        ]);
    }

    private function recordReceiverPhoneMismatch($orderId, array $user, $inputPhone, array $receiver, XhsOrderSyncService $service, $decryptedPhone = '', $decryptError = '')
    {
        $miniPhone = (string) ($user['phone'] ?? $inputPhone);
        $xhsPhone = (string) ($receiver['receiverPhone'] ?? '');
        $matched = array_key_exists('matched', $receiver) ? $receiver['matched'] : null;
        $debug = [
            'orderId' => $orderId,
            'userId' => intval($user['id'] ?? 0),
            'inputPhone' => $inputPhone,
            'miniPhone' => $miniPhone,
            'miniPhoneNormalized' => $service->normalizePhone($miniPhone),
            'xhsReceiverPhone' => $xhsPhone,
            'xhsReceiverPhoneNormalized' => $service->normalizePhone($xhsPhone),
            'decryptedPhone' => $decryptedPhone,
            'decryptedPhoneNormalized' => $service->normalizePhone($decryptedPhone),
            'decryptError' => $decryptError,
            'matched' => $matched,
            'receiverKeys' => array_keys($receiver),
            'receiverRaw' => $receiver,
            'time' => date('Y-m-d H:i:s'),
        ];
        Cache::set('xhs_receiver_phone_mismatch_latest', $debug, 86400);

        $xhsLabel = $decryptedPhone !== '' ? $this->phoneDebugLabel($decryptedPhone, $service) : $this->phoneDebugLabel($xhsPhone, $service);
        if ($decryptError !== '') {
            $xhsLabel .= '，解密错误=' . $decryptError;
        }

        return '小程序 ' . $this->phoneDebugLabel($miniPhone, $service)
            . '，小红书 ' . $xhsLabel
            . '，matched=' . ($matched === null ? 'null' : json_encode($matched, JSON_UNESCAPED_UNICODE));
    }

    private function phoneDebugLabel($phone, XhsOrderSyncService $service)
    {
        $normalized = $service->normalizePhone($phone);
        if ($normalized === '') {
            return '空(len=0)';
        }

        return $this->maskPhone($normalized) . '(len=' . strlen($normalized) . ')';
    }

    private function maskPhone($phone)
    {
        $phone = preg_replace('/\D+/', '', strval($phone));
        if (strlen($phone) < 7) {
            return $phone;
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    private function jsonResponse($code, $msg, array $data = [])
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }
}
