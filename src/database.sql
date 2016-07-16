CREATE TABLE `Autotype_tbl` (
  `AutotypeID` mediumint(9) NOT NULL auto_increment,
  `Content` text,
  `Approval` char(1) NOT NULL default 'U',
  `IpAddress` varchar(30) NOT NULL default '',
  `DateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `PostID` int(11) default NULL,
  PRIMARY KEY  (`AutotypeID`)
) ENGINE=MyISAM AUTO_INCREMENT=678 DEFAULT CHARSET=utf8;
