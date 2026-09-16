<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Models\User;
use RuntimeException;

final class UserManagementService
{
    private const ALLOWED_ROLES = ['employee', 'technician', 'administrator'];

    public function __construct(
        private readonly User $users,
        private readonly UserPolicy $policy,
        private readonly Database $database,
    ) {
    }

    /** @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int} */
    public function list(array $input, array $actor): array
    {
        $this->ensureCanView($actor);

        $search = trim((string) ($input['search'] ?? ''));
        $role = trim((string) ($input['role'] ?? ''));
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $role = '';
        }

        $isActiveRaw = $input['status'] ?? $input['is_active'] ?? '';
        $isActive = match ((string) $isActiveRaw) {
            'active', '1' => 1,
            'inactive', '0' => 0,
            default => null,
        };

        $filters = [
            'search' => $search,
            'role' => $role,
            'is_active' => $isActive,
        ];

        $perPage = 10;
        $page = max(1, (int) ($input['page'] ?? 1));
        $result = $this->users->paginate($filters, $page, $perPage);
        $pages = max(1, (int) ceil($result['total'] / $perPage));
        $page = min($page, $pages);

        if ($page !== max(1, (int) ($input['page'] ?? 1))) {
            $result = $this->users->paginate($filters, $page, $perPage);
        }

        return $result + ['pages' => $pages, 'page' => $page];
    }

    /** @return array<string, mixed> */
    public function findUser(int $id, array $actor): array
    {
        $this->ensureCanView($actor);

        $user = $this->users->findById($id);

        if ($user === null) {
            throw new HttpException(404, 'User not found');
        }

        return $user;
    }

    /** @return array<string, string> */
    public function validateCreate(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $passwordConfirmation = (string) ($input['password_confirmation'] ?? '');
        $role = trim((string) ($input['role'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Name must be between 2 and 100 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->users->isEmailTaken($email)) {
            $errors['email'] = 'An account with that email address already exists.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors['role'] = 'Select a valid role.';
        }

        return $errors;
    }

    public function create(array $input, array $actor): array
    {
        $this->ensureCanCreate($actor);

        $errors = $this->validateCreate($input);
        if ($errors !== []) {
            throw new RuntimeException('Validation failed.');
        }

        $name = trim((string) $input['name']);
        $email = strtolower(trim((string) $input['email']));
        $passwordHash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        $role = (string) $input['role'];

        $userId = $this->users->create($name, $email, $passwordHash, $role);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new RuntimeException('The created user could not be loaded.');
        }

        return $user;
    }

    /** @return array<string, string> */
    public function validateEdit(array $input, int $userId): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $role = trim((string) ($input['role'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Name must be between 2 and 100 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->users->isEmailTaken($email, $userId)) {
            $errors['email'] = 'An account with that email address already exists.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors['role'] = 'Select a valid role.';
        }

        return $errors;
    }

    public function update(int $userId, array $input, array $actor): void
    {
        $this->ensureCanEdit($actor);

        $targetUser = $this->findUser($userId, $actor);
        $errors = $this->validateEdit($input, $userId);

        if ($errors !== []) {
            throw new RuntimeException('Validation failed.');
        }

        $newRole = (string) $input['role'];

        // Role change safety checks for administrators
        if ($targetUser['role'] === 'administrator' && $newRole !== 'administrator') {
            if ((int) $actor['id'] === $userId) {
                throw new RuntimeException('You cannot remove your own administrative access.');
            }

            $this->mutateWithActiveAdministratorLock(
                $userId,
                'You cannot demote the last active administrator.',
                fn () => $this->users->update(
                    $userId,
                    trim((string) $input['name']),
                    strtolower(trim((string) $input['email'])),
                    $newRole
                )
            );

            return;
        }

        $this->users->update(
            $userId,
            trim((string) $input['name']),
            strtolower(trim((string) $input['email'])),
            $newRole
        );
    }

    public function activate(int $userId, array $actor): void
    {
        if (!$this->policy->canActivate($actor)) {
            throw new RuntimeException('You do not have permission to activate users.');
        }
        $this->findUser($userId, $actor); // Ensure user exists

        $this->users->setActive($userId, 1);
    }

    public function deactivate(int $userId, array $actor): void
    {
        if (!$this->policy->canDeactivate($actor)) {
            throw new RuntimeException('You do not have permission to deactivate users.');
        }
        $targetUser = $this->findUser($userId, $actor);

        if ((int) $actor['id'] === $userId) {
            throw new RuntimeException('You cannot deactivate your own account.');
        }

        if ($targetUser['role'] === 'administrator' && (bool) $targetUser['is_active']) {
            $this->mutateWithActiveAdministratorLock(
                $userId,
                'You cannot deactivate the last active administrator.',
                fn () => $this->users->setActive($userId, 0)
            );

            return;
        }

        $this->users->setActive($userId, 0);
    }

    /** @return array<string, string> */
    public function validatePasswordReset(array $input): array
    {
        $errors = [];
        $password = (string) ($input['password'] ?? '');
        $passwordConfirmation = (string) ($input['password_confirmation'] ?? '');

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    public function resetPassword(int $userId, array $input, array $actor): void
    {
        if (!$this->policy->canResetPassword($actor)) {
            throw new RuntimeException('You do not have permission to reset passwords.');
        }
        $this->findUser($userId, $actor);

        $errors = $this->validatePasswordReset($input);
        if ($errors !== []) {
            throw new RuntimeException('Validation failed.');
        }

        $passwordHash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        $this->users->updatePassword($userId, $passwordHash);
    }

    private function ensureCanView(array $actor): void
    {
        if (!$this->policy->canView($actor)) {
            throw new RuntimeException('You do not have permission to manage users.');
        }
    }

    private function ensureCanCreate(array $actor): void
    {
        if (!$this->policy->canCreate($actor)) {
            throw new RuntimeException('You do not have permission to create users.');
        }
    }

    private function ensureCanEdit(array $actor): void
    {
        if (!$this->policy->canEdit($actor)) {
            throw new RuntimeException('You do not have permission to update users.');
        }
    }

    private function mutateWithActiveAdministratorLock(int $userId, string $failureMessage, callable $mutation): void
    {
        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $activeAdministratorIds = $this->users->lockActiveAdministratorIds();

            if (in_array($userId, $activeAdministratorIds, true) && count($activeAdministratorIds) <= 1) {
                throw new RuntimeException($failureMessage);
            }

            $mutation();
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }
}
