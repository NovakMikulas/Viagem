<?php

declare(strict_types=1);

namespace App\Http;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => 'GET',
            'regex' => $this->patternToRegex($pattern),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->getMethod()) {
                continue;
            }

            if (preg_match($route['regex'], $request->getPath(), $matches)) {
                $request->setRouteParams($this->namedParameters($matches));
                $handler = $route['handler'];
                $handler($request);
                return;
            }
        }

        Response::error('Neznámá adresa ' . $request->getMethod() . ' ' . $request->getPath(), 404);
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = str_replace('.', '\.', $pattern);
        $regex = preg_replace('#\{(\w+)\}#', '(?<$1>[^/.]+)', $regex);

        return '#^' . $regex . '$#';
    }

    private function namedParameters(array $matches): array
    {
        $parameters = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
