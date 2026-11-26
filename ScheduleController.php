<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DomainException;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Repositories\BookingRepository;
use App\Repositories\ClassRepository;
use App\Services\BookingService;
use DateTimeImmutable;

final class ScheduleController
{
    public function week(int $offset = 0): array
    {
        $offset = max(-52, min(52, $offset));
        $monday = (new DateTimeImmutable('monday this week'))->modify(($offset >= 0 ? '+' : '') . $offset . ' weeks');
        $from = $monday->format('Y-m-d');
        $to = $monday->modify('+7 days')->format('Y-m-d');

        $db = Database::connection();
        $user = Auth::user();
        $myBookings = [];
        if ($user !== null && ($user['role'] ?? '') === 'client') {
            $myBookings = (new BookingRepository($db))->forUserInRange((int) $user['id'], $from, $to);
        }

        return [
            'week_start' => $from,
            'week_end' => $monday->modify('+6 days')->format('Y-m-d'),
            'classes' => (new ClassRepository($db))->forRange($from, $to),
            'my_bookings' => $myBookings,
        ];
    }

    public function book(int $classId): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $user = Auth::requireRole(['client']);
        try {
            return (new BookingService(Database::connection()))->book((int) $user['id'], $classId);
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }

    public function cancel(int $classId): array
    {
        Csrf::requireValid($_POST['t'] ?? null);
        $user = Auth::requireRole(['client']);
        try {
            return (new BookingService(Database::connection()))->cancel((int) $user['id'], $classId);
        } catch (DomainException $exception) {
            throw new HttpException($exception->getMessage(), 422);
        }
    }
}
