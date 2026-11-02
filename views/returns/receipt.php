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
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 8px;
            font-size: 10px;
            line-height: 1.4;
        }

        .print-btn-bar {
            width: 80mm;
            margin: 0 auto 15px auto;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 8px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary { background: #0d6efd; color: #fff; border: 1px solid #0d6efd; }
        .btn-secondary { background: #6c757d; color: #fff; border: 1px solid #6c757d; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .print-btn-bar {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-bar">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Chapa Risiti (Print)</button>
        <button onclick="window.close()" class="btn btn-secondary">Funga</button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <h2>NGENZI MOTO SPARE</h2>
            <p class="fw-bold">PIKIPIKI & BAJAJI SPARE PARTS</p>
            <p>Machinga Complex / Ilala, Dar es Salaam</p>
            <p>Simu: +255 712 345 678 / +255 754 000 111</p>
            <p class="fw-bold" style="margin-top: 4px; font-size: 13px;">RISITI YA MAREJESHO (RETURN)</p>
        </div>

        <!-- Meta -->
        <table class="meta-table">
            <tr>
                <td style="width: 45%;">Namba ya Rejesho:</td>
                <td class="fw-bold text-end"><?= htmlspecialchars($return['return_number']) ?></td>
            </tr>
            <tr>
                <td>Ankara ya Mauzo:</td>
                <td class="fw-bold text-end">#<?= htmlspecialchars($return['invoice_no']) ?></td>
            </tr>
            <tr>
                <td>Tarehe:</td>
                <td class="text-end"><?= date('d/m/Y', strtotime($return['return_date'])) ?></td>
            </tr>
            <tr>
                <td>Mteja:</td>
                <td class="text-end"><?= htmlspecialchars($return['customer_name'] ?? 'Mteja wa Kawaida') ?></td>
            </tr>
            <?php if (!empty($return['customer_phone'])): ?>
            <tr>
                <td>Simu ya Mteja:</td>
                <td class="text-end"><?= htmlspecialchars($return['customer_phone']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Sababu Kuu:</td>
                <td class="text-end"><?= htmlspecialchars(str_replace('_', ' ', $return['return_type'])) ?></td>
            </tr>
            <tr>
                <td>Imehudumiwa na:</td>
                <td class="text-end"><?= htmlspecialchars($return['created_by_name'] ?? 'Staff') ?></td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">KIPURI</th>
                    <th class="text-center" style="width: 15%;">IDADI</th>
                    <th class="text-end" style="width: 35%;">THAMANI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td colspan="3" class="fw-bold" style="padding-top: 4px;">
                            <?= htmlspecialchars($it['product_name'] ?? 'Kipuri') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="tiny" style="color: #444;">
                            SKU: <?= htmlspecialchars($it['product_sku'] ?? '') ?> &bull; Hali: <?= htmlspecialchars($it['condition_status']) ?>
                        </td>
                        <td class="text-center">
                            <?= $it['quantity'] ?>
                        </td>
                        <td class="text-end fw-bold">
                            <?= Helper::formatCurrency((float)$it['subtotal']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Jumla ya Vipuri:</td>
                <td class="text-end fw-bold"><?= Helper::formatCurrency((float)$return['subtotal']) ?></td>
            </tr>
            <?php if ((float)$return['return_fee'] > 0): ?>
            <tr>
                <td>Tozo ya Rejesho (Fee):</td>
                <td class="text-end">- <?= Helper::formatCurrency((float)$return['return_fee']) ?></td>
            </tr>
            <?php endif; ?>
            <tr style="font-size: 13px; font-weight: bold;">
                <td style="padding-top: 4px;">KIASI KILICHOREJESHWA:</td>
                <td class="text-end" style="padding-top: 4px;"><?= Helper::formatCurrency((float)$return['refund_amount']) ?></td>
            </tr>
            <tr>
                <td>Njia ya Malipo:</td>
                <td class="text-end fw-bold"><?= htmlspecialchars($return['refund_method'] ?: 'Haijalipwa') ?></td>
            </tr>
            <?php if (!empty($return['refund_reference_no'])): ?>
            <tr>
                <td>Kumbukumbu ya Malipo:</td>
                <td class="text-end fw-bold"><?= htmlspecialchars($return['refund_reference_no']) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- Footer -->
        <div class="receipt-footer">
            <p class="fw-bold">*** ASANTE KWA USHIRIKIANO WAKO ***</p>
            <p>Bidhaa zilizopokelewa zimehakikiwa.</p>
            <p class="tiny" style="margin-top: 4px; color: #666;">
                Imechapa: <?= date('d/m/Y H:i:s') ?> &bull; Ngenzi Moto Spare Parts System
            </p>
        </div>
    </div>

</body>
</html>
