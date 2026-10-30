<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    private string $method;
    private string $path;
    private array $routeParams = [];

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public static function fromGlobals(): Request
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        return new Request($_SERVER['REQUEST_METHOD'], $path);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }

    public function getRouteParam(string $name): string
    {
        return $this->routeParams[$name];
    }
}
