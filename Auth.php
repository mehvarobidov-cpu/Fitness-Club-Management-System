<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        $user = $_SESSION['u'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @param string[] $roles */
    public static function requireRole(array $roles = []): array
    {
        $user = self::user();

        if ($user === null) {
            throw new HttpException('Барои идома ворид шавед.', 401);
        }

        if ($roles !== [] && !in_array((string) ($user['role'] ?? ''), $roles, true)) {
            throw new HttpException('Шумо барои иҷрои ин амал иҷозат надоред.', 403);
        }

        return $user;
    }
}
