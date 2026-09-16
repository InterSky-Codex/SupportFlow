<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;
use App\Models\Notification;
use App\Services\NotificationService;
use DateTimeZone;
use RuntimeException;

final class Application
{
    /** @var array<string, mixed> */
    private array $config;
    private readonly Database $database;
    private readonly Session $session;
    private readonly Csrf $csrf;

    public function __construct(
        private readonly string $basePath,
        private readonly Router $router,
        private readonly View $view,
    ) {
        $this->config = [
            'app' => require $this->basePath . '/config/app.php',
            'database' => require $this->basePath . '/config/database.php',
        ];
        $this->database = new Database($this->config['database']);
        $this->applySettingsOverrides();
        $this->session = new Session();
        $this->csrf = new Csrf($this->session);
        $this->shareNotifications();
    }

    public function run(Request $request): void
    {
        try {
            $this->router->dispatch($request, $this);
        } catch (HttpException $exception) {
            http_response_code($exception->statusCode());
            $this->view->render('errors/error', [
                'title' => $exception->getMessage(),
                'statusCode' => $exception->statusCode(),
            ], 'layouts/error');
        } catch (\Throwable $exception) {
            error_log((string) $exception);
            http_response_code(500);
            $this->view->render('errors/error', [
                'title' => 'Something went wrong',
                'statusCode' => 500,
            ], 'layouts/error');
        }
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function view(): View
    {
        return $this->view;
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function session(): Session
    {
        return $this->session;
    }

    public function csrf(): Csrf
    {
        return $this->csrf;
    }

    public function config(string $key): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                throw new RuntimeException("Configuration key [{$key}] was not found.");
            }

            $value = $value[$segment];
        }

        return $value;
    }

    private function applySettingsOverrides(): void
    {
        $settings = (new Setting($this->database))->all();
        $workspaceName = trim((string) ($settings['workspace_name'] ?? ''));
        $timezone = (string) ($settings['timezone'] ?? '');

        if ($workspaceName !== '' && mb_strlen($workspaceName) <= 100) {
            $this->config['app']['name'] = $workspaceName;
        }

        if (in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $this->config['app']['timezone'] = $timezone;
            date_default_timezone_set($timezone);
        }

        $this->view->share('appName', (string) $this->config['app']['name']);
    }

    private function shareNotifications(): void
    {
        $userId = filter_var($this->session->get('authenticated_user_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false) {
            $this->view->share('notificationUnreadCount', 0);
            $this->view->share('notificationRecent', []);
            return;
        }

        $service = new NotificationService(new Notification($this->database));
        $this->view->share('notificationUnreadCount', $service->unreadCount((int) $userId));
        $this->view->share('notificationRecent', $service->recent((int) $userId, 5));
    }
}
