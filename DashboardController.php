<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\DashboardRepository;
use App\Repositories\VisitRepository;

final class DashboardController
{
    public function index(): array
    {
        Auth::requireRole(['admin']);
        $db = Database::connection();
        $dashboard = new DashboardRepository($db);

        return [
            'stats' => $dashboard->stats(),
            'upcoming_classes' => $dashboard->recentClasses(6),
            'recent_visits' => (new VisitRepository($db))->recent(10),
        ];
    }
}
