<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
    private readonly string $path,
        /** @var array<string, string> */
        private array $routeParameters = [],
    ) {
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = $scriptName === '/' ? '' : rtrim($scriptName, '/');

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        return new self($_SERVER['REQUEST_METHOD'] ?? 'GET', '/' . ltrim($uri, '/'));
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return rtrim($this->path, '/') ?: '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $_POST;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $_GET;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParameters[$key] ?? $default;
    }

    /** @param array<string, string> $parameters */
    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
    }

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $_FILES;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }
}

