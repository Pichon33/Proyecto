<?php
$host = 'localhost';
$dbname = 'HayPlan';
$username = 'root';
$password = '57884081';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

