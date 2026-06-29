CREATE TABLE IF NOT EXISTS `mp_xhs_oauth_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sellerId` varchar(64) NOT NULL DEFAULT '' COMMENT '小红书商家ID',
  `sellerName` varchar(120) NOT NULL DEFAULT '' COMMENT '小红书店铺名称',
  `accessToken` varchar(512) NOT NULL DEFAULT '' COMMENT '访问令牌',
  `accessTokenExpiresAt` datetime DEFAULT NULL COMMENT '访问令牌过期时间',
  `refreshToken` varchar(512) NOT NULL DEFAULT '' COMMENT '刷新令牌',
  `refreshTokenExpiresAt` datetime DEFAULT NULL COMMENT '刷新令牌过期时间',
  `rawResponse` text COMMENT '授权接口原始返回',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seller_id` (`sellerId`),
  KEY `idx_access_expires` (`accessTokenExpiresAt`),
  KEY `idx_refresh_expires` (`refreshTokenExpiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小红书开放平台授权Token';