<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CheckoutController (Phase 16 & 17)
 * Multi-Step Online Checkout, Flexible Delivery Selection,
 * Address Geolocation & Payment Initiation
 * Dar es Salaam, Tanzania
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\OnlineOrder;
use App\Models\Setting;
use App\Services\Payment\PaymentManager;
use App\Services\Delivery\DeliveryManager;
use Exception;

class CheckoutController extends Controller {

    /**
     * Display checkout page (supports both logged-in and guest customers)
     */
    public function index(): void {
        $customerId = Auth::customerId();
        $customer = Auth::customer() ?: [
            'id'    => 0,
            'name'  => '',
            'phone' => '',
            'email' => '',
        ];

        $cart = Cart::getSummary();
        if (empty($cart['items'])) {
            $this->setFlash('warning', 'Kapu lako halina bidhaa. Tafadhali chagua vipuri kabla ya kuelekea kulipa.');
            $this->redirect('shop');
        }

        if (!$cart['all_available']) {
            $this->setFlash('danger', 'Kuna baadhi ya bidhaa kwenye kapu ambazo hazitoshi stoo. Tafadhali rekebisha idadi.');
            $this->redirect('cart');
        }

        $addresses = $customerId ? CustomerAddress::forCustomer($customerId) : [];
        $defaultAddress = $customerId ? CustomerAddress::getDefault($customerId) : null;
        $deliveryOptions = DeliveryManager::getCheckoutOptions((float)$cart['subtotal']);

        $this->render('checkout/checkout', [
            'pageTitle'       => 'Kamilisha Agizo Lako (Checkout) — Ngenzi Moto Spare Parts',
            'cart'            => $cart,
            'addresses'       => $addresses,
            'defaultAddress'  => $defaultAddress,
            'deliveryOptions' => $deliveryOptions,
            'customer'        => $customer
        ], 'store');
    }

    /**
     * Process checkout submission (creates/links customer and records order in database)
     */
    public function process(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('checkout');
        }

        $pdo = \App\Core\Database::getConnection();

        try {
            $recipientName = trim($_POST['recipient_name'] ?? '');
            $recipientPhone = trim($_POST['phone'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (empty($recipientName)) {
                throw new Exception("Tafadhali jaza Jina Kamili la mteja.");
            }
            if (empty($recipientPhone)) {
                throw new Exception("Tafadhali jaza Namba ya Simu ya mawasiliano.");
            }

            $customerId = Auth::customerId();

            // Automatic Customer Creation for online purchasers
            if (!$customerId) {
                // Find existing customer by phone or email
                $stmtFind = $pdo->prepare("SELECT id, name, phone, email FROM `customers` WHERE phone = ? OR (email != '' AND email = ?) LIMIT 1");
                $stmtFind->execute([$recipientPhone, $email]);
                $existing = $stmtFind->fetch(\PDO::FETCH_ASSOC);

                if ($existing) {
                    $customerId = (int)$existing['id'];
                } else {
                    // Automatically register new online customer in database!
                    $customerCode = \App\Models\Customer::generateCode();
                    if (empty($email)) {
                        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
                        $email = 'online_' . $cleanPhone . '@ngenzi.co.tz';
                    }
                    $tempPass = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT);

                    $ins = $pdo->prepare("
                        INSERT INTO `customers` (
                            customer_code, name, phone, email, password,
                            customer_type, is_online_registered, registration_channel,
                            credit_limit, credit_allowed, credit_status, status,
                            notes, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?, ?,
                            'online', 1, 'ONLINE_STORE',
                            0.00, 0, 'active', 'active',
                            'Mteja wa mtandaoni aliyeagiza vipuri (Online Store Checkout)', NOW(), NOW()
                        )
                    ");
                    $ins->execute([
                        $customerCode,
                        $recipientName,
                        $recipientPhone,
                        $email,
                        $tempPass
                    ]);
                    $customerId = (int)$pdo->lastInsertId();
                }

                // Log into customer session and sync cart
                Auth::startSession();
                $_SESSION['customer_authenticated'] = true;
                $_SESSION['customer_id']            = $customerId;
                $_SESSION['customer_name']          = $recipientName;
                $_SESSION['customer_phone']         = $recipientPhone;
                $_SESSION['customer_email']         = $email;
                $_SESSION['customer_last_activity'] = time();

                Cart::syncCustomerCart($customerId);
            }

            if ($addressId <= 0) {
                // Find existing or create standard Store Pickup contact
                $existing = CustomerAddress::getDefault($customerId) ?? (CustomerAddress::forCustomer($customerId)[0] ?? null);
                if ($existing) {
                    $addressId = (int)$existing['id'];
                } else {
                    $addressId = CustomerAddress::create([
                        'customer_id'    => $customerId,
                        'label'          => 'Kuchukua Dukani',
                        'recipient_name' => $recipientName ?: 'Mteja',
                        'phone'          => $recipientPhone,
                        'region'         => 'Dar es Salaam',
                        'district'       => 'Ilala',
                        'ward'           => 'Kariakoo',
                        'street'         => 'Nyamwezi / Msimbazi',
                        'address'        => 'Dukani — Ngenzi Moto Spare Parts, Kariakoo',
                        'landmark'       => 'Dukani Kariakoo',
                        'is_default'     => 1
                    ]);
                }
            }

            // Utaratibu wa kawaida wa kuchukua dukani (Manual / Store Pickup)
            $deliveryProvider = 'CUSTOMER_PICKUP';
            $deliveryMethod = 'CUSTOMER_PICKUP';

            $deliveryDetails = [
                'courier_name'         => '',
                'courier_phone'        => '',
                'courier_instructions' => '',
                'pickup_instructions'  => trim($_POST['customer_notes'] ?? ''),
                'external_reference'   => ''
            ];

            $paymentMethod = trim($_POST['payment_method'] ?? 'Mobile Money');
            $paymentProvider = trim($_POST['payment_provider'] ?? 'M-Pesa');
            $customerNotes = trim($_POST['customer_notes'] ?? '');
            $idempotencyKey = trim($_POST['idempotency_key'] ?? '');

            // 1. Create order atomically with row locking and flexible shipping
            $orderResult = OnlineOrder::createOrder(
                $customerId,
                $addressId,
                $paymentMethod,
                $deliveryMethod,
                $customerNotes,
                $deliveryProvider,
                $deliveryDetails
            );

            $orderId = $orderResult['order_id'];
            $totalAmount = (float)$orderResult['total_amount'];

            // 2. Initiate payment transaction
            $paymentResult = PaymentManager::initiateOrderPayment(
                $orderId,
                $customerId,
                $totalAmount,
                $paymentMethod,
                $paymentProvider,
                $idempotencyKey
            );

            // Redirect to confirmation page
            $this->redirect('checkout_success&order_id=' . $orderId . '&payment_id=' . ($paymentResult['payment_id'] ?? 0));

        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
            $this->redirect('checkout');
        }
    }

    /**
     * Checkout confirmation / success page
     */
    public function success(): void {
        Auth::requireCustomer();
        $customerId = Auth::customerId();

        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = OnlineOrder::find($orderId, $customerId);

        if (!$order) {
            $this->setFlash('danger', 'Agizo halikupatikana.');
            $this->redirect('customer_orders');
        }

        $paymentId = (int)($_GET['payment_id'] ?? 0);
        $payment = null;
        if (!empty($order['payments'])) {
            $payment = $order['payments'][0];
        }

        $this->render('checkout/success', [
            'pageTitle' => "Agizo Limepokelewa #{$order['order_number']} — Ngenzi Moto Spare Parts",
            'order'     => $order,
            'payment'   => $payment
        ], 'store');
    }
}
