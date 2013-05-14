CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%url_generate` (
  `id` INT(11) unsigned NOT NULL auto_increment,
  `article_id` INT(11) NOT NULL,
  `clang` INT(11) NOT NULL DEFAULT '0',

  `url` TEXT DEFAULT NULL,
  `table` varchar(255) NOT NULL,
  `table_parameters` text NOT NULL,
  
  `createdate` INT(11) NOT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updatedate` INT(11) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;