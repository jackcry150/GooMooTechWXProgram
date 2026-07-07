<?php

namespace app\common\service;

use think\facade\Db;
use Throwable;

class AiIntentMatcher
{
    private array $examples;

    public function __construct(?array $examples = null)
    {
        $this->examples = $examples ?? [];
    }

    public function match(array $questionVector, string $appCode = 'goomoo'): ?array
    {
        if (empty($questionVector)) {
            return null;
        }

        $examples = !empty($this->examples) ? $this->examples : $this->loadExamples($appCode);
        if (empty($examples)) {
            return null;
        }

        $best = null;
        foreach ($examples as $example) {
            $vector = $this->parseVector($example['vector'] ?? []);
            if (empty($vector)) {
                continue;
            }
            $score = $this->cosine($questionVector, $vector);
            if ($best === null || $score > $best['score']) {
                $best = [
                    'routeType' => strtolower((string) ($example['routeType'] ?? 'handoff')),
                    'taskType' => (string) ($example['taskType'] ?? ''),
                    'score' => round($score, 4),
                    'exampleText' => (string) ($example['text'] ?? ''),
                ];
            }
        }

        return $best;
    }

    public function hasUsableExamples(string $appCode = 'goomoo'): bool
    {
        if (!empty($this->examples)) {
            foreach ($this->examples as $example) {
                if (!empty($this->parseVector($example['vector'] ?? []))) {
                    return true;
                }
            }
            return false;
        }

        if (!$this->tableExists('ai_intent_example')) {
            return false;
        }

        try {
            return Db::name('ai_intent_example')
                ->where('status', 1)
                ->where('embeddingStatus', 1)
                ->whereIn('app_code', ['common', $this->normalizeAppCode($appCode)])
                ->count() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function embedAndSave(int $id): array
    {
        if ($id <= 0 || !$this->tableExists('ai_intent_example')) {
            return ['ok' => false, 'error' => 'intent example table missing'];
        }

        try {
            $example = Db::name('ai_intent_example')->where('id', $id)->find();
            if (!$example) {
                return ['ok' => false, 'error' => 'intent example not found'];
            }

            $text = trim((string) ($example['text'] ?? ''));
            $embed = (new EmbeddingClient(RagConfig::load()))->embed($text);
            if (empty($embed['ok'])) {
                Db::name('ai_intent_example')->where('id', $id)->update([
                    'embeddingStatus' => 2,
                    'embeddingError' => mb_substr((string) ($embed['error'] ?? 'embedding failed'), 0, 500, 'UTF-8'),
                    'updateTime' => date('Y-m-d H:i:s'),
                ]);
                return ['ok' => false, 'error' => (string) ($embed['error'] ?? 'embedding failed')];
            }

            Db::name('ai_intent_example')->where('id', $id)->update([
                'vector' => json_encode($embed['embedding'], JSON_UNESCAPED_UNICODE),
                'embeddingStatus' => 1,
                'embeddingError' => '',
                'updateTime' => date('Y-m-d H:i:s'),
            ]);
            self::clearCache();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function runPendingEmbeddings(int $limit = 50): array
    {
        $limit = max(1, $limit);
        $result = ['ok' => true, 'processed' => 0, 'failed' => 0, 'error' => ''];
        if (!$this->tableExists('ai_intent_example')) {
            $result['ok'] = false;
            $result['error'] = 'intent example table missing';
            return $result;
        }

        try {
            $ids = Db::name('ai_intent_example')
                ->where('status', 1)
                ->where('embeddingStatus', '<>', 1)
                ->order('sort asc, id asc')
                ->limit($limit)
                ->column('id');
            foreach ($ids as $id) {
                $embed = $this->embedAndSave(intval($id));
                if (!empty($embed['ok'])) {
                    $result['processed']++;
                } else {
                    $result['failed']++;
                    $result['error'] = $embed['error'] ?? '';
                }
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public static function clearCache(): void
    {
        self::$exampleCache = [];
    }

    private static array $exampleCache = [];

    private function loadExamples(string $appCode): array
    {
        $appCode = $this->normalizeAppCode($appCode);
        if (isset(self::$exampleCache[$appCode])) {
            return self::$exampleCache[$appCode];
        }

        if (!$this->tableExists('ai_intent_example')) {
            self::$exampleCache[$appCode] = [];
            return [];
        }

        try {
            $rows = Db::name('ai_intent_example')
                ->where('status', 1)
                ->where('embeddingStatus', 1)
                ->whereIn('app_code', ['common', $appCode])
                ->order('sort asc, id asc')
                ->select()
                ->toArray();
            self::$exampleCache[$appCode] = $rows;
            return $rows;
        } catch (Throwable $e) {
            self::$exampleCache[$appCode] = [];
            return [];
        }
    }

    private function parseVector($value): array
    {
        if (is_array($value)) {
            return array_map('floatval', $value);
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_map('floatval', $decoded) : [];
    }

    private function cosine(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count <= 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $va = floatval($a[$i]);
            $vb = floatval($b[$i]);
            $dot += $va * $vb;
            $normA += $va * $va;
            $normB += $vb * $vb;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
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
