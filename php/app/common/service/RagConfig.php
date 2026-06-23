<?php

namespace app\common\service;

class RagConfig
{
    public static function load(): array
    {
        $root = dirname(__DIR__, 3);
        $localFile = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rag.local.php';
        $exampleFile = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rag.example.php';

        $config = [];
        if (is_file($localFile)) {
            $loaded = include $localFile;
            $config = is_array($loaded) ? $loaded : [];
        } elseif (is_file($exampleFile)) {
            $loaded = include $exampleFile;
            $config = is_array($loaded) ? $loaded : [];
        }

        return array_merge(self::defaults(), $config);
    }

    public static function enabled(): bool
    {
        $config = self::load();
        return !empty($config['enabled']);
    }

    private static function defaults(): array
    {
        return [
            'enabled' => false,
            'qdrant_url' => 'http://qdrant:6333',
            'qdrant_api_key' => '',
            'collection' => 'goomootech_customer_service',
            'embedding_base_url' => '',
            'embedding_api_key' => '',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'embedding_timeout' => 20,
            'search_limit' => 6,
            'score_threshold' => 0.35,
        ];
    }
}
