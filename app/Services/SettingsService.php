<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use RuntimeException;

final class SettingsService
{
    private const KEYS = ['workspace_name', 'timezone'];

    private const TIMEZONES = [
        'UTC',
        'Asia/Jakarta',
        'Asia/Singapore',
        'Europe/London',
        'America/New_York',
    ];

    public function __construct(
        private readonly Setting $settings,
        private readonly UserPolicy $policy,
        private readonly Database $database,
    ) {
    }

    /** @return array{workspace_name: string, timezone: string} */
    public function current(array $actor, string $defaultWorkspaceName, string $defaultTimezone): array
    {
        $this->ensureCanManage($actor);

        $stored = $this->settings->all();

        return [
            'workspace_name' => $stored['workspace_name'] ?? $defaultWorkspaceName,
            'timezone' => $stored['timezone'] ?? $defaultTimezone,
        ];
    }

    /** @return list<string> */
    public function timezoneOptions(): array
    {
        return self::TIMEZONES;
    }

    /** @return array<string, string> */
    public function validate(array $input): array
    {
        $errors = [];

        foreach (array_keys($input) as $key) {
            if ($key !== '_token' && !in_array($key, self::KEYS, true)) {
                $errors['form'] = 'The submitted settings are invalid.';
                break;
            }
        }

        $workspaceName = $input['workspace_name'] ?? null;
        if (!is_string($workspaceName)) {
            $errors['workspace_name'] = 'Workspace name is required.';
        } else {
            $workspaceName = trim($workspaceName);
            if (mb_strlen($workspaceName) < 2 || mb_strlen($workspaceName) > 100) {
                $errors['workspace_name'] = 'Workspace name must be between 2 and 100 characters.';
            }
        }

        $timezone = $input['timezone'] ?? null;
        if (!is_string($timezone) || !in_array($timezone, self::TIMEZONES, true)) {
            $errors['timezone'] = 'Select a valid timezone.';
        }

        return $errors;
    }

    public function update(array $input, array $actor): void
    {
        $this->ensureCanManage($actor);

        $errors = $this->validate($input);
        if ($errors !== []) {
            throw new RuntimeException('Validation failed.');
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $this->settings->save('workspace_name', trim((string) $input['workspace_name']));
            $this->settings->save('timezone', (string) $input['timezone']);
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function ensureCanManage(array $actor): void
    {
        if (!$this->policy->canManage($actor)) {
            throw new RuntimeException('You do not have permission to manage settings.');
        }
    }
}
