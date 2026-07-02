<?php

namespace app\common\service;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use Throwable;

class AiSafetyService
{
    public function rateLimit($userId, $ip, $appCode): array
    {
        $config = $this->config();
        $userId = intval($userId);
        $appCode = $this->normalizeAppCode((string) $appCode);
        $ip = trim((string) $ip);
        $limit = $userId > 0
            ? intval($config['rate_limit']['user_per_minute'] ?? 10)
            : intval($config['rate_limit']['guest_per_minute'] ?? 6);
        $limit = max(1, $limit);

        $identity = $userId > 0 ? ('user:' . $userId) : ('ip:' . md5($ip ?: 'unknown'));
        $key = 'ai_safety_rate:' . $appCode . ':' . date('YmdHi') . ':' . $identity;
        $count = intval(Cache::get($key, 0)) + 1;
        Cache::set($key, $count, 70);

        return [
            'ok' => $count <= $limit,
            'count' => $count,
            'limit' => $limit,
            'action' => $count <= $limit ? 'allow' : 'rate_limit',
            'safeReply' => $this->getSafeReply('rate_limit'),
        ];
    }

    public function checkText($text, $appCode, $stage): array
    {
        $text = trim((string) $text);
        $appCode = $this->normalizeAppCode((string) $appCode);
        $stage = trim((string) $stage) ?: 'input';
        if ($text === '' || !$this->tableExists('ai_sensitive_word')) {
            return $this->allowResult($stage);
        }

        try {
            $words = Db::name('ai_sensitive_word')
                ->where('status', 1)
                ->whereIn('app_code', ['common', $appCode])
                ->order('level desc, id asc')
                ->select()
                ->toArray();
        } catch (Throwable $e) {
            return $this->allowResult($stage);
        }

        $hitWords = [];
        $categories = [];
        $level = 0;
        $action = 'allow';
        foreach ($words as $row) {
            $word = trim((string) ($row['word'] ?? ''));
            if ($word === '' || mb_stripos($text, $word, 0, 'UTF-8') === false) {
                continue;
            }
            $hitWords[] = $word;
            $category = trim((string) ($row['category'] ?? ''));
            if ($category !== '') {
                $categories[] = $category;
            }
            $rowLevel = intval($row['level'] ?? 1);
            $rowAction = strtolower(trim((string) ($row['action'] ?? 'block')));
            $level = max($level, $rowLevel);
            if ($rowLevel >= 2 || $rowAction === 'block') {
                $action = 'block';
            } elseif ($action !== 'block' && $rowAction === 'transfer') {
                $action = 'transfer';
            }
        }

        $hitWords = array_values(array_unique($hitWords));
        $categories = array_values(array_unique($categories));
        if (empty($hitWords)) {
            return $this->allowResult($stage);
        }

        return [
            'ok' => $action === 'allow',
            'stage' => $stage,
            'action' => $action,
            'finalAction' => $action,
            'hitWords' => $hitWords,
            'category' => implode(',', $categories),
            'level' => $level,
            'safeReply' => $this->getSafeReply($action),
        ];
    }

    public function log($payload): void
    {
        if (!$this->tableExists('ai_safety_log')) {
            return;
        }

        try {
            $data = [
                'app_code' => $this->normalizeAppCode((string) ($payload['app_code'] ?? 'goomoo')),
                'userId' => intval($payload['userId'] ?? 0),
                'sessionId' => intval($payload['sessionId'] ?? 0),
                'scene' => mb_substr((string) ($payload['scene'] ?? ''), 0, 50, 'UTF-8'),
                'sourcePage' => mb_substr((string) ($payload['sourcePage'] ?? ''), 0, 255, 'UTF-8'),
                'question' => (string) ($payload['question'] ?? ''),
                'reply' => (string) ($payload['reply'] ?? ''),
                'checkStage' => mb_substr((string) ($payload['checkStage'] ?? ''), 0, 20, 'UTF-8'),
                'hitWords' => $this->joinValue($payload['hitWords'] ?? ''),
                'category' => mb_substr($this->joinValue($payload['category'] ?? ''), 0, 100, 'UTF-8'),
                'level' => intval($payload['level'] ?? 0),
                'action' => mb_substr((string) ($payload['action'] ?? 'allow'), 0, 20, 'UTF-8'),
                'finalAction' => mb_substr((string) ($payload['finalAction'] ?? ($payload['action'] ?? 'allow')), 0, 20, 'UTF-8'),
                'ip' => mb_substr((string) ($payload['ip'] ?? ''), 0, 64, 'UTF-8'),
                'retrievalSourceIds' => mb_substr($this->joinValue($payload['retrievalSourceIds'] ?? ''), 0, 500, 'UTF-8'),
                'retrievalContext' => $this->encodeContext($payload['retrievalContext'] ?? ''),
                'createTime' => date('Y-m-d H:i:s'),
            ];
            Db::name('ai_safety_log')->insert($data);
        } catch (Throwable $e) {
        }
    }

    public function getSafeReply($action): string
    {
        $action = strtolower(trim((string) $action));
        if ($action === 'rate_limit') {
            return '提问过于频繁，请稍后再试。';
        }
        return '这个问题涉及较高风险内容，当前无法由AI客服直接处理，建议联系人工客服协助。';
    }

    public function cleanExpired($days = null): array
    {
        $config = $this->config();
        $days = $days === null ? intval($config['retention_days'] ?? 90) : intval($days);
        $days = max(1, $days);
        $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);
        $result = [
            'ok' => true,
            'days' => $days,
            'cutoff' => $cutoff,
            'ai_safety_log' => 0,
            'ai_service_message' => 0,
            'ai_service_session' => 0,
            'skipped' => [],
        ];

        foreach (['ai_safety_log', 'ai_service_message', 'ai_service_session'] as $table) {
            if (!$this->tableExists($table)) {
                $result['skipped'][] = $table;
                continue;
            }
            try {
                $result[$table] = Db::name($table)->where('createTime', '<', $cutoff)->delete();
            } catch (Throwable $e) {
                $result['ok'] = false;
                $result['skipped'][] = $table . ':' . $e->getMessage();
            }
        }

        return $result;
    }

    private function allowResult(string $stage): array
    {
        return [
            'ok' => true,
            'stage' => $stage,
            'action' => 'allow',
            'finalAction' => 'allow',
            'hitWords' => [],
            'category' => '',
            'level' => 0,
            'safeReply' => '',
        ];
    }

    private function config(): array
    {
        $config = Config::get('ai_safety', []);
        return is_array($config) ? $config : [];
    }

    private function tableExists($name): bool
    {
        static $cache = [];
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $name);
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
        return $appCode === '' ? 'goomoo' : preg_replace('/[^a-zA-Z0-9_-]/', '', $appCode);
    }

    private function joinValue($value): string
    {
        if (is_array($value)) {
            $value = implode(',', array_unique(array_map('strval', $value)));
        }
        return mb_substr((string) $value, 0, 500, 'UTF-8');
    }

    private function encodeContext($context): string
    {
        if (is_array($context)) {
            return json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        return (string) $context;
    }
}