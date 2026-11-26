<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'regex' => $this->compilePath($path),
            'handler' => $handler,
        ];
    }

    private function compilePath(string $path): string
    {
        $segments = preg_split('/(\{[A-Za-z_][A-Za-z0-9_]*\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = '';

        foreach ($segments as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $match)) {
                $pattern .= '(?P<' . $match[1] . '>\d+)';
            } else {
                $pattern .= preg_quote($segment, '#');
            }
        }

        return '#^' . $pattern . '/?$#D';
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== strtoupper($method)) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = (int) $value;
                }
            }
            return $this->invoke($route['handler'], $params);
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            throw new HttpException('Method not allowed.', 405);
        }

        throw new HttpException('Саҳифа ёфт нашуд.', 404);
    }

    private function invoke(callable|array $handler, array $params): mixed
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && class_exists($handler[0])) {
            $controller = new $handler[0]();
            return $controller->{$handler[1]}(...$params);
        }

        return call_user_func_array($handler, $params);
    }
}
