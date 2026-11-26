<?php
declare(strict_types=1);

namespace App\Repositories;

final class TrainerRepository extends Repository
{
    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT t.id, t.user_id, t.specialty, u.full_name, u.phone
             FROM trainers t JOIN users u ON u.id = t.user_id
             WHERE t.user_id = :user_id AND u.active = 1 LIMIT 1'
        );
        $statement->execute([':user_id' => $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function activeList(): array
    {
        return $this->db->query(
            'SELECT t.id, t.user_id, t.specialty, u.full_name, u.phone
             FROM trainers t JOIN users u ON u.id = t.user_id
             WHERE u.active = 1 ORDER BY u.full_name'
        )->fetchAll();
    }

    public function create(int $userId, string $specialty): int
    {
        $statement = $this->db->prepare('INSERT INTO trainers (user_id, specialty) VALUES (:user_id, :specialty)');
        $statement->execute([':user_id' => $userId, ':specialty' => trim($specialty)]);
        return (int) $this->db->lastInsertId();
    }
}
