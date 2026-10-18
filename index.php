<?php

// Aplicar antes de que las vistas abran la sesión. En HTTPS la cookie solo viaja cifrada.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax',
    'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);

require_once __DIR__ . '/src/controllers/controllers.php';

$routes = require __DIR__ . '/src/routes/routes.php';
$route = isset($_GET['ruta']) && is_string($_GET['ruta']) ? $_GET['ruta'] : 'home';

(new PageController($routes))->dispatch($route);
