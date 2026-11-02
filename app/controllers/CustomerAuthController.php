<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CustomerAuthController (Phase 16)
 * Dedicated Customer Authentication, Registration & Session Isolation Engine
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Customer;
use App\Models\Cart;
use Exception;

class CustomerAuthController extends Controller {

    /**
     * Show customer login page
     */
    public function showLogin(): void {
        if (Auth::isCustomer()) {
            $this->redirect('customer_dashboard');
        }

        $redirect = $_GET['redirect'] ?? 'customer_dashboard';

        $this->render('customer/login', [
            'pageTitle' => 'Kuingia kwa Wateja — Ngenzi Moto Spare Parts',
            'redirect'  => $redirect
        ], 'store');
    }

    /**
     * Process customer login
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer_login');
        }

        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim($_POST['redirect'] ?? 'customer_dashboard');

        if (empty($identifier) || empty($password)) {
            $this->setFlash('danger', 'Tafadhali jaza barua pepe/simu na nenosiri lako.');
            $this->redirect('customer_login&redirect=' . urlencode($redirect));
        }

        if (Auth::customerLogin($identifier, $password)) {
            $customerId = Auth::customerId();

            // Synchronize guest cart to customer cart
            Cart::syncCustomerCart($customerId);

            $this->setFlash('success', 'Karibu tena, ' . htmlspecialchars($_SESSION['customer_name'] ?? 'Mteja') . '!');

            // Sanitize redirect target to prevent open redirects
            if (strpos($redirect, 'http://') === 0 || strpos($redirect, 'https://') === 0 || strpos($redirect, '//') === 0) {
                $redirect = 'customer_dashboard';
            }

            if (strpos($redirect, 'index.php') !== false) {
                header("Location: {$redirect}");
                exit;
            } else {
                $this->redirect($redirect ?: 'customer_dashboard');
            }
        } else {
            $this->setFlash('danger', 'Barua pepe/simu au nenosiri si sahihi, au akaunti imezuiwa.');
            $this->redirect('customer_login&redirect=' . urlencode($redirect));
        }
    }

    /**
     * Show customer registration page
     */
    public function showRegister(): void {
        if (Auth::isCustomer()) {
            $this->redirect('customer_dashboard');
        }

        $this->render('customer/register', [
            'pageTitle' => 'Kujisajili kama Mteja — Ngenzi Moto Spare Parts'
        ], 'store');
    }

    /**
     * Process customer registration
     */
    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer_register');
        }

        try {
            $regData = [
                'name'             => $_POST['name'] ?? '',
                'phone'            => $_POST['phone'] ?? '',
                'email'            => $_POST['email'] ?? '',
                'password'         => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
            ];

            $customer = Customer::register($regData);

            // Auto-login newly registered customer
            Auth::customerLogin($customer['email'], $regData['password']);
            Cart::syncCustomerCart($customer['id']);

            $this->setFlash('success', 'Usajili wako umekamilika kikamilifu! Karibu kwenye duka la Ngenzi Moto Spare Parts.');
            $this->redirect('customer_dashboard');

        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
            $this->render('customer/register', [
                'pageTitle' => 'Kujisajili kama Mteja — Ngenzi Moto Spare Parts',
                'formData'  => $_POST
            ], 'store');
        }
    }

    /**
     * Customer logout
     */
    public function logout(): void {
        Auth::customerLogout();
        $this->setFlash('info', 'Umeondoka kwenye akaunti yako ya mteja. Karibu tena!');
        $this->redirect('store');
    }
}
