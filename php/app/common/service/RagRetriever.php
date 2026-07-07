<?php

namespace app\common\service;

use Exception;
use think\facade\Db;

class RagRetriever
{
    public function retrieve(string $question, array $options = []): array
    {
        if (!RagConfig::enabled()) {
            return ['enabled' => false, 'contexts' => [], 'error' => 'rag disabled'];
        }

        try {
            $config = RagConfig::load();
            $questionVector = is_array($options['questionVector'] ?? null) ? $options['questionVector'] : [];
            if (empty($questionVector)) {
                $embed = (new EmbeddingClient($config))->embed($question);
                if (!$embed['ok']) {
                    return ['enabled' => true, 'contexts' => [], 'error' => $embed['error']];
                }
                $questionVector = $embed['embedding'];
            }

            $filter = $this->buildFilter($options);
            $search = (new QdrantClient($config))->search(
                $questionVector,
                $filter,
                intval($config['search_limit'] ?? 6),
                floatval($config['score_threshold'] ?? 0.35)
            );
            if (!$search['ok']) {
                return ['enabled' => true, 'contexts' => [], 'error' => $search['error']];
            }

            $contexts = [];
            foreach ($search['items'] as $item) {
                $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
                $chunkId = intval($payload['chunkId'] ?? 0);
                if ($chunkId <= 0) {
                    continue;
                }
                $chunk = Db::name('ai_knowledge_chunk')->where('id', $chunkId)->find();
                if (!$chunk || intval($chunk['embeddingStatus'] ?? 0) !== 1) {
                    continue;
                }
                $contexts[] = $this->formatContext($chunk, round(floatval($item['score'] ?? 0), 4), 'vector');
            }

            if (empty($contexts)) {
                $contexts = $this->keywordFallback($question, $options, intval($config['search_limit'] ?? 6));
            }

            return ['enabled' => true, 'contexts' => $contexts, 'error' => ''];
        } catch (Exception $e) {
            return ['enabled' => true, 'contexts' => [], 'error' => $e->getMessage()];
        }
    }

    private function buildFilter(array $options): array
    {
        $must = [];
        $appCode = (string) ($options['app_code'] ?? 'hasuki');
        if ($appCode !== '') {
            $must[] = ['key' => 'app_code', 'match' => ['value' => $appCode]];
        }

        return empty($must) ? [] : ['must' => $must];
    }

    private function keywordFallback(string $question, array $options, int $limit): array
    {
        $terms = $this->extractKeywordTerms($question);
        if (empty($terms)) {
            return [];
        }

        $appCode = (string) ($options['app_code'] ?? 'hasuki');
        $query = Db::name('ai_knowledge_chunk')
            ->where('embeddingStatus', 1);

        if ($appCode !== '') {
            $query->where('app_code', $appCode);
        }

        $query->where(function ($query) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';
                $query->whereOr('title', 'like', $like)
                    ->whereOr('content', 'like', $like);
            }
        });

        $rows = $query->order('sourceType asc, id asc')
            ->limit(max(1, $limit))
            ->select()
            ->toArray();

        $contexts = [];
        foreach ($rows as $row) {
            $contexts[] = $this->formatContext($row, 0.30, 'keyword');
        }

        return $contexts;
    }

    private function extractKeywordTerms(string $question): array
    {
        $terms = [];
        if (preg_match_all('/[a-z0-9][a-z0-9_-]{1,}/i', $question, $matches)) {
            foreach ($matches[0] as $match) {
                $terms[] = strtolower($match);
            }
        }

        if (preg_match_all('/[\x{4e00}-\x{9fa5}]{2,}/u', $question, $matches)) {
            foreach ($matches[0] as $phrase) {
                $length = mb_strlen($phrase, 'UTF-8');
                if ($length <= 6) {
                    $terms[] = $phrase;
                }
                for ($size = 2; $size <= 4; $size++) {
                    if ($length < $size) {
                        continue;
                    }
                    for ($offset = 0; $offset <= $length - $size; $offset++) {
                        $term = mb_substr($phrase, $offset, $size, 'UTF-8');
                        if (!$this->isWeakChineseTerm($term)) {
                            $terms[] = $term;
                        }
                    }
                }
            }
        }

        return array_slice(array_values(array_unique($terms)), 0, 40);
    }

    private function isWeakChineseTerm(string $term): bool
    {
        return in_array($term, ['这个', '那个', '什么', '怎么', '一下', '可以', '商品', '订单', '客服', '请问', '帮我'], true);
    }

    private function formatContext(array $chunk, float $score, string $matchType = 'vector'): array
    {
        return [
            'sourceId' => intval($chunk['sourceId']),
            'chunkId' => intval($chunk['id']),
            'sourceType' => (string) $chunk['sourceType'],
            'title' => (string) $chunk['title'],
            'content' => (string) $chunk['content'],
            'score' => $score,
            'matchType' => $matchType,
        ];
    }
}
