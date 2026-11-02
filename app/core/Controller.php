<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Base Controller
 */

namespace App\Core;

class Controller {
    /**
     * Render a PHP view with layout
     */
    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void {
        extract($data);
        $contentFile = __DIR__ . '/../../views/' . $viewPath . '.php';

        if (!file_exists($contentFile)) {
            die("View file not found: views/{$viewPath}.php");
        }

        if ($layout === 'auth') {
            require __DIR__ . '/../../views/layouts/auth_layout.php';
        } elseif ($layout === 'blank' || $layout === 'print') {
            require $contentFile;
        } elseif ($layout === 'store' || $layout === 'customer') {
            require __DIR__ . '/../../views/layouts/store_header.php';
            require __DIR__ . '/../../views/layouts/store_navbar.php';
            require $contentFile;
            require __DIR__ . '/../../views/layouts/store_footer.php';
        } else {
            require __DIR__ . '/../../views/layouts/header.php';
            require __DIR__ . '/../../views/layouts/sidebar.php';
            require __DIR__ . '/../../views/layouts/navbar.php';
            require $contentFile;
            require __DIR__ . '/../../views/layouts/footer.php';
        }
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Set a flash notification message
     */
    protected function setFlash(string $type, string $message): void {
        Helper::setFlash($type, $message);
    }

    /**
     * Redirect to a route
     */
    protected function redirect(string $route, ?string $flashType = null, ?string $flashMessage = null): void {
        if ($flashType && $flashMessage) {
            Helper::setFlash($flashType, $flashMessage);
        }
        if (strpos($route, 'index.php') !== false || strpos($route, '/') === 0) {
            header("Location: {$route}");
        } else {
            header("Location: index.php?route={$route}");
        }
        exit;
    }

    /**
     * Validate CSRF token in controller requests
     */
    protected function validateCsrfToken(?string $token = null): bool {
        $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
        return Helper::validateCsrf($token);
    }
}
