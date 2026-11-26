<?php
declare(strict_types=1);

namespace App\Repositories;

final class MembershipRepository extends Repository
{
    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT m.*, mt.name AS membership_name
             FROM memberships m JOIN membership_types mt ON mt.id = m.type_id
             WHERE m.id = :id LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM memberships WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT m.*, mt.name AS membership_name
             FROM memberships m JOIN membership_types mt ON mt.id = m.type_id
             WHERE m.user_id = :user_id
             ORDER BY (m.status = \'active\') DESC, m.end_date DESC, m.id DESC'
        );
        $statement->execute([':user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function validForUser(int $userId, string $date): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM memberships
             WHERE user_id = :user_id AND status = 'active'
               AND start_date <= :start_date AND end_date >= :end_date
               AND (visits_left IS NULL OR visits_left > 0)
             ORDER BY end_date, id LIMIT 1"
        );
        $statement->execute([':user_id' => $userId, ':start_date' => $date, ':end_date' => $date]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function validForUserForUpdate(int $userId, string $date): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM memberships
             WHERE user_id = :user_id AND status = 'active'
               AND start_date <= :start_date AND end_date >= :end_date
               AND (visits_left IS NULL OR visits_left > 0)
             ORDER BY end_date, id LIMIT 1 FOR UPDATE"
        );
        $statement->execute([':user_id' => $userId, ':start_date' => $date, ':end_date' => $date]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function frozenForUpdate(): array
    {
        return $this->db->query("SELECT * FROM memberships WHERE status = 'frozen' FOR UPDATE")->fetchAll();
    }

    public function freeze(int $id, string $date): void
    {
        $statement = $this->db->prepare(
            "UPDATE memberships SET status = 'frozen', frozen_at = :frozen_at WHERE id = :id"
        );
        $statement->execute([':frozen_at' => $date, ':id' => $id]);
    }

    public function unfreeze(int $id, int $days): void
    {
        $statement = $this->db->prepare(
            "UPDATE memberships
             SET status = 'active', frozen_at = NULL,
                 freeze_days_used = freeze_days_used + :used_days,
                 end_date = DATE_ADD(end_date, INTERVAL :extension_days DAY)
             WHERE id = :id"
        );
        $statement->execute([
            ':used_days' => $days,
            ':extension_days' => $days,
            ':id' => $id,
        ]);
    }

    public function expireOverdue(string $today): int
    {
        $statement = $this->db->prepare(
            "UPDATE memberships SET status = 'expired'
             WHERE status = 'active' AND end_date < :today"
        );
        $statement->execute([':today' => $today]);
        return $statement->rowCount();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO memberships
             (user_id, type_id, visits_total, visits_left, start_date, end_date, price_paid, sold_by)
             VALUES (:user_id, :type_id, :visits_total, :visits_left, :start_date, :end_date, :price_paid, :sold_by)'
        );
        $statement->execute([
            ':user_id' => $data['user_id'],
            ':type_id' => $data['type_id'],
            ':visits_total' => $data['visits_total'],
            ':visits_left' => $data['visits_left'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':price_paid' => $data['price_paid'],
            ':sold_by' => $data['sold_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function consumeOneVisit(int $id): void
    {
        $statement = $this->db->prepare(
            "UPDATE memberships
             SET visits_left = visits_left - 1,
                 status = IF(visits_left = 0, 'finished', status)
             WHERE id = :id AND visits_left IS NOT NULL AND visits_left > 0"
        );
        $statement->execute([':id' => $id]);
    }
}
