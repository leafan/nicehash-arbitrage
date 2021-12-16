-- ----------------------------
-- Table structure for my_orders
-- nicehash上订单表
-- ----------------------------
DROP TABLE IF EXISTS `my_orders`;
CREATE TABLE `my_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `workers` bigint(20) DEFAULT 0 COMMENT '挖矿人数',
  `pool_host` varchar(128) DEFAULT '' COMMENT '矿池host，用于区分币？',
  `pool_user` varchar(128) DEFAULT '' COMMENT '矿池user',
  `price` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '价格',
  `limit_speed` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '买多少算力',
  `accepted_speed` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '接受算力',
  `btc_paid` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '',
  `btc_avail` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nicehash_my_orders_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='我的订单列表信息';


-- ----------------------------
-- Table structure for mining_status
-- 矿池挖矿状态记录表
-- ----------------------------
DROP TABLE IF EXISTS `mining_status`;
CREATE TABLE `mining_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `last_block` varchar(128) NOT NULL,
  `elasped_seconds_when_start` bigint(20) DEFAULT 0 COMMENT '启动挖矿时已经过去多少秒了',
  `btc_paid_when_start` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '启动挖矿时已支付的 btc，累计各订单之和',
  `btc_paid_this_block` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '记录本区块消耗的btc_paid',
  `run_shares_this_block` decimal(20,10) unsigned DEFAULT '0.00' COMMENT '本区块已跑的shares',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间，也是挖矿启动时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nicehash_mining_status_last_block` (`last_block`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='矿池挖矿状态记录表';


