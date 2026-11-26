<?php
declare(strict_types=1);

namespace App\Repositories;

final class DashboardRepository extends Repository
{
    public function stats(): array
    {
        $row = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'client' AND active = 1) AS clients,
                (SELECT COUNT(*) FROM users WHERE role = 'trainer' AND active = 1) AS trainers,
                (SELECT COUNT(*) FROM memberships WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()) AS active_memberships,
                (SELECT COUNT(*) FROM visits WHERE DATE(visited_at) = CURDATE()) AS visits_today,
                (SELECT COUNT(*) FROM classes WHERE cancelled = 0 AND starts_at >= NOW()) AS upcoming_classes"
        )->fetch();

        foreach ($row as $key => $value) {
            $row[$key] = (int) $value;
        }
        return $row;
    }

    public function recentClasses(int $limit = 6): array
    {
        return (new ClassRepository($this->db))->upcoming($limit);
    }
}
