<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->redirectIfAuthenticated();
        $this->renderGuest('auth/login', 'Sign in');
    }

    public function login(Request $request): never
    {
        if (!$this->hasValidCsrfToken($request)) {
            $this->redirectWithError('/login', 'Your session has expired. Please try again.');
        }

        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->redirectWithError('/login', 'Enter a valid email address and password.');
        }

        $user = $this->auth()->attempt($email, $password);

        if ($user === null) {
            $this->redirectWithError('/login', 'The provided credentials are invalid.');
        }

        $this->auditService()->log(
            action: AuditService::ACTION_AUTH_LOGIN,
            actorId: (int) $user['id'],
            description: sprintf('%s signed in', $user['name'] ?? $email),
            entityType: 'user',
            entityId: (int) $user['id'],
            ticketId: null,
            metadata: [
                'user_id' => (int) $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
            ip: $_SERVER['REMOTE_ADDR'] ?? null,
            userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        $this->app->session()->flash('success', 'Welcome back to SupportFlow.');
        Response::redirect('/dashboard');
    }

    public function showRegister(Request $request): void
    {
        $this->redirectIfAuthenticated();
        $this->renderGuest('auth/register', 'Create your account');
    }

    public function register(Request $request): never
    {
        if (!$this->hasValidCsrfToken($request)) {
            $this->redirectWithError('/register', 'Your session has expired. Please try again.');
        }

        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $passwordConfirmation = (string) $request->input('password_confirmation');

        $error = $this->validateRegistration($name, $email, $password, $passwordConfirmation);

        if ($error !== null) {
            $this->redirectWithError('/register', $error);
        }

        if ($this->users()->findByEmail($email) !== null) {
            $this->redirectWithError('/register', 'An account with that email address already exists.');
        }

        $this->auth()->register($name, $email, $password);

        // Retrieve the newly created user to obtain their ID for auditing.
        // actor_id is null: registration is performed by an anonymous visitor
        // who is not authenticated during the registration flow.
        $newUser = $this->users()->findByEmail($email);
        if ($newUser !== null) {
            $this->auditService()->log(
                action: AuditService::ACTION_AUTH_REGISTER,
                actorId: null,
                description: sprintf('New user %s registered', $name),
                entityType: 'user',
                entityId: (int) $newUser['id'],
                ticketId: null,
                metadata: [
                    'user_id' => (int) $newUser['id'],
                    'name' => $newUser['name'],
                    'email' => $newUser['email'],
                    'role' => $newUser['role'],
                ],
                ip: $_SERVER['REMOTE_ADDR'] ?? null,
                userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        }

        $this->app->session()->flash('success', 'Your account has been created. Please sign in.');
        Response::redirect('/login');
    }

    public function logout(Request $request): never
    {
        if ($this->hasValidCsrfToken($request)) {
            // Capture actor identity BEFORE session destruction.
            $actor = $this->auth()->user();

            $this->auth()->logout();

            if ($actor !== null) {
                $this->auditService()->log(
                    action: AuditService::ACTION_AUTH_LOGOUT,
                    actorId: (int) $actor['id'],
                    description: sprintf('%s signed out', $actor['name'] ?? ''),
                    entityType: 'user',
                    entityId: (int) $actor['id'],
                    ticketId: null,
                    metadata: [
                        'user_id' => (int) $actor['id'],
                        'email' => $actor['email'],
                    ],
                    ip: $_SERVER['REMOTE_ADDR'] ?? null,
                    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null
                );
            }

            $this->app->session()->flash('success', 'You have been signed out safely.');
        }

        Response::redirect('/login');
    }

    private function auth(): AuthService
    {
        return new AuthService($this->users(), $this->app->session());
    }

    private function users(): User
    {
        return new User($this->app->database());
    }

    private function auditService(): AuditService
    {
        return new AuditService(new AuditLog($this->app->database()));
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        try {
            $this->app->csrf()->validate((string) $request->input('_token'));
            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    private function redirectIfAuthenticated(): void
    {
        if ($this->auth()->user() !== null) {
            Response::redirect('/dashboard');
        }
    }

    private function renderGuest(string $view, string $title): void
    {
        $this->app->view()->render($view, [
            'pageTitle' => $title,
            'csrfToken' => $this->app->csrf()->token(),
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ], 'layouts/guest');
    }

    private function validateRegistration(string $name, string $email, string $password, string $confirmation): ?string
    {
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return 'Your name must be between 2 and 100 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return 'Enter a valid email address.';
        }

        if (strlen($password) < 8) {
            return 'Your password must be at least 8 characters.';
        }

        if ($password !== $confirmation) {
            return 'Password confirmation does not match.';
        }

        return null;
    }

    private function redirectWithError(string $path, string $message): never
    {
        $this->app->session()->flash('error', $message);
        Response::redirect($path);
    }
}
