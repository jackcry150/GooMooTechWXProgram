<?php

namespace app\common\service;

use think\facade\Db;
use Throwable;

class AiBoundaryService
{
    public function route(string $question, array $context = []): array
    {
        $question = trim($question);
        $appCode = $this->normalizeAppCode((string) ($context['app_code'] ?? 'goomoo'));
        $scene = trim((string) ($context['scene'] ?? 'presale')) ?: 'presale';
        $productId = intval($context['productId'] ?? 0);
        $orderId = intval($context['orderId'] ?? 0);
        $userId = intval($context['userId'] ?? 0);
        $normalizer = new AiTextNormalizer();
        $textViews = is_array($context['normalizedText'] ?? null)
            ? $context['normalizedText']
            : $normalizer->normalize($question);

        $rules = $this->loadRules($appCode);
        foreach ($rules as $rule) {
            $routeType = strtolower((string) ($rule['routeType'] ?? 'allow'));
            if ($routeType === 'allow' || $routeType === 'clarify') {
                continue;
            }
            $matched = $this->matchRule($rule, $textViews, $normalizer);
            if ($matched === null) {
                continue;
            }
            return $this->buildResult($routeType, (string) ($rule['taskType'] ?? ''), $matched['reason'], $scene, $productId, $orderId, $userId, $matched['matchedVia'] ?? '');
        }

        $semantic = $this->matchSemanticIntent($context['questionVector'] ?? [], $appCode);
        if ($semantic !== null && !empty($semantic['finalRoute'])) {
            return $this->buildResult(
                (string) $semantic['finalRoute'],
                (string) ($semantic['taskType'] ?? 'semantic_intent'),
                (string) ($semantic['reason'] ?? ''),
                $scene,
                $productId,
                $orderId,
                $userId,
                'semantic'
            );
        }

        foreach ($rules as $rule) {
            $routeType = strtolower((string) ($rule['routeType'] ?? 'allow'));
            if ($routeType !== 'clarify') {
                continue;
            }
            $matched = $this->matchRule($rule, $textViews, $normalizer);
            if ($matched === null) {
                continue;
            }
            return $this->buildResult('clarify', (string) ($rule['taskType'] ?? ''), $matched['reason'], $scene, $productId, $orderId, $userId, $matched['matchedVia'] ?? '');
        }

        if ($productId <= 0 && $orderId <= 0 && $this->isVagueQuestion((string) ($textViews['compact'] ?? ''))) {
            return $this->buildResult('clarify', 'missing_context', 'question is vague and has no product/order context', $scene, $productId, $orderId, $userId);
        }

        foreach ($rules as $rule) {
            $routeType = strtolower((string) ($rule['routeType'] ?? 'allow'));
            if ($routeType !== 'allow') {
                continue;
            }
            $matched = $this->matchRule($rule, $textViews, $normalizer);
            if ($matched === null) {
                continue;
            }
            return $this->buildResult('allow', (string) ($rule['taskType'] ?? ''), $matched['reason'], $scene, $productId, $orderId, $userId, $matched['matchedVia'] ?? '');
        }

        return $this->buildResult('allow', 'general_customer_service', 'no boundary rule matched', $scene, $productId, $orderId, $userId);
    }

    public function buildRouteReply(array $route, array $knowledge = []): string
    {
        $finalRoute = (string) ($route['finalRoute'] ?? 'allow');
        if ($finalRoute === 'reject') {
            return '这个问题涉及平台规则、隐私或合规边界，AI客服无法协助处理。请通过平台内正规客服渠道咨询。';
        }
        if ($finalRoute === 'clarify') {
            return '为了更准确地帮你处理，请补充具体商品信息，或从商品详情页/订单详情页进入咨询。';
        }
        if ($finalRoute === 'handoff') {
            $summary = $this->buildFactSummary($knowledge);
            return '这个问题需要人工客服进一步核实处理，AI客服只做事实整理，不作处理结果承诺。' . ($summary !== '' ? "\n" . $summary : '');
        }

        return '';
    }

    public function buildFactSummary(array $knowledge = []): string
    {
        $order = is_array($knowledge['order'] ?? null) ? $knowledge['order'] : [];
        $product = is_array($knowledge['product'] ?? null) ? $knowledge['product'] : [];
        $lines = [];

        if (!empty($order)) {
            $lines[] = '已识别订单：' . (string) ($order['orderNo'] ?? '');
            $lines[] = '订单状态：' . intval($order['status'] ?? 0);
            $lines[] = '退款状态：' . intval($order['refundStatus'] ?? 0);
            if (!empty($order['freightName']) || !empty($order['freightNo'])) {
                $lines[] = '物流信息：' . (string) ($order['freightName'] ?? '暂无') . ' ' . (string) ($order['freightNo'] ?? '暂无');
            }
        }

        if (!empty($product)) {
            $name = trim((string) ($product['subtitle'] ?? '') . ' ' . (string) ($product['title'] ?? ''));
            if ($name !== '') {
                $lines[] = '相关商品：' . $name;
            }
        }

        if (empty($lines)) {
            return '当前没有拿到可核实的商品或订单事实，请从对应详情页进入或补充订单信息。';
        }

        return '事实摘要：' . implode('；', $lines);
    }

    private function matchRule(array $rule, array $textViews, AiTextNormalizer $normalizer): ?array
    {
        $keywords = $this->splitKeywords($rule['keywords'] ?? []);
        if (empty($keywords)) {
            return null;
        }

        $routeType = strtolower((string) ($rule['routeType'] ?? 'allow'));
        $aggressive = $routeType === 'reject';
        foreach ($keywords as $keyword) {
            $keywordViews = $normalizer->keywordViews($keyword);
            $matchedVia = $this->matchKeywordViews($textViews, $keywordViews, $aggressive);
            if ($matchedVia === '') {
                continue;
            }
            return [
                'reason' => 'matched rule keyword: ' . $keyword . ' via ' . $matchedVia,
                'matchedVia' => $matchedVia,
            ];
        }
        return null;
    }

    private function matchKeywordViews(array $textViews, array $keywordViews, bool $aggressive): string
    {
        $views = ['raw', 'compact'];
        $compactKeywordLength = mb_strlen((string) ($keywordViews['compact'] ?? ''), 'UTF-8');
        if ($aggressive && $compactKeywordLength >= 3) {
            $views = ['raw', 'compact', 'canonical', 'pinyin', 'pinyinInitials'];
        }
        foreach ($views as $view) {
            $content = (string) ($textViews[$view] ?? '');
            $keyword = (string) ($keywordViews[$view] ?? '');
            if ($keyword === '' || $content === '') {
                continue;
            }
            if (($view === 'pinyin' || $view === 'pinyinInitials') && empty($keywordViews['pinyinComplete'])) {
                continue;
            }
            if (($view === 'pinyin' || $view === 'pinyinInitials') && strlen($keyword) < 4) {
                if ($content === $keyword) {
                    return $view;
                }
                continue;
            }
            if (mb_strpos($content, $keyword, 0, 'UTF-8') !== false) {
                return $view;
            }
        }
        return '';
    }

    private function matchSemanticIntent($questionVector, string $appCode): ?array
    {
        if (!is_array($questionVector) || empty($questionVector)) {
            return null;
        }

        try {
            $match = (new AiIntentMatcher())->match($questionVector, $appCode);
            if ($match === null) {
                return null;
            }
            $config = RagConfig::load();
            $rejectThreshold = floatval($config['intent_reject_threshold'] ?? 0.88);
            $handoffThreshold = floatval($config['intent_handoff_threshold'] ?? 0.80);
            $score = floatval($match['score'] ?? 0);
            $routeType = strtolower((string) ($match['routeType'] ?? 'handoff'));
            $finalRoute = '';
            if ($routeType === 'reject' && $score >= $rejectThreshold) {
                $finalRoute = 'reject';
            } elseif ($score >= $handoffThreshold) {
                $finalRoute = $routeType === 'reject' ? 'handoff' : $routeType;
                if (!in_array($finalRoute, ['handoff', 'clarify'], true)) {
                    $finalRoute = 'handoff';
                }
            }
            if ($finalRoute === '') {
                return null;
            }
            return [
                'finalRoute' => $finalRoute,
                'taskType' => (string) ($match['taskType'] ?? 'semantic_intent'),
                'reason' => 'semantic_intent matched example "' . (string) ($match['exampleText'] ?? '') . '" score=' . $score . ' route=' . $routeType,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    private function buildResult(string $routeType, string $taskType, string $reason, string $scene, int $productId, int $orderId, int $userId, string $matchedVia = ''): array
    {
        $routeType = strtolower(trim($routeType));
        if (!in_array($routeType, ['allow', 'handoff', 'reject', 'clarify'], true)) {
            $routeType = 'allow';
        }
        $taskType = $taskType !== '' ? $taskType : $routeType;

        $dataBoundary = 'public_knowledge_only';
        $actionBoundary = 'answer_from_sources';
        if ($orderId > 0 || $scene === 'aftersale') {
            $dataBoundary = $userId > 0 ? 'own_order_only' : 'login_required_for_order';
        }
        if ($routeType === 'handoff') {
            $dataBoundary = $orderId > 0 ? 'own_order_fact_summary' : $dataBoundary;
            $actionBoundary = 'fact_summary_handoff';
        } elseif ($routeType === 'reject') {
            $dataBoundary = 'blocked_external_or_private_data';
            $actionBoundary = 'refuse';
        } elseif ($routeType === 'clarify') {
            $dataBoundary = ($productId <= 0 && $orderId <= 0) ? 'needs_product_or_order_context' : $dataBoundary;
            $actionBoundary = 'ask_for_context';
        }

        return [
            'taskBoundary' => $taskType,
            'dataBoundary' => $dataBoundary,
            'actionBoundary' => $actionBoundary,
            'finalRoute' => $routeType,
            'reason' => $reason,
            'matchedVia' => $matchedVia,
        ];
    }

    private function loadRules(string $appCode): array
    {
        $rows = $this->loadDbRules($appCode);
        if (!empty($rows)) {
            return array_merge($rows, $this->defaultRules());
        }
        return $this->defaultRules();
    }

    private function loadDbRules(string $appCode): array
    {
        if (!$this->tableExists('ai_boundary_rule')) {
            return [];
        }

        try {
            return Db::name('ai_boundary_rule')
                ->where('status', 1)
                ->whereIn('app_code', ['common', $appCode])
                ->order('sort asc, id asc')
                ->select()
                ->toArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function defaultRules(): array
    {
        return [
            ['routeType' => 'reject', 'taskType' => 'illegal_or_abuse', 'keywords' => '毒品,枪支,赌博,诈骗,洗钱,跑分,绕平台交易,私下交易,跳过平台,导出数据,导出全部,全部订单数据'],
            ['routeType' => 'reject', 'taskType' => 'abusive_language', 'keywords' => '操你妈,草你妈,艹你妈,曹尼玛,你妈逼,妈逼,傻逼,煞笔,cnm,nmsl,操你妹,你妈的,尼玛的,妈的智障,滚你妈'],
            ['routeType' => 'reject', 'taskType' => 'privacy_request', 'keywords' => '他人订单,别人订单,其他用户,手机号,完整地址,身份证,隐私,人肉,导出订单,订单数据,全部订单'],
            ['routeType' => 'reject', 'taskType' => 'off_platform_trade', 'keywords' => '绕平台交易,私下交易,跳过平台,加微信直接买,微信直接买,平台外交易'],
            ['routeType' => 'handoff', 'taskType' => 'refund_request', 'keywords' => '退款,退货,退定金,退一下,退一下定金,退尾款,退掉,能不能退,申请退,refund,chargeback'],
            ['routeType' => 'handoff', 'taskType' => 'complaint_or_compensation', 'keywords' => '投诉,赔偿,补偿,维权,仲裁,法务,律师,质量问题,破损,少件,错发'],
            ['routeType' => 'handoff', 'taskType' => 'shipping_commitment', 'keywords' => '承诺发货,保证发货,具体发货时间,必须发货,什么时候一定发,最晚什么时候发'],
            ['routeType' => 'handoff', 'taskType' => 'order_exception', 'keywords' => '订单异常,支付异常,扣款没订单,重复扣款,订单丢了,无法下单'],
            ['routeType' => 'allow', 'taskType' => 'product_intro', 'keywords' => '商品,介绍,尺寸,材质,比例,价格,多少钱,pa011,ntw-20,少女前线'],
            ['routeType' => 'allow', 'taskType' => 'presale_rule', 'keywords' => '预售,定金,尾款,补款,截单,预定'],
            ['routeType' => 'allow', 'taskType' => 'shipping_rule', 'keywords' => '发货规则,发货,物流,快递,到货'],
            ['routeType' => 'allow', 'taskType' => 'payment_rule', 'keywords' => '支付,付款,微信支付,支付宝,银行卡'],
            ['routeType' => 'allow', 'taskType' => 'points_rule', 'keywords' => '积分,猫币,蜗壳,抵扣,points'],
        ];
    }

    private function splitKeywords($keywords): array
    {
        if (is_array($keywords)) {
            $items = $keywords;
        } else {
            $items = preg_split('/[,，\r\n]+/u', (string) $keywords);
        }
        $result = [];
        foreach ($items as $item) {
            $item = mb_strtolower(trim((string) $item), 'UTF-8');
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return array_values(array_unique($result));
    }

    private function containsAny(string $content, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && mb_strpos($content, $keyword, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    private function isVagueQuestion(string $question): bool
    {
        $text = preg_replace('/[\s\p{P}]+/u', '', $question);
        if ($text === '') {
            return true;
        }
        if (mb_strlen($text, 'UTF-8') <= 6 && $this->containsAny($text, ['这个', '这个商品', '这个订单', '怎么', '咋办', '怎么样', '啥情况', '咨询'])) {
            return true;
        }
        return $this->containsAny($text, ['这个怎么样', '这个商品怎么样', '这个怎么弄', '这个订单怎么弄', '这个能买吗', '这个啥情况', '帮我看看这个']);
    }

    private function tableExists(string $name): bool
    {
        static $cache = [];
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        try {
            Db::name($name)->limit(1)->select();
            $cache[$name] = true;
            return true;
        } catch (Throwable $e) {
            $cache[$name] = false;
            return false;
        }
    }

    private function normalizeAppCode(string $appCode): string
    {
        if (function_exists('normalize_app_code_value')) {
            return normalize_app_code_value($appCode);
        }
        $appCode = strtolower(trim($appCode));
        $appCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $appCode);
        return $appCode === '' ? 'goomoo' : $appCode;
    }
}
