<?php
/* Funciones útiles reutilizables en todo el proyecto */

/* Redirecciona a una página si el usuario no está logueado */
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
