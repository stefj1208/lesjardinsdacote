<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    public function any(string $pattern, callable $handler): void { $this->add('ANY', $pattern, $handler); }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/') . '/?$#u';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        foreach ($this->routes as [$m, $regex, $handler]) {
            if (!preg_match($regex, $path, $mm)) continue;
            if ($m !== 'ANY' && $m !== $method) continue;
            $params = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
            if ($method === 'POST' && !str_starts_with($path, '/stripe/webhook') && !str_starts_with($path, '/api/agents/')) verify_csrf();
            $handler(...array_values($params));
            return;
        }
        abort(404);
    }
}
