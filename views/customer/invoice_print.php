<?php
use App\Models\Setting;
$businessName = Setting::get('business_name', 'NGENZI MOTO SPARE PARTS');
$businessPhone = Setting::get('business_phone', '+255 754 123 456');
$businessEmail = Setting::get('business_email', 'info@motosparertz.com');
$businessAddress = Setting::get('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam');
$footerNote = Setting::get('invoice_footer', 'Bidhaa zikishanunuliwa haziwezi kurudishwa isipokuwa kwa masharti maalum ndani ya siku 3.');
$addressData = json_decode($order['delivery_address_snapshot'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <title>Ankara ya Agizo #<?= htmlspecialchars($order['order_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #fff; color: #1e293b; padding: 24px; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #e2e8f0; padding: 32px; border-radius: 8px; }
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="invoice-box">
    <!-- Print Button -->
    <div class="text-end mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Chapisha Ankara (Print)</button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Funga</button>
    </div>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($businessName) ?></h3>
            <small class="text-muted d-block"><?= htmlspecialchars($businessAddress) ?></small>
            <small class="text-muted d-block">Simu: <?= htmlspecialchars($businessPhone) ?> • Email: <?= htmlspecialchars($businessEmail) ?></small>
        </div>
        <div class="text-end">
            <h4 class="text-primary fw-bold mb-1">ANKARA YA MAUZO</h4>
            <span class="font-monospace fw-bold fs-6"><?= htmlspecialchars($order['order_number']) ?></span><br>
            <small class="text-muted">Tarehe: <?= date('d/m/Y', strtotime($order['created_at'])) ?></small>
        </div>
    </div>

    <!-- Customer & Delivery Info -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <h6 class="fw-bold small text-muted text-uppercase mb-1">Mteja:</h6>
            <strong class="text-dark d-block"><?= htmlspecialchars($order['customer_name']) ?></strong>
            <small class="text-muted d-block">Simu: <?= htmlspecialchars($order['customer_phone']) ?></small>
            <small class="text-muted d-block">Barua Pepe: <?= htmlspecialchars($order['customer_email']) ?></small>
        </div>
        <div class="col-6 text-end">
            <h6 class="fw-bold small text-muted text-uppercase mb-1">Anwani ya Usafirishaji:</h6>
            <span class="small text-muted d-block"><?= htmlspecialchars($addressData['recipient_name'] ?? $order['customer_name']) ?></span>
            <span class="small text-muted d-block"><?= htmlspecialchars($addressData['district'] ?? '') ?>, <?= htmlspecialchars($addressData['address'] ?? '') ?></span>
            <span class="small text-muted d-block">Njia ya Malipo: <strong><?= htmlspecialchars($order['payment_method']) ?></strong> (<?= strtoupper($order['payment_status']) ?>)</span>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light small">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Maelezo ya Kipuri</th>
                <th>SKU</th>
                <th class="text-center" style="width: 80px;">Idadi</th>
                <th class="text-end" style="width: 130px;">Bei ya Kipimo</th>
                <th class="text-end" style="width: 140px;">Jumla (TSh)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $idx => $it): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td class="fw-semibold small"><?= htmlspecialchars($it['product_name']) ?></td>
                <td class="small text-muted font-monospace"><?= htmlspecialchars($it['product_sku']) ?></td>
                <td class="text-center small"><?= $it['quantity'] ?></td>
                <td class="text-end small"><?= number_format($it['unit_price'], 2) ?></td>
                <td class="text-end fw-bold small"><?= number_format($it['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end small fw-semibold">Jumla Ndogo:</td>
                <td class="text-end small fw-bold">TSh <?= number_format($order['subtotal'], 2) ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-end small fw-semibold">Gharama ya Usafirishaji:</td>
                <td class="text-end small fw-bold text-success">
                    <?= $order['delivery_fee'] == 0 ? 'BURE' : 'TSh ' . number_format($order['delivery_fee'], 2) ?>
                </td>
            </tr>
            <tr class="table-light">
                <td colspan="5" class="text-end fw-bold">Jumla Kuu ya Kulipwa:</td>
                <td class="text-end fw-bold text-primary fs-6">TSh <?= number_format($order['total_amount'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer Note -->
    <div class="border-top pt-3 text-center text-muted small">
        <p class="mb-1 fw-semibold"><?= htmlspecialchars($footerNote) ?></p>
        <p class="mb-0" style="font-size: 11px;">Asante sana kwa kufanya biashara na Ngenzi Moto Spare Parts! Karibu tena.</p>
    </div>
</div>
</body>
</html>
