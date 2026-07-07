CREATE TABLE IF NOT EXISTS `mp_ai_intent_example` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_code` varchar(32) NOT NULL DEFAULT 'common' COMMENT 'common/goomoo/hasuki',
  `routeType` varchar(20) NOT NULL DEFAULT 'handoff' COMMENT 'reject/handoff/clarify',
  `taskType` varchar(50) NOT NULL DEFAULT '' COMMENT '边界意图分类',
  `text` varchar(500) NOT NULL DEFAULT '' COMMENT '示例问法',
  `vector` mediumtext COMMENT 'embedding JSON',
  `embeddingStatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待生成/1成功/2失败',
  `embeddingError` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 enabled 2 disabled',
  `sort` int(11) NOT NULL DEFAULT 100,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_route_status` (`app_code`, `routeType`, `status`, `embeddingStatus`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服语义意图示例库';

DROP PROCEDURE IF EXISTS `seed_ai_intent_example`;
DELIMITER $$
CREATE PROCEDURE `seed_ai_intent_example`(
  IN p_route_type varchar(20),
  IN p_task_type varchar(50),
  IN p_text varchar(500),
  IN p_sort int
)
BEGIN
  INSERT INTO `mp_ai_intent_example` (`app_code`, `routeType`, `taskType`, `text`, `embeddingStatus`, `status`, `sort`, `createTime`, `updateTime`)
  SELECT 'common', p_route_type, p_task_type, p_text, 0, 1, p_sort, NOW(), NOW()
  WHERE NOT EXISTS (
    SELECT 1 FROM `mp_ai_intent_example`
    WHERE `app_code` = 'common'
      AND `routeType` = p_route_type
      AND `taskType` = p_task_type
      AND `text` = p_text
  );
END$$
DELIMITER ;

CALL `seed_ai_intent_example`('reject', 'privacy_request', '帮我查下我朋友买的东西', 10);
CALL `seed_ai_intent_example`('reject', 'privacy_request', '把收货人的电话给我看一下', 11);
CALL `seed_ai_intent_example`('reject', 'privacy_request', '我想看别人的收货地址', 12);
CALL `seed_ai_intent_example`('reject', 'privacy_request', '能不能帮我确认另一个用户买了什么', 13);
CALL `seed_ai_intent_example`('reject', 'privacy_request', '把客户订单资料发给我', 14);
CALL `seed_ai_intent_example`('reject', 'off_platform_trade', '加个微信直接买能不能便宜', 20);
CALL `seed_ai_intent_example`('reject', 'off_platform_trade', '我们不走平台付款可以吗', 21);
CALL `seed_ai_intent_example`('reject', 'off_platform_trade', '私聊转账下单有没有优惠', 22);
CALL `seed_ai_intent_example`('reject', 'off_platform_trade', '跳过小程序交易行不行', 23);
CALL `seed_ai_intent_example`('reject', 'off_platform_trade', '我直接打钱给你发货吧', 24);
CALL `seed_ai_intent_example`('handoff', 'refund_request', '钱给我打回来', 30);
CALL `seed_ai_intent_example`('handoff', 'refund_request', '我不要了把钱退我', 31);
CALL `seed_ai_intent_example`('handoff', 'refund_request', '定金我想拿回来', 32);
CALL `seed_ai_intent_example`('handoff', 'refund_request', '这单取消后款什么时候回到我这里', 33);
CALL `seed_ai_intent_example`('handoff', 'refund_request', '我想把这笔订单撤掉返钱', 34);
CALL `seed_ai_intent_example`('handoff', 'complaint_or_compensation', '这个质量不行你们得给说法', 40);
CALL `seed_ai_intent_example`('handoff', 'complaint_or_compensation', '东西坏了我要你们处理损失', 41);
CALL `seed_ai_intent_example`('handoff', 'complaint_or_compensation', '我对售后结果不接受', 42);
CALL `seed_ai_intent_example`('handoff', 'complaint_or_compensation', '发错东西造成损失怎么办', 43);
CALL `seed_ai_intent_example`('handoff', 'complaint_or_compensation', '我要找负责人处理这个纠纷', 44);
CALL `seed_ai_intent_example`('handoff', 'shipping_commitment', '你给我一个肯定发货日期', 50);
CALL `seed_ai_intent_example`('handoff', 'shipping_commitment', '最迟哪天一定能寄出来', 51);
CALL `seed_ai_intent_example`('handoff', 'shipping_commitment', '能不能保证这个月发出', 52);
CALL `seed_ai_intent_example`('handoff', 'shipping_commitment', '给我承诺一个准确到货时间', 53);
CALL `seed_ai_intent_example`('handoff', 'order_exception', '我付款了但页面没有订单', 60);
CALL `seed_ai_intent_example`('handoff', 'order_exception', '扣钱成功但是订单找不到', 61);
CALL `seed_ai_intent_example`('handoff', 'order_exception', '重复付款了怎么办', 62);
CALL `seed_ai_intent_example`('handoff', 'order_exception', '下单后状态一直不对', 63);
CALL `seed_ai_intent_example`('clarify', 'missing_context', '这个怎么处理', 70);
CALL `seed_ai_intent_example`('clarify', 'missing_context', '帮我看看这件事', 71);
DROP PROCEDURE IF EXISTS `seed_ai_intent_example`;

DROP PROCEDURE IF EXISTS `add_ai_safety_log_matched_via`;
DELIMITER $$
CREATE PROCEDURE `add_ai_safety_log_matched_via`()
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
        AND column_name = 'matchedVia'
    ) THEN
      ALTER TABLE `mp_ai_safety_log` ADD COLUMN `matchedVia` varchar(500) NOT NULL DEFAULT '' COMMENT '命中视图 raw/compact/canonical/pinyin/semantic' AFTER `hitWords`;
    END IF;
  END IF;
END$$
DELIMITER ;
CALL `add_ai_safety_log_matched_via`();
DROP PROCEDURE IF EXISTS `add_ai_safety_log_matched_via`;
