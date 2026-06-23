<?php

namespace app\common\service;

class EmbeddingClient
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: RagConfig::load();
    }

    public function embed(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'embedding' => [], 'error' => 'empty text'];
        }

        if (empty($this->config['embedding_base_url']) || empty($this->config['embedding_api_key'])) {
            return ['ok' => false, 'embedding' => [], 'error' => 'embedding config missing'];
        }

        $url = rtrim((string) $this->config['embedding_base_url'], '/') . '/embeddings';
        $payload = [
            'model' => (string) $this->config['embedding_model'],
            'input' => $text,
        ];

        $response = $this->postJson($url, $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['embedding_api_key'],
        ], intval($this->config['embedding_timeout'] ?? 20));

        if (!$response['ok']) {
            return ['ok' => false, 'embedding' => [], 'error' => $response['error'] ?: ('http ' . $response['httpCode'])];
        }

        $body = json_decode($response['body'], true);
        $embedding = $body['data'][0]['embedding'] ?? null;
        if (!is_array($embedding)) {
            return ['ok' => false, 'embedding' => [], 'error' => 'embedding missing'];
        }

        $vector = array_map('floatval', $embedding);
        $dimension = intval($this->config['embedding_dimension'] ?? 0);
        if ($dimension > 0 && count($vector) !== $dimension) {
            return ['ok' => false, 'embedding' => [], 'error' => 'embedding dimension mismatch'];
        }

        return ['ok' => true, 'embedding' => $vector, 'error' => ''];
    }

    private function postJson(string $url, array $payload, array $headers, int $timeout): array
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
            CURLOPT_TIMEOUT => max(1, $timeout),
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
}
