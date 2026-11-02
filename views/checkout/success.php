<?php
use App\Models\Setting;
$lipaNamba = Setting::get('lipa_namba_mpesa', '5522114');
$bankDetails = Setting::get('bank_transfer_instructions', 'CRDB Bank | A/C: 0152899472600 | NGENZI MOTO SPARE PARTS');
?>

<div class="container my-5" style="max-width: 780px;">
    <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white text-center">
        <!-- Success Icon -->
        <div class="p-4 rounded-circle bg-success-subtle text-success d-inline-block mx-auto mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-check-lg fs-1"></i>
        </div>

        <h3 class="fw-bold text-dark mb-1">Agizo Lako Limepokelewa Kikamilifu!</h3>
        <p class="text-muted small mb-3">Asante kwa kununua vipuri halisi kutoka Ngenzi Moto Spare Parts.</p>

        <div class="p-3 bg-light rounded-3 d-inline-block mx-auto mb-4 border">
            <span class="text-muted small d-block">Namba ya Agizo (Order Number):</span>
            <span class="fs-4 fw-bold text-primary font-monospace"><?= htmlspecialchars($order['order_number']) ?></span>
        </div>

        <!-- Payment Instructions Box -->
        <div class="card border-primary border-opacity-25 bg-light rounded-4 p-4 text-start mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 text-primary"></i> Maelekezo ya Kukamilisha Malipo</h6>

            <?php if ($order['payment_method'] === 'Mobile Money'): ?>
                <div class="p-3 bg-white rounded-3 border mb-3">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Lipa Namba ya Mtandao:</span>
                            <span class="fs-4 fw-bold text-danger"><?= htmlspecialchars($lipaNamba) ?></span>
                            <small class="text-muted d-block">Jina: <strong>NGENZI MOTO SPARE</strong></small>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                            <span class="text-muted small d-block">Kiasi cha Kulipa:</span>
                            <span class="fs-4 fw-bold text-dark">TSh <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
                <ol class="small text-muted mb-0 ps-3">
                    <li>Fungua menyu ya malipo kwenye simu yako (M-Pesa, Tigo Pesa au Airtel Money).</li>
                    <li>Chagua <strong>Lipa kwa Simu / Lipa Namba</strong> kisha weka: <strong><?= htmlspecialchars($lipaNamba) ?></strong>.</li>
                    <li>Weka Kiasi: <strong>TSh <?= number_format($order['total_amount'], 2) ?></strong>.</li>
                    <li>Weka namba ya kumbukumbu: <strong><?= htmlspecialchars($order['order_number']) ?></strong>.</li>
                </ol>
            <?php else: ?>
                <div class="p-3 bg-white rounded-3 border mb-3">
                    <span class="text-muted small d-block">Akaunti ya Benki:</span>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($bankDetails) ?></span>
                    <div class="mt-2">
                        <span class="text-muted small">Kiasi cha Kulipa:</span>
                        <span class="fs-4 fw-bold text-dark ms-2">TSh <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
                <p class="small text-muted mb-0">Tafadhali weka namba ya kumbukumbu: <strong><?= htmlspecialchars($order['order_number']) ?></strong> unapofanya uhamisho.</p>
            <?php endif; ?>
        </div>

        <!-- Order Actions -->
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="/index.php?route=customer_order_show&id=<?= $order['id'] ?>" class="btn btn-store-primary rounded-pill px-4 py-2">
                <i class="bi bi-eye me-1"></i> Fuatilia Hali ya Agizo
            </a>
            <a href="/index.php?route=customer_order_invoice&id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                <i class="bi bi-printer me-1"></i> Chapisha Ankara / Risiti
            </a>
            <a href="/index.php?route=shop" class="btn btn-link text-decoration-none">
                Endelea na Manunuzi &rarr;
            </a>
        </div>
    </div>
</div>
