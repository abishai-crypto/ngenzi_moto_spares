<?php
/**
 * NGENZI MOTO SPARE PARTS
 * AuditController (Phase 14)
 * Dedicated Administrative Audit Trail Viewer & Security Inspection
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\AuditLog;
use App\Models\User;

class AuditController extends Controller {

    /**
     * Check authorization: Only Super Admin, Manager, or users with view_audit_logs
     */
    private function checkAccess(): void {
        Auth::requireAuth();
        if (!Auth::hasRole(['super_admin', 'manager']) && !Auth::hasPermission('view_audit_logs') && !Auth::hasPermission('audit.view')) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia kumbukumbu ya matukio ya mfumo (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Main Audit Trail Directory with Filtering and Pagination
     */
    public function index(): void {
        $this->checkAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;

        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'user_id'   => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'module'    => trim($_GET['module'] ?? ''),
            'action'    => trim($_GET['action'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? '')
        ];

        $logs = AuditLog::all($filters, $page, $perPage);
        $totalLogs = AuditLog::count($filters);
        $totalPages = max(1, (int)ceil($totalLogs / $perPage));

        $users = User::all();
        $modules = AuditLog::getModules();
        $actions = AuditLog::getActions();

        $this->render('audit/index', [
            'pageTitle'   => 'Kumbukumbu ya Matukio ya Mfumo (Audit Trail) — NGENZI MOTO',
            'activeMenu'  => 'audit',
            'logs'        => $logs,
            'totalLogs'   => $totalLogs,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'filters'     => $filters,
            'users'       => $users,
            'modules'     => $modules,
            'actions'     => $actions
        ]);
    }

    /**
     * AJAX Endpoint for Detailed Inspection of an Audit Record
     */
    public function details(): void {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        $log = AuditLog::find($id);

        if (!$log) {
            echo json_encode(['success' => false, 'message' => 'Kumbukumbu haikupatikana.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data'    => [
                'id'           => (int)$log['id'],
                'created_at'   => date('d/m/Y H:i:s', strtotime($log['created_at'])),
                'user_name'    => $log['full_name'] ? ($log['full_name'] . ' (@' . $log['username'] . ')') : 'System / CLI',
                'role_display' => $log['role_display'] ?? 'N/A',
                'action'       => $log['action'],
                'module'       => $log['module'],
                'record_id'    => $log['record_id'],
                'ip_address'   => $log['ip_address'],
                'user_agent'   => $log['user_agent'],
                'old_values'   => $log['old_values'],
                'new_values'   => $log['new_values']
            ]
        ]);
        exit;
    }

    /**
     * Export Audit Trail to CSV with Formula Injection Sanitization
     */
    public function export(): void {
        $this->checkAccess();

        if (!Auth::hasRole(['super_admin']) && !Auth::hasPermission('audit.export')) {
            Helper::setFlash('danger', 'Huna idhini ya kupakua kumbukumbu ya ukaguzi.');
            $this->redirect('/index.php?route=audit');
            exit;
        }

        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'user_id'   => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'module'    => trim($_GET['module'] ?? ''),
            'action'    => trim($_GET['action'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? '')
        ];

        $filename = "Audit_Trail_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($out, Helper::sanitizeCsvRow(['ID', 'Tarehe na Saa', 'Mtumiaji', 'Username', 'Kitendo (Action)', 'Moduli', 'ID ya Rekodi', 'IP Address', 'Kifaa/Browser', 'Thamani za Zamani', 'Thamani Mpya']));

        $data = AuditLog::all($filters, 1, 5000);
        foreach ($data as $r) {
            fputcsv($out, Helper::sanitizeCsvRow([
                $r['id'],
                date('d/m/Y H:i:s', strtotime($r['created_at'])),
                $r['full_name'] ?? 'System',
                $r['username'] ?? 'System',
                $r['action'],
                $r['module'],
                $r['record_id'] ?? '—',
                $r['ip_address'],
                $r['user_agent'],
                $r['old_values'] ?? '—',
                $r['new_values'] ?? '—'
            ]));
        }

        fclose($out);
        exit;
    }
}
