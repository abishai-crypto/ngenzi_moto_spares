<?php
use App\Core\Helper;
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            color: #212529;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            padding: 30px 0;
        }

        .invoice-box {
            max-width: 850px;
            margin: auto;
            padding: 40px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
        }

        .invoice-logo {
            font-weight: 800;
            font-size: 24px;
            color: #0d6efd;
            letter-spacing: -0.5px;
        }

        .table-invoice th {
            background-color: #f8f9fa;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            color: #6c757d;
        }

        .table-invoice td {
            vertical-align: middle;
            font-size: 13px;
        }

        .no-print-bar {
            max-width: 850px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .invoice-box {
                box-shadow: none;
                border: none;
                padding: 10px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- On-Screen Control Bar -->
    <div class="no-print-bar">
        <a href="/index.php?route=sale_show&id=<?= $sale['id'] ?>" class="btn btn-outline-secondary btn-sm">
            &larr; Rudi kwenye Mauzo
        </a>
        <div>
            <a href="/index.php?route=sale_receipt&id=<?= $sale['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                Risiti ya Mashine (Thermal)
            </a>
            <button onclick="window.print();" class="btn btn-primary btn-sm">
                &#128438; Chapisha Ankara (Print A4)
            </button>
        </div>
    </div>

    <div class="invoice-box">
        <!-- Top Header -->
        <div class="row align-items-center mb-4">
            <div class="col-7">
                <div class="invoice-logo mb-1">
                    <?= htmlspecialchars($settings['business_name']) ?>
                </div>
                <p class="text-muted small mb-0"><?= htmlspecialchars($settings['business_tagline']) ?></p>
                <p class="text-muted small mb-0"><?= htmlspecialchars($settings['business_address']) ?></p>
                <p class="text-muted small mb-0">Simu: <?= htmlspecialchars($settings['business_phone']) ?> | Barua Pepe: <?= htmlspecialchars($settings['business_email']) ?></p>
            </div>
            <div class="col-5 text-end">
                <h3 class="fw-bold text-dark text-uppercase mb-1">ANKARA YA MAUZO</h3>
                <h5 class="fw-bold text-primary font-monospace mb-1">#<?= htmlspecialchars($sale['invoice_no']) ?></h5>
                <p class="text-muted small mb-0">Tarehe: <strong><?= date('d/m/Y', strtotime($sale['sale_date'])) ?></strong></p>
                <p class="text-muted small mb-0">Cashier: <?= htmlspecialchars($sale['cashier_name'] ?? 'Staff') ?></p>
            </div>
        </div>

        <hr class="my-3">

        <!-- Bill To & Payment Info -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="text-muted small text-uppercase fw-semibold mb-2">Imetolewa Kwa (Customer):</h6>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($sale['customer_name'] ?? 'Mteja wa Kawaida (Walk-in)') ?></h5>
                <?php if (!empty($sale['customer_code'])): ?>
                    <p class="text-muted small mb-0">Kodi ya Mteja: <span class="font-monospace"><?= htmlspecialchars($sale['customer_code']) ?></span></p>
                <?php endif; ?>
                <?php if (!empty($sale['customer_phone'])): ?>
                    <p class="text-muted small mb-0">Simu: <?= htmlspecialchars($sale['customer_phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($sale['customer_address'])): ?>
                    <p class="text-muted small mb-0">Mahali: <?= htmlspecialchars($sale['customer_address']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted small text-uppercase fw-semibold mb-2">Taarifa za Malipo:</h6>
                <p class="mb-1 small">Hali: 
                    <?php if ($sale['sale_status'] === 'cancelled'): ?>
                        <span class="badge bg-danger">Cancelled</span>
                    <?php elseif ($sale['payment_status'] === 'paid'): ?>
                        <span class="badge bg-success">Paid in Full</span>
                    <?php elseif ($sale['payment_status'] === 'partially_paid'): ?>
                        <span class="badge bg-warning text-dark">Partially Paid</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Credit Sale (Unpaid)</span>
                    <?php endif; ?>
                </p>
                <p class="mb-1 small">Njia ya Malipo: <strong><?= htmlspecialchars($sale['payment_method']) ?></strong></p>
                <?php if (!empty($sale['mechanic_name'])): ?>
                    <p class="mb-0 small text-muted">Fundi Husika: <strong><?= htmlspecialchars($sale['mechanic_name']) ?></strong></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-invoice mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Maelezo ya Vipuri</th>
                        <th class="text-center" style="width: 12%;">Idadi</th>
                        <th class="text-end" style="width: 18%;">Bei (TSh)</th>
                        <th class="text-end" style="width: 20%;">Jumla (TSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($items as $it): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i++ ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($it['product_name'] ?? $it['live_product_name'] ?? 'Kipuri') ?></div>
                                <small class="text-muted font-monospace">SKU: <?= htmlspecialchars($it['product_sku'] ?? $it['live_product_sku'] ?? '') ?></small>
                            </td>
                            <td class="text-center font-monospace"><?= $it['quantity'] ?> <?= htmlspecialchars($it['unit_name'] ?? 'Pcs') ?></td>
                            <td class="text-end font-monospace"><?= number_format((float)$it['unit_price'], 2) ?></td>
                            <td class="text-end font-monospace fw-bold"><?= number_format((float)$it['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Row -->
        <div class="row justify-content-end mb-5">
            <div class="col-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small">Jumla Ndogo (Subtotal):</td>
                        <td class="text-end font-monospace fw-semibold"><?= Helper::formatCurrency((float)$sale['subtotal']) ?></td>
                    </tr>
                    <?php if ((float)$sale['discount_amount'] > 0): ?>
                        <tr>
                            <td class="text-muted small">Punguzo (Discount):</td>
                            <td class="text-end font-monospace text-danger">-<?= Helper::formatCurrency((float)$sale['discount_amount']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="border-top border-bottom">
                        <td class="fw-bold fs-6 py-2">JUMLA KUU:</td>
                        <td class="text-end font-monospace fw-bold fs-6 text-primary py-2"><?= Helper::formatCurrency((float)$sale['total_amount']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small pt-2">Kiasi Kilicholipwa:</td>
                        <td class="text-end font-monospace text-success pt-2"><?= Helper::formatCurrency((float)$sale['paid_amount']) ?></td>
                    </tr>
                    <?php if ((float)$sale['balance_due'] > 0): ?>
                        <tr class="fw-bold text-danger">
                            <td>Salio Linalodaiwa (Deni):</td>
                            <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$sale['balance_due']) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Signature & Footer -->
        <div class="row mt-5 pt-4 border-top">
            <div class="col-6 text-center">
                <div style="border-bottom: 1px dashed #999; width: 220px; margin: auto; height: 40px;"></div>
                <small class="text-muted d-block mt-1">Sahihi ya Mteja / Aliyepokea</small>
            </div>
            <div class="col-6 text-center">
                <div style="border-bottom: 1px dashed #999; width: 220px; margin: auto; height: 40px;"></div>
                <small class="text-muted d-block mt-1">Sahihi & Muhuri wa Duka (NGENZI MOTO)</small>
            </div>
        </div>
    </div>
</body>
</html>
