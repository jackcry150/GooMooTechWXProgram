ALTER TABLE `mp_setting`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

ALTER TABLE `mp_ad`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

ALTER TABLE `mp_album`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

ALTER TABLE `mp_server`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

ALTER TABLE `mp_news`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

ALTER TABLE `mp_product`
    ADD COLUMN `app_code` varchar(32) NULL DEFAULT NULL COMMENT '小程序标识' AFTER `id`;

UPDATE `mp_setting` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';
UPDATE `mp_ad` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';
UPDATE `mp_album` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';
UPDATE `mp_server` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';
UPDATE `mp_news` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';
UPDATE `mp_product` SET `app_code` = 'goomoo' WHERE `app_code` IS NULL OR `app_code` = '';

ALTER TABLE `mp_setting` ADD INDEX `idx_app_code` (`app_code`);
ALTER TABLE `mp_ad` ADD INDEX `idx_app_code_type_status` (`app_code`, `type`, `status`);
ALTER TABLE `mp_album` ADD INDEX `idx_app_code_status_sort` (`app_code`, `status`, `sort`);
ALTER TABLE `mp_server` ADD INDEX `idx_app_code_type_status` (`app_code`, `type`, `status`);
ALTER TABLE `mp_news` ADD INDEX `idx_app_code_code` (`app_code`, `code`);
ALTER TABLE `mp_product` ADD INDEX `idx_app_code_status_time` (`app_code`, `status`, `startT`, `endT`);
