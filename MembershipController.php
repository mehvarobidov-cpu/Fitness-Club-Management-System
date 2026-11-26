<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DomainException;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Repositories\MembershipRepository;
use App\Services\MembershipService;

final class MembershipController
{
    public function mine(): array
    {
        $user = Auth::requireRole(['client']);
        $memberships = new MembershipRepository(Database::connection());

        return ['items' => $memberships->forUser((int) $user['id'])];
    }

    public function freeze(int $id): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $user = Auth::requireRole(['client']);
        try {
            $message = (new MembershipService(Database::connection()))->freeze($id, (int) $user['id']);
            return ['success' => true, 'message' => $message];
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }

    public function unfreeze(int $id): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $user = Auth::requireRole(['client']);
        try {
            $message = (new MembershipService(Database::connection()))->unfreeze($id, (int) $user['id']);
            return ['success' => true, 'message' => $message];
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }

    public function sell(int $clientId, int $typeId, string $startDate): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $admin = Auth::requireRole(['admin']);
        try {
            $id = (new MembershipService(Database::connection()))->sell(
                $clientId,
                $typeId,
                $startDate,
                (int) $admin['id']
            );
            return ['success' => true, 'membership_id' => $id];
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }
}
