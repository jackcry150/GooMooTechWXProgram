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
            $embed = (new EmbeddingClient($config))->embed($question);
            if (!$embed['ok']) {
                return ['enabled' => true, 'contexts' => [], 'error' => $embed['error']];
            }

            $filter = $this->buildFilter($options);
            $search = (new QdrantClient($config))->search(
                $embed['embedding'],
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
                $contexts[] = [
                    'sourceId' => intval($chunk['sourceId']),
                    'chunkId' => intval($chunk['id']),
                    'sourceType' => (string) $chunk['sourceType'],
                    'title' => (string) $chunk['title'],
                    'content' => (string) $chunk['content'],
                    'score' => round(floatval($item['score'] ?? 0), 4),
                ];
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
}
