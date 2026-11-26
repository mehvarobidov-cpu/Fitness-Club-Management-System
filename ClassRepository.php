<?php
declare(strict_types=1);

namespace App\Repositories;

final class ClassRepository extends Repository
{
    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT c.*, t.id AS trainer_id, u.full_name AS trainer_name
             FROM classes c
             JOIN trainers t ON t.id = c.trainer_id
             JOIN users u ON u.id = t.user_id
             WHERE c.id = :id LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM classes WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function forRange(string $from, string $to, ?int $trainerId = null): array
    {
        $sql = "SELECT c.*, u.full_name AS trainer_name,
                       (SELECT COUNT(*) FROM class_bookings b
                        WHERE b.class_id = c.id AND b.status = 'booked') AS booked_count,
                       (SELECT COUNT(*) FROM class_bookings b
                        WHERE b.class_id = c.id AND b.status = 'waitlist') AS waitlist_count
                FROM classes c
                JOIN trainers t ON t.id = c.trainer_id
                JOIN users u ON u.id = t.user_id
                WHERE c.cancelled = 0 AND c.starts_at >= :from_date AND c.starts_at < :to_date";
        $params = [':from_date' => $from, ':to_date' => $to];

        if ($trainerId !== null) {
            $sql .= ' AND c.trainer_id = :trainer_id';
            $params[':trainer_id'] = $trainerId;
        }

        $sql .= ' ORDER BY c.starts_at, c.id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function upcoming(int $limit = 8): array
    {
        $statement = $this->db->prepare(
            'SELECT c.*, u.full_name AS trainer_name
             FROM classes c
             JOIN trainers t ON t.id = c.trainer_id
             JOIN users u ON u.id = t.user_id
             WHERE c.cancelled = 0 AND c.starts_at >= NOW()
             ORDER BY c.starts_at LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min($limit, 100)), \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO classes (title, trainer_id, starts_at, duration_min, capacity, room)
             VALUES (:title, :trainer_id, :starts_at, :duration_min, :capacity, :room)'
        );
        $statement->execute([
            ':title' => trim($data['title']),
            ':trainer_id' => (int) $data['trainer_id'],
            ':starts_at' => $data['starts_at'],
            ':duration_min' => (int) $data['duration_min'],
            ':capacity' => (int) $data['capacity'],
            ':room' => trim((string) ($data['room'] ?? 'Толори 1')),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function cancel(int $id): void
    {
        $statement = $this->db->prepare('UPDATE classes SET cancelled = 1 WHERE id = :id');
        $statement->execute([':id' => $id]);
    }
}
