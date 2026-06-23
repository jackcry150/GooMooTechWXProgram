<?php

namespace app\common\service;

class QdrantClient
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: RagConfig::load();
    }

    public function ensureCollection(int $dimension): array
    {
        $collection = $this->collection();
        $exists = $this->request('GET', '/collections/' . rawurlencode($collection));
        if ($exists['ok']) {
            return ['ok' => true, 'error' => ''];
        }

        $payload = [
            'vectors' => [
                'size' => $dimension,
                'distance' => 'Cosine',
            ],
        ];
        $created = $this->request('PUT', '/collections/' . rawurlencode($collection), $payload);
        return ['ok' => $created['ok'], 'error' => $created['error']];
    }

    public function upsertPoint(string $pointId, array $vector, array $payload): array
    {
        $body = [
            'points' => [
                [
                    'id' => $pointId,
                    'vector' => array_values($vector),
                    'payload' => $payload,
                ],
            ],
        ];
        $res = $this->request('PUT', '/collections/' . rawurlencode($this->collection()) . '/points?wait=true', $body);
        return ['ok' => $res['ok'], 'error' => $res['error']];
    }

    public function deletePoint(string $pointId): array
    {
        $body = ['points' => [$pointId]];
        $res = $this->request('POST', '/collections/' . rawurlencode($this->collection()) . '/points/delete?wait=true', $body);
        return ['ok' => $res['ok'], 'error' => $res['error']];
    }

    public function search(array $vector, array $filter, int $limit, float $scoreThreshold): array
    {
        $body = [
            'vector' => array_values($vector),
            'limit' => max(1, $limit),
            'with_payload' => true,
            'score_threshold' => $scoreThreshold,
        ];
        if (!empty($filter)) {
            $body['filter'] = $filter;
        }

        $res = $this->request('POST', '/collections/' . rawurlencode($this->collection()) . '/points/search', $body);
        if (!$res['ok']) {
            return ['ok' => false, 'items' => [], 'error' => $res['error']];
        }

        $decoded = json_decode($res['body'], true);
        $items = is_array($decoded['result'] ?? null) ? $decoded['result'] : [];
        return ['ok' => true, 'items' => $items, 'error' => ''];
    }

    private function collection(): string
    {
        return (string) ($this->config['collection'] ?? 'goomootech_customer_service');
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $url = rtrim((string) $this->config['qdrant_url'], '/') . $path;
        $headers = ['Content-Type: application/json'];
        if (!empty($this->config['qdrant_api_key'])) {
            $headers[] = 'api-key: ' . $this->config['qdrant_api_key'];
        }

        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HEADER => false,
        ];
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($curl, $options);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        curl_close($curl);

        return [
            'ok' => $error === '' && $httpCode >= 200 && $httpCode < 300,
            'body' => is_string($body) ? $body : '',
            'error' => $error ?: ('http ' . $httpCode),
            'httpCode' => $httpCode,
        ];
    }
}
