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

        .receipt-title-box {
            text-align: center;
            margin: 8px 0;
            padding: 4px;
            background: #eee;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            border: 1px solid #ccc;
        }

        .meta-table {
            width: 100%;
            font-size: 11px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .amount-box {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px 0;
            margin: 8px 0;
            text-align: center;
        }

        .amount-box h3 {
            font-size: 18px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            line-height: 1.3;
            border-top: 1px dashed #000;
            padding-top: 8px;
        }

        .no-print-bar {
            width: 80mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary { background-color: #0d6efd; color: #fff; }
        .btn-secondary { background-color: #6c757d; color: #fff; }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print-bar { display: none !important; }
            .receipt-container { box-shadow: none; width: 100%; max-width: 80mm; padding: 5px 0; }
        }
    </style>
</head>
<body>

    <!-- Control Bar -->
    <div class="no-print-bar">
        <a href="/index.php?route=payments" class="btn btn-secondary">&larr; Rudi kwenye Malipo</a>
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

        <div class="receipt-title-box">
            RISITI YA MALIPO (PAYMENT RECEIPT)
        </div>

        <!-- Meta Information -->
        <table class="meta-table">
            <tr>
                <td>Risiti #: <strong><?= htmlspecialchars($payment['payment_no']) ?></strong></td>
                <td class="text-end"><?= date('d/m/Y H:i', strtotime($payment['payment_date'])) ?></td>
            </tr>
            <tr>
                <td>Ankara Ref #:</td>
                <td class="text-end"><strong><?= htmlspecialchars($payment['invoice_no']) ?></strong></td>
            </tr>
            <tr>
                <td colspan="2">Mteja: <strong><?= htmlspecialchars($payment['customer_name']) ?></strong></td>
            </tr>
            <?php if (!empty($payment['customer_phone'])): ?>
                <tr>
                    <td colspan="2">Simu: <?= htmlspecialchars($payment['customer_phone']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Njia ya Malipo:</td>
                <td class="text-end">
                    <strong><?= htmlspecialchars($payment['payment_method']) ?></strong>
                    <?= !empty($payment['provider']) ? '(' . htmlspecialchars($payment['provider']) . ')' : '' ?>
                </td>
            </tr>
            <?php if (!empty($payment['reference_no'])): ?>
                <tr>
                    <td>Kumbukumbu (Tx Ref):</td>
                    <td class="text-end font-monospace"><?= htmlspecialchars($payment['reference_no']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Mpokeaji (Cashier):</td>
                <td class="text-end"><?= htmlspecialchars($payment['received_by_name'] ?? 'Staff') ?></td>
            </tr>
        </table>

        <!-- Amount Box -->
        <div class="amount-box">
            <p class="small text-uppercase">Kiasi Kilichopokelewa:</p>
            <h3><?= Helper::formatCurrency((float)$payment['amount']) ?></h3>
        </div>

        <!-- Remaining Balance Summary -->
        <table class="meta-table">
            <tr>
                <td>Jumla ya Ankara:</td>
                <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$payment['sale_total_amount']) ?></td>
            </tr>
            <tr>
                <td>Jumla Iliyolipwa:</td>
                <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$payment['sale_paid_amount']) ?></td>
            </tr>
            <tr class="fw-bold" style="color: #c00;">
                <td>Salio Linalobaki:</td>
                <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$payment['sale_balance_due']) ?></td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="receipt-footer">
            <p><?= htmlspecialchars($settings['receipt_footer']) ?></p>
            <p class="tiny mt-1" style="color: #666; margin-top: 4px;">Imetolewa kiotomatiki na mfumo wa NGENZI MOTO</p>
        </div>
    </div>

    <script>
        if (window.location.search.includes('autoprint=1')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
