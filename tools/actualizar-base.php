<?php
// Uso: C:\xampp\php\php.exe tools/actualizar-base.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../database/db.php';
$pdo->exec(file_get_contents(__DIR__ . '/../database/funciones_sociales.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database/seguridad.sql'));
// Actualización compatible con bases que ya tenían las funciones sociales.
if (!$pdo->query("SHOW COLUMNS FROM EventoExtra LIKE 'ciudad'")->fetch()) {
    $pdo->exec("ALTER TABLE EventoExtra ADD ciudad VARCHAR(100) NOT NULL DEFAULT ''");
}
echo "Funciones sociales instaladas. Se conservaron los datos existentes.\n";
