<?php

namespace App;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, regex: string, handler: callable}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $trimmed = trim($pattern, '/');
        $parts = preg_split('#(\{[a-zA-Z_]+\})#', $trimmed, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('#^\{([a-zA-Z_]+)\}$#', $part, $match)) {
                $regex .= '(?P<' . $match[1] . '>[^/]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        $this->routes[$method][] = [
            'pattern' => $trimmed,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 'POST' : 'GET';
        $route = $_GET['__route'] ?? null;

        if (!is_string($route) || $route === '') {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $route = trim($path, '/');
            $route = preg_replace('/\.php$/i', '', $route);
            if ($route === '' || $route === 'store') {
                $route = 'home';
            }
        }
        $route = trim((string) $route, '/');

        foreach ($this->routes[$method] as $candidate) {
            if (preg_match($candidate['regex'], $route, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn ($key): bool => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );
                ($candidate['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        render('errors/404', ['pageTitle' => 'Page not found'], 'layout');
    }
}
