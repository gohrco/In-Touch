


CREATE TABLE IF NOT EXISTS `mod_intouch_settings` (
  `key` varchar(50) NOT NULL,
  `value` text NOT NULL,
PRIMARY KEY(`key`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- command split --


CREATE TABLE IF NOT EXISTS `mod_intouch_groups` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `group` mediumint(8) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `params` text NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- command split --


CREATE TABLE IF NOT EXISTS `mod_intouch_quotexref` (
  `qid` mediumint(8) NOT NULL,
  `gid` mediumint(8) NOT NULL,
PRIMARY KEY(`qid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

