<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Setting
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $statement = $this->connection()->query(
            'SELECT setting_key, setting_value FROM application_settings'
        );

        $settings = [];
        foreach ($statement->fetchAll() as $setting) {
            $settings[(string) $setting['setting_key']] = (string) $setting['setting_value'];
        }

        return $settings;
    }

    public function save(string $key, string $value): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO application_settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
