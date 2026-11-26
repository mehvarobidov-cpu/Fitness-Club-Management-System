<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DomainException;
use App\Repositories\MembershipRepository;
use App\Repositories\VisitRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

final class VisitService
{
    private MembershipRepository $memberships;
    private VisitRepository $visits;

    public function __construct(private PDO $db)
    {
        $this->memberships = new MembershipRepository($db);
        $this->visits = new VisitRepository($db);
    }

    public function checkIn(int $userId, int $membershipId, ?int $classId, int $checkedBy): array
    {
        $this->db->beginTransaction();
        try {
            $membership = $this->memberships->findForUpdate($membershipId);
            $today = (new DateTimeImmutable('today'))->format('Y-m-d');

            if ($membership === null || (int) $membership['user_id'] !== $userId) {
                throw new DomainException('Абонементи интихобшуда ба ин мизоҷ тааллуқ надорад.');
            }
            if ($membership['status'] !== 'active'
                || $membership['start_date'] > $today
                || $membership['end_date'] < $today
            ) {
                throw new DomainException('Абонемент фаъол нест ё мӯҳлати он гузаштааст.');
            }
            if ($membership['visits_left'] !== null && (int) $membership['visits_left'] <= 0) {
                throw new DomainException('Ташрифҳои боқимонда тамом шудаанд.');
            }

            $visitId = $this->visits->create($userId, $membershipId, $classId, $checkedBy);
            if ($membership['visits_left'] !== null) {
                $this->memberships->consumeOneVisit($membershipId);
            }

            $remaining = $membership['visits_left'] === null
                ? null
                : max(0, (int) $membership['visits_left'] - 1);

            $this->db->commit();
            return ['visit_id' => $visitId, 'visits_left' => $remaining, 'message' => 'Ташриф қайд карда шуд.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
