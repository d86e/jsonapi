<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Install extends BaseController
{
    public function index()
    {
        $config = config('database');
        $prefix = $config['connections'][$config['default']]['prefix']; // 获取表前缀

        Db::execute("SET NAMES utf8mb4;");
        Db::execute("SET NAMES utf8mb4;");
        Db::execute("DROP TABLE IF EXISTS `{$prefix}auth`;");
        Db::execute("CREATE TABLE `{$prefix}auth` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表ID',
            `name` varchar(255) DEFAULT NULL COMMENT '表名称',
            `read` int(11) DEFAULT '0' COMMENT 'rule表控制类型 0: 只能访问自有数据 1:空访问所有, 2: 空不可访问',
            `write` int(11) DEFAULT '0' COMMENT 'rule表控制类型 0: 都不可写入 1:空访可写入, 2: 空不可写入',
            `update` int(11) DEFAULT '0' COMMENT 'rule表控制类型 0: 只能更新自有数据 1:空更新所有, 2: 空不可更新',
            `del` int(11) DEFAULT '0' COMMENT 'rule表控制类型  0: 只能删除自有数据 1:空删除所有, 2: 空不可删除',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='表的权限控制标记';");

        Db::execute("DROP TABLE IF EXISTS `{$prefix}auth_delete_rule`;");
        Db::execute("CREATE TABLE `{$prefix}auth_delete_rule` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主ID',
            `name` varchar(255) DEFAULT NULL COMMENT '表名称',
            `data_id` int(11) DEFAULT NULL COMMENT '数据ID',
            `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
            `type` int(11) DEFAULT '0' COMMENT '0 不可删除, 1 可删除',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据表删除权限控制规则';");

        Db::execute("DROP TABLE IF EXISTS `{$prefix}auth_read_rule`;");
        Db::execute("CREATE TABLE `{$prefix}auth_read_rule` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主ID',
            `name` varchar(255) DEFAULT NULL COMMENT '表名称',
            `data_id` int(11) DEFAULT NULL COMMENT '数据ID',
            `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
            `type` int(11) DEFAULT '0' COMMENT '0 不可读取, 1 可读取',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据表读权限控制规则';");

        Db::execute("DROP TABLE IF EXISTS `{$prefix}auth_update_rule`;");
        Db::execute("CREATE TABLE `{$prefix}auth_update_rule` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主ID',
            `name` varchar(255) DEFAULT NULL COMMENT '表名称',
            `data_id` int(11) DEFAULT NULL COMMENT '数据ID',
            `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
            `type` int(11) DEFAULT '0' COMMENT '0 不可更新, 1 可更新',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据表更新权限控制规则';");

        Db::execute("DROP TABLE IF EXISTS `{$prefix}auth_write_rule`;");
        Db::execute("CREATE TABLE `{$prefix}auth_write_rule` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主ID',
            `name` varchar(255) DEFAULT NULL COMMENT '表名称',
            `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
            `type` int(11) DEFAULT '0' COMMENT '0 不可写, 1 可写入',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据表写权限控制规则';");

        return '权限表安装成功';
    }
}
