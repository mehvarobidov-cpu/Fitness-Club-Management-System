<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DomainException;
use App\Repositories\MembershipRepository;
use App\Repositories\MembershipTypeRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

final class MembershipService
{
    public const MAX_FREEZE_DAYS = 14;

    private MembershipRepository $memberships;
    private MembershipTypeRepository $types;
    private UserRepository $users;

    public function __construct(private PDO $db)
    {
        $this->memberships = new MembershipRepository($db);
        $this->types = new MembershipTypeRepository($db);
        $this->users = new UserRepository($db);
    }

    public function sell(
        int $userId,
        int $typeId,
        string $startDate,
        ?int $soldBy
    ): int {
        $start = $this->parseDate($startDate);

        $this->db->beginTransaction();
        try {
            if ($this->users->findActiveClientForUpdate($userId) === null) {
                throw new DomainException('Мизоҷи фаъол ёфт нашуд.');
            }

            $type = $this->types->findActiveForUpdate($typeId);
            if ($type === null) {
                throw new DomainException('Намуди абонемент фаъол нест ё ёфт нашуд.');
            }

            $duration = max(1, (int) $type['duration_days']);
            $endDate = $start->modify('+' . $duration . ' days')->format('Y-m-d');
            $visits = $type['visits'] === null ? null : (int) $type['visits'];

            $id = $this->memberships->create([
                'user_id' => $userId,
                'type_id' => $typeId,
                'visits_total' => $visits,
                'visits_left' => $visits,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $endDate,
                'price_paid' => (float) $type['price'],
                'sold_by' => $soldBy,
            ]);

            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function freeze(int $membershipId, ?int $ownerUserId = null): string
    {
        $this->db->beginTransaction();
        try {
            $membership = $this->memberships->findForUpdate($membershipId);
            if ($membership === null || ($ownerUserId !== null && (int) $membership['user_id'] !== $ownerUserId)) {
                throw new DomainException('Абонемент ёфт нашуд.');
            }
            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
            if ($membership['status'] !== 'active'
                || $membership['start_date'] > $today
                || $membership['end_date'] < $today
            ) {
                throw new DomainException('Танҳо абонементи фаъол ва дар муҳлат бударо боздоштан мумкин аст.');
            }

            $remaining = self::MAX_FREEZE_DAYS - (int) $membership['freeze_days_used'];
            if ($remaining <= 0) {
                throw new DomainException('Лимити боздошт барои ин абонемент тамом шудааст.');
            }

            $this->memberships->freeze($membershipId, $today);
            $this->db->commit();
            return "Абонемент боздошта шуд. Рӯзҳои боқимондаи лимит: {$remaining}.";
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function unfreeze(int $membershipId, ?int $ownerUserId = null): string
    {
        $this->db->beginTransaction();
        try {
            $membership = $this->memberships->findForUpdate($membershipId);
            if ($membership === null || ($ownerUserId !== null && (int) $membership['user_id'] !== $ownerUserId)) {
                throw new DomainException('Абонемент ёфт нашуд.');
            }
            if ($membership['status'] !== 'frozen' || empty($membership['frozen_at'])) {
                throw new DomainException('Абонемент дар ҳолати боздошта нест.');
            }

            $remaining = max(0, self::MAX_FREEZE_DAYS - (int) $membership['freeze_days_used']);
            $elapsed = $this->daysBetween((string) $membership['frozen_at'], (new DateTimeImmutable('today'))->format('Y-m-d'));
            $extension = min($elapsed, $remaining);
            $this->memberships->unfreeze($membershipId, $extension);
            $this->db->commit();

            return "Абонемент фаъол шуд. Мӯҳлат {$extension} рӯз дароз карда шуд.";
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /** Run periodically or before rendering membership screens, like the current config.php sync(). */
    public function synchronizeStatuses(): array
    {
        $this->db->beginTransaction();
        try {
            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
            $autoUnfrozen = 0;

            foreach ($this->memberships->frozenForUpdate() as $membership) {
                $remaining = max(0, self::MAX_FREEZE_DAYS - (int) $membership['freeze_days_used']);
                $elapsed = $this->daysBetween((string) $membership['frozen_at'], $today);
                if ($elapsed >= $remaining) {
                    $this->memberships->unfreeze((int) $membership['id'], $remaining);
                    $autoUnfrozen++;
                }
            }

            $expired = $this->memberships->expireOverdue($today);
            $this->db->commit();
            return ['auto_unfrozen' => $autoUnfrozen, 'expired' => $expired];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function validForUser(int $userId, string $date): ?array
    {
        return $this->memberships->validForUser($userId, $date);
    }

    private function parseDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new DomainException('Сана бояд дар формати YYYY-MM-DD бошад.');
        }
        return $parsed;
    }

    private function daysBetween(string $from, string $to): int
    {
        $start = $this->parseDate($from);
        $end = $this->parseDate($to);
        return max(0, (int) $start->diff($end)->format('%r%a'));
    }
}
