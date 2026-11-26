<?php
declare(strict_types=1);

namespace App\Repositories;

final class VisitRepository extends Repository
{
    public function create(int $userId, int $membershipId, ?int $classId, int $checkedBy): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO visits (user_id, membership_id, class_id, checked_by)
             VALUES (:user_id, :membership_id, :class_id, :checked_by)'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':membership_id' => $membershipId,
            ':class_id' => $classId ?: null,
            ':checked_by' => $checkedBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        $statement = $this->db->prepare(
            'SELECT v.id, v.visited_at, c.title AS class_title,
                    mt.name AS membership_name, checker.full_name AS checked_by_name
             FROM visits v
             JOIN memberships m ON m.id = v.membership_id
             JOIN membership_types mt ON mt.id = m.type_id
             LEFT JOIN classes c ON c.id = v.class_id
             JOIN users checker ON checker.id = v.checked_by
             WHERE v.user_id = :user_id
             ORDER BY v.visited_at DESC, v.id DESC LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', max(1, min($limit, 100)), \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function recent(int $limit = 10): array
    {
        $statement = $this->db->prepare(
            'SELECT v.id, v.visited_at, u.full_name, u.phone,
                    c.title AS class_title, mt.name AS membership_name,
                    checker.full_name AS checked_by_name
             FROM visits v
             JOIN users u ON u.id = v.user_id
             JOIN memberships m ON m.id = v.membership_id
             JOIN membership_types mt ON mt.id = m.type_id
             LEFT JOIN classes c ON c.id = v.class_id
             JOIN users checker ON checker.id = v.checked_by
             ORDER BY v.visited_at DESC, v.id DESC LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min($limit, 100)), \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
