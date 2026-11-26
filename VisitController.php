<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DomainException;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Repositories\VisitRepository;
use App\Services\VisitService;

final class VisitController
{
    public function history(): array
    {
        $user = Auth::requireRole(['client', 'admin']);
        $visits = new VisitRepository(Database::connection());

        return $user['role'] === 'admin'
            ? ['items' => $visits->recent(30)]
            : ['items' => $visits->forUser((int) $user['id'], 30)];
    }

    public function checkIn(int $userId, int $membershipId, ?int $classId = null): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $admin = Auth::requireRole(['admin']);
        try {
            return (new VisitService(Database::connection()))->checkIn(
                $userId,
                $membershipId,
                $classId,
                (int) $admin['id']
            );
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }
}
