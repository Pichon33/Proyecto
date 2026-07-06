CREATE DATABASE IF NOT EXISTS ApexDigital;
USE ApexDigital;

CREATE TABLE `users` (
 `id` int(99) NOT NULL,
 `username` varchar(255) NOT NULL,
 `password` varchar(255) NOT NULL,
 `email`    varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) 