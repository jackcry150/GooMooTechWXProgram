-- AI customer service demo tables
-- Execute this script manually in the target database before enabling message persistence.

CREATE TABLE IF NOT EXISTS `mp_ai_service_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionNo` varchar(50) NOT NULL DEFAULT '' COMMENT '会话编号',
  `userId` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID，游客为0',
  `scene` varchar(20) NOT NULL DEFAULT 'presale' COMMENT 'presale/aftersale',
  `sourcePage` varchar(50) DEFAULT '' COMMENT '来源页面',
  `productId` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `orderId` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1进行中 2结束',
  `lastMessageTime` datetime DEFAULT NULL COMMENT '最后消息时间',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessionNo` (`sessionNo`),
  KEY `idx_userId` (`userId`),
  KEY `idx_scene` (`scene`),
  KEY `idx_orderId` (`orderId`),
  KEY `idx_productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服会话表';

CREATE TABLE IF NOT EXISTS `mp_ai_service_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionId` int(11) NOT NULL COMMENT '会话ID',
  `role` varchar(20) NOT NULL DEFAULT 'user' COMMENT 'user/ai/system',
  `content` text COMMENT '消息内容',
  `needTransfer` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否建议转人工',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sessionId` (`sessionId`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI客服消息表';