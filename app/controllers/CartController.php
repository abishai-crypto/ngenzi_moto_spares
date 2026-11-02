<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CartController (Phase 16)
 * Shopping Cart Management & Live Calculations
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cart;
use Exception;

class CartController extends Controller {

    /**
     * Show Cart Page
     */
    public function index(): void {
        $summary = Cart::getSummary();

        $this->render('cart/index', [
            'pageTitle' => 'Kapu Lako la Ununuzi — Ngenzi Moto Spare Parts',
            'cart'      => $summary
        ], 'store');
    }

    /**
     * Add product to cart
     */
    public function add(): void {
        $productId = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));
        $quantity = max(1, (int)($_POST['quantity'] ?? ($_GET['quantity'] ?? 1)));
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {
            $summary = Cart::addItem($productId, $quantity);

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Kipuri kimeongezwa kwenye kapu.',
                    'cart'    => $summary
                ]);
            } else {
                $this->setFlash('success', 'Kipuri kimeongezwa kwenye kapu lako la ununuzi.');
                $this->redirect('cart');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            } else {
                $this->setFlash('danger', $e->getMessage());
                $this->redirect('product_detail&id=' . $productId);
            }
        }
    }

    /**
     * Update quantity
     */
    public function update(): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {
            $summary = Cart::updateQuantity($productId, $quantity);
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Idadi imesasishwa.',
                    'cart'    => $summary
                ]);
            } else {
                $this->setFlash('success', 'Kapu limesasishwa.');
                $this->redirect('cart');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            } else {
                $this->setFlash('danger', $e->getMessage());
                $this->redirect('cart');
            }
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(): void {
        $productId = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));
        Cart::removeItem($productId);
        $this->setFlash('info', 'Kipuri kimeondolewa kwenye kapu.');
        $this->redirect('cart');
    }

    /**
     * Clear all items in cart
     */
    public function clear(): void {
        Cart::clear();
        $this->setFlash('info', 'Kapu lako la ununuzi limefutwa.');
        $this->redirect('cart');
    }

    /**
     * Return JSON summary for header badges
     */
    public function summaryJson(): void {
        $summary = Cart::getSummary();
        $this->json($summary);
    }
}
