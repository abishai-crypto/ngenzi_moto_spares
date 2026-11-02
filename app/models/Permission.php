<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Permission Model
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Permission {
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `permissions` ORDER BY module ASC, id ASC")->fetchAll();
    }

    /**
     * Group permissions by module
     */
    public static function groupedByModule(): array {
        $all = self::all();
        $grouped = [];
        foreach ($all as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }
}
