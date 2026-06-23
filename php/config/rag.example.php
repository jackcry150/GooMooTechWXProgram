<?php

return [
    'enabled' => false,
    'qdrant_url' => 'http://qdrant:6333',
    'qdrant_api_key' => '',
    'collection' => 'goomootech_customer_service',
    'embedding_base_url' => 'https://api.example.com/v1',
    'embedding_api_key' => '',
    'embedding_model' => 'text-embedding-3-small',
    'embedding_dimension' => 1536,
    'embedding_timeout' => 20,
    'search_limit' => 6,
    'score_threshold' => 0.35,
];
