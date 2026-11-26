<?php
declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends Repository
{
    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, full_name, login, phone, role, active, created_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findActiveByLogin(string $login): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE login = :login AND active = 1 LIMIT 1');
        $statement->execute([':login' => trim($login)]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findActiveClientForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT id FROM users WHERE id = :id AND role = 'client' AND active = 1 FOR UPDATE"
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function createClient(string $fullName, string $login, string $passwordHash, ?string $phone = null): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO users (full_name, login, password_hash, phone, role, active)
             VALUES (:full_name, :login, :password_hash, :phone, 'client', 1)"
        );
        $statement->execute([
            ':full_name' => trim($fullName),
            ':login' => trim($login),
            ':password_hash' => $passwordHash,
            ':phone' => $phone !== null && trim($phone) !== '' ? trim($phone) : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function listByRole(string $role, bool $activeOnly = true): array
    {
        if (!in_array($role, ['client', 'trainer', 'admin'], true)) {
            return [];
        }

        $sql = 'SELECT id, full_name, login, phone, role, active, created_at FROM users WHERE role = :role';
        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY full_name';

        $statement = $this->db->prepare($sql);
        $statement->execute([':role' => $role]);
        return $statement->fetchAll();
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare('UPDATE users SET active = :active WHERE id = :id');
        $statement->execute([':active' => $active ? 1 : 0, ':id' => $id]);
    }
}
