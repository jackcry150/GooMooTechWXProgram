<?php

namespace app\common\service;

use Exception;
use think\facade\Db;

class KnowledgeIndexer
{
    public function syncProduct(int $productId): array
    {
        $product = Db::name('product')->where('id', $productId)->find();
        if (!$product) {
            return ['ok' => false, 'sourceId' => 0, 'error' => 'product not found'];
        }

        $appCode = $this->normalizeAppCode($product['app_code'] ?? 'goomoo');
        $title = trim(($product['subtitle'] ?? '') . ' ' . ($product['title'] ?? ''));
        $content = $this->buildProductContent($product);
        return $this->upsertSource('product', $productId, $appCode, $title, $content, intval($product['status'] ?? 1) === 1 ? 1 : 2, '');
    }

    public function syncNews(int $newsId): array
    {
        $news = Db::name('news')->where('id', $newsId)->find();
        if (!$news) {
            return ['ok' => false, 'sourceId' => 0, 'error' => 'news not found'];
        }

        $appCode = $this->normalizeAppCode($news['app_code'] ?? 'goomoo');
        return $this->upsertSource('news', $newsId, $appCode, (string) ($news['title'] ?? ''), (string) ($news['content'] ?? ''), 1, '');
    }

    public function syncManualSource(int $sourceId): array
    {
        $source = Db::name('ai_knowledge_source')->where('id', $sourceId)->find();
        if (!$source) {
            return ['ok' => false, 'sourceId' => 0, 'error' => 'source not found'];
        }

        return $this->rebuildChunks($source);
    }

    public function enqueueChunks(int $sourceId): array
    {
        $source = Db::name('ai_knowledge_source')->where('id', $sourceId)->find();
        if (!$source) {
            return ['ok' => false, 'queued' => 0, 'error' => 'source not found'];
        }
        $result = $this->rebuildChunks($source);
        return ['ok' => $result['ok'], 'queued' => $result['queued'] ?? 0, 'error' => $result['error'] ?? ''];
    }

    public function runPendingJobs(int $limit = 20): array
    {
        if (!RagConfig::enabled()) {
            return ['ok' => false, 'processed' => 0, 'failed' => 0, 'error' => 'rag disabled'];
        }

        $config = RagConfig::load();
        $jobs = Db::name('ai_embedding_job')
            ->where('status', 0)
            ->where(function ($query) {
                $query->whereNull('runAfter')->whereOr('runAfter', '<=', date('Y-m-d H:i:s'));
            })
            ->order('id asc')
            ->limit(max(1, $limit))
            ->select()
            ->toArray();

        $embedding = new EmbeddingClient($config);
        $qdrant = new QdrantClient($config);
        $processed = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            Db::name('ai_embedding_job')->where('id', $job['id'])->update([
                'status' => 1,
                'attempts' => intval($job['attempts'] ?? 0) + 1,
            ]);

            try {
                $chunk = Db::name('ai_knowledge_chunk')->where('id', $job['chunkId'])->find();
                if (!$chunk) {
                    throw new Exception('chunk not found');
                }

                if (($job['jobType'] ?? 'upsert') === 'delete') {
                    $delete = $qdrant->deletePoint((string) $chunk['qdrantPointId']);
                    if (!$delete['ok']) {
                        throw new Exception($delete['error']);
                    }
                } else {
                    $embed = $embedding->embed((string) $chunk['content']);
                    if (!$embed['ok']) {
                        throw new Exception($embed['error']);
                    }
                    $collection = $qdrant->ensureCollection(intval($config['embedding_dimension'] ?? 1536));
                    if (!$collection['ok']) {
                        throw new Exception($collection['error']);
                    }
                    $pointId = $chunk['qdrantPointId'] ?: $this->uuidFromHash($chunk['contentHash'] . ':' . $chunk['id']);
                    $upsert = $qdrant->upsertPoint($pointId, $embed['embedding'], [
                        'chunkId' => intval($chunk['id']),
                        'sourceId' => intval($chunk['sourceId']),
                        'sourceType' => (string) $chunk['sourceType'],
                        'originId' => intval($chunk['originId']),
                        'app_code' => (string) $chunk['app_code'],
                        'title' => (string) $chunk['title'],
                    ]);
                    if (!$upsert['ok']) {
                        throw new Exception($upsert['error']);
                    }
                    Db::name('ai_knowledge_chunk')->where('id', $chunk['id'])->update([
                        'qdrantPointId' => $pointId,
                        'embeddingStatus' => 1,
                        'embeddingError' => '',
                        'lastEmbeddedAt' => date('Y-m-d H:i:s'),
                    ]);
                }

                Db::name('ai_embedding_job')->where('id', $job['id'])->update(['status' => 2, 'lastError' => '']);
                $processed++;
            } catch (Exception $e) {
                $failed++;
                $message = mb_substr($e->getMessage(), 0, 500, 'UTF-8');
                Db::name('ai_embedding_job')->where('id', $job['id'])->update([
                    'status' => 3,
                    'lastError' => $message,
                    'runAfter' => date('Y-m-d H:i:s', time() + 300),
                ]);
                if (!empty($job['chunkId'])) {
                    Db::name('ai_knowledge_chunk')->where('id', $job['chunkId'])->update([
                        'embeddingStatus' => 2,
                        'embeddingError' => $message,
                    ]);
                }
            }
        }

        return ['ok' => true, 'processed' => $processed, 'failed' => $failed, 'error' => ''];
    }

    private function upsertSource(string $type, int $originId, string $appCode, string $title, string $content, int $status, string $aliases = ''): array
    {
        $contentHash = hash('sha256', $title . "\n" . $aliases . "\n" . $content . "\n" . $status);
        $where = ['sourceType' => $type, 'sourceId' => $originId, 'app_code' => $appCode];
        $existing = Db::name('ai_knowledge_source')->where($where)->find();
        $data = [
            'sourceType' => $type,
            'sourceId' => $originId,
            'app_code' => $appCode,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'contentHash' => $contentHash,
        ];
        if ($this->sourceAliasesSupported()) {
            $data['aliases'] = $aliases;
        }
        if ($this->sourceReviewSupported() && $type !== 'manual') {
            $data['reviewStatus'] = 2;
            $data['reviewerId'] = 0;
            $data['reviewerName'] = 'system';
            $data['reviewedAt'] = date('Y-m-d H:i:s');
            $data['reviewRemark'] = '';
        }

        if ($existing) {
            Db::name('ai_knowledge_source')->where('id', $existing['id'])->update($data);
            $sourceId = intval($existing['id']);
            $source = array_merge($existing, $data, ['id' => $sourceId]);
        } else {
            $sourceId = Db::name('ai_knowledge_source')->insertGetId($data);
            $source = array_merge($data, ['id' => $sourceId]);
        }

        $result = $this->rebuildChunks($source);
        return ['ok' => $result['ok'], 'sourceId' => $sourceId, 'queued' => $result['queued'] ?? 0, 'error' => $result['error'] ?? ''];
    }

    private function rebuildChunks(array $source): array
    {
        $sourceId = intval($source['id'] ?? 0);
        if ($sourceId <= 0) {
            return ['ok' => false, 'queued' => 0, 'error' => 'invalid source'];
        }

        Db::name('ai_knowledge_chunk')->where('sourceId', $sourceId)->update(['embeddingStatus' => 2, 'embeddingError' => 'superseded']);
        $chunks = (new KnowledgeChunker())->chunk((string) ($source['title'] ?? ''), (string) ($source['content'] ?? ''), 650, 80, (string) ($source['aliases'] ?? ''));
        $queued = 0;
        $sourceEnabled = intval($source['status'] ?? 1) === 1;
        $sourceApproved = $this->sourceApprovedForEmbedding($source);
        $embeddingStatus = ($sourceEnabled && $sourceApproved) ? 0 : 2;
        $embeddingError = $sourceEnabled ? ($sourceApproved ? '' : 'source not approved') : 'source disabled';

        foreach ($chunks as $index => $chunk) {
            $contentHash = hash('sha256', $chunk['content']);
            $pointId = $this->uuidFromHash($sourceId . ':' . $index . ':' . $contentHash);
            $chunkData = [
                'sourceId' => $sourceId,
                'sourceType' => (string) ($source['sourceType'] ?? 'manual'),
                'originId' => intval($source['sourceId'] ?? 0),
                'app_code' => (string) ($source['app_code'] ?? 'goomoo'),
                'chunkIndex' => $index,
                'title' => (string) ($chunk['title'] ?? ''),
                'content' => (string) ($chunk['content'] ?? ''),
                'contentHash' => $contentHash,
                'qdrantPointId' => $pointId,
                'embeddingStatus' => $embeddingStatus,
                'embeddingError' => $embeddingError,
            ];

            $existing = Db::name('ai_knowledge_chunk')
                ->where('sourceId', $sourceId)
                ->where('chunkIndex', $index)
                ->where('contentHash', $contentHash)
                ->find();
            if ($existing) {
                Db::name('ai_knowledge_chunk')->where('id', $existing['id'])->update($chunkData);
                $chunkId = intval($existing['id']);
            } else {
                $chunkId = Db::name('ai_knowledge_chunk')->insertGetId($chunkData);
            }

            if ($sourceEnabled && $sourceApproved) {
                Db::name('ai_embedding_job')->insert([
                    'chunkId' => $chunkId,
                    'jobType' => 'upsert',
                    'status' => 0,
                ]);
                $queued++;
            }
        }

        Db::name('ai_knowledge_source')->where('id', $sourceId)->update(['lastIndexedAt' => date('Y-m-d H:i:s')]);
        return ['ok' => true, 'queued' => $queued, 'error' => ''];
    }

    private function sourceReviewSupported(): bool
    {
        if (function_exists('table_has_column')) {
            return table_has_column('ai_knowledge_source', 'reviewStatus');
        }
        return false;
    }

    private function sourceAliasesSupported(): bool
    {
        if (function_exists('table_has_column')) {
            return table_has_column('ai_knowledge_source', 'aliases');
        }
        return false;
    }

    private function sourceApprovedForEmbedding(array $source): bool
    {
        if (!$this->sourceReviewSupported()) {
            return true;
        }
        $sourceType = (string) ($source['sourceType'] ?? 'manual');
        if ($sourceType !== 'manual' && !array_key_exists('reviewStatus', $source)) {
            return true;
        }
        return intval($source['reviewStatus'] ?? 1) === 2;
    }
    private function buildProductContent(array $product): string
    {
        $lines = [];
        $lines[] = '商品名称：' . trim(($product['subtitle'] ?? '') . ' ' . ($product['title'] ?? ''));
        $lines[] = '商品类型：' . (intval($product['type'] ?? 1) === 2 ? '预售' : '现货');
        $lines[] = '售价：' . number_format((float) ($product['price'] ?? 0), 2, '.', '');
        $lines[] = '定金：' . number_format((float) ($product['deposit'] ?? 0), 2, '.', '');
        $lines[] = '预售结束时间：' . (string) ($product['endTime'] ?? '');
        foreach (['proportion' => '比例', 'dimensions' => '尺寸', 'material' => '材质', 'copyright' => '版权所属'] as $field => $label) {
            if (!empty($product[$field])) {
                $lines[] = $label . '：' . $product[$field];
            }
        }
        if (!empty($product['content'])) {
            $lines[] = '商品详情：' . $product['content'];
        }
        return implode("\n", $lines);
    }

    private function normalizeAppCode(string $appCode): string
    {
        if (function_exists('normalize_app_code_value')) {
            return normalize_app_code_value($appCode);
        }
        return $appCode === '' ? 'goomoo' : $appCode;
    }

    private function uuidFromHash(string $value): string
    {
        $hash = md5($value);
        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);
    }
}
