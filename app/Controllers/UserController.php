<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\UserManagementService;
use App\Services\UserPolicy;
use RuntimeException;

final class UserController extends Controller
{
    public function index(Request $request): void
    {
        $this->ensureAdmin();

        $service = $this->service();
        $listing = $service->list($request->query(), $this->currentUser());

        $this->app->view()->render('users/index', $this->layoutData() + [
            'pageTitle' => 'Users',
            'listing' => $listing,
            'filters' => $request->query(),
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    public function show(Request $request): void
    {
        $this->ensureAdmin();

        $userId = $this->findUserId($request);
        $user = $this->service()->findUser($userId, $this->currentUser());

        $this->app->view()->render('users/show', $this->layoutData() + [
            'pageTitle' => $user['name'],
            'userItem' => $user,
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    public function create(Request $request): void
    {
        $this->ensureAdmin();

        $this->renderForm('Create user', '/users', [], [], false);
    }

    public function store(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $input = $request->post();
        $errors = $this->service()->validateCreate($input);

        if ($errors !== []) {
            $this->renderForm('Create user', '/users', $input, $errors, false);
            exit;
        }

        try {
            $user = $this->service()->create($input, $this->currentUser());
            $this->app->session()->flash('success', "User {$user['name']} created successfully.");
            Response::redirect('/users/' . $user['id']);
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $this->renderForm('Create user', '/users', $input, [], false);
            exit;
        }
    }

    public function edit(Request $request): void
    {
        $this->ensureAdmin();

        $userId = $this->findUserId($request);
        $user = $this->service()->findUser($userId, $this->currentUser());

        $this->renderForm('Edit user', '/users/' . $userId, $user, [], true);
    }

    public function update(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $userId = $this->findUserId($request);
        $service = $this->service();
        $actor = $this->currentUser();
        $service->findUser($userId, $actor);
        $input = $request->post();
        $errors = $service->validateEdit($input, $userId);

        if ($errors !== []) {
            $input['id'] = $userId;
            $this->renderForm('Edit user', '/users/' . $userId, $input, $errors, true);
            exit;
        }

        try {
            $service->update($userId, $input, $actor);
            $this->app->session()->flash('success', 'User updated successfully.');
            Response::redirect('/users/' . $userId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $input['id'] = $userId;
            $this->renderForm('Edit user', '/users/' . $userId, $input, [], true);
            exit;
        }
    }

    public function activate(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $userId = $this->findUserId($request);

        try {
            $this->service()->activate($userId, $this->currentUser());
            $this->app->session()->flash('success', 'User activated successfully.');
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/users/' . $userId);
    }

    public function deactivate(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $userId = $this->findUserId($request);

        try {
            $this->service()->deactivate($userId, $this->currentUser());
            $this->app->session()->flash('success', 'User deactivated successfully.');
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/users/' . $userId);
    }

    public function showPasswordReset(Request $request): void
    {
        $this->ensureAdmin();

        $userId = $this->findUserId($request);
        $user = $this->service()->findUser($userId, $this->currentUser());

        $this->app->view()->render('users/password', $this->layoutData() + [
            'pageTitle' => 'Reset password · ' . $user['name'],
            'userItem' => $user,
            'errors' => [],
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    public function resetPassword(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $userId = $this->findUserId($request);
        $user = $this->service()->findUser($userId, $this->currentUser());
        $input = $request->post();

        $errors = $this->service()->validatePasswordReset($input);
        if ($errors !== []) {
            $this->app->view()->render('users/password', $this->layoutData() + [
                'pageTitle' => 'Reset password · ' . $user['name'],
                'userItem' => $user,
                'errors' => $errors,
                'errorMessage' => null,
            ]);
            exit;
        }

        try {
            $this->service()->resetPassword($userId, $input, $this->currentUser());
            $this->app->session()->flash('success', "Password for {$user['name']} has been reset successfully.");
            Response::redirect('/users/' . $userId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            Response::redirect('/users/' . $userId . '/password');
        }
    }

    private function service(): UserManagementService
    {
        return new UserManagementService(
            new User($this->app->database()),
            new UserPolicy(),
            $this->app->database()
        );
    }

    private function ensureAdmin(): void
    {
        $currentUser = $this->currentUser();
        if (!(new UserPolicy())->canManage($currentUser)) {
            throw new HttpException(403, 'You do not have permission to access user management.');
        }
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'activeNavigation' => 'users',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
        ];
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function renderForm(string $title, string $action, array $values, array $errors, bool $editing): void
    {
        $this->app->view()->render('users/form', $this->layoutData() + [
            'pageTitle' => $title,
            'action' => $action,
            'values' => $values,
            'errors' => $errors,
            'editing' => $editing,
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    private function findUserId(Request $request): int
    {
        $userId = filter_var($request->route('id', $request->input('id')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userId === false || $userId === null) {
            throw new HttpException(404, 'User not found');
        }

        return (int) $userId;
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
