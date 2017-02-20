-- phpMyAdmin SQL Dump
-- version 4.0.10.11
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2017-02-17 23:36:04
-- 服务器版本: 5.5.21-log
-- PHP 版本: 5.4.45

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `wechat`
--

-- --------------------------------------------------------

--
-- 表的结构 `access_token`
--

CREATE TABLE IF NOT EXISTS `access_token` (
  `id` int(2) NOT NULL COMMENT '排序ID',
  `token` varchar(200) NOT NULL COMMENT 'access_token',
  `expires_in` int(6) NOT NULL COMMENT '有效时间',
  `expires_time` int(20) NOT NULL COMMENT '生效时间',
  `date` datetime NOT NULL COMMENT '可观时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信access_token';

-- --------------------------------------------------------

--
-- 表的结构 `jsapi_ticket`
--

CREATE TABLE IF NOT EXISTS `jsapi_ticket` (
  `id` int(2) NOT NULL COMMENT '排序ID',
  `ticket` varchar(250) NOT NULL COMMENT 'jsapi_ticket',
  `expires_in` int(6) NOT NULL COMMENT '有效时间',
  `expires_time` int(20) NOT NULL COMMENT '产生时间',
  `date` datetime NOT NULL COMMENT '可观时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='微信JS接口票据';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
