<?php

// Conexión compartida a la base de datos.
$host = 'localhost';
$dbname = 'ivento';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log((string)$e);
    http_response_code(503);
    exit('No se pudo conectar con la base de datos. Intentá nuevamente en unos minutos.');
}
?>
