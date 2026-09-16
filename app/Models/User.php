<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, name, email, password, role, is_active, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, name, email, role, is_active, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $parameters = [];

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(name LIKE :search_name OR email LIKE :search_email)';
            $searchTerm = '%' . $filters['search'] . '%';
            $parameters['search_name'] = $searchTerm;
            $parameters['search_email'] = $searchTerm;
        }

        if (($filters['role'] ?? '') !== '') {
            $conditions[] = 'role = :role';
            $parameters['role'] = $filters['role'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $conditions[] = 'is_active = :is_active';
            $parameters['is_active'] = $filters['is_active'];
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $count = $this->connection()->prepare('SELECT COUNT(*) FROM users' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $statement = $this->connection()->prepare(
            'SELECT id, name, email, role, is_active, created_at, updated_at FROM users' . $where . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total];
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'employee'): int
    {
        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO users (name, email, password, role, is_active)
             VALUES (:name, :email, :password, :role, :is_active)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
            'is_active' => 1,
        ]);

        return (int) $connection->lastInsertId();
    }

    public function update(int $id, string $name, string $email, string $role): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);
    }

    public function setActive(int $id, int $isActive): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE users SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $isActive,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE users SET password = :password WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'password' => $passwordHash]);
    }

    /** @return list<int> */
    public function lockActiveAdministratorIds(): array
    {
        $statement = $this->connection()->query(
            "SELECT id FROM users WHERE role = 'administrator' AND is_active = 1 FOR UPDATE"
        );

        return array_map(
            static fn (mixed $id): int => (int) $id,
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function isEmailTaken(string $email, ?int $excludeUserId = null): bool
    {
        $query = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($excludeUserId !== null) {
            $query .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeUserId;
        }

        $statement = $this->connection()->prepare($query);
        $statement->execute($params);

        return ((int) $statement->fetchColumn()) > 0;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
