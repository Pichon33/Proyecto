CREATE DATABASE IF NOT EXISTS ApexDigital;
USE ApexDigital;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email`    varchar(255) NOT NULL,
    `rol`      VARCHAR(20) NOT NULL
) 

CREATE TABLE `rutascortadas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` TEXT NOT NULL,
    `coord_inicio` VARCHAR(50) NOT NULL, 
    `coord_final` VARCHAR(50) NOT NULL
)