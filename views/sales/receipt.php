<?php
use App\Core\Helper;
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background-color: #f4f6f9;
            padding: 20px;
            color: #000;
        }

        .receipt-container {
            width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 15px 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .small { font-size: 11px; }
        .tiny { font-size: 9px; }

        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .receipt-header h2 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .receipt-header p {
            font-size: 11px;
            line-height: 1.3;
        }

        .meta-table {
            width: 100%;
            font-size: 11px;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }

        .meta-table td {
            padding: 1px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            padding: 4px 0;
            text-align: left;
            font-size: 10px;
        }

        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            font-size: 11px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-bottom: 8px;
        }

        .totals-table td {
            padding: 2px 0;
        }

        .receipt-footer {
            border-top: 1px dashed #000;
            padding-top: 8px;
            text-align: center;
            font-size: 10px;
            line-height: 1.4;
        }

        .no-print-bar {
            width: 80mm;
            margin: 0 auto 15px auto;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: #fff;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .receipt-container {
                box-shadow: none;
                width: 100%;
                max-width: 80mm;
                padding: 5px 0;
            }
        }
    </style>
</head>
<body>

    <!-- On-Screen Action Bar -->
    <div class="no-print-bar">
        <a href="/index.php?route=pos" class="btn btn-secondary">&larr; Rudi POS</a>
        <button onclick="window.print();" class="btn btn-primary">&#128438; Chapisha Risiti</button>
    </div>

    <div class="receipt-container">
        <!-- Business Header -->
        <div class="receipt-header">
            <h2><?= htmlspecialchars($settings['business_name']) ?></h2>
            <p><?= htmlspecialchars($settings['business_tagline']) ?></p>
            <p><?= htmlspecialchars($settings['business_address']) ?></p>
            <p>Simu: <?= htmlspecialchars($settings['business_phone']) ?></p>
        </div>

        <!-- Meta Details -->
        <table class="meta-table">
            <tr>
                <td>Ankara #: <strong><?= htmlspecialchars($sale['invoice_no']) ?></strong></td>
                <td class="text-end"><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
            </tr>
            <tr>
                <td>Cashier: <?= htmlspecialchars($sale['cashier_name'] ?? 'Staff') ?></td>
                <td class="text-end">Njia: <strong><?= htmlspecialchars($sale['payment_method']) ?></strong></td>
            </tr>
            <tr>
                <td colspan="2">Mteja: <strong><?= htmlspecialchars($sale['customer_name'] ?? 'Mteja wa Kawaida (Walk-in)') ?></strong></td>
            </tr>
            <?php if (!empty($sale['customer_phone'])): ?>
                <tr>
                    <td colspan="2">Simu ya Mteja: <?= htmlspecialchars($sale['customer_phone']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- Purchased Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Kipuri</th>
                    <th class="text-center" style="width: 15%;">Idd</th>
                    <th class="text-end" style="width: 35%;">Jumla (TSh)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td colspan="3" class="fw-bold" style="padding-top: 4px;">
                            <?= htmlspecialchars($it['product_name'] ?? $it['live_product_name'] ?? 'Kipuri') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="tiny" style="color: #444;">
                            @ <?= number_format((float)$it['unit_price'], 2) ?>
                            <?php if ((float)$it['discount_per_unit'] > 0): ?>
                                <span style="color: #c00;">(-<?= number_format((float)$it['discount_per_unit'], 2) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $it['quantity'] ?></td>
                        <td class="text-end fw-bold font-monospace"><?= number_format((float)$it['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Table -->
        <table class="totals-table">
            <tr>
                <td>Jumla Ndogo (Subtotal):</td>
                <td class="text-end font-monospace"><?= number_format((float)$sale['subtotal'], 2) ?></td>
            </tr>
            <?php if ((float)$sale['discount_amount'] > 0): ?>
                <tr>
                    <td>Punguzo (Discount):</td>
                    <td class="text-end font-monospace" style="color: #c00;">-<?= number_format((float)$sale['discount_amount'], 2) ?></td>
                </tr>
            <?php endif; ?>
            <tr class="fw-bold" style="font-size: 13px;">
                <td style="padding: 4px 0;">JUMLA KUU:</td>
                <td class="text-end font-monospace" style="padding: 4px 0;"><?= Helper::formatCurrency((float)$sale['total_amount']) ?></td>
            </tr>
            <tr>
                <td>Kiasi Kilicholipwa:</td>
                <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$sale['paid_amount']) ?></td>
            </tr>
            <?php 
                $change = max(0.00, (float)$sale['paid_amount'] - (float)$sale['total_amount']);
                $balanceDue = (float)$sale['balance_due'];
            ?>
            <?php if ($change > 0): ?>
                <tr class="fw-bold">
                    <td>Chenji (Change):</td>
                    <td class="text-end font-monospace"><?= Helper::formatCurrency($change) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($balanceDue > 0): ?>
                <tr class="fw-bold" style="color: #c00;">
                    <td>Salio Linalodaiwa (Deni):</td>
                    <td class="text-end font-monospace"><?= Helper::formatCurrency($balanceDue) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- Footer Note -->
        <div class="receipt-footer">
            <p><?= htmlspecialchars($settings['receipt_footer']) ?></p>
            <p class="tiny mt-1" style="color: #666; margin-top: 4px;">Imetolewa kiotomatiki na mfumo wa NGENZI MOTO</p>
        </div>
    </div>

    <!-- Auto Print Script on load if query param print=1 is present -->
    <script>
        if (window.location.search.includes('autoprint=1')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
