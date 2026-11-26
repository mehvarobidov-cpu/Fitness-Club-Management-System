<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class Repository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }
}
