DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `stream_key` varchar(255) NOT NULL,
  `private` tinyint(1) NOT NULL DEFAULT 1,
  `private_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`,`stream_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
