-- Add arrival confirm columns for subscribe-message flow
ALTER TABLE `mp_order`
  ADD COLUMN `arrivalConfirmStatus` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Arrival confirmation status: 0-pending notify,1-notified pending confirm,2-confirmed,3-timeout' AFTER `status`,
  ADD COLUMN `arrivalNotifiedAt` datetime DEFAULT NULL COMMENT 'Arrival notification sent at' AFTER `arrivalConfirmStatus`,
  ADD COLUMN `arrivalConfirmDeadlineAt` datetime DEFAULT NULL COMMENT 'Arrival confirmation deadline' AFTER `arrivalNotifiedAt`,
  ADD COLUMN `arrivalConfirmedAt` datetime DEFAULT NULL COMMENT 'Arrival confirmed at' AFTER `arrivalConfirmDeadlineAt`,
  ADD COLUMN `arrivalConfirmSnapshot` text COMMENT 'Arrival confirmation address snapshot JSON' AFTER `arrivalConfirmedAt`,
  ADD COLUMN `arrivalConfirmRemark` varchar(500) DEFAULT '' COMMENT 'Arrival confirmation remark' AFTER `arrivalConfirmSnapshot`,
  ADD INDEX `idx_arrivalConfirmStatus` (`arrivalConfirmStatus`);
