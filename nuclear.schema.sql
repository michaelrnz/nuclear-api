SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_change_email` (
  `user` int(10) unsigned NOT NULL,
  `hash` char(44) character set ascii default NULL,
  `email` varchar(320) collate utf8_general_ci default NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_password_reset` (
  `user` int(10) unsigned NOT NULL,
  `name` varchar(64) character set utf8 default NULL,
  `hash` char(44) character set ascii default NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user`),
  KEY `name` (`name`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_user` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `email` varchar(320) default NULL,
  `domain` varchar(255) default NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_userapi` (
  `id` int(10) unsigned NOT NULL,
  `key0` char(22) default NULL,
  `key1` char(22) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `key0` (`key0`),
  KEY `key1` (`key1`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_userkey` (
  `id` int(10) unsigned NOT NULL,
  `pass` char(28) default NULL,
  `verify` char(44) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_logged` (
  `user` int(10) NOT NULL,
  `ip` bigint(20) unsigned default '0',
  `session` char(64) NOT NULL,
  `login` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `session` (`session`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_system` (
  `id` int(10) unsigned NOT NULL,
  `level` enum('root','super','administrator','moderator','default') default 'default',
  `verified` enum('no','yes') default 'no',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_username` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nuclear_verify` (
  `hash` char(44) default NULL,
  `user` varchar(64) NOT NULL,
  `pass` char(28) default NULL,
  `email` varchar(320) default NULL,
  `domain` varchar(255) default NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `user` (`user`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
