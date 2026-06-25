<?php
/* ==================================
   CONFIGURACIÓN CENTRAL DEL PROYECTO
   ================================== */

/* Base de Datos */
$host = 'localhost';
$dbname = 'apexdigital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

/* ==================================
   FUNCIONES ÚTILES
   ================================== */

/* Redirecciona a login si el usuario no está logueado */
function verificarSesion() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/* Obtiene el nombre de usuario de la sesión */
function obtenerUsuario() {
    return isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario';
}

/* Limpia datos para evitar inyecciones de código */
function limpiar($dato) {
    return htmlspecialchars(trim($dato));
}

/* Redirecciona de forma segura */
function redirigir($url) {
    header('Location: ' . $url);
    exit;
}