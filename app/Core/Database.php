<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private ?PDO $connection = null;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset'],
        );

        $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->connection;
    }
}
