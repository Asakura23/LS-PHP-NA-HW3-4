/*
Navicat MySQL Data Transfer

Source Server         : terrenum
Source Server Version : 50562
Source Host           : 192.168.0.1:3306
Source Database       : blogdb

Target Server Type    : MYSQL
Target Server Version : 50562
File Encoding         : 65001

Date: 2022-09-23 06:00:52
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `groups`
-- ----------------------------
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `g_id` int(10) NOT NULL AUTO_INCREMENT,
  `g_title` varchar(256) NOT NULL,
  PRIMARY KEY (`g_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES ('1', 'Administrators');
INSERT INTO `groups` VALUES ('2', 'Users');
INSERT INTO `groups` VALUES ('3', 'Guests');

-- ----------------------------
-- Table structure for `images`
-- ----------------------------
DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `i_id` int(75) NOT NULL AUTO_INCREMENT,
  `i_path` varchar(1024) NOT NULL,
  PRIMARY KEY (`i_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of images
-- ----------------------------
INSERT INTO `images` VALUES ('1', '/usr/local/htdocs/1.jpg');
INSERT INTO `images` VALUES ('2', '/usr/local/htdocs/2.jpg');
INSERT INTO `images` VALUES ('3', '/usr/htdocs/3.jpg');
INSERT INTO `images` VALUES ('4', '/usr/htdocs/4.png');
INSERT INTO `images` VALUES ('5', '/usr/htdocs/3.jpg');
INSERT INTO `images` VALUES ('6', '/usr/htdocs/4.png');
INSERT INTO `images` VALUES ('7', '/usr/htdocs/3.jpg');
INSERT INTO `images` VALUES ('8', '/usr/htdocs/4.png');
INSERT INTO `images` VALUES ('9', '/usr/htdocs/3.jpg');
INSERT INTO `images` VALUES ('10', '/usr/htdocs/4.png');

-- ----------------------------
-- Table structure for `messages`
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `m_id` int(50) NOT NULL AUTO_INCREMENT,
  `m_text` mediumtext NOT NULL,
  `m_owner` int(50) NOT NULL,
  `m_date` int(75) NOT NULL,
  `m_images` varchar(256) NOT NULL,
  `m_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`m_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of messages
-- ----------------------------
INSERT INTO `messages` VALUES ('1', 'Ляляля', '1', '1663869536', '1,2', '0');
INSERT INTO `messages` VALUES ('2', 'Там тигидам тигидам пам пам', '2', '1663873440', '5,6', '0');
INSERT INTO `messages` VALUES ('3', 'Там тигидам тигидам пам пам', '2', '1663873482', '7,8', '0');
INSERT INTO `messages` VALUES ('4', 'Там тигидам тигидам пам пам', '2', '1663873514', '9,10', '0');

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `u_id` int(50) NOT NULL AUTO_INCREMENT,
  `u_group` int(10) NOT NULL,
  `u_name` varchar(128) NOT NULL,
  `u_mail` varchar(128) NOT NULL,
  `u_passhash` varchar(80) NOT NULL,
  `u_rdate` int(75) NOT NULL,
  PRIMARY KEY (`u_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '2', 'Hiori', 'hiorirm@gmail.com', '', '0');
INSERT INTO `users` VALUES ('2', '2', 'Julia', 'anonymous@gmail.com', 'd34b09cbc4b2b50d8dd17f4309a570b9416821b2', '1663869536');
