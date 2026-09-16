<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(private readonly int $status, string $message)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->status;
    }
}
