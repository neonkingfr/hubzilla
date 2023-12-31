CREATE TABLE IF NOT EXISTS `abconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chan` int(10) unsigned NOT NULL DEFAULT 0 ,
  `xchan` char(191) NOT NULL DEFAULT '',
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chan_xchan` (`chan`, `xchan`),
  KEY `cat` (`cat`),
  KEY `k` (`k`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `abook` (
  `abook_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `abook_account` int(10) unsigned NOT NULL DEFAULT 0 ,
  `abook_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `abook_xchan` char(191) NOT NULL DEFAULT '',
  `abook_my_perms` int(11) NOT NULL DEFAULT 0 ,
  `abook_their_perms` int(11) NOT NULL DEFAULT 0 ,
  `abook_closeness` tinyint(3) unsigned NOT NULL DEFAULT 99,
  `abook_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `abook_updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `abook_connected` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `abook_dob` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `abook_flags` int(11) NOT NULL DEFAULT 0 ,
  `abook_blocked` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_ignored` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_hidden` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_archived` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_pending` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_unconnected` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_self` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_feed` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_not_here` tinyint(4) NOT NULL DEFAULT 0 ,
  `abook_profile` char(191) NOT NULL DEFAULT '',
  `abook_incl` text NOT NULL,
  `abook_excl` text NOT NULL,
  `abook_instance` text NOT NULL,
  `abook_role` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`abook_id`),
  KEY `abook_account` (`abook_account`),
  KEY `abook_channel` (`abook_channel`),
  KEY `abook_xchan` (`abook_xchan`),
  KEY `abook_my_perms` (`abook_my_perms`),
  KEY `abook_their_perms` (`abook_their_perms`),
  KEY `abook_closeness` (`abook_closeness`),
  KEY `abook_created` (`abook_created`),
  KEY `abook_updated` (`abook_updated`),
  KEY `abook_flags` (`abook_flags`),
  KEY `abook_profile` (`abook_profile`),
  KEY `abook_dob` (`abook_dob`),
  KEY `abook_connected` (`abook_connected`),
  KEY `abook_blocked` (`abook_blocked`),
  KEY `abook_ignored` (`abook_ignored`),
  KEY `abook_hidden` (`abook_hidden`),
  KEY `abook_archived` (`abook_archived`),
  KEY `abook_pending` (`abook_pending`),
  KEY `abook_unconnected` (`abook_unconnected`),
  KEY `abook_self` (`abook_self`),
  KEY `abook_not_here` (`abook_not_here`),
  KEY `abook_feed` (`abook_feed`),
  KEY `abook_role` (`abook_role`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `account` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_parent` int(10) unsigned NOT NULL DEFAULT 0 ,
  `account_default_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `account_salt` char(32) NOT NULL DEFAULT '',
  `account_password` char(191) NOT NULL DEFAULT '',
  `account_email` char(191) NOT NULL DEFAULT '',
  `account_external` char(191) NOT NULL DEFAULT '',
  `account_language` char(16) NOT NULL DEFAULT 'en',
  `account_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `account_lastlog` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `account_flags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `account_roles` int(10) unsigned NOT NULL DEFAULT 0 ,
  `account_reset` char(191) NOT NULL DEFAULT '',
  `account_expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `account_expire_notified` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `account_service_class` char(32) NOT NULL DEFAULT '',
  `account_level` int(10) unsigned NOT NULL DEFAULT 0 ,
  `account_password_changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`account_id`),
  KEY `account_email` (`account_email`),
  KEY `account_service_class` (`account_service_class`),
  KEY `account_parent` (`account_parent`),
  KEY `account_flags` (`account_flags`),
  KEY `account_roles` (`account_roles`),
  KEY `account_lastlog` (`account_lastlog`),
  KEY `account_expires` (`account_expires`),
  KEY `account_default_channel` (`account_default_channel`),
  KEY `account_external` (`account_external`),
  KEY `account_level` (`account_level`),
  KEY `account_password_changed` (`account_password_changed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `addon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aname` char(191) NOT NULL DEFAULT '',
  `version` char(191) NOT NULL DEFAULT '',
  `installed` tinyint(1) NOT NULL DEFAULT 0 ,
  `hidden` tinyint(1) NOT NULL DEFAULT 0 ,
  `tstamp` bigint(20) NOT NULL DEFAULT 0 ,
  `plugin_admin` tinyint(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `hidden` (`hidden`),
  KEY `aname` (`aname`),
  KEY `installed` (`installed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `app` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` char(191) NOT NULL DEFAULT '',
  `app_sig` char(191) NOT NULL DEFAULT '',
  `app_author` char(191) NOT NULL DEFAULT '',
  `app_name` char(191) NOT NULL DEFAULT '',
  `app_desc` text NOT NULL,
  `app_url` char(191) NOT NULL DEFAULT '',
  `app_photo` char(191) NOT NULL DEFAULT '',
  `app_version` char(191) NOT NULL DEFAULT '',
  `app_channel` int(11) NOT NULL DEFAULT 0 ,
  `app_addr` char(191) NOT NULL DEFAULT '',
  `app_price` char(191) NOT NULL DEFAULT '',
  `app_page` char(191) NOT NULL DEFAULT '',
  `app_requires` char(191) NOT NULL DEFAULT '',
  `app_deleted` int(11) NOT NULL DEFAULT 0 ,
  `app_system` int(11) NOT NULL DEFAULT 0 ,
  `app_plugin` char(191) NOT NULL DEFAULT '',
  `app_options` int(11) NOT NULL DEFAULT 0 ,
  `app_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `app_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `app_id` (`app_id`),
  KEY `app_name` (`app_name`),
  KEY `app_url` (`app_url`),
  KEY `app_photo` (`app_photo`),
  KEY `app_version` (`app_version`),
  KEY `app_channel` (`app_channel`),
  KEY `app_price` (`app_price`),
  KEY `app_created` (`app_created`),
  KEY `app_deleted` (`app_deleted`),
  KEY `app_system` (`app_system`),
  KEY `app_edited` (`app_edited`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `atoken` (
  `atoken_id` int(11) NOT NULL AUTO_INCREMENT,
  `atoken_guid` char(191) NOT NULL DEFAULT '',
  `atoken_aid` int(11) NOT NULL DEFAULT 0 ,
  `atoken_uid` int(11) NOT NULL DEFAULT 0 ,
  `atoken_name` char(191) NOT NULL DEFAULT '',
  `atoken_token` char(191) NOT NULL DEFAULT '',
  `atoken_expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`atoken_id`),
  KEY `atoken_guid` (`atoken_guid`),
  KEY `atoken_aid` (`atoken_aid`),
  KEY `atoken_uid` (`atoken_uid`),
  KEY `atoken_uid_2` (`atoken_uid`),
  KEY `atoken_name` (`atoken_name`),
  KEY `atoken_token` (`atoken_token`),
  KEY `atoken_expires` (`atoken_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `hash` char(191) NOT NULL DEFAULT '',
  `creator` char(191) NOT NULL DEFAULT '',
  `filename` char(191) NOT NULL DEFAULT '',
  `filetype` char(191) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT 0 ,
  `revision` int(10) unsigned NOT NULL DEFAULT 0 ,
  `folder` char(191) NOT NULL DEFAULT '',
  `flags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `is_dir` tinyint(1) NOT NULL DEFAULT 0 ,
  `is_photo` tinyint(1) NOT NULL DEFAULT 0 ,
  `os_storage` tinyint(1) NOT NULL DEFAULT 0 ,
  `os_path` mediumtext NOT NULL,
  `display_path` mediumtext NOT NULL,
  `content` longblob NOT NULL,
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  KEY `hash` (`hash`),
  KEY `filename` (`filename`),
  KEY `filetype` (`filetype`),
  KEY `filesize` (`filesize`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `revision` (`revision`),
  KEY `folder` (`folder`),
  KEY `flags` (`flags`),
  KEY `creator` (`creator`),
  KEY `is_dir` (`is_dir`),
  KEY `is_photo` (`is_photo`),
  KEY `os_storage` (`os_storage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `auth_codes` (
  `id` varchar(40) NOT NULL DEFAULT '',
  `client_id` varchar(20) NOT NULL DEFAULT '',
  `redirect_uri` varchar(200) NOT NULL DEFAULT '',
  `expires` int(11) NOT NULL DEFAULT 0 ,
  `auth_scope` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cache` (
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  `updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cal` (
  `cal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cal_aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cal_uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cal_hash` varchar(191) NOT NULL DEFAULT '',
  `cal_name` varchar(191) NOT NULL DEFAULT '',
  `uri` varchar(191) NOT NULL DEFAULT '',
  `logname` varchar(191) NOT NULL DEFAULT '',
  `pass` varchar(191) NOT NULL DEFAULT '',
  `ctag` varchar(191) NOT NULL DEFAULT '',
  `synctoken` varchar(191) NOT NULL DEFAULT '',
  `cal_types` varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`cal_id`),
  KEY `cal_aid` (`cal_aid`),
  KEY `cal_uid` (`cal_uid`),
  KEY `cal_hash` (`cal_hash`),
  KEY `cal_name` (`cal_name`),
  KEY `cal_types` (`cal_types`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `channel` (
  `channel_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_account_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `channel_primary` tinyint(1) unsigned NOT NULL DEFAULT 0 ,
  `channel_name` char(191) NOT NULL DEFAULT '',
  `channel_address` char(191) NOT NULL DEFAULT '',
  `channel_guid` char(191) NOT NULL DEFAULT '',
  `channel_guid_sig` text NOT NULL,
  `channel_hash` char(191) NOT NULL DEFAULT '',
  `channel_portable_id` char(191) NOT NULL DEFAULT '',
  `channel_timezone` char(128) NOT NULL DEFAULT 'UTC',
  `channel_location` char(191) NOT NULL DEFAULT '',
  `channel_theme` char(191) NOT NULL DEFAULT '',
  `channel_startpage` char(191) NOT NULL DEFAULT '',
  `channel_pubkey` text NOT NULL,
  `channel_prvkey` text NOT NULL,
  `channel_notifyflags` int(10) unsigned NOT NULL DEFAULT 65535,
  `channel_pageflags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `channel_dirdate` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `channel_lastpost` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `channel_deleted` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `channel_active` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `channel_max_anon_mail` int(10) unsigned NOT NULL DEFAULT 10,
  `channel_max_friend_req` int(10) unsigned NOT NULL DEFAULT 10,
  `channel_expire_days` int(11) NOT NULL DEFAULT 0 ,
  `channel_passwd_reset` char(191) NOT NULL DEFAULT '',
  `channel_default_group` char(191) NOT NULL DEFAULT '',
  `channel_allow_cid` mediumtext NOT NULL,
  `channel_allow_gid` mediumtext NOT NULL,
  `channel_deny_cid` mediumtext NOT NULL,
  `channel_deny_gid` mediumtext NOT NULL,
  `channel_removed` tinyint(1) NOT NULL DEFAULT 0 ,
  `channel_system` tinyint(1) NOT NULL DEFAULT 0 ,
  `channel_moved` char(191) NOT NULL DEFAULT '',
  `channel_password` varchar(191) NOT NULL,
  `channel_salt` varchar(191) NOT NULL,
  PRIMARY KEY (`channel_id`),
  KEY `channel_address` (`channel_address`),
  KEY `channel_account_id` (`channel_account_id`),
  KEY `channel_primary` (`channel_primary`),
  KEY `channel_name` (`channel_name`),
  KEY `channel_timezone` (`channel_timezone`),
  KEY `channel_location` (`channel_location`),
  KEY `channel_theme` (`channel_theme`),
  KEY `channel_notifyflags` (`channel_notifyflags`),
  KEY `channel_pageflags` (`channel_pageflags`),
  KEY `channel_max_anon_mail` (`channel_max_anon_mail`),
  KEY `channel_max_friend_req` (`channel_max_friend_req`),
  KEY `channel_default_gid` (`channel_default_group`),
  KEY `channel_guid` (`channel_guid`),
  KEY `channel_hash` (`channel_hash`),
  KEY `channel_portable_id` (`channel_portable_id`),
  KEY `channel_expire_days` (`channel_expire_days`),
  KEY `channel_deleted` (`channel_deleted`),
  KEY `channel_active` (`channel_active`),
  KEY `channel_dirdate` (`channel_dirdate`),
  KEY `channel_removed` (`channel_removed`),
  KEY `channel_system` (`channel_system`),
  KEY `channel_lastpost` (`channel_lastpost`),
  KEY `channel_moved` (`channel_moved`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_room` int(10) unsigned NOT NULL DEFAULT 0 ,
  `chat_xchan` char(191) NOT NULL DEFAULT '',
  `chat_text` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`chat_id`),
  KEY `chat_room` (`chat_room`),
  KEY `chat_xchan` (`chat_xchan`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chatpresence` (
  `cp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cp_room` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cp_xchan` char(191) NOT NULL DEFAULT '',
  `cp_last` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `cp_status` char(191) NOT NULL DEFAULT '',
  `cp_client` char(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`cp_id`),
  KEY `cp_room` (`cp_room`),
  KEY `cp_xchan` (`cp_xchan`),
  KEY `cp_last` (`cp_last`),
  KEY `cp_status` (`cp_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chatroom` (
  `cr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cr_aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cr_uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cr_name` char(191) NOT NULL DEFAULT '',
  `cr_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `cr_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `cr_expire` int(10) unsigned NOT NULL DEFAULT 0 ,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`cr_id`),
  KEY `cr_aid` (`cr_aid`),
  KEY `cr_uid` (`cr_uid`),
  KEY `cr_name` (`cr_name`),
  KEY `cr_created` (`cr_created`),
  KEY `cr_edited` (`cr_edited`),
  KEY `cr_expire` (`cr_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `clients` (
  `client_id` varchar(191) NOT NULL DEFAULT '',
  `pw` varchar(191) NOT NULL DEFAULT '',
  `redirect_uri` varchar(200) NOT NULL DEFAULT '',
  `clname` text,
  `icon` text,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`cat`,`k`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dreport` (
  `dreport_id` int(11) NOT NULL AUTO_INCREMENT,
  `dreport_channel` int(11) NOT NULL DEFAULT 0 ,
  `dreport_mid` char(191) NOT NULL DEFAULT '',
  `dreport_site` char(191) NOT NULL DEFAULT '',
  `dreport_recip` char(191) NOT NULL DEFAULT '',
  `dreport_result` char(191) NOT NULL DEFAULT '',
  `dreport_name` char(191) NOT NULL DEFAULT '',
  `dreport_time` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `dreport_xchan` char(191) NOT NULL DEFAULT '',
  `dreport_queue` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`dreport_id`),
  KEY `dreport_mid` (`dreport_mid`),
  KEY `dreport_site` (`dreport_site`),
  KEY `dreport_time` (`dreport_time`),
  KEY `dreport_xchan` (`dreport_xchan`),
  KEY `dreport_queue` (`dreport_queue`),
  KEY `dreport_channel` (`dreport_channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `cal_id` int(11) unsigned NOT NULL DEFAULT 0 ,
  `event_xchan` char(191) NOT NULL DEFAULT '',
  `event_hash` char(191) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `dtstart` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `dtend` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `summary` text NOT NULL,
  `description` text NOT NULL,
  `location` text NOT NULL,
  `etype` char(191) NOT NULL DEFAULT '',
  `nofinish` tinyint(1) NOT NULL DEFAULT 0 ,
  `adjust` tinyint(1) NOT NULL DEFAULT 1,
  `dismissed` tinyint(1) NOT NULL DEFAULT 0 ,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `event_status` char(191) NOT NULL DEFAULT '',
  `event_status_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `event_percent` smallint(6) NOT NULL DEFAULT 0 ,
  `event_repeat` text NOT NULL,
  `event_sequence` smallint(6) NOT NULL DEFAULT 0 ,
  `event_priority` smallint(6) NOT NULL DEFAULT 0 ,
  `event_vdata` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cal_id` (`cal_id`),
  KEY `etype` (`etype`),
  KEY `dtstart` (`dtstart`),
  KEY `dtend` (`dtend`),
  KEY `adjust` (`adjust`),
  KEY `nofinish` (`nofinish`),
  KEY `dismissed` (`dismissed`),
  KEY `aid` (`aid`),
  KEY `event_hash` (`event_hash`),
  KEY `event_xchan` (`event_xchan`),
  KEY `event_status` (`event_status`),
  KEY `event_sequence` (`event_sequence`),
  KEY `event_priority` (`event_priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pgrp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(191) NOT NULL DEFAULT '',
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `visible` tinyint(1) NOT NULL DEFAULT 0 ,
  `deleted` tinyint(1) NOT NULL DEFAULT 0 ,
  `gname` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`),
  KEY `hash` (`hash`),
  KEY `gname` (`gname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pgrp_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `gid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `xchan` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`),
  KEY `xchan` (`xchan`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `hook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hook` char(191) NOT NULL DEFAULT '',
  `file` char(191) NOT NULL DEFAULT '',
  `fn` char(191) NOT NULL DEFAULT '',
  `priority` smallint NOT NULL DEFAULT 0 ,
  `hook_version` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `hook` (`hook`),
  KEY `priority` (`priority`),
  KEY `hook_version` (`hook_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `hubloc` (
  `hubloc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hubloc_guid` char(191) NOT NULL DEFAULT '',
  `hubloc_guid_sig` text NOT NULL,
  `hubloc_id_url` char(191) NOT NULL DEFAULT '',
  `hubloc_hash` char(191) NOT NULL DEFAULT '',
  `hubloc_addr` char(191) NOT NULL DEFAULT '',
  `hubloc_network` char(32) NOT NULL DEFAULT '',
  `hubloc_flags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `hubloc_status` int(10) unsigned NOT NULL DEFAULT 0 ,
  `hubloc_url` char(191) NOT NULL DEFAULT '',
  `hubloc_url_sig` text NOT NULL,
  `hubloc_site_id` char(191) NOT NULL DEFAULT '',
  `hubloc_host` char(191) NOT NULL DEFAULT '',
  `hubloc_callback` char(191) NOT NULL DEFAULT '',
  `hubloc_connect` char(191) NOT NULL DEFAULT '',
  `hubloc_sitekey` text NOT NULL,
  `hubloc_updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `hubloc_connected` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `hubloc_primary` tinyint(1) NOT NULL DEFAULT 0 ,
  `hubloc_orphancheck` tinyint(1) NOT NULL DEFAULT 0 ,
  `hubloc_error` tinyint(1) NOT NULL DEFAULT 0 ,
  `hubloc_deleted` tinyint(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`hubloc_id`),
  KEY `hubloc_url` (`hubloc_url`),
  KEY `hubloc_site_id` (`hubloc_site_id`),
  KEY `hubloc_guid` (`hubloc_guid`),
  KEY `hubloc_id_url` (`hubloc_id_url`),
  KEY `hubloc_hash` (`hubloc_hash`),
  KEY `hubloc_flags` (`hubloc_flags`),
  KEY `hubloc_connect` (`hubloc_connect`),
  KEY `hubloc_host` (`hubloc_host`),
  KEY `hubloc_addr` (`hubloc_addr`),
  KEY `hubloc_updated` (`hubloc_updated`),
  KEY `hubloc_connected` (`hubloc_connected`),
  KEY `hubloc_status` (`hubloc_status`),
  KEY `hubloc_network` (`hubloc_network`),
  KEY `hubloc_primary` (`hubloc_primary`),
  KEY `hubloc_orphancheck` (`hubloc_orphancheck`),
  KEY `hubloc_deleted` (`hubloc_deleted`),
  KEY `hubloc_error` (`hubloc_error`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `iconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iid` int(11) NOT NULL DEFAULT 0 ,
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  `sharing` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `iid` (`iid`),
  KEY `cat` (`cat`),
  KEY `k` (`k`),
  KEY `sharing` (`sharing`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `issue` (
  `issue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issue_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `issue_updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `issue_assigned` char(191) NOT NULL DEFAULT '',
  `issue_priority` int(11) NOT NULL DEFAULT 0 ,
  `issue_status` int(11) NOT NULL DEFAULT 0 ,
  `issue_component` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`issue_id`),
  KEY `issue_created` (`issue_created`),
  KEY `issue_updated` (`issue_updated`),
  KEY `issue_assigned` (`issue_assigned`),
  KEY `issue_priority` (`issue_priority`),
  KEY `issue_status` (`issue_status`),
  KEY `issue_component` (`issue_component`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(191) NOT NULL DEFAULT '',
  `mid` char(191) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `parent` int(10) unsigned NOT NULL DEFAULT 0 ,
  `parent_mid` char(191) NOT NULL DEFAULT '',
  `thr_parent` char(191) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `comments_closed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `owner_xchan` char(191) NOT NULL DEFAULT '',
  `author_xchan` char(191) NOT NULL DEFAULT '',
  `source_xchan` char(191) NOT NULL DEFAULT '',
  `mimetype` char(191) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `summary` mediumtext NOT NULL,
  `body` mediumtext NOT NULL,
  `html` mediumtext NOT NULL,
  `app` char(191) NOT NULL DEFAULT '',
  `lang` char(64) NOT NULL DEFAULT '',
  `revision` int(10) unsigned NOT NULL DEFAULT 0 ,
  `verb` char(191) NOT NULL DEFAULT '',
  `obj_type` char(191) NOT NULL DEFAULT '',
  `obj` text NOT NULL,
  `tgt_type` char(191) NOT NULL DEFAULT '',
  `target` text NOT NULL,
  `layout_mid` char(191) NOT NULL DEFAULT '',
  `postopts` text NOT NULL,
  `route` text NOT NULL,
  `llink` text NOT NULL,
  `plink` text NOT NULL,
  `resource_id` char(191) NOT NULL DEFAULT '',
  `resource_type` char(16) NOT NULL DEFAULT '',
  `attach` mediumtext NOT NULL,
  `sig` text NOT NULL,
  `location` char(191) NOT NULL DEFAULT '',
  `coord` char(191) NOT NULL DEFAULT '',
  `public_policy` char(191) NOT NULL DEFAULT '',
  `comment_policy` char(191) NOT NULL DEFAULT '',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `item_restrict` int(11) NOT NULL DEFAULT 0 ,
  `item_flags` int(11) NOT NULL DEFAULT 0 ,
  `item_private` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_origin` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_unseen` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_starred` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_uplink` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_consensus` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_wall` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_thread_top` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_notshown` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_nsfw` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_relay` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_mentionsme` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_nocomment` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_obscured` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_verified` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_retained` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_rss` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_deleted` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_type` int(11) NOT NULL DEFAULT 0 ,
  `item_hidden` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_unpublished` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_delayed` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_pending_remove` tinyint(1) NOT NULL DEFAULT 0 ,
  `item_blocked` tinyint(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `parent` (`parent`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `received` (`received`),
  KEY `uid_commented` (`uid`, `commented`),
  KEY `uid_created` (`uid`, `created`),
  KEY `uid_item_unseen` (`uid`, `item_unseen`),
  KEY `uid_item_type` (`uid`, `item_type`),
  KEY `uid_item_thread_top` (`uid`, `item_thread_top`),
  KEY `uid_item_blocked` (`uid`, `item_blocked`),
  KEY `uid_item_wall` (`uid`, `item_wall`),
  KEY `uid_item_starred` (`uid`, `item_starred`),
  KEY `uid_item_retained` (`uid`, `item_retained`),
  KEY `uid_item_private` (`uid`, `item_private`),
  KEY `uid_resource_type` (`uid`, `resource_type`),
  KEY `owner_xchan` (`owner_xchan`),
  KEY `author_xchan` (`author_xchan`),
  KEY `resource_id` (`resource_id`),
  KEY `resource_type` (`resource_type`),
  KEY `commented` (`commented`),
  KEY `verb` (`verb`),
  KEY `obj_type` (`obj_type`),
  KEY `expires` (`expires`),
  KEY `revision` (`revision`),
  KEY `mimetype` (`mimetype`),
  KEY `mid` (`mid`),
  KEY `parent_mid` (`parent_mid`),
  KEY `uid_mid` (`uid`,`mid`),
  KEY `comment_policy` (`comment_policy`),
  KEY `layout_mid` (`layout_mid`),
  KEY `public_policy` (`public_policy`),
  KEY `comments_closed` (`comments_closed`),
  KEY `changed` (`changed`),
  KEY `item_origin` (`item_origin`),
  KEY `item_wall` (`item_wall`),
  KEY `item_uplink` (`item_uplink`),
  KEY `item_nsfw` (`item_nsfw`),
  KEY `item_mentionsme` (`item_mentionsme`),
  KEY `item_nocomment` (`item_nocomment`),
  KEY `item_obscured` (`item_obscured`),
  KEY `item_rss` (`item_rss`),
  KEY `item_consensus` (`item_consensus`),
  KEY `item_deleted_pending_remove_changed` (`item_deleted`, `item_pending_remove`, `changed`),
  KEY `item_pending_remove_changed` (`item_pending_remove`, `changed`),
  KEY `thr_parent` (`thr_parent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `item_id` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iid` int(11) NOT NULL DEFAULT 0 ,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `sid` char(191) NOT NULL DEFAULT '',
  `service` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sid` (`sid`),
  KEY `service` (`service`),
  KEY `iid` (`iid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `likes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `liker` char(191) NOT NULL DEFAULT '',
  `likee` char(191) NOT NULL DEFAULT '',
  `iid` int(11) unsigned NOT NULL DEFAULT 0 ,
  `i_mid` char(191) NOT NULL DEFAULT '',
  `verb` char(191) NOT NULL DEFAULT '',
  `target_type` char(191) NOT NULL DEFAULT '',
  `target_id` char(191) NOT NULL DEFAULT '',
  `target` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `liker` (`liker`),
  KEY `likee` (`likee`),
  KEY `iid` (`iid`),
  KEY `i_mid` (`i_mid`),
  KEY `verb` (`verb`),
  KEY `target_type` (`target_type`),
  KEY `channel_id` (`channel_id`),
  KEY `target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listeners (
  id int(11) NOT NULL AUTO_INCREMENT,
  target_id varchar(191) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  portable_id varchar(191) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  ltype int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY target_id (target_id),
  KEY portable_id (portable_id),
  KEY ltype (ltype)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu` (
  `menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_channel_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `menu_name` char(191) NOT NULL DEFAULT '',
  `menu_desc` char(191) NOT NULL DEFAULT '',
  `menu_flags` int(11) NOT NULL DEFAULT 0 ,
  `menu_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `menu_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`menu_id`),
  KEY `menu_channel_id` (`menu_channel_id`),
  KEY `menu_name` (`menu_name`),
  KEY `menu_flags` (`menu_flags`),
  KEY `menu_created` (`menu_created`),
  KEY `menu_edited` (`menu_edited`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu_item` (
  `mitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mitem_link` char(191) NOT NULL DEFAULT '',
  `mitem_desc` char(191) NOT NULL DEFAULT '',
  `mitem_flags` int(11) NOT NULL DEFAULT 0 ,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `mitem_channel_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `mitem_menu_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `mitem_order` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`mitem_id`),
  KEY `mitem_channel_id` (`mitem_channel_id`),
  KEY `mitem_menu_id` (`mitem_menu_id`),
  KEY `mitem_flags` (`mitem_flags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` char(191) NOT NULL DEFAULT '',
  `xname` char(191) NOT NULL DEFAULT '',
  `url` char(191) NOT NULL DEFAULT '',
  `photo` char(191) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `msg` mediumtext NOT NULL,
  `aid` int(11) NOT NULL DEFAULT 0 ,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `link` char(191) NOT NULL DEFAULT '',
  `parent` char(191) NOT NULL DEFAULT '',
  `seen` tinyint(1) NOT NULL DEFAULT 0 ,
  `ntype` int(11) NOT NULL DEFAULT 0 ,
  `verb` char(191) NOT NULL DEFAULT '',
  `otype` char(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `ntype` (`ntype`),
  KEY `seen` (`seen`),
  KEY `uid` (`uid`),
  KEY `created` (`created`),
  KEY `hash` (`hash`),
  KEY `parent` (`parent`),
  KEY `link` (`link`),
  KEY `otype` (`otype`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `obj` (
  `obj_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_page` char(64) NOT NULL DEFAULT '',
  `obj_verb` char(191) NOT NULL DEFAULT '',
  `obj_type` int(10) unsigned NOT NULL DEFAULT 0 ,
  `obj_obj` char(191) NOT NULL DEFAULT '',
  `obj_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `obj_term` char(191) NOT NULL DEFAULT '',
  `obj_url` char(191) NOT NULL DEFAULT '',
  `obj_imgurl` char(191) NOT NULL DEFAULT '',
  `obj_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `obj_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `obj_quantity` int(11) NOT NULL DEFAULT 0 ,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`obj_id`),
  KEY `obj_verb` (`obj_verb`),
  KEY `obj_page` (`obj_page`),
  KEY `obj_type` (`obj_type`),
  KEY `obj_channel` (`obj_channel`),
  KEY `obj_term` (`obj_term`),
  KEY `obj_url` (`obj_url`),
  KEY `obj_imgurl` (`obj_imgurl`),
  KEY `obj_created` (`obj_created`),
  KEY `obj_edited` (`obj_edited`),
  KEY `obj_quantity` (`obj_quantity`),
  KEY `obj_obj` (`obj_obj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `outq` (
  `outq_hash` char(191) NOT NULL,
  `outq_account` int(10) unsigned NOT NULL DEFAULT 0 ,
  `outq_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `outq_driver` char(32) NOT NULL DEFAULT '',
  `outq_posturl` char(191) NOT NULL DEFAULT '',
  `outq_async` tinyint(1) NOT NULL DEFAULT 0 ,
  `outq_delivered` tinyint(1) NOT NULL DEFAULT 0 ,
  `outq_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `outq_updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `outq_scheduled` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `outq_notify` mediumtext NOT NULL,
  `outq_msg` mediumtext NOT NULL,
  `outq_priority` smallint(6) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`outq_hash`),
  KEY `outq_account` (`outq_account`),
  KEY `outq_channel` (`outq_channel`),
  KEY `outq_hub` (`outq_posturl`),
  KEY `outq_created` (`outq_created`),
  KEY `outq_updated` (`outq_updated`),
  KEY `outq_scheduled` (`outq_scheduled`),
  KEY `outq_async` (`outq_async`),
  KEY `outq_delivered` (`outq_delivered`),
  KEY `outq_priority` (`outq_priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pchan (
  `pchan_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pchan_guid` char(191) NOT NULL DEFAULT '',
  `pchan_hash` char(191) NOT NULL DEFAULT '',
  `pchan_pubkey` text NOT NULL,
  `pchan_prvkey` text NOT NULL,
  PRIMARY KEY (`pchan_id`),
  KEY `pchan_guid` (`pchan_guid`),
  KEY `pchan_hash` (`pchan_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  `updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`uid`,`cat`,`k`),
  KEY `pconfig_updated` (`updated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `xchan` char(191) NOT NULL DEFAULT '',
  `resource_id` char(191) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `title` char(191) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `album` char(191) NOT NULL DEFAULT '',
  `filename` char(191) NOT NULL DEFAULT '',
  `mimetype` char(128) NOT NULL DEFAULT 'image/jpeg',
  `height` smallint(6) NOT NULL DEFAULT 0 ,
  `width` smallint(6) NOT NULL DEFAULT 0 ,
  `filesize` int(10) unsigned NOT NULL DEFAULT 0 ,
  `content` mediumblob NOT NULL,
  `imgscale` tinyint(3) NOT NULL DEFAULT 0 ,
  `photo_usage` smallint(6) NOT NULL DEFAULT 0 ,
  `profile` tinyint(1) NOT NULL DEFAULT 0 ,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0 ,
  `os_storage` tinyint(1) NOT NULL DEFAULT 0 ,
  `os_path` mediumtext NOT NULL,
  `display_path` mediumtext NOT NULL,
  `photo_flags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `album` (`album`),
  KEY `imgscale` (`imgscale`),
  KEY `profile` (`profile`),
  KEY `photo_flags` (`photo_flags`),
  KEY `mimetype` (`mimetype`),
  KEY `aid` (`aid`),
  KEY `xchan` (`xchan`),
  KEY `filesize` (`filesize`),
  KEY `resource_id` (`resource_id`),
  KEY `expires` (`expires`),
  KEY `is_nsfw` (`is_nsfw`),
  KEY `os_storage` (`os_storage`),
  KEY `photo_usage` (`photo_usage`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `poll` (
  `poll_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_guid` varchar(191) NOT NULL,
  `poll_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `poll_author` varchar(191) NOT NULL,
  `poll_desc` text NOT NULL,
  `poll_flags` int(11) NOT NULL DEFAULT 0 ,
  `poll_votes` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`poll_id`),
  KEY `poll_guid` (`poll_guid`),
  KEY `poll_channel` (`poll_channel`),
  KEY `poll_author` (`poll_author`),
  KEY `poll_flags` (`poll_flags`),
  KEY `poll_votes` (`poll_votes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `poll_elm` (
  `pelm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pelm_guid` varchar(191) NOT NULL,
  `pelm_poll` int(10) unsigned NOT NULL DEFAULT 0 ,
  `pelm_desc` text NOT NULL,
  `pelm_flags` int(11) NOT NULL DEFAULT 0 ,
  `pelm_result` float NOT NULL DEFAULT 0 ,
  `pelm_order` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`pelm_id`),
  KEY `pelm_guid` (`pelm_guid`),
  KEY `pelm_poll` (`pelm_poll`),
  KEY `pelm_result` (`pelm_result`),
  KEY `pelm_order` (`pelm_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profdef` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_name` char(191) NOT NULL DEFAULT '',
  `field_type` char(16) NOT NULL DEFAULT '',
  `field_desc` char(191) NOT NULL DEFAULT '',
  `field_help` char(191) NOT NULL DEFAULT '',
  `field_inputs` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `hash` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `channel_id` (`channel_id`),
  KEY `hash` (`hash`),
  KEY `k` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_guid` char(64) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `profile_name` char(191) NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 ,
  `hide_friends` tinyint(1) NOT NULL DEFAULT 0 ,
  `fullname` char(191) NOT NULL DEFAULT '',
  `pdesc` char(191) NOT NULL DEFAULT '',
  `chandesc` text NOT NULL,
  `dob` char(32) NOT NULL DEFAULT '0000-00-00',
  `dob_tz` char(191) NOT NULL DEFAULT 'UTC',
  `address` char(191) NOT NULL DEFAULT '',
  `locality` char(191) NOT NULL DEFAULT '',
  `region` char(191) NOT NULL DEFAULT '',
  `postal_code` char(32) NOT NULL DEFAULT '',
  `country_name` char(191) NOT NULL DEFAULT '',
  `hometown` char(191) NOT NULL DEFAULT '',
  `gender` char(32) NOT NULL DEFAULT '',
  `marital` char(191) NOT NULL DEFAULT '',
  `partner` text NOT NULL,
  `howlong` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `sexual` char(191) NOT NULL DEFAULT '',
  `politic` char(191) NOT NULL DEFAULT '',
  `religion` char(191) NOT NULL DEFAULT '',
  `keywords` text NOT NULL,
  `likes` text NOT NULL,
  `dislikes` text NOT NULL,
  `about` text NOT NULL,
  `summary` char(191) NOT NULL DEFAULT '',
  `music` text NOT NULL,
  `book` text NOT NULL,
  `tv` text NOT NULL,
  `film` text NOT NULL,
  `interest` text NOT NULL,
  `romance` text NOT NULL,
  `employment` text NOT NULL,
  `education` text NOT NULL,
  `contact` text NOT NULL,
  `channels` text NOT NULL,
  `homepage` char(191) NOT NULL DEFAULT '',
  `photo` char(191) NOT NULL DEFAULT '',
  `thumb` char(191) NOT NULL DEFAULT '',
  `publish` tinyint(1) NOT NULL DEFAULT 0 ,
  `profile_vcard` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guid` (`profile_guid`,`uid`),
  KEY `uid` (`uid`),
  KEY `locality` (`locality`),
  KEY `hometown` (`hometown`),
  KEY `gender` (`gender`),
  KEY `marital` (`marital`),
  KEY `sexual` (`sexual`),
  KEY `publish` (`publish`),
  KEY `aid` (`aid`),
  KEY `is_default` (`is_default`),
  KEY `hide_friends` (`hide_friends`),
  KEY `postal_code` (`postal_code`),
  KEY `country_name` (`country_name`),
  KEY `profile_guid` (`profile_guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profile_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `cid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `dfrn_id` char(191) NOT NULL DEFAULT '',
  `sec` char(191) NOT NULL DEFAULT '',
  `expire` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cid` (`cid`),
  KEY `dfrn_id` (`dfrn_id`),
  KEY `sec` (`sec`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `register` (
  `reg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reg_vital` int(10) unsigned NOT NULL DEFAULT 1,
  `reg_flags` int(10) unsigned NOT NULL DEFAULT 0,
  `reg_didx` char(1) NOT NULL DEFAULT '',
  `reg_did2` char(191) NOT NULL DEFAULT '',
  `reg_hash` char(191) NOT NULL DEFAULT '',
  `reg_email` char(191) NOT NULL DEFAULT '',
  `reg_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `reg_startup` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `reg_expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `reg_byc` int(10) unsigned NOT NULL DEFAULT 0 ,
  `reg_uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `reg_atip` char(191) NOT NULL DEFAULT '',
  `reg_pass` char(191) NOT NULL DEFAULT '',
  `reg_lang` char(16) NOT NULL DEFAULT '',
  `reg_stuff` text NOT NULL,
  PRIMARY KEY (`reg_id`),
  KEY `ix_reg_vital` (`reg_vital`),
  KEY `ix_reg_flags` (`reg_flags`),
  KEY `ix_reg_didx` (`reg_didx`),
  KEY `ix_reg_did2` (`reg_did2`),
  KEY `ix_reg_hash` (`reg_hash`),
  KEY `ix_reg_email` (`reg_email`),
  KEY `ix_reg_created` (`reg_created`),
  KEY `ix_reg_startup` (`reg_startup`),
  KEY `ix_reg_expires` (`reg_expires`),
  KEY `ix_reg_byc` (`reg_byc`),
  KEY `ix_reg_uid` (`reg_uid`),
  KEY `ix_reg_atip` (`reg_atip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sid` char(191) NOT NULL DEFAULT '',
  `sess_data` text NOT NULL,
  `expire` bigint(20) unsigned NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shares` (
  `share_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `share_type` int(11) NOT NULL DEFAULT 0 ,
  `share_target` int(10) unsigned NOT NULL DEFAULT 0 ,
  `share_xchan` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`share_id`),
  KEY `share_type` (`share_type`),
  KEY `share_target` (`share_target`),
  KEY `share_xchan` (`share_xchan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `retract_iid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `signed_text` mediumtext NOT NULL,
  `signature` text NOT NULL,
  `signer` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `iid` (`iid`),
  KEY `retract_iid` (`retract_iid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site` (
  `site_url` char(191) NOT NULL,
  `site_access` int(11) NOT NULL DEFAULT 0 ,
  `site_flags` int(11) NOT NULL DEFAULT 0 ,
  `site_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `site_pull` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `site_sync` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `site_directory` char(191) NOT NULL DEFAULT '',
  `site_register` int(11) NOT NULL DEFAULT 0 ,
  `site_sellpage` char(191) NOT NULL DEFAULT '',
  `site_location` char(191) NOT NULL DEFAULT '',
  `site_realm` char(191) NOT NULL DEFAULT '',
  `site_valid` smallint NOT NULL DEFAULT 0 ,
  `site_dead` smallint NOT NULL DEFAULT 0 ,
  `site_type` smallint NOT NULL DEFAULT 0 ,
  `site_project` char(191) NOT NULL DEFAULT '',
  `site_version` varchar(32) NOT NULL DEFAULT '',
  `site_crypto` text NOT NULL,
  PRIMARY KEY (`site_url`),
  KEY `site_flags` (`site_flags`),
  KEY `site_update` (`site_update`),
  KEY `site_directory` (`site_directory`),
  KEY `site_register` (`site_register`),
  KEY `site_access` (`site_access`),
  KEY `site_sellpage` (`site_sellpage`),
  KEY `site_pull` (`site_pull`),
  KEY `site_realm` (`site_realm`),
  KEY `site_valid` (`site_valid`),
  KEY `site_dead` (`site_dead`),
  KEY `site_type` (`site_type`),
  KEY `site_project` (`site_project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `source` (
  `src_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `src_channel_id` int(10) unsigned NOT NULL DEFAULT 0 ,
  `src_channel_xchan` char(191) NOT NULL DEFAULT '',
  `src_xchan` char(191) NOT NULL DEFAULT '',
  `src_patt` mediumtext NOT NULL,
  `src_tag` mediumtext NOT NULL,
  PRIMARY KEY (`src_id`),
  KEY `src_channel_id` (`src_channel_id`),
  KEY `src_channel_xchan` (`src_channel_xchan`),
  KEY `src_xchan` (`src_xchan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sys_perms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  `public_perm` tinyint(1) unsigned NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `term` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `uid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `oid` int(10) unsigned NOT NULL DEFAULT 0 ,
  `otype` tinyint(3) unsigned NOT NULL DEFAULT 0 ,
  `ttype` tinyint(3) unsigned NOT NULL DEFAULT 0 ,
  `term` char(191) NOT NULL DEFAULT '',
  `url` char(191) NOT NULL DEFAULT '',
  `imgurl` char(191) NOT NULL DEFAULT '',
  `term_hash` char(191) NOT NULL DEFAULT '',
  `parent_hash` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`tid`),
  KEY `oid` (`oid`),
  KEY `otype` (`otype`),
  KEY `ttype` (`ttype`),
  KEY `term` (`term`),
  KEY `uid` (`uid`),
  KEY `aid` (`aid`),
  KEY `imgurl` (`imgurl`),
  KEY `term_hash` (`term_hash`),
  KEY `parent_hash` (`parent_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` varchar(191) NOT NULL DEFAULT '',
  `secret` text NOT NULL,
  `client_id` varchar(191) NOT NULL DEFAULT '',
  `expires` bigint(20) unsigned NOT NULL DEFAULT 0 ,
  `auth_scope` varchar(512) NOT NULL DEFAULT '',
  `uid` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `expires` (`expires`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `updates` (
  `ud_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ud_hash` char(191) NOT NULL DEFAULT '',
  `ud_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `ud_last` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `ud_flags` int(11) NOT NULL DEFAULT 0 ,
  `ud_addr` char(191) NOT NULL DEFAULT '',
  `ud_update` tinyint(1) NOT NULL DEFAULT 0,
  `ud_host` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`ud_id`),
  KEY `ud_date` (`ud_date`),
  KEY `ud_hash` (`ud_hash`),
  KEY `ud_flags` (`ud_flags`),
  KEY `ud_addr` (`ud_addr`),
  KEY `ud_last` (`ud_last`),
  KEY `ud_update` (`ud_update`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `verify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `vtype` char(32) NOT NULL DEFAULT '',
  `token` char(191) NOT NULL DEFAULT '',
  `meta` char(191) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `vtype` (`vtype`),
  KEY `token` (`token`),
  KEY `meta` (`meta`),
  KEY `created` (`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vote_guid` varchar(191) NOT NULL,
  `vote_poll` int(11) NOT NULL DEFAULT 0 ,
  `vote_element` int(11) NOT NULL DEFAULT 0 ,
  `vote_result` text NOT NULL,
  `vote_xchan` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `vote_vote` (`vote_poll`,`vote_element`,`vote_xchan`),
  KEY `vote_guid` (`vote_guid`),
  KEY `vote_poll` (`vote_poll`),
  KEY `vote_element` (`vote_element`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xchan` (
  `xchan_hash` char(191) NOT NULL,
  `xchan_guid` char(191) NOT NULL DEFAULT '',
  `xchan_guid_sig` text NOT NULL,
  `xchan_pubkey` text NOT NULL,
  `xchan_photo_mimetype` char(32) NOT NULL DEFAULT 'image/jpeg',
  `xchan_photo_l` char(191) NOT NULL DEFAULT '',
  `xchan_photo_m` char(191) NOT NULL DEFAULT '',
  `xchan_photo_s` char(191) NOT NULL DEFAULT '',
  `xchan_addr` char(191) NOT NULL DEFAULT '',
  `xchan_url` char(191) NOT NULL DEFAULT '',
  `xchan_connurl` char(191) NOT NULL DEFAULT '',
  `xchan_follow` char(191) NOT NULL DEFAULT '',
  `xchan_connpage` char(191) NOT NULL DEFAULT '',
  `xchan_name` char(191) NOT NULL DEFAULT '',
  `xchan_network` char(191) NOT NULL DEFAULT '',
  `xchan_instance_url` char(191) NOT NULL DEFAULT '',
  `xchan_flags` int(10) unsigned NOT NULL DEFAULT 0 ,
  `xchan_photo_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `xchan_name_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `xchan_hidden` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_orphan` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_censored` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_selfcensored` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_system` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_pubforum` tinyint(1) NOT NULL DEFAULT 0 ,
  `xchan_deleted` tinyint(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`xchan_hash`),
  KEY `xchan_guid` (`xchan_guid`),
  KEY `xchan_addr` (`xchan_addr`),
  KEY `xchan_name` (`xchan_name`),
  KEY `xchan_network` (`xchan_network`),
  KEY `xchan_url` (`xchan_url`),
  KEY `xchan_flags` (`xchan_flags`),
  KEY `xchan_connurl` (`xchan_connurl`),
  KEY `xchan_instance_url` (`xchan_instance_url`),
  KEY `xchan_follow` (`xchan_follow`),
  KEY `xchan_hidden` (`xchan_hidden`),
  KEY `xchan_orphan` (`xchan_orphan`),
  KEY `xchan_censored` (`xchan_censored`),
  KEY `xchan_selfcensored` (`xchan_selfcensored`),
  KEY `xchan_system` (`xchan_system`),
  KEY `xchan_pubforum` (`xchan_pubforum`),
  KEY `xchan_deleted` (`xchan_deleted`),
  KEY `xchan_photo_m` (`xchan_photo_m`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xchat` (
  `xchat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xchat_url` char(191) NOT NULL DEFAULT '',
  `xchat_desc` char(191) NOT NULL DEFAULT '',
  `xchat_xchan` char(191) NOT NULL DEFAULT '',
  `xchat_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`xchat_id`),
  KEY `xchat_url` (`xchat_url`),
  KEY `xchat_desc` (`xchat_desc`),
  KEY `xchat_xchan` (`xchat_xchan`),
  KEY `xchat_edited` (`xchat_edited`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xchan` char(191) NOT NULL DEFAULT '',
  `cat` char(191) NOT NULL DEFAULT '',
  `k` char(191) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `xchan` (`xchan`),
  KEY `cat` (`cat`),
  KEY `k` (`k`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT 0 ,
  `xchan` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `xchan` (`xchan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xlink` (
  `xlink_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xlink_xchan` char(191) NOT NULL DEFAULT '',
  `xlink_link` char(191) NOT NULL DEFAULT '',
  `xlink_rating` int(11) NOT NULL DEFAULT 0 ,
  `xlink_rating_text` text NOT NULL,
  `xlink_updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `xlink_static` tinyint(1) NOT NULL DEFAULT 0 ,
  `xlink_sig` text NOT NULL,
  PRIMARY KEY (`xlink_id`),
  KEY `xlink_xchan` (`xlink_xchan`),
  KEY `xlink_link` (`xlink_link`),
  KEY `xlink_updated` (`xlink_updated`),
  KEY `xlink_rating` (`xlink_rating`),
  KEY `xlink_static` (`xlink_static`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xperm` (
  `xp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xp_client` varchar(20) NOT NULL DEFAULT '',
  `xp_channel` int(10) unsigned NOT NULL DEFAULT 0 ,
  `xp_perm` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`xp_id`),
  KEY `xp_client` (`xp_client`),
  KEY `xp_channel` (`xp_channel`),
  KEY `xp_perm` (`xp_perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xprof` (
  `xprof_hash` char(191) NOT NULL,
  `xprof_age` tinyint(3) unsigned NOT NULL DEFAULT 0 ,
  `xprof_desc` char(191) NOT NULL DEFAULT '',
  `xprof_dob` char(12) NOT NULL DEFAULT '',
  `xprof_gender` char(191) NOT NULL DEFAULT '',
  `xprof_marital` char(191) NOT NULL DEFAULT '',
  `xprof_sexual` char(191) NOT NULL DEFAULT '',
  `xprof_locale` char(191) NOT NULL DEFAULT '',
  `xprof_region` char(191) NOT NULL DEFAULT '',
  `xprof_postcode` char(32) NOT NULL DEFAULT '',
  `xprof_country` char(191) NOT NULL DEFAULT '',
  `xprof_keywords` text NOT NULL,
  `xprof_about` text NOT NULL,
  `xprof_homepage` char(191) NOT NULL DEFAULT '',
  `xprof_hometown` char(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`xprof_hash`),
  KEY `xprof_desc` (`xprof_desc`),
  KEY `xprof_dob` (`xprof_dob`),
  KEY `xprof_gender` (`xprof_gender`),
  KEY `xprof_marital` (`xprof_marital`),
  KEY `xprof_sexual` (`xprof_sexual`),
  KEY `xprof_locale` (`xprof_locale`),
  KEY `xprof_region` (`xprof_region`),
  KEY `xprof_postcode` (`xprof_postcode`),
  KEY `xprof_country` (`xprof_country`),
  KEY `xprof_age` (`xprof_age`),
  KEY `xprof_hometown` (`xprof_hometown`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `xtag` (
  `xtag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xtag_hash` char(191) NOT NULL DEFAULT '',
  `xtag_term` char(191) NOT NULL DEFAULT '',
  `xtag_flags` int(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`xtag_id`),
  KEY `xtag_term` (`xtag_term`),
  KEY `xtag_hash` (`xtag_hash`),
  KEY `xtag_flags` (`xtag_flags`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists addressbooks (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    displayname VARCHAR(255),
    uri VARBINARY(200),
    description TEXT,
    synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
    UNIQUE(principaluri(100), uri(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists cards (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    addressbookid INT(11) UNSIGNED NOT NULL,
    carddata MEDIUMBLOB,
    uri VARBINARY(200),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists addressbookchanges (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    addressbookid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX addressbookid_synctoken (addressbookid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendarobjects (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendardata MEDIUMBLOB,
    uri VARBINARY(200),
    calendarid INTEGER UNSIGNED NOT NULL,
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL,
    componenttype VARBINARY(8),
    firstoccurence INT(11) UNSIGNED,
    lastoccurence INT(11) UNSIGNED,
    uid VARBINARY(200),
    UNIQUE(calendarid, uri),
    INDEX calendarid_time (calendarid, firstoccurence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
    components VARBINARY(21)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendarinstances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    share_href VARBINARY(100),
    share_displayname VARCHAR(100),
    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
    UNIQUE(principaluri, uri),
    UNIQUE(calendarid, principaluri),
    UNIQUE(calendarid, share_href)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendarchanges (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    calendarid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX calendarid_synctoken (calendarid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendarsubscriptions (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    principaluri VARBINARY(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    striptodos TINYINT(1) NULL,
    stripalarms TINYINT(1) NULL,
    stripattachments TINYINT(1) NULL,
    lastmodified INT(11) UNSIGNED,
    UNIQUE(principaluri, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists schedulingobjects (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    calendardata MEDIUMBLOB,
    uri VARBINARY(200),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists locks (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    owner VARCHAR(100),
    timeout INTEGER UNSIGNED,
    created INTEGER,
    token VARBINARY(100),
    scope TINYINT,
    depth TINYINT,
    uri VARBINARY(1000),
    INDEX(token),
    INDEX(uri(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists principals (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    email VARBINARY(80),
    displayname VARCHAR(80),
    UNIQUE(uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists groupmembers (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principal_id INTEGER UNSIGNED NOT NULL,
    member_id INTEGER UNSIGNED NOT NULL,
    UNIQUE(principal_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists propertystorage (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path VARBINARY(1024) NOT NULL,
    name VARBINARY(100) NOT NULL,
    valuetype INT UNSIGNED,
    value MEDIUMBLOB
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE UNIQUE INDEX path_property ON propertystorage (path(600), name(100));

CREATE TABLE if not exists users (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARBINARY(50),
    digesta1 VARBINARY(32),
    UNIQUE(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists calendarinstances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    share_href VARBINARY(100),
    share_displayname VARCHAR(100),
    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
    UNIQUE(principaluri, uri),
    UNIQUE(calendarid, principaluri),
    UNIQUE(calendarid, share_href)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE if not exists oauth_clients (
  client_id             VARCHAR(80)   NOT NULL,
  client_secret         VARCHAR(80),
  redirect_uri          VARCHAR(2000),
  grant_types           VARCHAR(80),
  scope                 VARCHAR(4000),
  user_id               int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists oauth_access_tokens (
  access_token         VARCHAR(40)    NOT NULL,
  client_id            VARCHAR(80)    NOT NULL,
  user_id              int(10) unsigned NOT NULL DEFAULT 0,
  expires              TIMESTAMP      NOT NULL,
  scope                VARCHAR(4000),
  PRIMARY KEY (access_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists oauth_authorization_codes (
  authorization_code  VARCHAR(40)     NOT NULL,
  client_id           VARCHAR(80)     NOT NULL,
  user_id             int(10) unsigned NOT NULL DEFAULT 0,
  redirect_uri        VARCHAR(2000),
  expires             TIMESTAMP       NOT NULL,
  scope               VARCHAR(4000),
  id_token            VARCHAR(1000),
  PRIMARY KEY (authorization_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists oauth_refresh_tokens (
  refresh_token       VARCHAR(40)     NOT NULL,
  client_id           VARCHAR(80)     NOT NULL,
  user_id             int(10) unsigned NOT NULL DEFAULT 0,
  expires             TIMESTAMP       NOT NULL,
  scope               VARCHAR(4000),
  PRIMARY KEY (refresh_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists oauth_scopes (
  scope               VARCHAR(191)    NOT NULL,
  is_default          TINYINT(1),
  PRIMARY KEY (scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE if not exists oauth_jwt (
  client_id           VARCHAR(80)     NOT NULL,
  subject             VARCHAR(80),
  public_key          VARCHAR(2000)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workerq (
	workerq_id BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	workerq_priority smallint,
	workerq_reservationid varchar(25) DEFAULT NULL,
	workerq_processtimeout datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
	workerq_data text,
	workerq_uuid char(36) NOT NULL DEFAULT '',
	workerq_cmd varchar(191) NOT NULL DEFAULT '',
	KEY workerq_priority (workerq_priority),
	KEY workerq_reservationid (workerq_reservationid),
	KEY workerq_processtimeout (workerq_processtimeout),
	KEY workerq_uuid (workerq_uuid)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
