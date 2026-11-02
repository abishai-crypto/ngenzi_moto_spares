<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Enhanced Authentication & Granular RBAC Engine (Phase 3)
 */

namespace App\Core;

use PDO;

class Auth {
    private static bool $isHandlingTimeout = false;

    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                if (PHP_VERSION_ID >= 70300) {
                    session_set_cookie_params([
                        'lifetime' => 0,
                        'path'     => '/',
                        'domain'   => '',
                        'secure'   => $isHttps,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } else {
                    ini_set('session.cookie_httponly', '1');
                    ini_set('session.use_only_cookies', '1');
                    if ($isHttps) {
                        ini_set('session.cookie_secure', '1');
                    }
                }
                session_start();
            } else {
                @session_start();
            }
        }

        // Check session inactivity timeout for authenticated sessions
        if (!self::$isHandlingTimeout && !empty($_SESSION['authenticated']) && !empty($_SESSION['last_activity'])) {
            $timeoutMinutes = class_exists('\App\Models\Setting') ? (int)\App\Models\Setting::get('session_timeout_minutes', '30') : 30;
            if ($timeoutMinutes <= 0) {
                $timeoutMinutes = 30;
            }
            $timeoutSeconds = $timeoutMinutes * 60;

            if ((time() - (int)$_SESSION['last_activity']) > $timeoutSeconds) {
                self::$isHandlingTimeout = true;
                unset($_SESSION['authenticated'], $_SESSION['last_activity']);
                self::logout();
                self::$isHandlingTimeout = false;
                Helper::setFlash('warning', 'Muda wako wa kutumia mfumo umemalizika (Session Expired). Tafadhali ingia tena.');
                if (!headers_sent() && php_sapi_name() !== 'cli') {
                    header('Location: index.php?route=login');
                    exit;
                }
            } else {
                // Refresh activity timestamp on active interaction
                $_SESSION['last_activity'] = time();
            }
        } elseif (!empty($_SESSION['authenticated']) && empty($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Authenticate user credentials with brute-force delay and inactive checks
     */
    public static function attempt(string $username, string $password): bool {
        self::startSession();
        $pdo = Database::getConnection();

        // Check failed attempts rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $attemptKey = 'failed_attempts_' . md5($ip . '_' . $username);
        $failedCount = $_SESSION[$attemptKey]['count'] ?? 0;
        $lastAttempt = $_SESSION[$attemptKey]['time'] ?? 0;

        // If >= 5 failed attempts within 3 minutes, block temporarily
        if ($failedCount >= 5 && (time() - $lastAttempt) < 180) {
            Helper::logAudit('LOCKED_OUT', 'AUTH', null, null, [
                'username' => $username,
                'ip'       => $ip,
                'reason'   => 'Majaribio mengi ya nenosiri yasiyo sahihi (Rate limit exceeded)'
            ]);
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT u.*, r.name AS role_name, r.display_name AS role_display
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE (u.username = ? OR u.email = ?)
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        // 1. Verify user exists and password matches
        if ($user && password_verify($password, $user['password'])) {
            // 2. Check if user status is active
            if ($user['status'] !== 'active') {
                Helper::logAudit('INACTIVE_LOGIN_ATTEMPT', 'AUTH', $user['id'], null, [
                    'username' => $username,
                    'status'   => $user['status']
                ]);
                return false;
            }

            // Reset failed login counter
            unset($_SESSION[$attemptKey]);

            // Update last login
            $update = $pdo->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?");
            $update->execute([$user['id']]);

            // Regenerate session id
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }

            // Load user permissions from role_permissions
            $permStmt = $pdo->prepare("
                SELECT p.name
                FROM `role_permissions` rp
                JOIN `permissions` p ON rp.permission_id = p.id
                WHERE rp.role_id = ?
            ");
            $permStmt->execute([$user['role_id']]);
            $permissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);

            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['role_id']       = (int)$user['role_id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['phone']         = $user['phone'];
            $_SESSION['role_name']     = $user['role_name'];
            $_SESSION['role_display']  = $user['role_display'];
            $_SESSION['permissions']   = $permissions;
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();

            Helper::logAudit('LOGIN', 'AUTH', $user['id'], null, [
                'message' => "Mtumiaji {$user['username']} ({$user['role_display']}) ameingia kwenye mfumo"
            ]);
            return true;
        }

        // Track failed attempt
        $_SESSION[$attemptKey] = [
            'count' => $failedCount + 1,
            'time'  => time()
        ];

        Helper::logAudit('FAILED_LOGIN', 'AUTH', null, null, [
            'attempted_username' => $username,
            'ip'                 => $ip,
            'failed_count'       => $failedCount + 1
        ]);

        return false;
    }

    /**
     * Check if user is logged in and active in database
     */
    public static function check(): bool {
        self::startSession();
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
    }

    /**
     * Retrieve logged-in user details
     */
    public static function user(): ?array {
        self::startSession();
        if (!self::check()) {
            return null;
        }
        return [
            'id'           => $_SESSION['user_id'],
            'role_id'      => $_SESSION['role_id'],
            'username'     => $_SESSION['username'],
            'full_name'    => $_SESSION['full_name'],
            'email'        => $_SESSION['email'],
            'phone'        => $_SESSION['phone'],
            'role_name'    => $_SESSION['role_name'],
            'role_display' => $_SESSION['role_display'],
            'permissions'  => $_SESSION['permissions'] ?? [],
        ];
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function username(): ?string {
        return $_SESSION['username'] ?? null;
    }

    public static function fullName(): ?string {
        return $_SESSION['full_name'] ?? null;
    }

    public static function role(): string {
        return $_SESSION['role_name'] ?? 'guest';
    }

    public static function roleId(): ?int {
        return $_SESSION['role_id'] ?? null;
    }

    /**
     * Check if user has specific permission
     * Super Admin / Owner has all permissions by default
     */
    public static function can(string $permission): bool {
        if (!self::check()) {
            return false;
        }

        // Owner/Super Admin has unlimited access
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            return true;
        }

        $userPerms = $_SESSION['permissions'] ?? [];
        return in_array($permission, $userPerms);
    }

    /**
     * Alias for can()
     */
    public static function hasPermission(string $permission): bool {
        return self::can($permission);
    }

    /**
     * Check if user has any of given roles
     */
    public static function hasRole($roles): bool {
        if (!self::check()) return false;
        $currentRole = $_SESSION['role_name'] ?? '';

        if ($currentRole === 'super_admin') {
            return true;
        }

        if (is_array($roles)) {
            return in_array($currentRole, $roles);
        }
        return $currentRole === $roles;
    }

    /**
     * Alias for hasRole() with multiple roles
     */
    public static function hasAnyRole(array $roles): bool {
        return self::hasRole($roles);
    }

    /**
     * Require authentication middleware
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            Helper::setFlash('warning', 'Tafadhali ingia kwenye akaunti yako ili kuendelea.');
            header('Location: index.php?route=login');
            exit;
        }

        // Verify that user has not been deactivated since logging in
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT status FROM `users` WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $status = $stmt->fetchColumn();

        if ($status !== 'active') {
            self::logout();
            Helper::setFlash('danger', 'Akaunti yako imezimwa na msimamizi. Wasiliana na mmiliki wa duka.');
            header('Location: index.php?route=login');
            exit;
        }
    }

    public static function requireLogin(): void {
        self::requireAuth();
    }

    /**
     * Require specific permission middleware
     */
    public static function requirePermission(string $permission): void {
        self::requireAuth();
        if (!self::can($permission)) {
            Helper::setFlash('danger', "Huna idhini ya kufanya kitendo hiki (Inahitajika: {$permission}). Wasiliana na mmiliki.");
            header('Location: index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Require specific role middleware
     */
    public static function requireRole($roles): void {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            Helper::setFlash('danger', 'Huna ruhusa ya kufikia ukurasa huu. Wasiliana na msimamizi.');
            header('Location: index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Check if current user can view cost prices and profit margins
     * Allowed for super_admin, manager, accountant
     */
    public static function canViewFinancials(): bool {
        if (!self::check()) return false;
        if (($_SESSION['role_name'] ?? '') === 'super_admin') return true;
        return in_array($_SESSION['role_name'] ?? '', ['manager', 'accountant']) || self::can('manage_prices') || self::can('view_reports');
    }

    /**
     * Mask financial values (buying prices / profits) from unauthorized roles like cashier
     */
    public static function maskFinancial($value, string $mask = '*** (Imezuiwa)'): string {
        if (self::canViewFinancials()) {
            return is_numeric($value) ? Helper::formatCurrency((float)$value) : (string)$value;
        }
        return $mask;
    }

    /**
     * Terminate user session
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!empty($_SESSION['user_id'])) {
            Helper::logAudit('LOGOUT', 'AUTH', $_SESSION['user_id'], null, [
                'message' => "Mtumiaji ameondoka kwenye mfumo (Logout)"
            ]);
        }
        $_SESSION = [];
        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    // =========================================================================
    // PHASE 16: CUSTOMER PORTAL AUTHENTICATION & AUTHORIZATION
    // =========================================================================

    /**
     * Authenticate online customer with email/phone and password
     */
    public static function customerLogin(string $identifier, string $password): bool {
        self::startSession();
        $pdo = Database::getConnection();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $attemptKey = 'customer_failed_attempts_' . md5($ip . '_' . strtolower(trim($identifier)));
        $failedCount = $_SESSION[$attemptKey]['count'] ?? 0;
        $lastAttempt = $_SESSION[$attemptKey]['time'] ?? 0;

        // Rate limit: 5 failed attempts in 3 minutes
        if ($failedCount >= 5 && (time() - $lastAttempt) < 180) {
            Helper::logAudit('CUSTOMER_LOCKED_OUT', 'CUSTOMER_AUTH', null, null, [
                'identifier' => $identifier,
                'ip'         => $ip,
                'reason'     => 'Majaribio mengi ya nenosiri yasiyo sahihi (Customer rate limit exceeded)'
            ]);
            return false;
        }

        $cleanId = trim($identifier);
        $stmt = $pdo->prepare("
            SELECT id, customer_code, name, phone, email, password, status 
            FROM `customers` 
            WHERE (email = ? OR phone = ? OR customer_code = ?)
            LIMIT 1
        ");
        $stmt->execute([$cleanId, $cleanId, $cleanId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && !empty($customer['password']) && password_verify($password, $customer['password'])) {
            if ($customer['status'] === 'blocked') {
                Helper::logAudit('BLOCKED_CUSTOMER_LOGIN_ATTEMPT', 'CUSTOMER_AUTH', $customer['id'], null, [
                    'identifier' => $identifier,
                    'status'     => 'blocked'
                ]);
                return false;
            }

            // Reset failed counter
            unset($_SESSION[$attemptKey]);

            // Update last_login
            $pdo->prepare("UPDATE `customers` SET `last_login` = NOW() WHERE `id` = ?")->execute([$customer['id']]);

            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }

            $_SESSION['customer_authenticated'] = true;
            $_SESSION['customer_id']            = (int)$customer['id'];
            $_SESSION['customer_code']          = $customer['customer_code'];
            $_SESSION['customer_name']          = $customer['name'];
            $_SESSION['customer_email']         = $customer['email'];
            $_SESSION['customer_phone']         = $customer['phone'];
            $_SESSION['customer_last_activity'] = time();

            Helper::logAudit('CUSTOMER_LOGIN', 'CUSTOMER_AUTH', $customer['id'], null, [
                'message' => "Mteja {$customer['name']} (#{$customer['customer_code']}) ameingia kwenye duka la mtandaoni"
            ]);

            return true;
        }

        $_SESSION[$attemptKey] = [
            'count' => $failedCount + 1,
            'time'  => time()
        ];

        Helper::logAudit('CUSTOMER_FAILED_LOGIN', 'CUSTOMER_AUTH', null, null, [
            'attempted_identifier' => $identifier,
            'ip'                   => $ip,
            'failed_count'         => $failedCount + 1
        ]);

        return false;
    }

    /**
     * Check if an online customer is currently authenticated
     */
    public static function isCustomer(): bool {
        self::startSession();
        return !empty($_SESSION['customer_authenticated']) && !empty($_SESSION['customer_id']);
    }

    /**
     * Get logged-in customer ID
     */
    public static function customerId(): ?int {
        return self::isCustomer() ? (int)$_SESSION['customer_id'] : null;
    }

    /**
     * Get logged-in customer details
     */
    public static function customer(): ?array {
        if (!self::isCustomer()) {
            return null;
        }
        return [
            'id'    => $_SESSION['customer_id'],
            'code'  => $_SESSION['customer_code'] ?? '',
            'name'  => $_SESSION['customer_name'] ?? '',
            'email' => $_SESSION['customer_email'] ?? '',
            'phone' => $_SESSION['customer_phone'] ?? '',
        ];
    }

    /**
     * Require customer authentication middleware
     */
    public static function requireCustomer(): void {
        if (!self::isCustomer()) {
            Helper::setFlash('warning', 'Tafadhali ingia kwenye akaunti yako ya mteja ili kuendelea.');
            $currentUri = $_SERVER['REQUEST_URI'] ?? 'index.php?route=customer_dashboard';
            header('Location: index.php?route=customer_login&redirect=' . urlencode($currentUri));
            exit;
        }
    }

    /**
     * Log out customer without affecting staff session if any
     */
    public static function customerLogout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!empty($_SESSION['customer_id'])) {
            Helper::logAudit('CUSTOMER_LOGOUT', 'CUSTOMER_AUTH', $_SESSION['customer_id'], null, [
                'message' => "Mteja ameondoka kwenye duka la mtandaoni (Customer Logout)"
            ]);
        }
        unset(
            $_SESSION['customer_authenticated'],
            $_SESSION['customer_id'],
            $_SESSION['customer_code'],
            $_SESSION['customer_name'],
            $_SESSION['customer_email'],
            $_SESSION['customer_phone'],
            $_SESSION['customer_last_activity']
        );
    }
}

