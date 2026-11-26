<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Repositories\BookingRepository;
use App\Repositories\ClassRepository;
use App\Repositories\TrainerRepository;
use DateTimeImmutable;

final class TrainerController
{
    public function week(int $offset = 0): array
    {
        $user = Auth::requireRole(['trainer']);
        $offset = max(-52, min(52, $offset));
        $monday = (new DateTimeImmutable('monday this week'))->modify(($offset >= 0 ? '+' : '') . $offset . ' weeks');
        $from = $monday->format('Y-m-d');
        $to = $monday->modify('+7 days')->format('Y-m-d');

        $db = Database::connection();
        $trainer = (new TrainerRepository($db))->findByUserId((int) $user['id']);
        if ($trainer === null) {
            throw new HttpException('Профили мураббӣ ёфт нашуд.', 404);
        }

        $classes = (new ClassRepository($db))->forRange($from, $to, (int) $trainer['id']);
        $bookings = new BookingRepository($db);
        foreach ($classes as &$class) {
            $class['participants'] = $bookings->forClass((int) $class['id']);
        }
        unset($class);

        return [
            'trainer' => $trainer,
            'week_start' => $from,
            'week_end' => $monday->modify('+6 days')->format('Y-m-d'),
            'classes' => $classes,
        ];
    }
}
