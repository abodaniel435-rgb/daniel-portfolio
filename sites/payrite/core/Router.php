<?php
declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/') . '/?$#';
        $this->routes[] = ['method' => $method, 'pattern' => $regex, 'handler' => $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                // Route placeholders are also exposed through $_GET so controller
                // functions can read them consistently with query parameters.
                $_GET = array_merge($_GET, $params);
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                    $_POST = array_merge($_POST, $params);
                }
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        view('errors/404', ['title' => 'Page not found']);
    }
}
