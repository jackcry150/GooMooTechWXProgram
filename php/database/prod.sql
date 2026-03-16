-- =============================================================================
-- 数据库表结构优化脚本 schema_optimized.sql
-- 字符集：utf8mb4（支持 emoji，兼容 utf8）
-- 引擎：InnoDB（支持事务、外键）
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 广告表 mp_ad
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_ad` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1-banner轮播，2-会员页',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片路径',
  `link` varchar(255) DEFAULT NULL COMMENT '跳转链接',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1-显示，2-隐藏',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序，数字越大越靠前',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告表';

-- -----------------------------------------------------------------------------
-- 2. 收货地址表 mp_address
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `userId` int(11) NOT NULL COMMENT '用户ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `province` varchar(100) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(100) NOT NULL DEFAULT '' COMMENT '市',
  `region` varchar(100) NOT NULL DEFAULT '' COMMENT '区/县',
  `detail` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `isDefault` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认：0-否，1-是',
  `isDelete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '删除状态：0-正常，1-已删除',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_isDefault` (`isDefault`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收货地址表';

-- -----------------------------------------------------------------------------
-- 3. 画册表 mp_album
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `labels` varchar(200) DEFAULT '' COMMENT '标签',
  `proportion` varchar(100) DEFAULT '' COMMENT '比例',
  `size` varchar(200) DEFAULT '' COMMENT '尺寸',
  `material` varchar(255) DEFAULT '' COMMENT '材质',
  `copyright` varchar(255) DEFAULT '' COMMENT '版权所属',
  `price` varchar(50) DEFAULT '' COMMENT '价格',
  `content` text COMMENT '详情内容',
  `images` text COMMENT '图片JSON',
  `image` varchar(255) DEFAULT '' COMMENT '主图',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1-上架，2-下架',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类型',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='画册表';

-- -----------------------------------------------------------------------------
-- 4. 购物车表 mp_cart
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `userId` int(11) NOT NULL COMMENT '用户ID',
  `productId` int(11) NOT NULL COMMENT '商品ID',
  `productCode` varchar(50) NOT NULL DEFAULT '' COMMENT '商品编码/管家婆商品ID',
  `version` varchar(100) NOT NULL DEFAULT '' COMMENT '规格版本',
  `quantity` int(11) NOT NULL DEFAULT '1' COMMENT '数量',
  `createDate` datetime DEFAULT NULL COMMENT '创建日期',
  `createTime` int(11) DEFAULT NULL COMMENT '创建时间戳',
  `createIp` varchar(50) DEFAULT '' COMMENT '创建IP',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_productId` (`productId`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='购物车表';

-- -----------------------------------------------------------------------------
-- 5. 收藏表 mp_collect
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_collect` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `userId` int(11) NOT NULL COMMENT '用户ID',
  `productId` int(11) NOT NULL COMMENT '商品ID',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品收藏表';

-- -----------------------------------------------------------------------------
-- 6. 图库分类表 mp_gallery_category
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_gallery_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `categoryName` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序，数字越大越靠前',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_categoryName` (`categoryName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图库分类表';

-- -----------------------------------------------------------------------------
-- 7. 图库图片表 mp_gallery
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `categoryId` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `imageName` varchar(200) NOT NULL DEFAULT '' COMMENT '图片名称',
  `imageUrl` varchar(500) NOT NULL DEFAULT '' COMMENT '图片URL路径',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_categoryId` (`categoryId`),
  KEY `idx_imageName` (`imageName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图库图片表';

-- -----------------------------------------------------------------------------
-- 8. 订单表 mp_order
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `userId` int(11) NOT NULL COMMENT '用户ID',
  `orderNo` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
  `totalPrice` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总价',
  `product` text COMMENT '商品信息JSON',
  `address` text COMMENT '收货地址JSON',
  `orderDetails` text COMMENT '订单明细JSON',
  `remarks` varchar(500) DEFAULT '' COMMENT '订单备注',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '订单状态：1-待支付，2-待发货，4-已取消，6-待收货，7-已完成，8-已预定，10-已付定金待付尾款',
  `depositAmount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金金额',
  `depositPaid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '定金是否已支付：0-未支付，1-已支付',
  `depositPayTime` datetime DEFAULT NULL COMMENT '定金支付时间',
  `depositPayTimeStamp` int(11) DEFAULT '0' COMMENT '定金支付时间戳',
  `balanceAmount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '尾款金额',
  `balancePaid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尾款是否已支付：0-未支付，1-已支付',
  `balancePayTime` datetime DEFAULT NULL COMMENT '尾款支付时间',
  `balancePayTimeStamp` int(11) DEFAULT '0' COMMENT '尾款支付时间戳',
  `balanceDueTime` datetime DEFAULT NULL COMMENT '尾款到期时间',
  `balanceDueTimeStamp` int(11) DEFAULT '0' COMMENT '尾款到期时间戳',
  `refundStatus` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退款状态：0-未申请，1-申请中，2-同意，3-拒绝',
  `refundReason` varchar(500) DEFAULT NULL COMMENT '退款原因',
  `refundAmount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refundRemark` varchar(500) DEFAULT NULL COMMENT '退款备注',
  `refundApplyTime` datetime DEFAULT NULL COMMENT '退款申请时间',
  `refundApplyTimeStamp` int(11) DEFAULT '0' COMMENT '退款申请时间戳',
  `refundTime` datetime DEFAULT NULL COMMENT '退款处理时间',
  `refundTimeStamp` int(11) DEFAULT '0' COMMENT '退款处理时间戳',
  `payNo` varchar(50) DEFAULT '' COMMENT '支付流水号',
  `payDate` datetime DEFAULT NULL COMMENT '支付时间',
  `payTime` int(11) DEFAULT NULL COMMENT '支付时间戳',
  `freightName` varchar(100) DEFAULT NULL COMMENT '物流公司名称',
  `freightCode` varchar(50) DEFAULT NULL COMMENT '物流公司编码',
  `freightNo` varchar(100) DEFAULT NULL COMMENT '物流单号',
  `freightTime` datetime DEFAULT NULL COMMENT '发货时间',
  `apiStatus` tinyint(1) DEFAULT '0' COMMENT '同步管家婆状态：0-未同步，1-已同步',
  `apiMsg` varchar(255) DEFAULT '' COMMENT '同步消息',
  `payType` varchar(20) DEFAULT '' COMMENT '支付方式',
  `createDate` datetime DEFAULT NULL COMMENT '创建日期',
  `createTime` int(11) DEFAULT NULL COMMENT '创建时间戳',
  `createIp` varchar(50) DEFAULT '' COMMENT '创建IP',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_orderNo` (`orderNo`),
  KEY `idx_status` (`status`),
  KEY `idx_createTime` (`createTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- -----------------------------------------------------------------------------
-- 9. 商品表 mp_product
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `productId` varchar(50) NOT NULL DEFAULT '' COMMENT '管家婆商品ID/外部商品编码',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '商品全称',
  `subtitle` varchar(200) NOT NULL DEFAULT '' COMMENT '商品简称',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1-现货，2-预售',
  `mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '推荐：1-普通，2-推荐',
  `image` text NOT NULL COMMENT '主图JSON数组',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金（预售）',
  `deduct` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最大可抵扣金额',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存数量',
  `limitStock` int(11) NOT NULL DEFAULT '0' COMMENT '限购数量：0-不限',
  `shippingTemplateId` int(11) NOT NULL DEFAULT '0' COMMENT '运费模板ID',
  `version` varchar(200) NOT NULL DEFAULT '' COMMENT '规格版本，逗号分隔',
  `proportion` varchar(100) DEFAULT '' COMMENT '比例',
  `dimensions` varchar(100) DEFAULT '' COMMENT '尺寸',
  `material` varchar(255) DEFAULT '' COMMENT '材质',
  `copyright` varchar(255) DEFAULT '' COMMENT '版权所属',
  `splitReceiverId` varchar(50) DEFAULT '' COMMENT '分账接收方ID（斗拱）',
  `splitRatio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '分账比例%：0-不分帐',
  `content` text NOT NULL COMMENT '商品详情图JSON',
  `purchaseNotice` text COMMENT '购买须知图JSON',
  `promoImages` text COMMENT '宣传图JSON（特别贩售）',
  `reservationNotice` text COMMENT '预定须知图JSON（特别贩售）',
  `isSpecialSale` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否特别贩售：0-否，1-是',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序，数字越大越靠前',
  `startTime` datetime DEFAULT NULL COMMENT '预售开始时间',
  `endTime` datetime DEFAULT NULL COMMENT '预售结束时间',
  `startT` int(11) DEFAULT NULL COMMENT '预售开始时间戳',
  `endT` int(11) DEFAULT NULL COMMENT '预售结束时间戳',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '上架状态：1-上架，2-下架',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_isSpecialSale` (`isSpecialSale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';

-- -----------------------------------------------------------------------------
-- 10. 贩售表 mp_product_sell（关联商品，独立贩售活动）
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_product_sell` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `productId` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `promoImages` text COMMENT '宣传图JSON',
  `reservationNotice` text COMMENT '预定须知图JSON',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '上架状态：1-上架，2-下架',
  `startTime` datetime DEFAULT NULL COMMENT '开始时间',
  `endTime` datetime DEFAULT NULL COMMENT '结束时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_productId` (`productId`),
  KEY `idx_status_sort` (`status`,`sort`),
  KEY `idx_time` (`startTime`,`endTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='贩售活动表';

-- -----------------------------------------------------------------------------
-- 11. 客服表 mp_server
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_server` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1-微信社区群，2-社交账号',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) DEFAULT '' COMMENT '图片JSON',
  `link` varchar(255) DEFAULT '' COMMENT '链接',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1-显示，2-隐藏',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客服/社群表';

-- -----------------------------------------------------------------------------
-- 12. 在线客服表 mp_server_online
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_server_online` (
     `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '产品详情：1-否，2-是',
    `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
    `image` varchar(255) DEFAULT '' COMMENT '图片JSON',
    `link` varchar(255) DEFAULT '' COMMENT '链接（如企业微信客服链接）',
    `corpId` varchar(50) DEFAULT '' COMMENT '企业微信corpId',
    `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1-显示，2-隐藏',
    `sort` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线客服';

-- -----------------------------------------------------------------------------
-- 13. 系统设置表 mp_setting
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) DEFAULT '' COMMENT '站点名称',
  `link` varchar(255) DEFAULT '' COMMENT '站点链接',
  `aboutUs` text COMMENT '关于我们',
  `contactUs` text COMMENT '联系我们',
  `address` varchar(255) DEFAULT '' COMMENT '公司地址',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `customerLink` varchar(255) DEFAULT '' COMMENT '微信客服链接',
  `corpId` varchar(50) DEFAULT '' COMMENT '企业微信corpId',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- -----------------------------------------------------------------------------
-- 14. 资讯表 mp_news
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一标识',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text COMMENT '内容',
  `isSystem` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否系统内置：1-是，0-否',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资讯表';

-- -----------------------------------------------------------------------------
-- 15. 运费模板表 mp_shipping_template
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_shipping_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '计费方式：1-按件，2-按重量',
  `firstPiece` int(11) NOT NULL DEFAULT '1' COMMENT '首件数',
  `firstFee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首件/首重费用',
  `continuePiece` int(11) NOT NULL DEFAULT '1' COMMENT '续件数',
  `continueFee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续件/续重费用',
  `firstWeight` decimal(10,2) DEFAULT '1.00' COMMENT '首重(kg)',
  `continueWeight` decimal(10,2) DEFAULT '1.00' COMMENT '续重(kg)',
  `createTime` int(11) DEFAULT '0' COMMENT '创建时间戳',
  `createDate` datetime DEFAULT NULL COMMENT '创建时间',
  `updateTime` int(11) DEFAULT '0' COMMENT '更新时间戳',
  `updateDate` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='运费模板表';

-- -----------------------------------------------------------------------------
-- 16. 管理后台-管理员表 mp_system_admin
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_system_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别：1-男，2-女',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '密码',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `weixin` varchar(50) DEFAULT '' COMMENT '微信',
  `qq` varchar(20) DEFAULT '' COMMENT 'QQ',
  `department` int(11) DEFAULT NULL COMMENT '部门ID',
  `addTime` int(11) DEFAULT NULL COMMENT '入职时间戳',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1-在职，2-离职',
  PRIMARY KEY (`id`),
  KEY `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- -----------------------------------------------------------------------------
-- 17. 管理后台-部门表 mp_system_department
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_system_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '部门名称',
  `role` varchar(255) DEFAULT '' COMMENT '权限ID',
  `other` varchar(255) DEFAULT '' COMMENT '负责人ID',
  `sort` tinyint(4) NOT NULL DEFAULT '1' COMMENT '排序',
  `channel_role` varchar(255) DEFAULT '' COMMENT '渠道权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门表';

-- -----------------------------------------------------------------------------
-- 18. 管理后台-导航表 mp_system_nav
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_system_nav` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '导航名称',
  `url` varchar(255) DEFAULT '' COMMENT '链接地址',
  `navId` int(11) DEFAULT '0' COMMENT '父级ID，0为一级',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `idx_navId` (`navId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台导航表';

-- -----------------------------------------------------------------------------
-- 19. 用户表 mp_user
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mp_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `nickName` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` text COMMENT '头像URL',
  `openId` varchar(64) NOT NULL DEFAULT '' COMMENT '微信openId',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `collectionCards` int(11) NOT NULL DEFAULT '0' COMMENT '收藏卡数量',
  `snailShells` int(11) NOT NULL DEFAULT '0' COMMENT '蜗壳积分',
  `regDate` datetime DEFAULT NULL COMMENT '注册日期',
  `regTime` int(11) DEFAULT NULL COMMENT '注册时间戳',
  `regIp` varchar(50) DEFAULT '' COMMENT '注册IP',
  `loginDate` datetime DEFAULT NULL COMMENT '最后登录日期',
  `loginTime` int(11) DEFAULT NULL COMMENT '最后登录时间戳',
  `loginIp` varchar(50) DEFAULT '' COMMENT '最后登录IP',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openId` (`openId`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序用户表';


-- ----------------------------
-- Records of mp_news
-- ----------------------------
INSERT INTO `mp_news` VALUES ('1', 'about', '公司简介', '郑州蜗之壳文化传媒有限公司成立于2019年，专注于原创手办开发，快速成为国内品牌领导者。核心开发团队由国内外顶级模型研发团队组成，拥有丰富经验和众多知名项目案例，涵盖从原创概念图到建模、涂装、工程、摄影的全流程。\r\n\r\n2023年建立自有生产和模具工厂，大幅提升效率和质量。2024年初引入行业领先的5轴雕刻机，从传统铸钢工艺升级，在模具精度和产品细节上达到新高度，确立领先地位。我们致力于持续技术创新，提供无与伦比的手办产品。合作IP包括：《战双帕弥什》、《游戏王》、《胜利女神：NIKKE》、《新月同行》。', '1', '2026-02-10 17:04:19', '2026-02-10 17:04:19');
INSERT INTO `mp_news` VALUES ('2', 'after_sale', '售后说明', '请在管理后台「资讯管理」中编辑售后说明内容。', '1', '2026-02-10 17:04:19', '2026-02-10 17:04:19');
INSERT INTO `mp_news` VALUES ('3', 'service_agreement', '服务协议', '请在管理后台「资讯管理」中编辑服务协议内容。', '1', '2026-02-10 17:04:19', '2026-02-10 17:04:19');
INSERT INTO `mp_news` VALUES ('4', 'privacy_agreement', '隐私协议', '请在管理后台「资讯管理」中编辑服务协议内容。', '1', '2026-02-10 21:40:40', '2026-02-10 21:41:27');

-- ----------------------------
-- Records of mp_server
-- ----------------------------
INSERT INTO `mp_server` VALUES ('1', '1', '蜗之壳官方粉丝交流①群', '[\"/uploads/20251105/1762334155.png\"]', '1', '3', '3');
INSERT INTO `mp_server` VALUES ('2', '1', '蜗之壳官方粉丝交流②群', '[\"/uploads/20251105/1762334177.png\"]', '1', '3', '2');
INSERT INTO `mp_server` VALUES ('3', '2', '蜗之壳SNAIL SHELL', '[\"/uploads/20251105/1762330858.png\"]', '1', '3', '9');
INSERT INTO `mp_server` VALUES ('4', '2', '蜗之壳Snail-Shell', '[\"/uploads/20251105/1762330888.png\"]', '1', '3', '8');
INSERT INTO `mp_server` VALUES ('5', '2', '@wozhike', '[\"/uploads/20251105/1762330928.png\"]', '1', '3', '7');
INSERT INTO `mp_server` VALUES ('6', '2', '蜗之壳SnailShell', '[\"/uploads/20251105/1762330958.png\"]', '1', '3', '6');
INSERT INTO `mp_server` VALUES ('7', '2', '蜗之壳SNAIL SHELL', '[\"/uploads/20251105/1762330992.png\"]', '1', '3', '5');

INSERT INTO `mp_server_online` VALUES ('1', '1', '售前客服', '[\"/uploads/20260210/1770717189309.png\"]', 'https://work.weixin.qq.com/kfid/kfc8ed1ca5ca7bfa56c', 'wwbc9e055c515dc9e5', '1', '2');

INSERT INTO `mp_setting` VALUES ('1', '12', 'www.baidu.com', '郑州蜗之壳文化传媒有限公司成立于2019年，专注于原创手办开发，快速成为国内品牌领导者。核心开发团队由国内外顶级模型研发团队组成，拥有丰富经验和众多知名项目案例，涵盖从原创概念图到建模、涂装、工程、摄影的全流程。\r\n\r\n2023年建立自有生产和模具工厂，大幅提升效率和质量。2024年初引入行业领先的5轴雕刻机，从传统铸钢工艺升级，在模具精度和产品细节上达到新高度，确立领先地位。我们致力于持续技术创新，提供无与伦比的手办产品。合作IP包括：《战双帕弥什》、《游戏王》、《胜利女神：NIKKE》、《新月同行》。', '蜗之壳希望与业界各领域开展合作！\r\n\r\n如果您希望IP实体化、跨领域合作、代理零售等业务合作需求，欢迎联系我们。', '河南省郑州经济技术开发区航海东路与经开第二十大街交汇处 中兴新业港三期28号楼5层501室', 'bd@wozhike.club', '', '');

INSERT INTO `mp_system_admin` VALUES ('116', 'admin', '1', '13512345678', 'c3284d0f94606de1fd2af172aba15bf3', '13512345678', '13512345678', '13512345678', '22', null, '1');

INSERT INTO `mp_system_department` VALUES ('22', '管理部', '12,19,37,38,70,77,78,71,79,80,91,95,96,72,81,82,83,73,84,74,85,75,86,87,76,88,89,90,92,93,94', '总权限', '1', '32,33,36,65,58,59,39,40,42,67,68,71,60,61,62,63,64,66,72');


-- ----------------------------
-- Records of mp_system_nav
-- ----------------------------
INSERT INTO `mp_system_nav` VALUES ('12', '系统管理', '/adm/system', '0', '9');
INSERT INTO `mp_system_nav` VALUES ('19', '导航栏', '/adm/system/nav', '12', '3');
INSERT INTO `mp_system_nav` VALUES ('37', '管理员', '/adm/system/user', '12', '2');
INSERT INTO `mp_system_nav` VALUES ('38', '部门管理', '/adm/system/department', '12', '1');
INSERT INTO `mp_system_nav` VALUES ('70', '广告管理', '/adm/ad', '0', '1');
INSERT INTO `mp_system_nav` VALUES ('71', '商品管理', '/adm/product', '0', '2');
INSERT INTO `mp_system_nav` VALUES ('72', '图册管理', '/adm/album', '0', '3');
INSERT INTO `mp_system_nav` VALUES ('73', '用户管理', '/adm/user', '0', '4');
INSERT INTO `mp_system_nav` VALUES ('74', '订单管理', '/adm/order', '0', '5');
INSERT INTO `mp_system_nav` VALUES ('75', '客服管理', '/adm/servers', '0', '6');
INSERT INTO `mp_system_nav` VALUES ('76', '资讯管理', '/adm/news', '0', '7');
INSERT INTO `mp_system_nav` VALUES ('77', '广告列表', '/adm/ad/index', '70', null);
INSERT INTO `mp_system_nav` VALUES ('78', '广告添加', '/adm/ad/add', '70', null);
INSERT INTO `mp_system_nav` VALUES ('79', '商品列表', '/adm/product/index', '71', '2');
INSERT INTO `mp_system_nav` VALUES ('80', '商品添加', '/adm/product/add', '71', '3');
INSERT INTO `mp_system_nav` VALUES ('81', '图册列表', '/adm/album/index', '72', null);
INSERT INTO `mp_system_nav` VALUES ('82', '图册添加', '/adm/album/add', '72', null);
INSERT INTO `mp_system_nav` VALUES ('83', '图册图片', '/adm/album/img', '72', null);
INSERT INTO `mp_system_nav` VALUES ('84', '用户列表', '/adm/user/index', '73', null);
INSERT INTO `mp_system_nav` VALUES ('85', '订单列表', '/adm/order/index', '74', null);
INSERT INTO `mp_system_nav` VALUES ('86', '客服列表', '/adm/servers/index', '75', null);
INSERT INTO `mp_system_nav` VALUES ('87', '在线客服', '/adm/servers/online', '75', '2');
INSERT INTO `mp_system_nav` VALUES ('88', '资讯列表', '/adm/news/index', '76', null);
INSERT INTO `mp_system_nav` VALUES ('89', '平台设置', '/adm/setting', '0', null);
INSERT INTO `mp_system_nav` VALUES ('90', '平台设置', '/adm/setting', '89', null);
INSERT INTO `mp_system_nav` VALUES ('91', '特别贩售', '/adm/sell/index', '71', '4');
INSERT INTO `mp_system_nav` VALUES ('92', '图库管理', '/adm/gallery', '0', '8');
INSERT INTO `mp_system_nav` VALUES ('93', '图库列表', '/adm/gallery/image', '92', '2');
INSERT INTO `mp_system_nav` VALUES ('94', '图库分类', '/adm/gallery/category', '92', '1');
INSERT INTO `mp_system_nav` VALUES ('95', '同步商品', '/adm/product/gjp', '71', '1');
INSERT INTO `mp_system_nav` VALUES ('96', '运费模板', '/adm/shipping', '71', null);
