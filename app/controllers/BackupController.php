<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Backup Controller
 * Dar es Salaam, Tanzania
 */

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helper;
use App\Core\Logger;
use App\Models\AuditLog;
use App\Models\Backup;

class BackupController extends Controller {

    /**
     * Backup Dashboard & Management List
     */
    public function index(): void {
        Auth::requirePermission('backups.view');

        $backups = Backup::listBackups();
        $backupDir = Backup::getBackupDir();
        $totalSize = array_sum(array_column($backups, 'size_bytes'));

        // Check disk free space
        $freeSpace = @disk_free_space($backupDir);
        $totalSpace = @disk_total_space($backupDir);

        $this->render('backup/index', [
            'pageTitle' => 'Hifadhi & Nakala za Data (Backups)',
            'backups' => $backups,
            'backupDir' => $backupDir,
            'totalBackups' => count($backups),
            'totalSizeFormatted' => Backup::formatBytes($totalSize),
            'freeSpaceFormatted' => $freeSpace ? Backup::formatBytes($freeSpace) : 'N/A',
            'totalSpaceFormatted' => $totalSpace ? Backup::formatBytes($totalSpace) : 'N/A',
            'isWritable' => is_writable($backupDir)
        ]);
    }

    /**
     * Trigger Manual Database Backup
     */
    public function create(): void {
        Auth::requirePermission('backups.create');
        Helper::validateCsrf();

        $userId = Auth::id();
        $result = Backup::createBackup($userId);

        if ($result['success']) {
            AuditLog::log($userId, 'CREATE_BACKUP', 'BACKUP', null, null, [
                'filename' => $result['filename'],
                'size' => $result['formatted_size'],
                'engine' => $result['engine']
            ]);
            $_SESSION['flash_success'] = "Nakala ya database imetengenezwa kwa mafanikio! Faili: {$result['filename']} ({$result['formatted_size']}).";
        } else {
            $_SESSION['flash_error'] = "Hitilafu: " . $result['message'];
        }

        $this->redirect('/index.php?route=backups');
    }

    /**
     * Download Backup File Securely
     */
    public function download(): void {
        Auth::requirePermission('backups.download');

        $filename = $_GET['file'] ?? '';
        $filePath = Backup::getBackupPath($filename);

        if (!$filePath) {
            $_SESSION['flash_error'] = "Faili la nakala halikupatikana au jina si sahihi.";
            $this->redirect('/index.php?route=backups');
            return;
        }

        AuditLog::log(Auth::id(), 'DOWNLOAD_BACKUP', 'BACKUP', null, null, [
            'filename' => basename($filePath),
            'size' => filesize($filePath)
        ]);

        // Stream file directly to browser as download
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    /**
     * Delete Backup File
     */
    public function delete(): void {
        Auth::requirePermission('backups.delete');
        Helper::validateCsrf();

        $filename = $_POST['file'] ?? '';
        $path = Backup::getBackupPath($filename);

        if (!$path) {
            $_SESSION['flash_error'] = "Faili la nakala halikupatikana au jina si sahihi.";
            $this->redirect('/index.php?route=backups');
            return;
        }

        $deleted = Backup::deleteBackup($filename, Auth::id());
        if ($deleted) {
            AuditLog::log(Auth::id(), 'DELETE_BACKUP', 'BACKUP', null, ['filename' => $filename], null);
            $_SESSION['flash_success'] = "Faili la nakala '{$filename}' limefutwa kikamilifu.";
        } else {
            $_SESSION['flash_error'] = "Haikuweza kufuta faili la nakala. Hakikisha mfumo una ruhusa stahiki za storage.";
        }

        $this->redirect('/index.php?route=backups');
    }

    /**
     * Perform Safe Disaster Recovery Verification in Temp DB
     */
    public function restoreTest(): void {
        Auth::requirePermission('backups.restore_test');
        Helper::validateCsrf();

        $filename = $_POST['file'] ?? '';
        $path = Backup::getBackupPath($filename);

        if (!$path) {
            $_SESSION['flash_error'] = "Faili la nakala halikupatikana.";
            $this->redirect('/index.php?route=backups');
            return;
        }

        $res = Backup::testRestore($filename, 'ngenzi_restore_test');

        if ($res['success']) {
            AuditLog::log(Auth::id(), 'TEST_RESTORE', 'BACKUP', null, null, [
                'filename' => $filename,
                'tables_restored' => $res['tables_restored'],
                'duration' => $res['duration']
            ]);
            $_SESSION['flash_success'] = "JARIBIO LA UREJESHO LIMIFAULU! Majedwali {$res['tables_restored']} yamerejeshwa na kuthibitishwa kwenye database ya majaribio ndani ya sekunde {$res['duration']}. Database ya majaribio imesafishwa.";
        } else {
            $_SESSION['flash_error'] = "JARIBIO LA UREJESHO LIMESHINDWA: " . $res['message'];
        }

        $this->redirect('/index.php?route=backups');
    }
}
