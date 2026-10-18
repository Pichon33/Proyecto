<?php

class PageController
{
    public function __construct(private array $routes)
    {
    }

    public function dispatch(string $route): void
    {
        if ($route === 'api') {
            require __DIR__ . '/api.php';
            return;
        }
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Página no encontrada | iVento</title><h1>Página no encontrada</h1><p>El enlace no existe o cambió.</p><a href="index.php?ruta=home">Volver a iVento</a></html>';
            return;
        }
        $view = $this->routes[$route];
        $viewPath = dirname(__DIR__) . '/views/' . $view;

        if (!is_file($viewPath)) {
            http_response_code(404);
            exit('Página no encontrada.');
        }

        require $viewPath;
    }
}
