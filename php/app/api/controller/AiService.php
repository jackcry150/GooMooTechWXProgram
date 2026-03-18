<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class AiService
{
    public function sendMessage()
    {
        $data = [
            'code' => 100,
            'msg' => 'operate fail',
        ];

        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $scene = Request::post('scene', 'presale');
            $content = trim((string) Request::post('content', ''));
            $productId = intval(Request::post('productId', 0));
            $orderId = intval(Request::post('orderId', 0));
            $sourcePage = trim((string) Request::post('sourcePage', ''));
            $userId = $this->getAuthorizedUserId();

            if ($content === '') {
                $data['msg'] = 'content required';
                return json($data);
            }

            $knowledge = $this->buildKnowledgeContext($scene, $productId, $orderId, $userId);
            $fallbackReply = $scene === 'aftersale'
                ? $this->buildAftersaleReply($content, $knowledge, $userId)
                : $this->buildPresaleReply($content, $knowledge);
            $needTransfer = $this->shouldSuggestTransfer($content) ? 1 : 0;

            $reply = $this->requestCloudReply($scene, $content, $knowledge, $needTransfer);
            if (!$reply) {
                $reply = $fallbackReply;
            }

            $sessionId = $this->persistConversation([
                'userId' => $userId,
                'scene' => $scene,
                'sourcePage' => $sourcePage,
                'productId' => $productId,
                'orderId' => $orderId,
                'question' => $content,
                'reply' => $reply,
                'needTransfer' => $needTransfer,
            ]);

            $data['code'] = 200;
            $data['msg'] = 'success';
            $data['data'] = [
                'reply' => $reply,
                'scene' => $scene,
                'productId' => $productId,
                'orderId' => $orderId,
                'needTransfer' => $needTransfer,
                'sessionId' => $sessionId,
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    private function requestCloudReply($scene, $userQuestion, $knowledge, $needTransfer)
    {
        $config = $this->loadAiConfig();
        if (empty($config['enabled']) || empty($config['base_url']) || empty($config['api_key']) || empty($config['model'])) {
            return '';
        }

        $url = rtrim($config['base_url'], '/') . '/chat/completions';
        $payload = [
            'model' => $config['model'],
            'temperature' => isset($config['temperature']) ? floatval($config['temperature']) : 0.2,
            'max_tokens' => isset($config['max_tokens']) ? intval($config['max_tokens']) : 800,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($scene, $userQuestion, $knowledge, $needTransfer),
                ],
            ],
        ];

        $response = $this->postJson($url, $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key'],
        ], isset($config['timeout']) ? intval($config['timeout']) : 20);

        if (!$response['ok']) {
            return '';
        }

        $body = json_decode($response['body'], true);
        if (!is_array($body)) {
            return '';
        }

        $content = $body['choices'][0]['message']['content'] ?? '';
        if (!is_string($content)) {
            return '';
        }

        $content = trim($content);
        return $content === '' ? '' : $content;
    }

    private function buildSystemPrompt()
    {
        return implode("\n", [
            'You are the customer service assistant for JuMao mini app.',
            'Only answer from the provided business context.',
            'Do not invent product, order, refund, logistics, or platform policy details.',
            'If the issue involves compensation, complaints, quality disputes, law, special refunds, or manual review, advise transfer to human support.',
            'Keep the answer concise, polite, and natural.',
            'Use JuMao as the platform name.',
            'Use MaoBi for points or deduction wording.',
        ]);
    }

    private function buildUserPrompt($scene, $userQuestion, $knowledge, $needTransfer)
    {
        return implode("\n", [
            'Scene: ' . ($scene === 'aftersale' ? 'after-sales' : 'pre-sales'),
            'Need transfer: ' . ($needTransfer ? 'yes' : 'no'),
            'User question: ' . $userQuestion,
            'Business context:',
            $this->buildKnowledgeLines($knowledge),
            'Reply in Chinese for the end user. Do not expose chain-of-thought.',
        ]);
    }

    private function buildKnowledgeLines($knowledge)
    {
        $lines = [];
        $product = $knowledge['product'] ?? [];
        $order = $knowledge['order'] ?? [];
        $setting = $knowledge['setting'] ?? [];
        $afterSale = $knowledge['afterSaleArticle'] ?? [];
        $agreement = $knowledge['serviceAgreementArticle'] ?? [];

        if (!empty($product)) {
            $lines[] = 'Product:';
            $lines[] = '- Name: ' . trim(($product['subtitle'] ?? '') . ' ' . ($product['title'] ?? ''));
            $lines[] = '- Type: ' . (intval($product['type'] ?? 1) === 2 ? 'presale' : 'instock');
            $lines[] = '- Price: ' . number_format((float) ($product['price'] ?? 0), 2, '.', '');
            $lines[] = '- Deposit: ' . number_format((float) ($product['deposit'] ?? 0), 2, '.', '');
            $lines[] = '- End time: ' . (string) ($product['endTime'] ?? 'not_set');
        }

        if (!empty($order)) {
            $lines[] = 'Order:';
            $lines[] = '- OrderNo: ' . (string) ($order['orderNo'] ?? '');
            $lines[] = '- Status: ' . intval($order['status'] ?? 0);
            $lines[] = '- RefundStatus: ' . intval($order['refundStatus'] ?? 0);
            $lines[] = '- FreightName: ' . (string) ($order['freightName'] ?? '');
            $lines[] = '- FreightNo: ' . (string) ($order['freightNo'] ?? '');
        }

        if (!empty($setting)) {
            $lines[] = 'Site:';
            $lines[] = '- Contact: ' . $this->shortText($setting['contactUs'] ?? '', 120);
            $lines[] = '- Address: ' . (string) ($setting['address'] ?? '');
            $lines[] = '- Email: ' . (string) ($setting['email'] ?? '');
        }

        if (!empty($afterSale)) {
            $lines[] = 'AfterSale: ' . $this->shortText($afterSale['content'] ?? '', 180);
        }

        if (!empty($agreement)) {
            $lines[] = 'Agreement: ' . $this->shortText($agreement['content'] ?? '', 180);
        }

        if (empty($lines)) {
            $lines[] = 'No business context available.';
        }

        return implode("\n", $lines);
    }

    private function loadAiConfig()
    {
        $localFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai.local.php';
        $exampleFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai.example.php';

        if (is_file($localFile)) {
            $config = include $localFile;
            return is_array($config) ? $config : [];
        }

        if (is_file($exampleFile)) {
            $config = include $exampleFile;
            return is_array($config) ? $config : [];
        }

        return [];
    }

    private function postJson($url, $payload, $headers, $timeout)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
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

    private function persistConversation($payload)
    {
        if (!$this->tableExists('ai_service_session') || !$this->tableExists('ai_service_message')) {
            return 0;
        }

        try {
            Db::startTrans();

            $sessionId = Db::name('ai_service_session')->insertGetId([
                'sessionNo' => $this->buildSessionNo(),
                'userId' => intval($payload['userId'] ?? 0),
                'scene' => (string) ($payload['scene'] ?? 'presale'),
                'sourcePage' => (string) ($payload['sourcePage'] ?? ''),
                'productId' => intval($payload['productId'] ?? 0),
                'orderId' => intval($payload['orderId'] ?? 0),
                'status' => 1,
                'lastMessageTime' => date('Y-m-d H:i:s'),
                'createTime' => date('Y-m-d H:i:s'),
                'updateTime' => date('Y-m-d H:i:s'),
            ]);

            Db::name('ai_service_message')->insertAll([
                [
                    'sessionId' => $sessionId,
                    'role' => 'user',
                    'content' => (string) ($payload['question'] ?? ''),
                    'needTransfer' => 0,
                    'createTime' => date('Y-m-d H:i:s'),
                ],
                [
                    'sessionId' => $sessionId,
                    'role' => 'ai',
                    'content' => (string) ($payload['reply'] ?? ''),
                    'needTransfer' => intval($payload['needTransfer'] ?? 0),
                    'createTime' => date('Y-m-d H:i:s'),
                ],
            ]);

            Db::commit();
            return $sessionId;
        } catch (Exception $e) {
            Db::rollback();
            return 0;
        }
    }

    private function tableExists($name)
    {
        static $cache = [];
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        try {
            $tableName = Db::getConfig('prefix') . $name;
            $result = Db::query('SHOW TABLES LIKE ?', [$tableName]);
            $cache[$name] = !empty($result);
            return $cache[$name];
        } catch (Exception $e) {
            $cache[$name] = false;
            return false;
        }
    }

    private function buildSessionNo()
    {
        return 'AICS' . date('YmdHis') . mt_rand(1000, 9999);
    }

    private function buildKnowledgeContext($scene, $productId, $orderId, $userId)
    {
        $context = [
            'scene' => $scene,
            'product' => null,
            'order' => null,
            'setting' => $this->getSettingInfo(),
            'afterSaleArticle' => $this->getNewsByCode('after_sale'),
            'serviceAgreementArticle' => $this->getNewsByCode('service_agreement'),
        ];

        if ($productId > 0) {
            $context['product'] = $this->getProductInfo($productId);
        }

        if ($orderId > 0 && $userId > 0) {
            $context['order'] = $this->getOrderInfo($orderId, $userId);
        }

        return $context;
    }

    private function buildPresaleReply($content, $knowledge)
    {
        $product = $knowledge['product'];
        $setting = $knowledge['setting'];
        $serviceAgreement = $knowledge['serviceAgreementArticle'];
        $lowerContent = mb_strtolower($content, 'UTF-8');

        if (!$product) {
            return '当前没有拿到对应商品信息。你可以从商品详情页重新进入，或直接转人工客服处理。';
        }

        $productName = trim(($product['subtitle'] ?? '') . ' ' . ($product['title'] ?? ''));
        $productType = intval($product['type'] ?? 1) === 2 ? '预售' : '现货';
        $price = number_format((float) ($product['price'] ?? 0), 2, '.', '');
        $deposit = number_format((float) ($product['deposit'] ?? 0), 2, '.', '');
        $balance = number_format(max(0, (float) ($product['price'] ?? 0) - (float) ($product['deposit'] ?? 0)), 2, '.', '');
        $contactSnippet = $this->shortText($setting['contactUs'] ?? '', 60);
        $agreementSnippet = $this->shortText($serviceAgreement['content'] ?? '', 60);

        if ($this->containsAny($lowerContent, ['发票', 'invoice', '开票'])) {
            $extra = $agreementSnippet ? '服务协议里提到：' . $agreementSnippet : '当前没有读到更详细的开票规则，建议以下单页实际展示或人工客服答复为准。';
            return '关于商品“' . $productName . '”的发票问题，当前支持情况建议以实际下单页展示为准。' . $extra;
        }

        if ($this->containsAny($lowerContent, ['发货', '多久', '什么时候', '物流', 'ship', 'when', 'logistics'])) {
            if (intval($product['type'] ?? 1) === 2) {
                $endTime = (string) ($product['endTime'] ?? '未设置');
                return '商品“' . $productName . '”当前是预售商品，售价 ' . $price . ' 元，定金 ' . $deposit . ' 元，尾款 ' . $balance . ' 元。发货时间通常要结合预售结束时间和实际排单结果判断，当前参考结束时间为：' . $endTime . '。';
            }

            return '商品“' . $productName . '”当前为现货，售价 ' . $price . ' 元。具体发货时间建议以下单后的实际处理结果为准，如需更精确时间可以转人工客服确认。';
        }

        if ($this->containsAny($lowerContent, ['预售', '定金', '尾款', 'presale', 'deposit', 'balance'])) {
            if (intval($product['type'] ?? 1) === 2) {
                return '商品“' . $productName . '”当前是预售商品，定金 ' . $deposit . ' 元，尾款 ' . $balance . ' 元，商品总价 ' . $price . ' 元。';
            }

            return '商品“' . $productName . '”当前不是预售商品，商品价格为 ' . $price . ' 元。';
        }

        if ($this->containsAny($lowerContent, ['猫币', '积分', '抵扣', 'points'])) {
            return '当前商品“' . $productName . '”的咨询已收到。猫币抵扣规则建议以下单页实际展示为准，如需确认特殊抵扣限制，建议转人工客服。';
        }

        if ($this->containsAny($lowerContent, ['人工', '客服', '联系'])) {
            if ($contactSnippet) {
                return '关于商品“' . $productName . '”的问题，如果你想直接联系人工客服，可以优先参考这里：' . $contactSnippet;
            }
            return '关于商品“' . $productName . '”的问题，建议你直接转人工客服进一步确认。';
        }

        return '商品“' . $productName . '”当前属于' . $productType . '商品，价格为 ' . $price . ' 元。你可以继续问我发货、预售、猫币抵扣、发票这些问题。';
    }

    private function buildAftersaleReply($content, $knowledge, $userId)
    {
        $order = $knowledge['order'];
        $afterSaleArticle = $knowledge['afterSaleArticle'];
        $setting = $knowledge['setting'];
        $lowerContent = mb_strtolower($content, 'UTF-8');

        if (!$userId) {
            return '当前没有识别到登录用户信息。请先重新登录，再从订单详情页进入售后咨询。';
        }

        if (!$order) {
            return '当前没有拿到有效订单上下文。请从你自己的订单详情页重新进入，或直接转人工客服处理。';
        }

        $status = intval($order['status'] ?? 0);
        $refundStatus = intval($order['refundStatus'] ?? 0);
        $orderNo = (string) ($order['orderNo'] ?? '');
        $freightName = (string) ($order['freightName'] ?? '');
        $freightNo = (string) ($order['freightNo'] ?? '');
        $afterSaleSnippet = $this->shortText($afterSaleArticle['content'] ?? '', 80);
        $contactSnippet = $this->shortText($setting['contactUs'] ?? '', 60);

        if ($this->containsAny($lowerContent, ['退款', '退货', 'refund'])) {
            $extra = $afterSaleSnippet ? '售后说明参考：' . $afterSaleSnippet : '当前没有读到更详细的售后条款，建议联系人工客服确认。';
            return '订单 ' . $orderNo . ' 当前退款状态为 ' . $refundStatus . '。' . $extra;
        }

        if ($this->containsAny($lowerContent, ['物流', '发货', '快递', 'ship', 'logistics'])) {
            if ($freightName || $freightNo) {
                return '订单 ' . $orderNo . ' 的物流信息如下：物流公司 ' . ($freightName ?: '暂无') . '，物流单号 ' . ($freightNo ?: '暂无') . '。';
            }
            return '订单 ' . $orderNo . ' 当前还没有查到明确的物流信息，当前订单状态为 ' . $status . '。如果长时间未更新，建议转人工客服。';
        }

        if ($this->containsAny($lowerContent, ['状态', '订单', 'status', 'order'])) {
            return '订单 ' . $orderNo . ' 当前订单状态为 ' . $status . '，退款状态为 ' . $refundStatus . '。';
        }

        if ($this->containsAny($lowerContent, ['质量', '破损', '售后', '投诉', '赔偿'])) {
            $extra = $afterSaleSnippet ? '售后说明参考：' . $afterSaleSnippet : '这类问题建议尽快转人工客服处理。';
            return '订单 ' . $orderNo . ' 涉及质量或售后争议，建议尽快转人工客服进一步处理。' . $extra;
        }

        if ($this->containsAny($lowerContent, ['人工', '客服', '联系'])) {
            if ($contactSnippet) {
                return '订单 ' . $orderNo . ' 如需人工客服介入，可以优先参考这里：' . $contactSnippet;
            }
            return '订单 ' . $orderNo . ' 如需人工客服介入，建议你直接转人工客服处理。';
        }

        return '当前已获取订单 ' . $orderNo . ' 的上下文，订单状态为 ' . $status . '，退款状态为 ' . $refundStatus . '。你可以继续问我物流、退款、售后处理这些问题。';
    }

    private function shouldSuggestTransfer($content)
    {
        $lowerContent = mb_strtolower($content, 'UTF-8');
        return $this->containsAny($lowerContent, ['人工', '投诉', '赔偿', '质量', '破损', '法务', '仲裁', 'manual']);
    }

    private function containsAny($content, $keywords)
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && mb_strpos($content, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function shortText($content, $length)
    {
        $content = trim(strip_tags((string) $content));
        if ($content === '') {
            return '';
        }

        $content = preg_replace('/\s+/u', ' ', $content);
        if (mb_strlen($content, 'UTF-8') <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length, 'UTF-8') . '...';
    }

    private function getProductInfo($productId)
    {
        if ($productId <= 0) {
            return null;
        }

        return Db::name('product')
            ->field('id, title, subtitle, type, price, deposit, endTime')
            ->where('id', $productId)
            ->find();
    }

    private function getOrderInfo($orderId, $userId)
    {
        if ($orderId <= 0 || $userId <= 0) {
            return null;
        }

        return Db::name('order')
            ->field('id, userId, orderNo, status, refundStatus, freightName, freightNo')
            ->where('id', $orderId)
            ->where('userId', $userId)
            ->find();
    }

    private function getSettingInfo()
    {
        return Db::name('setting')
            ->where('id', 1)
            ->find();
    }

    private function getNewsByCode($code)
    {
        if ($code === '') {
            return null;
        }

        return Db::name('news')
            ->field('id, code, title, content')
            ->where('code', $code)
            ->find();
    }

    private function getAuthorizedUserId()
    {
        $headers = Request::header();
        if (!$headers || !isset($headers['authorization'])) {
            return 0;
        }

        $token = $headers['authorization'];
        $userToken = json_decode(decrypt(base64_decode($token)), true);
        if (!$userToken || !isset($userToken['userId'])) {
            return 0;
        }

        return intval($userToken['userId']);
    }
}
