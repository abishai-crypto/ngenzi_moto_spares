<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Backup & Disaster Recovery Model
 * Dar es Salaam, Tanzania
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Env;
use App\Core\Logger;
use PDO;
use Exception;

class Backup {
    private static ?string $backupDir = null;

    /**
     * Get absolute path to the secure backups directory
     */
    public static function getBackupDir(): string {
        if (self::$backupDir === null) {
            $configured = Env::get('BACKUP_PATH', 'storage/backups');
            $dir = dirname(__DIR__, 2) . '/' . trim($configured, '/\\');
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
            self::$backupDir = realpath($dir) ?: $dir;
        }
        return self::$backupDir;
    }

    /**
     * Generate standard backup filename: spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql
     */
    public static function generateFilename(): string {
        return 'spare_parts_backup_' . date('Y-m-d_H-i-s') . '.sql';
    }

    /**
     * Create a full database backup using dual-engine approach:
     * 1. Primary: mysqldump CLI (fast, native, complete)
     * 2. Fallback: Pure PHP PDO schema and data dumper
     */
    public static function createBackup(?int $userId = null): array {
        $startTime = microtime(true);
        $filename = self::generateFilename();
        $filepath = self::getBackupDir() . DIRECTORY_SEPARATOR . $filename;

        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $dbname = $dbConfig['dbname'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];

        $engineUsed = 'mysqldump';
        $success = false;
        $errorMsg = '';

        // Attempt 1: mysqldump CLI
        $mysqldumpBin = self::findMysqldumpBinary();
        if ($mysqldumpBin && function_exists('exec')) {
            $cmd = sprintf(
                '"%s" --host=%s --port=%s --user=%s %s --routines --triggers --single-transaction --skip-add-locks --skip-lock-tables --default-character-set=utf8mb4 %s > "%s" 2>&1',
                $mysqldumpBin,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                (!empty($password) && $password !== '') ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($dbname),
                $filepath
            );

            $output = [];
            $exitCode = 0;
            @exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($filepath) && filesize($filepath) > 1024) {
                $success = true;
            } else {
                $errorMsg = implode("\n", $output);
                @unlink($filepath); // Clean partial file
            }
        }

        // Attempt 2: Pure PHP PDO Dumper Fallback
        if (!$success) {
            $engineUsed = 'php_pdo_fallback';
            try {
                $success = self::dumpViaPdo($filepath, $dbname);
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                $success = false;
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $fileSize = $success && file_exists($filepath) ? filesize($filepath) : 0;

        if ($success) {
            Logger::info("Database backup created successfully: {$filename}", [
                'engine' => $engineUsed,
                'size_bytes' => $fileSize,
                'duration_sec' => $duration,
                'user_id' => $userId
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'filesize' => $fileSize,
                'formatted_size' => self::formatBytes($fileSize),
                'engine' => $engineUsed,
                'duration' => $duration,
                'message' => "Nakala ya database imetengenezwa kwa mafanikio ({$engineUsed})."
            ];
        }

        Logger::error("Failed to create database backup: {$errorMsg}", [
            'engine' => $engineUsed,
            'user_id' => $userId
        ]);

        return [
            'success' => false,
            'filename' => '',
            'filepath' => '',
            'filesize' => 0,
            'formatted_size' => '0 B',
            'engine' => $engineUsed,
            'duration' => $duration,
            'message' => "Haikuweza kutengeneza nakala ya database: {$errorMsg}"
        ];
    }

    /**
     * Pure PHP PDO Schema & Data Dumper (Works on any server without mysqldump)
     */
    public static function dumpViaPdo(string $targetFile, string $dbname): bool {
        $pdo = Database::getConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $handle = fopen($targetFile, 'w');
        if (!$handle) {
            throw new Exception("Haikuweza kufungua faili la nakala kwa uandishi.");
        }

        // Write SQL Header
        fwrite($handle, "-- ========================================================\n");
        fwrite($handle, "-- NGENZI MOTO SPARE PARTS - DATABASE BACKUP (PDO Engine)\n");
        fwrite($handle, "-- Tarehe: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: {$dbname}\n");
        fwrite($handle, "-- ========================================================\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        // Fetch all base tables
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $stmt->fetchAll(PDO::FETCH_NUM);

        foreach ($tables as $tableRow) {
            $tableName = $tableRow[0];

            // Table Structure
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Muundo wa Jedwali `{$tableName}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                fwrite($handle, $createRow[1] . ";\n\n");
            }

            // Table Data Dump in Chunks
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
            $totalRows = (int)$countStmt->fetchColumn();

            if ($totalRows > 0) {
                fwrite($handle, "-- Data za `{$tableName}` ({$totalRows} rekodi)\n");
                $chunkSize = 250;
                for ($offset = 0; $offset < $totalRows; $offset += $chunkSize) {
                    $rowsStmt = $pdo->query("SELECT * FROM `{$tableName}` LIMIT {$chunkSize} OFFSET {$offset}");
                    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $firstRow = $rows[0];
                        $columns = array_keys($firstRow);
                        $colList = '`' . implode('`, `', $columns) . '`';

                        $valuesList = [];
                        foreach ($rows as $row) {
                            $rowVals = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $rowVals[] = 'NULL';
                                } else {
                                    $rowVals[] = $pdo->quote($val);
                                }
                            }
                            $valuesList[] = '(' . implode(', ', $rowVals) . ')';
                        }

                        $insertSql = "INSERT INTO `{$tableName}` ({$colList}) VALUES\n" . implode(",\n", $valuesList) . ";\n";
                        fwrite($handle, $insertSql);
                    }
                }
                fwrite($handle, "\n");
            }
        }

        // Write SQL Footer
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($handle, "-- Mwisho wa Nakala ya Ngenzi Moto Spare Parts\n");

        fclose($handle);
        return true;
    }

    /**
     * List existing backups with metadata
     */
    public static function listBackups(): array {
        $dir = self::getBackupDir();
        $files = glob($dir . '/*.sql');
        $backups = [];

        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $mtime = filemtime($file);

                $backups[] = [
                    'filename' => $filename,
                    'filepath' => $file,
                    'size_bytes' => $size,
                    'formatted_size' => self::formatBytes($size),
                    'created_at' => date('Y-m-d H:i:s', $mtime),
                    'timestamp' => $mtime
                ];
            }

            // Sort newest first
            usort($backups, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }

        return $backups;
    }

    /**
     * Validate and get verified path to backup file (prevents directory traversal)
     */
    public static function getBackupPath(string $filename): ?string {
        $safeName = basename($filename);
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $safeName)) {
            return null;
        }

        $fullPath = self::getBackupDir() . DIRECTORY_SEPARATOR . $safeName;
        if (file_exists($fullPath) && is_file($fullPath)) {
            return $fullPath;
        }

        return null;
    }

    /**
     * Delete an existing backup file
     */
    public static function deleteBackup(string $filename, ?int $userId = null): bool {
        $path = self::getBackupPath($filename);
        if ($path && file_exists($path)) {
            $deleted = @unlink($path);
            if ($deleted) {
                Logger::info("Backup file deleted: {$filename}", ['user_id' => $userId]);
                return true;
            }
        }
        return false;
    }

    /**
     * Perform safe automated restore verification into a TEMPORARY database.
     * Guaranteed never to touch or overwrite the production database!
     */
    public static function testRestore(string $filename, string $tempDbName = 'ngenzi_restore_test'): array {
        $startTime = microtime(true);
        $filepath = self::getBackupPath($filename);

        if (!$filepath) {
            return [
                'success' => false,
                'message' => "Faili la nakala halikupatikana: {$filename}"
            ];
        }

        // Validate safe database name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tempDbName)) {
            return [
                'success' => false,
                'message' => "Jina la database ya majaribio si sahihi."
            ];
        }

        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $charset = $dbConfig['charset'];

        // Safety check: Never allow tempDbName to equal production database!
        if (strtolower($tempDbName) === strtolower($dbConfig['dbname'])) {
            return [
                'success' => false,
                'message' => "HITILAFU YA USALAMA: Jina la database ya majaribio haliwezi kuwa sawa na database ya uzalishaji!"
            ];
        }

        $dsnServer = "mysql:host={$host};port={$port};charset={$charset}";

        try {
            // 1. Create fresh temp database
            $pdoServer = new PDO($dsnServer, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoServer->exec("DROP DATABASE IF EXISTS `{$tempDbName}`");
            $pdoServer->exec("CREATE DATABASE `{$tempDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdoServer = null; // Close connection before spawning import process

            // 2. Import into temp database using proc_open or pure PDO
            $mysqlBin = self::findMysqlBinary();
            $importedViaCli = false;

            if ($mysqlBin && function_exists('proc_open')) {
                $cmd = sprintf(
                    '"%s" --host=%s --port=%s --user=%s %s --default-character-set=utf8mb4 %s',
                    $mysqlBin,
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    (!empty($password) && $password !== '') ? '--password=' . escapeshellarg($password) : '',
                    escapeshellarg($tempDbName)
                );

                $descriptorspec = [
                    0 => ["file", $filepath, "r"],
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"]
                ];

                $process = proc_open($cmd, $descriptorspec, $pipes);
                if (is_resource($process)) {
                    $stdout = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    $stderr = stream_get_contents($pipes[2]);
                    fclose($pipes[2]);
                    $exitCode = proc_close($process);

                    if ($exitCode === 0) {
                        $importedViaCli = true;
                    }
                }
            }

            if (!$importedViaCli) {
                // PDO Multi-Statements execution fallback
                $dsnTemp = "mysql:host={$host};port={$port};dbname={$tempDbName};charset={$charset}";
                $pdoTemp = new PDO($dsnTemp, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
                ]);
                $sqlContent = file_get_contents($filepath);
                $pdoTemp->exec($sqlContent);
                $pdoTemp = null;
            }

            // 3. Connect to restored temp DB and verify tables & records
            $dsnTemp = "mysql:host={$host};port={$port};dbname={$tempDbName};charset={$charset}";
            $pdoTemp = new PDO($dsnTemp, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $tablesRestored = $pdoTemp->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
            $tableCount = count($tablesRestored);

            // Verify critical business tables
            $criticalTables = [
                'users', 'products', 'categories', 'brands', 'sales',
                'sale_items', 'purchases', 'sale_payments', 'customers', 'suppliers',
                'inventory_transactions', 'returns', 'settings'
            ];

            $missingTables = [];
            foreach ($criticalTables as $ct) {
                if (!in_array($ct, $tablesRestored)) {
                    $missingTables[] = $ct;
                }
            }

            // Sample verification counts
            $sampleCounts = [];
            foreach (['users', 'products', 'sales', 'customers'] as $tbl) {
                if (in_array($tbl, $tablesRestored)) {
                    $cnt = (int)$pdoTemp->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
                    $sampleCounts[$tbl] = $cnt;
                }
            }

            // 4. Safely cleanup: drop temporary database
            $pdoTemp = null;
            $pdoServerClean = new PDO($dsnServer, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoServerClean->exec("DROP DATABASE IF EXISTS `{$tempDbName}`");
            $pdoServerClean = null;

            $duration = round(microtime(true) - $startTime, 2);

            if (!empty($missingTables)) {
                return [
                    'success' => false,
                    'tables_restored' => $tableCount,
                    'missing_critical_tables' => $missingTables,
                    'duration' => $duration,
                    'message' => "Jaribio la kurejesha limekamilika lakini majedwali muhimu yafuatayo yamekosekana: " . implode(', ', $missingTables)
                ];
            }

            return [
                'success' => true,
                'temp_database' => $tempDbName,
                'tables_restored' => $tableCount,
                'sample_counts' => $sampleCounts,
                'critical_tables_verified' => true,
                'duration' => $duration,
                'message' => "Uthibitisho wa kurejesha data umefaulu kwa 100%! Majedwali {$tableCount} yamerejeshwa na kukaguliwa kikamilifu kwenye mazingira ya majaribio."
            ];

        } catch (Exception $e) {
            try {
                $pdoServerClean = new PDO($dsnServer, $username, $password);
                $pdoServerClean->exec("DROP DATABASE IF EXISTS `{$tempDbName}`");
            } catch (Exception $cleanupEx) {
                // Silent cleanup
            }

            return [
                'success' => false,
                'duration' => round(microtime(true) - $startTime, 2),
                'message' => "Hitilafu wakati wa kujaribu kurejesha: " . $e->getMessage()
            ];
        }
    }

    /**
     * Locate mysqldump executable path
     */
    private static function findMysqldumpBinary(): ?string {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'mysqldump'
        ];

        foreach ($candidates as $bin) {
            if (file_exists($bin)) {
                return $bin;
            }
        }

        $which = (DIRECTORY_SEPARATOR === '\\') ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null';
        $out = @shell_exec($which);
        if ($out) {
            $lines = explode("\n", trim($out));
            if (!empty($lines[0]) && file_exists(trim($lines[0]))) {
                return trim($lines[0]);
            }
        }

        return null;
    }

    /**
     * Locate mysql CLI executable path
     */
    private static function findMysqlBinary(): ?string {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            'mysql'
        ];

        foreach ($candidates as $bin) {
            if (file_exists($bin)) {
                return $bin;
            }
        }

        $which = (DIRECTORY_SEPARATOR === '\\') ? 'where mysql 2>NUL' : 'which mysql 2>/dev/null';
        $out = @shell_exec($which);
        if ($out) {
            $lines = explode("\n", trim($out));
            if (!empty($lines[0]) && file_exists(trim($lines[0]))) {
                return trim($lines[0]);
            }
        }

        return null;
    }

    /**
     * Format byte count to human-readable size
     */
    public static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
