<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private readonly string $viewsPath)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        $templatePath = $this->resolve($template);
        $layoutPath = $this->resolve($layout);
        $data = $data + $this->shared;
        $appName = (string) ($data['appName'] ?? env('APP_NAME', 'SupportFlow'));

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        require $layoutPath;
    }

    private function resolve(string $template): string
    {
        $path = $this->viewsPath . '/' . trim($template, '/') . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View [{$template}] was not found.");
        }

        return $path;
    }
}
