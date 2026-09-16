<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Services\SettingsService;
use App\Services\UserPolicy;
use RuntimeException;

final class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        $this->ensureAdmin();

        $this->renderSettings(
            $this->service()->current(
                $this->currentUser(),
                (string) $this->app->config('app.name'),
                (string) $this->app->config('app.timezone')
            ),
            []
        );
    }

    public function update(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $input = $request->post();
        $service = $this->service();
        $errors = $service->validate($input);

        if ($errors !== []) {
            $this->renderSettings($input, $errors);
            exit;
        }

        try {
            $service->update($input, $this->currentUser());
            $this->app->session()->flash('success', 'Settings updated successfully.');
            Response::redirect('/settings');
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $this->renderSettings($input, []);
            exit;
        }
    }

    private function service(): SettingsService
    {
        return new SettingsService(
            new Setting($this->app->database()),
            new UserPolicy(),
            $this->app->database()
        );
    }

    private function ensureAdmin(): void
    {
        if (!(new UserPolicy())->canManage($this->currentUser())) {
            throw new HttpException(403, 'You do not have permission to access settings.');
        }
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function renderSettings(array $values, array $errors): void
    {
        $this->app->view()->render('settings/index', [
            'pageTitle' => 'Settings',
            'activeNavigation' => 'settings',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
            'values' => $values,
            'errors' => $errors,
            'timezones' => $this->service()->timezoneOptions(),
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    private function ensureCsrf(Request $request): void
    {
        try {
            $this->app->csrf()->validate((string) $request->input('_token'));
        } catch (RuntimeException) {
            throw new HttpException(419, 'Your session has expired. Please try again.');
        }
    }
}
