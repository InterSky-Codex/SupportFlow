<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AuthService;

final class Authenticate
{
    public function handle(Request $request, Application $app): void
    {
        $auth = new AuthService(new User($app->database()), $app->session());

        if ($auth->user() === null) {
            $app->session()->flash('error', 'Please sign in to access the workspace.');
            Response::redirect('/login');
        }
    }
}
