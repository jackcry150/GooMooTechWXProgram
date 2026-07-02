DROP PROCEDURE IF EXISTS `add_ai_knowledge_review_column`;
DELIMITER $$
CREATE PROCEDURE `add_ai_knowledge_review_column`(
  IN p_column_name varchar(64),
  IN p_alter_sql text
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'mp_ai_knowledge_source'
  ) THEN
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'mp_ai_knowledge_source'
        AND column_name = p_column_name
    ) THEN
      SET @alter_sql = p_alter_sql;
      PREPARE stmt FROM @alter_sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END IF;
END$$
DELIMITER ;

CALL `add_ai_knowledge_review_column`('reviewStatus', 'ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `reviewStatus` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1 pending 2 approved 3 rejected'' AFTER `status`');
CALL `add_ai_knowledge_review_column`('reviewerId', 'ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `reviewerId` int(11) NOT NULL DEFAULT 0 COMMENT ''审核人ID'' AFTER `reviewStatus`');
CALL `add_ai_knowledge_review_column`('reviewerName', 'ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `reviewerName` varchar(100) NOT NULL DEFAULT '''' COMMENT ''审核人'' AFTER `reviewerId`');
CALL `add_ai_knowledge_review_column`('reviewedAt', 'ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `reviewedAt` datetime NULL COMMENT ''审核时间'' AFTER `reviewerName`');
CALL `add_ai_knowledge_review_column`('reviewRemark', 'ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `reviewRemark` varchar(500) NOT NULL DEFAULT '''' COMMENT ''审核备注'' AFTER `reviewedAt`');
DROP PROCEDURE IF EXISTS `add_ai_knowledge_review_column`;

UPDATE `mp_ai_knowledge_source`
SET `reviewStatus` = 2,
    `reviewerName` = 'system',
    `reviewedAt` = IFNULL(`reviewedAt`, NOW())
WHERE `sourceType` <> 'manual'
  AND (`reviewStatus` IS NULL OR `reviewStatus` = 1);

CREATE TABLE IF NOT EXISTS `mp_ai_sensitive_word` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_code` varchar(32) NOT NULL DEFAULT 'common' COMMENT 'common/goomoo/hasuki',
  `word` varchar(100) NOT NULL DEFAULT '' COMMENT '敏感词',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `level` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 normal 2 high',
  `action` varchar(20) NOT NULL DEFAULT 'block' COMMENT 'allow/block/transfer',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 enabled 2 disabled',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_status_category` (`app_code`, `status`, `category`),
  KEY `idx_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服敏感词';
INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '毒品', '毒品枪爆', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '毒品');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '枪支', '毒品枪爆', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '枪支');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '赌博', '赌博诈骗', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '赌博');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '诈骗', '赌博诈骗', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '诈骗');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '洗钱', '黑灰产', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '洗钱');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '跑分', '黑灰产', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '跑分');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '裸聊', '色情低俗', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '裸聊');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '色情', '色情低俗', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '色情');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '自杀', '自伤暴力', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '自杀');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '杀人', '自伤暴力', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '杀人');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '人肉搜索', '隐私侵害', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '人肉搜索');

INSERT INTO `mp_ai_sensitive_word` (`app_code`, `word`, `category`, `level`, `action`, `status`, `remark`, `createTime`, `updateTime`)
SELECT 'common', '身份证泄露', '隐私侵害', 2, 'block', 1, '默认高风险敏感词种子', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_sensitive_word` WHERE `app_code` = 'common' AND `word` = '身份证泄露');

CREATE TABLE IF NOT EXISTS `mp_ai_safety_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_code` varchar(32) NOT NULL DEFAULT 'goomoo',
  `userId` int(11) NOT NULL DEFAULT 0,
  `sessionId` int(11) NOT NULL DEFAULT 0,
  `scene` varchar(50) NOT NULL DEFAULT '',
  `sourcePage` varchar(255) NOT NULL DEFAULT '',
  `question` mediumtext,
  `reply` mediumtext,
  `checkStage` varchar(20) NOT NULL DEFAULT '' COMMENT 'rate_limit/input/output',
  `hitWords` varchar(500) NOT NULL DEFAULT '',
  `category` varchar(100) NOT NULL DEFAULT '',
  `level` tinyint(1) NOT NULL DEFAULT 0,
  `action` varchar(20) NOT NULL DEFAULT 'allow',
  `finalAction` varchar(20) NOT NULL DEFAULT 'allow',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `retrievalSourceIds` varchar(500) NOT NULL DEFAULT '',
  `retrievalContext` mediumtext,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_time` (`app_code`, `createTime`),
  KEY `idx_user` (`userId`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服安全日志';

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
SELECT @ai_parent_id, '敏感词管理', '/adm/ai_safety/words', 2
WHERE NOT EXISTS (
  SELECT 1 FROM `mp_system_nav`
  WHERE `name` = '敏感词管理' AND `url` = '/adm/ai_safety/words'
);

INSERT INTO `mp_system_nav` (`navId`, `name`, `url`, `sort`)
SELECT @ai_parent_id, '安全日志', '/adm/ai_safety/logs', 3
WHERE NOT EXISTS (
  SELECT 1 FROM `mp_system_nav`
  WHERE `name` = '安全日志' AND `url` = '/adm/ai_safety/logs'
);

SET @ai_words_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = '敏感词管理' AND `url` = '/adm/ai_safety/words'
  LIMIT 1
);
SET @ai_logs_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = '安全日志' AND `url` = '/adm/ai_safety/logs'
  LIMIT 1
);

UPDATE `mp_system_department`
SET `role` = CASE
  WHEN @ai_parent_id IS NULL THEN `role`
  WHEN `role` IS NULL OR `role` = '' THEN CAST(@ai_parent_id AS CHAR)
  WHEN FIND_IN_SET(@ai_parent_id, `role`) = 0 THEN CONCAT(`role`, ',', @ai_parent_id)
  ELSE `role`
END
WHERE `name` = '管理部';

UPDATE `mp_system_department`
SET `role` = CASE
  WHEN @ai_words_id IS NULL THEN `role`
  WHEN `role` IS NULL OR `role` = '' THEN CAST(@ai_words_id AS CHAR)
  WHEN FIND_IN_SET(@ai_words_id, `role`) = 0 THEN CONCAT(`role`, ',', @ai_words_id)
  ELSE `role`
END
WHERE `name` = '管理部';

UPDATE `mp_system_department`
SET `role` = CASE
  WHEN @ai_logs_id IS NULL THEN `role`
  WHEN `role` IS NULL OR `role` = '' THEN CAST(@ai_logs_id AS CHAR)
  WHEN FIND_IN_SET(@ai_logs_id, `role`) = 0 THEN CONCAT(`role`, ',', @ai_logs_id)
  ELSE `role`
END
WHERE `name` = '管理部';