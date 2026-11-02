<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Automated Scheduled Backup Runner (CLI)
 * Suitable for Windows Task Scheduler or Linux Cron
 * 
 * Usage:
 *   Windows: C:\xampp\php\php.exe "D:\MOTO_SPARE TRADE\app\scripts\cron_backup.php"
 *   Linux:   /usr/bin/php /var/www/moto_spare/app/scripts/cron_backup.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: CLI execution only.\n");
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../models/Backup.php';

use App\Core\Env;
use App\Core\Logger;
use App\Models\Backup;

Env::init();

echo "========================================================\n";
echo "NGENZI MOTO SPARE PARTS - AUTOMATED DATABASE BACKUP\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "========================================================\n";

$result = Backup::createBackup(null);

if ($result['success']) {
    echo "SUCCESS: Backup created successfully!\n";
    echo "File:     " . $result['filename'] . "\n";
    echo "Path:     " . $result['filepath'] . "\n";
    echo "Size:     " . $result['formatted_size'] . "\n";
    echo "Engine:   " . $result['engine'] . "\n";
    echo "Duration: " . $result['duration'] . "s\n";

    // Enforce retention policy (keep newest 14 backups by default)
    $retentionCount = (int)Env::get('BACKUP_RETENTION_COUNT', 14);
    $allBackups = Backup::listBackups();
    if (count($allBackups) > $retentionCount) {
        $toPrune = array_slice($allBackups, $retentionCount);
        $pruned = 0;
        foreach ($toPrune as $b) {
            if (Backup::deleteBackup($b['filename'])) {
                $pruned++;
            }
        }
        echo "Retention Policy: Pruned {$pruned} older backup(s) beyond limit of {$retentionCount}.\n";
    }
    exit(0);
} else {
    echo "FAILED: " . $result['message'] . "\n";
    exit(1);
}
