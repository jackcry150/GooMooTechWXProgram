CREATE TABLE IF NOT EXISTS `mp_ai_knowledge_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceType` varchar(30) NOT NULL DEFAULT 'manual' COMMENT 'manual/product/news/setting',
  `sourceId` int(11) NOT NULL DEFAULT '0',
  `app_code` varchar(30) NOT NULL DEFAULT 'goomoo',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` mediumtext,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 enabled 2 disabled',
  `contentHash` varchar(64) NOT NULL DEFAULT '',
  `lastIndexedAt` datetime DEFAULT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`sourceType`, `sourceId`, `app_code`),
  KEY `idx_status` (`status`),
  KEY `idx_app_code` (`app_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI知识源';

CREATE TABLE IF NOT EXISTS `mp_ai_knowledge_chunk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceId` int(11) NOT NULL DEFAULT '0',
  `sourceType` varchar(30) NOT NULL DEFAULT 'manual',
  `originId` int(11) NOT NULL DEFAULT '0',
  `app_code` varchar(30) NOT NULL DEFAULT 'goomoo',
  `chunkIndex` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  `contentHash` varchar(64) NOT NULL DEFAULT '',
  `qdrantPointId` varchar(80) NOT NULL DEFAULT '',
  `embeddingStatus` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 pending 1 indexed 2 failed',
  `embeddingError` varchar(500) NOT NULL DEFAULT '',
  `lastEmbeddedAt` datetime DEFAULT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chunk_hash` (`sourceId`, `chunkIndex`, `contentHash`),
  KEY `idx_source` (`sourceId`),
  KEY `idx_embedding_status` (`embeddingStatus`),
  KEY `idx_point` (`qdrantPointId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI知识切片';

CREATE TABLE IF NOT EXISTS `mp_ai_embedding_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chunkId` int(11) NOT NULL DEFAULT '0',
  `jobType` varchar(20) NOT NULL DEFAULT 'upsert' COMMENT 'upsert/delete',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 pending 1 running 2 success 3 failed',
  `attempts` int(11) NOT NULL DEFAULT '0',
  `lastError` varchar(500) NOT NULL DEFAULT '',
  `runAfter` datetime DEFAULT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_run` (`status`, `runAfter`),
  KEY `idx_chunk` (`chunkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI向量化任务';

ALTER TABLE `mp_ai_service_message`
  ADD COLUMN IF NOT EXISTS `retrievalContext` mediumtext NULL COMMENT 'RAG检索上下文JSON',
  ADD COLUMN IF NOT EXISTS `retrievalSourceIds` varchar(500) NOT NULL DEFAULT '' COMMENT '命中知识源ID列表';

SET @ai_parent_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = 'AI客服' AND `navId` = 0
  LIMIT 1
);

INSERT INTO `mp_system_nav` (`navId`, `name`, `url`, `sort`)
SELECT 0, 'AI客服', '', 80
WHERE @ai_parent_id IS NULL;

SET @ai_parent_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = 'AI客服' AND `navId` = 0
  LIMIT 1
);

INSERT INTO `mp_system_nav` (`navId`, `name`, `url`, `sort`)
SELECT @ai_parent_id, '知识库管理', '/adm/ai_knowledge/index', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `mp_system_nav`
  WHERE `name` = '知识库管理' AND `url` = '/adm/ai_knowledge/index'
);
