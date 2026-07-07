CREATE TABLE IF NOT EXISTS `mp_ai_boundary_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_code` varchar(32) NOT NULL DEFAULT 'common' COMMENT 'common/goomoo/hasuki',
  `routeType` varchar(20) NOT NULL DEFAULT 'allow' COMMENT 'allow/handoff/reject/clarify',
  `taskType` varchar(50) NOT NULL DEFAULT '' COMMENT '任务边界分类',
  `keywords` text COMMENT '逗号或换行分隔关键词',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 enabled 2 disabled',
  `sort` int(11) NOT NULL DEFAULT 100,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_route_status` (`app_code`, `routeType`, `status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服边界路由规则';

INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'reject', 'illegal_or_abuse', '毒品,枪支,赌博,诈骗,洗钱,跑分,绕平台交易,私下交易,跳过平台,导出数据', 1, 10
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='reject' AND `taskType`='illegal_or_abuse');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'reject', 'privacy_request', '他人订单,别人订单,其他用户,手机号,完整地址,身份证,隐私,人肉,导出订单', 1, 20
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='reject' AND `taskType`='privacy_request');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'handoff', 'refund_request', '退款,退货,退定金,退尾款,refund,chargeback', 1, 30
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='handoff' AND `taskType`='refund_request');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'handoff', 'complaint_or_compensation', '投诉,赔偿,补偿,维权,仲裁,法务,律师,质量问题,破损,少件,错发', 1, 40
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='handoff' AND `taskType`='complaint_or_compensation');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'handoff', 'shipping_commitment', '承诺发货,保证发货,具体发货时间,必须发货,什么时候一定发,最晚什么时候发', 1, 50
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='handoff' AND `taskType`='shipping_commitment');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'allow', 'product_intro', '商品,介绍,尺寸,材质,比例,价格,多少钱,pa011,ntw-20,少女前线', 1, 100
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='allow' AND `taskType`='product_intro');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'allow', 'presale_rule', '预售,定金,尾款,补款,截单,预定', 1, 110
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='allow' AND `taskType`='presale_rule');
INSERT INTO `mp_ai_boundary_rule` (`app_code`, `routeType`, `taskType`, `keywords`, `status`, `sort`)
SELECT 'common', 'allow', 'payment_points_rule', '支付,付款,微信支付,支付宝,银行卡,积分,猫币,蜗壳,抵扣,points', 1, 120
WHERE NOT EXISTS (SELECT 1 FROM `mp_ai_boundary_rule` WHERE `app_code`='common' AND `routeType`='allow' AND `taskType`='payment_points_rule');

DROP PROCEDURE IF EXISTS `add_ai_safety_log_boundary_column`;
DELIMITER $$
CREATE PROCEDURE `add_ai_safety_log_boundary_column`(
  IN p_column_name varchar(64),
  IN p_alter_sql text
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'mp_ai_safety_log'
  ) THEN
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'mp_ai_safety_log'
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

CALL `add_ai_safety_log_boundary_column`('taskBoundary', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `taskBoundary` varchar(50) NOT NULL DEFAULT '''' COMMENT ''任务边界'' AFTER `finalAction`');
CALL `add_ai_safety_log_boundary_column`('dataBoundary', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `dataBoundary` varchar(50) NOT NULL DEFAULT '''' COMMENT ''数据边界'' AFTER `taskBoundary`');
CALL `add_ai_safety_log_boundary_column`('actionBoundary', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `actionBoundary` varchar(50) NOT NULL DEFAULT '''' COMMENT ''动作边界'' AFTER `dataBoundary`');
CALL `add_ai_safety_log_boundary_column`('finalRoute', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `finalRoute` varchar(20) NOT NULL DEFAULT '''' COMMENT ''边界最终路由'' AFTER `actionBoundary`');
CALL `add_ai_safety_log_boundary_column`('routeReason', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `routeReason` varchar(500) NOT NULL DEFAULT '''' COMMENT ''边界路由原因'' AFTER `finalRoute`');
CALL `add_ai_safety_log_boundary_column`('reviewStatus', 'ALTER TABLE `mp_ai_safety_log` ADD COLUMN `reviewStatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0未复盘/1误杀/2漏拦截/3已补词/4已转人工'' AFTER `routeReason`');
DROP PROCEDURE IF EXISTS `add_ai_safety_log_boundary_column`;

DROP PROCEDURE IF EXISTS `add_ai_knowledge_source_aliases_column`;
DELIMITER $$
CREATE PROCEDURE `add_ai_knowledge_source_aliases_column`()
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
        AND column_name = 'aliases'
    ) THEN
      ALTER TABLE `mp_ai_knowledge_source` ADD COLUMN `aliases` varchar(500) NOT NULL DEFAULT '' COMMENT '别名/同义词，逗号分隔' AFTER `title`;
    END IF;
  END IF;
END$$
DELIMITER ;
CALL `add_ai_knowledge_source_aliases_column`();
DROP PROCEDURE IF EXISTS `add_ai_knowledge_source_aliases_column`;