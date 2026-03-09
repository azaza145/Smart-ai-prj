<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, callable|array $handler, array $middlewares): self
    {
        $pattern = $this->pathToRegex($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
        return $this;
    }

    private function pathToRegex(string $path): string
    {
        $path = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }

    public function middleware(callable $mw): self
    {
        $this->middlewares[] = $mw;
        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $allMiddlewares = array_merge($this->middlewares, $route['middlewares']);
                $handler = $route['handler'];
                $runner = function () use ($handler, $params) {
                    if (is_array($handler)) {
                        [$class, $method] = $handler;
                        $controller = new $class();
                        return $controller->$method(...array_values($params));
                    }
                    return $handler(...array_values($params));
                };
                foreach (array_reverse($allMiddlewares) as $mw) {
                    $runner = fn () => $mw($runner);
                }
                $runner();
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
