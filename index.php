<?php

require_once __DIR__ . '/src/controllers/controllers.php';

$routes = require __DIR__ . '/src/routes/routes.php';
$route = isset($_GET['ruta']) && is_string($_GET['ruta']) ? $_GET['ruta'] : 'home';

(new PageController($routes))->dispatch($route);
