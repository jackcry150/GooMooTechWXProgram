SET @xhs_parent_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = '渠道管理' AND `navId` = 0
  LIMIT 1
);

INSERT INTO `mp_system_nav` (`navId`, `name`, `url`, `sort`)
SELECT 0, '渠道管理', '', 10
WHERE @xhs_parent_id IS NULL;

SET @xhs_parent_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = '渠道管理' AND `navId` = 0
  LIMIT 1
);

INSERT INTO `mp_system_nav` (`navId`, `name`, `url`, `sort`)
SELECT @xhs_parent_id, '小红书绑定审核', '/adm/xhs/bind', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `mp_system_nav`
  WHERE `name` = '小红书绑定审核' AND `url` = '/adm/xhs/bind'
);

SET @xhs_child_id := (
  SELECT `id` FROM `mp_system_nav`
  WHERE `name` = '小红书绑定审核' AND `url` = '/adm/xhs/bind'
  LIMIT 1
);

UPDATE `mp_system_department`
SET `role` = CASE
  WHEN @xhs_parent_id IS NULL THEN `role`
  WHEN `role` IS NULL OR `role` = '' THEN CAST(@xhs_parent_id AS CHAR)
  WHEN FIND_IN_SET(@xhs_parent_id, `role`) = 0 THEN CONCAT(`role`, ',', @xhs_parent_id)
  ELSE `role`
END
WHERE `name` = '管理部';

UPDATE `mp_system_department`
SET `role` = CASE
  WHEN @xhs_child_id IS NULL THEN `role`
  WHEN `role` IS NULL OR `role` = '' THEN CAST(@xhs_child_id AS CHAR)
  WHEN FIND_IN_SET(@xhs_child_id, `role`) = 0 THEN CONCAT(`role`, ',', @xhs_child_id)
  ELSE `role`
END
WHERE `name` = '管理部';