<?php

namespace App\Core;

class Router
{
    protected $routes = [];

    public function __construct()
    {
        $this->routes = require __DIR__ . '/../routes.php';
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['path'] === $uri && (empty($route['http_method']) || strtoupper($route['http_method']) === $method)) {
                $controllerName = "App\\Controllers\\" . $route['controller'];
                $methodName = $route['method'];

                if (class_exists($controllerName) && method_exists($controllerName, $methodName)) {
                    $controller = new $controllerName();
                    $controller->$methodName();
                    return;
                }
            }
        }

        header("HTTP/1.0 404 Not Found");
        echo "404 - Page non trouvée";
    }
}