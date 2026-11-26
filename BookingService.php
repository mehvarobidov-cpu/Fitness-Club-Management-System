<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DomainException;
use App\Repositories\BookingRepository;
use App\Repositories\ClassRepository;
use App\Repositories\MembershipRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

final class BookingService
{
    private ClassRepository $classes;
    private BookingRepository $bookings;
    private MembershipRepository $memberships;

    public function __construct(private PDO $db)
    {
        $this->classes = new ClassRepository($db);
        $this->bookings = new BookingRepository($db);
        $this->memberships = new MembershipRepository($db);
    }

    public function book(int $userId, int $classId): array
    {
        $this->db->beginTransaction();
        try {
            $class = $this->classes->findForUpdate($classId);
            if ($class === null || (int) $class['cancelled'] === 1) {
                throw new DomainException('Машғулият дастрас нест.');
            }
            if (new DateTimeImmutable((string) $class['starts_at']) < new DateTimeImmutable()) {
                throw new DomainException('Ба машғулияти гузашта сабт шудан мумкин нест.');
            }

            $existing = $this->bookings->findForUpdate($classId, $userId);
            if ($existing !== null) {
                throw new DomainException('Шумо аллакай ба ин машғулият сабт шудаед.');
            }

            $classDate = (new DateTimeImmutable((string) $class['starts_at']))->format('Y-m-d');
            if ($this->memberships->validForUserForUpdate($userId, $classDate) === null) {
                throw new DomainException('Барои сабт абонементи фаъол бо ташрифи боқимонда лозим аст.');
            }

            $status = $this->bookings->countBooked($classId) < (int) $class['capacity']
                ? 'booked'
                : 'waitlist';
            $this->bookings->save($classId, $userId, $status);
            $this->db->commit();

            return [
                'status' => $status,
                'message' => $status === 'booked'
                    ? 'Шумо ба машғулият сабт шудед.'
                    : 'Гурӯҳ пур аст. Шумо ба рӯйхати интизорӣ дохил шудед.',
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel(int $userId, int $classId): array
    {
        $this->db->beginTransaction();
        try {
            $class = $this->classes->findForUpdate($classId);
            $booking = $this->bookings->findForUpdate($classId, $userId);
            if ($class === null || $booking === null || new DateTimeImmutable((string) $class['starts_at']) < new DateTimeImmutable()) {
                throw new DomainException('Бекор кардани ин сабт имконнопазир аст.');
            }

            $wasBooked = $booking['status'] === 'booked';
            $this->bookings->cancel((int) $booking['id']);
            $promotedUserId = null;

            if ($wasBooked) {
                $classDate = (new DateTimeImmutable((string) $class['starts_at']))->format('Y-m-d');
                foreach ($this->bookings->waitlistForUpdate($classId) as $waiting) {
                    if ($this->memberships->validForUserForUpdate((int) $waiting['user_id'], $classDate) !== null) {
                        $this->bookings->setStatus((int) $waiting['id'], 'booked');
                        $promotedUserId = (int) $waiting['user_id'];
                        break;
                    }
                }
            }

            $this->db->commit();
            return [
                'message' => $promotedUserId === null
                    ? 'Сабт бекор карда шуд.'
                    : 'Сабт бекор шуд. Ҷой ба шахси навбатӣ дода шуд.',
                'promoted_user_id' => $promotedUserId,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
