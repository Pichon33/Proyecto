<?php

class PageController
{
    public function __construct(private array $routes)
    {
    }

    public function dispatch(string $route): void
    {
        $view = $this->routes[$route] ?? $this->routes['home'];
        $viewPath = dirname(__DIR__) . '/views/' . $view;

        if (!is_file($viewPath)) {
            http_response_code(404);
            exit('Página no encontrada.');
        }

        require $viewPath;
    }
}
