-- 抽奖功能补丁

ALTER TABLE `mp_setting`
  ADD COLUMN `lotteryCost` int(11) NOT NULL DEFAULT '10' COMMENT '单次抽奖消耗猫饼' AFTER `corpId`,
  ADD COLUMN `lotteryRule` text COMMENT '抽奖规则' AFTER `lotteryCost`;

CREATE TABLE IF NOT EXISTS `mp_lottery_prize` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `image` varchar(255) DEFAULT '' COMMENT '奖品图片',
  `rewardType` tinyint(1) NOT NULL DEFAULT '1' COMMENT '奖励类型：1谢谢参与 2猫饼 3收藏卡 4实物奖品',
  `rewardValue` int(11) NOT NULL DEFAULT '0' COMMENT '奖励数值',
  `weight` int(11) NOT NULL DEFAULT '1' COMMENT '抽中权重',
  `stock` int(11) NOT NULL DEFAULT '-1' COMMENT '库存：-1不限',
  `description` varchar(255) DEFAULT '' COMMENT '奖品说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1开启 2关闭',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`),
  KEY `idx_weight` (`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抽奖奖品表';

CREATE TABLE IF NOT EXISTS `mp_lottery_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `userId` int(11) NOT NULL COMMENT '用户ID',
  `prizeId` int(11) NOT NULL DEFAULT '0' COMMENT '奖品ID',
  `prizeName` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称快照',
  `prizeImage` varchar(255) DEFAULT '' COMMENT '奖品图片快照',
  `rewardType` tinyint(1) NOT NULL DEFAULT '1' COMMENT '奖励类型',
  `rewardValue` int(11) NOT NULL DEFAULT '0' COMMENT '奖励数值',
  `costShells` int(11) NOT NULL DEFAULT '0' COMMENT '消耗猫饼',
  `snailShellsBefore` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖前猫饼',
  `snailShellsAfter` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖后猫饼',
  `description` varchar(255) DEFAULT '' COMMENT '奖品说明快照',
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `createTime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_prizeId` (`prizeId`),
  KEY `idx_createTime` (`createTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抽奖记录表';

INSERT INTO `mp_lottery_prize` (`id`, `name`, `image`, `rewardType`, `rewardValue`, `weight`, `stock`, `description`, `status`, `sort`)
VALUES
  (1, '谢谢参与', '', 1, 0, 50, -1, '再接再厉，下次一定中', 1, 1),
  (2, '猫饼 x5', '', 2, 5, 25, -1, '奖励 5 个猫饼', 1, 2),
  (3, '收藏卡 x1', '', 3, 1, 15, -1, '奖励 1 张收藏卡', 1, 3),
  (4, '神秘周边', '', 4, 0, 10, 20, '请联系管理员登记发放', 1, 4)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `image` = VALUES(`image`),
  `rewardType` = VALUES(`rewardType`),
  `rewardValue` = VALUES(`rewardValue`),
  `weight` = VALUES(`weight`),
  `stock` = VALUES(`stock`),
  `description` = VALUES(`description`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`);

UPDATE `mp_setting`
SET `lotteryCost` = 10,
    `lotteryRule` = '每次抽奖消耗 10 个猫饼。奖品概率由后台配置，抽到实物奖品后请联系客服登记发放。'
WHERE `id` = 1;

INSERT INTO `mp_system_nav` (`id`, `name`, `url`, `navId`, `sort`) VALUES
  (97, '抽奖管理', '/adm/lottery/index', 0, 10),
  (98, '奖品列表', '/adm/lottery/index', 97, 1),
  (99, '抽奖记录', '/adm/lottery/record', 97, 2)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `url` = VALUES(`url`),
  `navId` = VALUES(`navId`),
  `sort` = VALUES(`sort`);

UPDATE `mp_system_department`
SET `role` = CONCAT_WS(',', `role`, '97', '98', '99')
WHERE `id` = 22
  AND FIND_IN_SET('97', `role`) = 0;
