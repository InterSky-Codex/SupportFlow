<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
    /** @var array<string, array<string, array{handler: callable|array{class-string, string}, middleware: list<class-string>}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(Request $request, Application $application): void
    {
        $route = $this->match($request);

        if ($route === null) {
            throw new HttpException(404, 'Page not found');
        }

        foreach ($route['middleware'] as $middleware) {
            (new $middleware())->handle($request, $application);
        }

        $handler = $route['handler'];

        if ($handler instanceof Closure) {
            $handler($request, $application);
            return;
        }

        [$class, $method] = $handler;
        $controller = new $class($application);
        $controller->{$method}($request);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[$method][rtrim($path, '/') ?: '/'] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /** @return array{handler: callable|array{class-string, string}, middleware: list<class-string>}|null */
    private function match(Request $request): ?array
    {
        $routes = $this->routes[$request->method()] ?? [];
        $exact = $routes[$request->path()] ?? null;

        if ($exact !== null) {
            return $exact;
        }

        foreach ($routes as $pattern => $route) {
            if (!str_contains($pattern, '{')) {
                continue;
            }

            $parameterNames = [];
            $expression = preg_replace_callback(
                '/\{([a-zA-Z][a-zA-Z0-9_]*)\}/',
                static function (array $matches) use (&$parameterNames): string {
                    $parameterNames[] = $matches[1];
                    return '([^/]+)';
                },
                $pattern
            );

            if (preg_match('#^' . $expression . '$#', $request->path(), $matches) !== 1) {
                continue;
            }

            $parameters = [];
            foreach ($parameterNames as $index => $name) {
                $parameters[$name] = $matches[$index + 1];
            }
            $request->setRouteParameters($parameters);

            return $route;
        }

        return null;
    }
}
