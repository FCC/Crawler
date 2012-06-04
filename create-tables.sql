SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `links` (
  `from` int(10) NOT NULL,
  `to` int(10) NOT NULL,
  KEY `from` (`from`,`to`),
  KEY `to` (`to`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `urls` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `url` varchar(264) NOT NULL,
  `title` varchar(128) DEFAULT NULL,
  `crawled` int(1) NOT NULL DEFAULT '0',
  `clicks` int(3) DEFAULT NULL,
  `http_code` int(3) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `modified` int(15) DEFAULT NULL,
  `md5` varchar(32) DEFAULT NULL,
  `crawl_tag` varchar(32) DEFAULT NULL,
  `html` text DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE INDEX crawl_tag ON urls (crawl_tag);
