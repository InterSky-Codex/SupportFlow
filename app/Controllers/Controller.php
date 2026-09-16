<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Models\User;
use App\Services\AuthService;

abstract class Controller
{
    public function __construct(protected readonly Application $app)
    {
    }

    /** @return array<string, mixed> */
    protected function currentUser(): array
    {
        $auth = new AuthService(new User($this->app->database()), $this->app->session());
        $user = $auth->user();

        if ($user === null) {
            throw new \RuntimeException('Authenticated user was not found.');
        }

        return $user;
    }
}
