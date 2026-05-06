<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    private function addRoute(string $method, string $path, $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        return $route;
    }

    public function getRoute(string $path, $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function postRoute(string $path, $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function patchRoute(string $path, $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function deleteRoute(string $path, $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

public function route(string $requestMethod, string $requestPath)
{
    foreach ($this->routes as $route) {
        
        if ($route->method !== $requestMethod) {
            continue;
        }

        $params = $this->matchPath($route->path, $requestPath);

        if ($params === false) {
            continue;
        }

        $this->runMiddleware($route->middleware);

        return $this->callHandler($route->handler, $params);
    }

    throw new \Exception('Route not found', 404);
}

    private function matchPath(string $routePath, string $requestPath): array|false
    {
        $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return false;
        }

        array_shift($matches);

        return $matches;
    }

    private function callHandler($handler, array $params)
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;

            $controller = new $class();

            return call_user_func_array([$controller, $method], $params);
        }

        throw new \Exception('Invalid route handler');
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $middlewareClass) {
            $middlewareInstance = new $middlewareClass();

            if (!method_exists($middlewareInstance, 'handle')) {
                throw new \Exception("Middleware {$middlewareClass} must have a handle method.");
            }

            $middlewareInstance->handle();
        }
    }

    private function json($data): void
    {
        header('Content-Type: application/json');

        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}