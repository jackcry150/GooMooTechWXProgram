ALTER TABLE `mp_setting`
  ADD COLUMN `wechatMiniAppId` varchar(64) DEFAULT '' COMMENT '微信小程序 AppID' AFTER `lotteryRule`,
  ADD COLUMN `wechatMiniSecret` varchar(128) DEFAULT '' COMMENT '微信小程序 Secret' AFTER `wechatMiniAppId`,
  ADD COLUMN `huifuMerchantId` varchar(64) DEFAULT '' COMMENT '汇付商户号' AFTER `wechatMiniSecret`,
  ADD COLUMN `huifuPrivateKey` text COMMENT '汇付 RSA 私钥' AFTER `huifuMerchantId`,
  ADD COLUMN `huifuNotifyUrl` varchar(255) DEFAULT '' COMMENT '汇付支付回调地址' AFTER `huifuPrivateKey`,
  ADD COLUMN `paymentSplitEnabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用支付分账：0否 1是' AFTER `huifuNotifyUrl`;