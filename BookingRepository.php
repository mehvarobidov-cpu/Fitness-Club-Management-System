<?php
declare(strict_types=1);

namespace App\Repositories;

final class BookingRepository extends Repository
{
    public function findForUpdate(int $classId, int $userId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM class_bookings
             WHERE class_id = :class_id AND user_id = :user_id AND status <> 'cancelled'
             LIMIT 1 FOR UPDATE"
        );
        $statement->execute([':class_id' => $classId, ':user_id' => $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function countBooked(int $classId): int
    {
        $statement = $this->db->prepare("SELECT COUNT(*) FROM class_bookings WHERE class_id = :class_id AND status = 'booked'");
        $statement->execute([':class_id' => $classId]);
        return (int) $statement->fetchColumn();
    }

    public function save(int $classId, int $userId, string $status): void
    {
        if (!in_array($status, ['booked', 'waitlist'], true)) {
            throw new \InvalidArgumentException('Unsupported booking status.');
        }

        $statement = $this->db->prepare(
            'INSERT INTO class_bookings (class_id, user_id, status)
             VALUES (:class_id, :user_id, :status)
             ON DUPLICATE KEY UPDATE status = VALUES(status), created_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([':class_id' => $classId, ':user_id' => $userId, ':status' => $status]);
    }

    public function cancel(int $bookingId): void
    {
        $statement = $this->db->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE id = :id");
        $statement->execute([':id' => $bookingId]);
    }

    public function waitlistForUpdate(int $classId): array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM class_bookings
             WHERE class_id = :class_id AND status = 'waitlist'
             ORDER BY created_at, id FOR UPDATE"
        );
        $statement->execute([':class_id' => $classId]);
        return $statement->fetchAll();
    }

    public function setStatus(int $bookingId, string $status): void
    {
        if (!in_array($status, ['booked', 'waitlist', 'cancelled'], true)) {
            throw new \InvalidArgumentException('Unsupported booking status.');
        }
        $statement = $this->db->prepare('UPDATE class_bookings SET status = :status WHERE id = :id');
        $statement->execute([':status' => $status, ':id' => $bookingId]);
    }

    public function forUserInRange(int $userId, string $from, string $to): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id AS booking_id, b.status AS booking_status, b.created_at AS booked_at,
                    c.id AS class_id, c.title, c.starts_at, c.duration_min, c.room, c.capacity,
                    u.full_name AS trainer_name
             FROM class_bookings b
             JOIN classes c ON c.id = b.class_id
             JOIN trainers t ON t.id = c.trainer_id
             JOIN users u ON u.id = t.user_id
             WHERE b.user_id = :user_id AND b.status <> 'cancelled'
               AND c.cancelled = 0 AND c.starts_at >= :from_date AND c.starts_at < :to_date
             ORDER BY c.starts_at"
        );
        $statement->execute([':user_id' => $userId, ':from_date' => $from, ':to_date' => $to]);
        return $statement->fetchAll();
    }

    public function forClass(int $classId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id, b.status, b.created_at, u.id AS user_id,
                    u.full_name, u.phone
             FROM class_bookings b JOIN users u ON u.id = b.user_id
             WHERE b.class_id = :class_id AND b.status <> 'cancelled'
             ORDER BY FIELD(b.status, 'booked', 'waitlist'), b.created_at, b.id"
        );
        $statement->execute([':class_id' => $classId]);
        return $statement->fetchAll();
    }
}
