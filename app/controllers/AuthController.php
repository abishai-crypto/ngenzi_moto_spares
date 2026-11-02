<?php
/**
 * NGENZI MOTO SPARE PARTS
 * AuthController
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;

class AuthController extends Controller {
    /**
     * Display the login page
     */
    public function showLogin(): void {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $this->render('auth/login', [
            'pageTitle' => 'Kuingia Kwenye Mfumo — NGENZI MOTO SPARE PARTS',
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ], 'auth');
    }

    /**
     * Process login form submission
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('login');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrf)) {
            $this->redirect('login', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF). Tafadhali jaribu tena.');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->redirect('login', 'warning', 'Tafadhali jaza jina la mtumiaji na nenosiri.');
        }

        if (Auth::attempt($username, $password)) {
            $user = Auth::user();
            Helper::setFlash('success', "Karibu tena, {$user['full_name']}! ({$user['role_display']})");
            $this->redirect('dashboard');
        } else {
            $this->redirect('login', 'danger', 'Jina la mtumiaji au nenosiri sio sahihi. Tafadhali hakiki tena.');
        }
    }

    /**
     * Process logout
     */
    public function logout(): void {
        Auth::logout();
        $this->redirect('login', 'info', 'Umetoka kwenye mfumo kikamilifu.');
    }
}
