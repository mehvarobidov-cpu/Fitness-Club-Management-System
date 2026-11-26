<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    /** Uses the existing FCMS session key so current forms can migrate gradually. */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['t']) || !is_string($_SESSION['t'])) {
            $_SESSION['t'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['t'];
    }

    public static function verify(?string $candidate): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['t']) || !is_string($candidate)) {
            return false;
        }

        return hash_equals($_SESSION['t'], $candidate);
    }

    public static function requireValid(?string $candidate): void
    {
        if (!self::verify($candidate)) {
            throw new HttpException('Дархост кӯҳна аст ё нишонаи амниятӣ нодуруст мебошад.', 419);
        }
    }
}
