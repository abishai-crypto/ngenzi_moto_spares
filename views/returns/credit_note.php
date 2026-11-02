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
        <a href="/index.php?route=return_show&id=<?= $return['id'] ?>" class="btn btn-outline-secondary btn-sm">
            &larr; Rudi kwenye Rejesho
        </a>
        <div>
            <a href="/index.php?route=return_receipt&id=<?= $return['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                Risiti ya Mashine (Thermal)
            </a>
            <button onclick="window.print();" class="btn btn-primary btn-sm">
                🖨️ Chapisha Credit Note (Print A4)
            </button>
        </div>
    </div>

    <div class="invoice-box">
        <!-- Top Header -->
        <div class="row align-items-center mb-4">
            <div class="col-7">
                <div class="invoice-logo mb-1">
                    NGENZI MOTO SPARE PARTS
                </div>
                <p class="text-muted small mb-0">Wauzaji wa Vipuri Halisi vya Pikipiki na Bajaji</p>
                <p class="text-muted small mb-0">Machinga Complex, Ilala, Dar es Salaam, Tanzania</p>
                <p class="text-muted small mb-0">Simu: +255 712 345 678 &bull; Barua Pepe: sales@ngenzimoto.co.tz</p>
            </div>
            <div class="col-5 text-end">
                <h3 class="fw-bold text-danger text-uppercase mb-1">CREDIT NOTE</h3>
                <h5 class="fw-bold font-monospace text-dark mb-1">#<?= htmlspecialchars($return['return_number']) ?></h5>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 font-monospace text-uppercase">
                    <?= htmlspecialchars($return['status']) ?>
                </span>
            </div>
        </div>

        <hr class="my-3">

        <!-- Invoice & Customer Info -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="fw-bold text-muted small text-uppercase mb-2">HATI YA MAREJESHO KWA:</h6>
                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($return['customer_name'] ?? 'Mteja wa Kawaida (Walk-in)') ?></div>
                <?php if (!empty($return['customer_code'])): ?>
                    <div class="small text-muted font-monospace">Kodi ya Mteja: <?= htmlspecialchars($return['customer_code']) ?></div>
                <?php endif; ?>
                <?php if (!empty($return['customer_phone'])): ?>
                    <div class="small text-muted">Simu: <?= htmlspecialchars($return['customer_phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <table class="table table-sm table-borderless d-inline-block text-start" style="width: auto;">
                    <tr>
                        <td class="text-muted small py-1">Tarehe ya Rejesho:</td>
                        <td class="fw-bold py-1"><?= date('d/m/Y', strtotime($return['return_date'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small py-1">Ankara ya Mauzo:</td>
                        <td class="font-monospace fw-bold py-1">#<?= htmlspecialchars($return['invoice_no']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small py-1">Aina ya Rejesho:</td>
                        <td class="py-1"><span class="badge bg-light text-dark border"><?= htmlspecialchars(str_replace('_', ' ', $return['return_type'])) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small py-1">Afisa Aliyehudumia:</td>
                        <td class="py-1"><?= htmlspecialchars($return['created_by_name'] ?? 'Staff') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-invoice table-bordered mb-4">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Maelezo ya Kipuri (Product Description)</th>
                    <th class="text-center" style="width: 12%;">Idadi</th>
                    <th class="text-end" style="width: 18%;">Bei ya Rejesho</th>
                    <th class="text-end" style="width: 20%;">Jumla Ndogo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-muted small font-monospace"><?= $idx + 1 ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($it['product_name'] ?? 'Kipuri') ?></div>
                            <small class="text-muted font-monospace">
                                SKU: <?= htmlspecialchars($it['product_sku'] ?? 'N/A') ?> 
                                &bull; Hali: <span class="badge bg-light text-dark border py-0"><?= htmlspecialchars($it['condition_status']) ?></span>
                            </small>
                        </td>
                        <td class="text-center font-monospace fw-bold"><?= $it['quantity'] ?> <?= htmlspecialchars($it['unit_name'] ?? 'Pcs') ?></td>
                        <td class="text-end font-monospace"><?= Helper::formatCurrency((float)$it['unit_refund_price']) ?></td>
                        <td class="text-end font-monospace fw-bold"><?= Helper::formatCurrency((float)$it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end text-muted small fw-semibold">Jumla ya Thamani ya Vipuri:</td>
                    <td class="text-end font-monospace fw-bold"><?= Helper::formatCurrency((float)$return['subtotal']) ?></td>
                </tr>
                <?php if ((float)$return['return_fee'] > 0): ?>
                    <tr>
                        <td colspan="4" class="text-end text-danger small fw-semibold">Tozo ya Kurudisha (Restocking Fee):</td>
                        <td class="text-end font-monospace text-danger">- <?= Helper::formatCurrency((float)$return['return_fee']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-bold text-dark fs-6">JUMLA KUU INAYOREJESHWA (NET REFUND):</td>
                    <td class="text-end font-monospace fw-bold text-primary fs-5"><?= Helper::formatCurrency((float)$return['refund_amount']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Settlement & Refund Status -->
        <div class="row g-3 mb-4">
            <div class="col-8">
                <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold small text-muted text-uppercase mb-1">Taarifa za Malipo & Makazi (Settlement Details)</h6>
                    <div class="small">
                        <strong>Njia ya Marejesho:</strong> <?= htmlspecialchars($return['refund_method'] ?: 'Inasubiri Malipo') ?>
                        <?php if (!empty($return['refund_provider'])): ?> (<?= htmlspecialchars($return['refund_provider']) ?>)<?php endif; ?><br>
                        <?php if (!empty($return['refund_reference_no'])): ?>
                            <strong>Nambari ya Kumbukumbu ya Malipo:</strong> <span class="font-monospace text-success fw-bold"><?= htmlspecialchars($return['refund_reference_no']) ?></span><br>
                        <?php endif; ?>
                        <strong>Hadhi ya Malipo:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($return['refund_status']) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-4 text-center d-flex flex-column justify-content-end">
                <div class="border-top pt-2">
                    <small class="text-muted d-block mb-4">Sahihi ya Mpokeaji / Mteja</small>
                    <small class="text-muted">Tarehe: ___________________</small>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="border-top pt-3 text-center text-muted small">
            <p class="mb-0">Hati hii inathibitisha kupokelewa na kurekodiwa kwa bidhaa zilizorudishwa kwa mujibu wa vigezo na masharti ya Ngenzi Moto Spare Parts.</p>
        </div>
    </div>

</body>
</html>
