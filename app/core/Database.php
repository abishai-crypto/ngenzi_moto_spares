<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Core Database Class (PDO Singleton)
 */

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                // Log technical error securely, show polite user message
                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border-radius:12px;background:#fff3f3;border:1px solid #ffcaca;color:#a00;'>
                    <h2 style='margin-top:0;'>Tatizo la Muunganisho wa Database</h2>
                    <p>Haikuweza kuunganishwa na MySQL kwenye Port {$config['port']}. Tafadhali hakikisha XAMPP MySQL inafanya kazi.</p>
                </div>");
            }
        }

        return self::$instance;
    }
}
