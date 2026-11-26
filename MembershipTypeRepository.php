<?php
declare(strict_types=1);

namespace App\Repositories;

final class MembershipTypeRepository extends Repository
{
    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, name, visits, duration_days, price, active FROM membership_types';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY price, duration_days';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM membership_types WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findActiveForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM membership_types WHERE id = :id AND active = 1 FOR UPDATE');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(string $name, ?int $visits, int $durationDays, float $price): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO membership_types (name, visits, duration_days, price)
             VALUES (:name, :visits, :duration_days, :price)'
        );
        $statement->execute([
            ':name' => trim($name),
            ':visits' => $visits,
            ':duration_days' => $durationDays,
            ':price' => $price,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare('UPDATE membership_types SET active = :active WHERE id = :id');
        $statement->execute([':active' => $active ? 1 : 0, ':id' => $id]);
    }
}
