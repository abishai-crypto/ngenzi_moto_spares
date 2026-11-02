<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Business Logic & System Helper
 */

namespace App\Core;

class Helper {
    /**
     * Format currency into standard Tanzanian Shillings format
     * e.g. TSh 14,300.00
     */
    public static function formatMoney(float $amount, bool $showSymbol = true): string {
        $formatted = number_format($amount, 2, '.', ',');
        return $showSymbol ? "TSh " . $formatted : $formatted;
    }

    public static function formatCurrency(float $amount, bool $showSymbol = true): string {
        return self::formatMoney($amount, $showSymbol);
    }

    /**
     * Calculate recommended selling price based on cost and target gross margin
     * Selling Price = Cost / (1 - TargetMargin)
     * e.g. Cost 10,000, Margin 30% (0.30) => 10,000 / 0.70 = 14,285.71 => round to 14,300
     */
    public static function calcRecommendedPrice(float $cost, float $targetMargin = 0.30, bool $roundToHundred = true): float {
        if ($targetMargin >= 1.0) $targetMargin = 0.99;
        $price = $cost / (1 - $targetMargin);
        
        if ($roundToHundred) {
            // In Dar es Salaam, spare parts prices are rounded to nearest 50 or 100 TSh
            return ceil($price / 100) * 100;
        }
        return round($price, 2);
    }

    /**
     * Calculate actual Gross Profit and Gross Margin Percentage
     * Gross Profit = Selling Price - Cost
     * Gross Margin % = (Gross Profit / Selling Price) * 100
     */
    public static function calcProfitAndMargin(float $cost, float $sellingPrice): array {
        $grossProfit = $sellingPrice - $cost;
        $marginPercent = ($sellingPrice > 0) ? ($grossProfit / $sellingPrice) * 100 : 0.0;

        $status = 'normal';
        $badgeClass = 'success';
        $statusText = 'Salama (Target Imefikiwa)';

        if ($marginPercent < 20.0) {
            $status = 'critical';
            $badgeClass = 'danger';
            $statusText = 'Hatari (Chini ya 20%)';
        } elseif ($marginPercent < 30.0) {
            $status = 'warning';
            $badgeClass = 'warning';
            $statusText = 'Tahadhari (Chini ya 30%)';
        }

        return [
            'gross_profit'   => round($grossProfit, 2),
            'margin_percent' => round($marginPercent, 2),
            'status'         => $status,
            'badge_class'    => $badgeClass,
            'status_text'    => $statusText,
        ];
    }

    /**
     * Generate or fetch CSRF Token
     */
    public static function csrfToken(): string {
        Auth::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Alias for csrfToken()
     */
    public static function generateCsrf(): string {
        return self::csrfToken();
    }

    /**
     * Generate CSRF hidden input field HTML
     */
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate submitted CSRF Token
     */
    public static function validateCsrf(?string $token): bool {
        Auth::startSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Alias for validateCsrf()
     */
    public static function verifyCSRF(?string $token): bool {
        return self::validateCsrf($token);
    }

    /**
     * Prevent CSV / Spreadsheet Formula Injection (CWE-1236)
     * Prepends single quote if value starts with =, +, -, @, \t, \r
     */
    public static function sanitizeCsvValue($value) {
        if ($value === null || is_numeric($value) || is_bool($value)) {
            return $value;
        }
        $str = (string)$value;
        if (strlen($str) > 0 && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"])) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Sanitize an entire CSV record row against formula injection
     */
    public static function sanitizeCsvRow(array $row): array {
        return array_map([self::class, 'sanitizeCsvValue'], $row);
    }

    /**
     * Set a flash message
     */
    public static function setFlash(string $type, string $message): void {
        Auth::startSession();
        $_SESSION['flash'] = [
            'type'    => $type, // success, danger, warning, info
            'message' => $message,
        ];
    }

    /**
     * Retrieve and clear flash message
     */
    public static function getFlash(): ?array {
        Auth::startSession();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Sanitize string input for safe HTML output
     */
    public static function escape(?string $data): string {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Scrub sensitive keys from audit payloads before storage
     */
    private static function scrubAuditData($data) {
        if (!is_array($data)) return $data;
        $sensitiveKeys = ['password', 'password_hash', 'current_password', 'new_password', 'confirm_password', 'token', 'csrf_token'];
        $scrubbed = [];
        foreach ($data as $key => $val) {
            if (in_array(strtolower((string)$key), $sensitiveKeys)) {
                $scrubbed[$key] = '*** (REDACTED)';
            } elseif (is_array($val)) {
                $scrubbed[$key] = self::scrubAuditData($val);
            } else {
                $scrubbed[$key] = $val;
            }
        }
        return $scrubbed;
    }

    /**
     * Record system activity into audit_logs table (Server-side sanitized)
     */
    public static function logAudit(string $action, string $module, ?int $recordId = null, $oldValues = null, $newValues = null): void {
        try {
            $pdo = Database::getConnection();
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId !== null) {
                $uChk = $pdo->prepare("SELECT id FROM `users` WHERE id = ? LIMIT 1");
                $uChk->execute([$userId]);
                if (!$uChk->fetchColumn()) {
                    $userId = null;
                }
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

            $scrubbedOld = self::scrubAuditData($oldValues);
            $scrubbedNew = self::scrubAuditData($newValues);

            $stmt = $pdo->prepare("
                INSERT INTO `audit_logs` (`user_id`, `action`, `module`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $action,
                $module,
                $recordId,
                is_array($scrubbedOld) ? json_encode($scrubbedOld, JSON_UNESCAPED_UNICODE) : $scrubbedOld,
                is_array($scrubbedNew) ? json_encode($scrubbedNew, JSON_UNESCAPED_UNICODE) : $scrubbedNew,
                $ip,
                $ua
            ]);
        } catch (\Exception $e) {
            error_log("Audit Log Failure: " . $e->getMessage());
        }
    }

    /**
     * Format a timestamp or date string into human-readable relative time
     */
    public static function timeAgo($datetime): string {
        if (empty($datetime)) return 'Haijulikani';
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$timestamp) return (string)$datetime;

        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'Sasa hivi';
        } elseif ($diff < 3600) {
            $m = (int)floor($diff / 60);
            return $m == 1 ? 'Dakika 1 iliyopita' : "Dakika {$m} zilizopita";
        } elseif ($diff < 86400) {
            $h = (int)floor($diff / 3600);
            return $h == 1 ? 'Saa 1 lililopita' : "Masaa {$h} yaliyopita";
        } elseif ($diff < 2592000) {
            $d = (int)floor($diff / 86400);
            return $d == 1 ? 'Jana' : "Siku {$d} zilizopita";
        } else {
            return date('d/m/Y H:i', $timestamp);
        }
    }
}
