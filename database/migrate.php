<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Database Migration & Seeder Runner
 */

$dbConfig = require __DIR__ . '/../app/config/database.php';

echo "=========================================================\n";
echo "  NGENZI MOTO SPARE PARTS - Database Migration\n";
echo "  Target Host: {$dbConfig['host']}:{$dbConfig['port']}\n";
echo "=========================================================\n\n";

try {
    // 1. Connect without selecting database first
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    echo "[OK] Connected to MySQL Server on port {$dbConfig['port']}.\n";

    // Check if --fresh flag is passed to drop and rebuild
    if (in_array('--fresh', $argv ?? [])) {
        $pdo->exec("DROP DATABASE IF EXISTS `{$dbConfig['dbname']}`");
        echo "[RESET] Database `{$dbConfig['dbname']}` dropped for fresh migration.\n";
    }

    // 2. Create Database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database `{$dbConfig['dbname']}` verified/created.\n";

    // 3. Connect to the specific database
    $pdo->exec("USE `{$dbConfig['dbname']}`");

    // 4. Run Schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at $schemaFile");
    }
    $schemaSql = file_get_contents($schemaFile);
    $schemaStmts = array_filter(array_map('trim', explode(';', $schemaSql)));
    foreach ($schemaStmts as $stmt) {
        if (empty($stmt) || stripos($stmt, 'CREATE DATABASE') === 0 || stripos($stmt, 'USE ') === 0) continue;
        $pdo->exec($stmt);
    }
    echo "[OK] Schema executed successfully (All tables created).\n";

    // 5. Run Seed
    $seedFile = __DIR__ . '/seed.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("seed.sql not found at $seedFile");
    }
    $seedSql = file_get_contents($seedFile);
    $seedStmts = array_filter(array_map('trim', explode(';', $seedSql)));
    foreach ($seedStmts as $stmt) {
        if (empty($stmt) || stripos($stmt, 'USE ') === 0) continue;
        $pdo->exec($stmt);
    }
    echo "[OK] Seed data inserted successfully.\n";

    // 6. Verify Tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTotal tables created: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$t`");
        $count = $countStmt->fetchColumn();
        echo "  - {$t} ({$count} records)\n";
    }

    echo "\n=========================================================\n";
    echo "  MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=========================================================\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n[ERROR] System error: " . $e->getMessage() . "\n";
    exit(1);
}
