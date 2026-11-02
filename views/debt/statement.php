<?php
use App\Core\Helper;
use App\Core\Auth;

$cust = $statement['customer'];
$openingBal = (float)($statement['opening_balance'] ?? 0.00);
$totalDebits = (float)($statement['total_debits'] ?? 0.00);
$totalCredits = (float)($statement['total_credits'] ?? 0.00);
$closingBal = (float)($statement['closing_balance'] ?? 0.00);
$transactions = $statement['transactions'] ?? [];
?>
<?php if (!empty($isPrint)): ?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            color: #212529;
            background: #fff;
            padding: 20px;
        }
        .statement-card {
            max-width: 900px;
            margin: 0 auto;
        }
        .table-sm th, .table-sm td {
            padding: 6px 8px;
        }
        .debit-val { color: #b02a37; }
        .credit-val { color: #146c43; font-weight: 600; }
        .balance-col { font-weight: 700; }
        .company-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .stamp-box {
            border: 2px dashed #adb5bd;
            border-radius: 6px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .statement-card { max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print();">
<div class="statement-card">
<?php else: ?>
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/index.php?route=debts">Madeni ya Wateja</a></li>
                <li class="breadcrumb-item"><a href="/index.php?route=debt_customer_profile&id=<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Taarifa ya Akaunti (Statement)</li>
            </ol>
        </nav>
        <div class="d-flex gap-2">
            <a href="/index.php?route=debt_customer_profile&id=<?= $cust['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Rudi Wasifuni
            </a>
            <a href="/index.php?route=debt_collect&customer_id=<?= $cust['id'] ?>" class="btn btn-success btn-sm">
                <i class="fas fa-hand-holding-usd me-1"></i> Pokea Malipo
            </a>
            <a href="/index.php?route=debt_statement&customer_id=<?= $cust['id'] ?>&start_date=<?= urlencode($statement['start_date']) ?>&end_date=<?= urlencode($statement['end_date']) ?>&print=1" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-print me-1"></i> Chapisha / Print
            </a>
        </div>
    </div>

    <!-- Date Filter Form -->
    <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body py-3">
            <form method="GET" action="/index.php" class="row g-3 align-items-end">
                <input type="hidden" name="route" value="debt_statement">
                <input type="hidden" name="customer_id" value="<?= $cust['id'] ?>">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Kuanzia Tarehe (From):</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($statement['start_date']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Mpaka Tarehe (To):</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($statement['end_date']) ?>" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i> Chuja Taarifa
                    </button>
                    <a href="/index.php?route=debt_statement&customer_id=<?= $cust['id'] ?>&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">
                        Mwezi Huu
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statement Document Sheet -->
    <div class="card shadow border-0 mb-5">
        <div class="card-body p-4 p-md-5">
<?php endif; ?>

            <!-- Company & Statement Header -->
            <div class="row company-header pb-3 mb-4">
                <div class="col-sm-7">
                    <h3 class="fw-bold text-primary mb-1">
                        <i class="fas fa-motorcycle me-2 text-danger"></i><?= htmlspecialchars($settings['business_name']) ?>
                    </h3>
                    <div class="text-muted small mb-1"><?= htmlspecialchars($settings['business_tagline']) ?></div>
                    <div class="small text-secondary">
                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> <?= htmlspecialchars($settings['business_address']) ?><br>
                        <i class="fas fa-phone-alt me-1 text-success"></i> <?= htmlspecialchars($settings['business_phone']) ?> | 
                        <i class="fas fa-envelope me-1 text-info"></i> <?= htmlspecialchars($settings['business_email']) ?>
                    </div>
                </div>
                <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                    <div class="badge bg-danger text-white fs-6 mb-2 px-3 py-2 text-uppercase">
                        Taarifa ya Akaunti ya Mteja
                    </div>
                    <div class="text-muted small"><strong>Tarehe ya Kuchapishwa:</strong> <?= date('d/m/Y H:i') ?></div>
                    <div class="text-muted small"><strong>Kipindi:</strong> <?= date('d/m/Y', strtotime($statement['start_date'])) ?> hadi <?= date('d/m/Y', strtotime($statement['end_date'])) ?></div>
                </div>
            </div>

            <!-- Customer & Account Details Grid -->
            <div class="row g-3 mb-4 p-3 bg-light rounded border">
                <div class="col-md-6 border-end">
                    <h6 class="text-uppercase text-muted fw-bold mb-2 small"><i class="fas fa-user me-1 text-primary"></i> Taarifa za Mteja (Customer Information)</h6>
                    <table class="table table-sm table-borderless mb-0 small">
                        <tr>
                            <td class="text-muted" style="width: 140px;">Jina la Mteja:</td>
                            <td class="fw-bold fs-6"><?= htmlspecialchars($cust['name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Namba ya Mteja:</td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($cust['customer_code']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Namba ya Simu:</td>
                            <td class="fw-semibold text-primary"><?= htmlspecialchars($cust['phone'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Eneo / Anuani:</td>
                            <td><?= htmlspecialchars($cust['area'] ?: ($cust['address'] ?: 'Dar es Salaam')) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 ps-md-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-2 small"><i class="fas fa-wallet me-1 text-success"></i> Muhtasari wa Mkopo (Credit Terms & Status)</h6>
                    <table class="table table-sm table-borderless mb-0 small">
                        <tr>
                            <td class="text-muted" style="width: 150px;">Ukomo wa Mkopo (Limit):</td>
                            <td class="fw-bold">TSh <?= number_format($cust['credit_limit'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Muda wa Kulipa:</td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cust['payment_terms'] ?? '30_days'))) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Hali ya Mkopo:</td>
                            <td>
                                <?php if (($cust['credit_status'] ?? 'active') === 'blocked'): ?>
                                    <span class="badge bg-danger">Umezuiwa (Blocked)</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Unaruhusiwa (Active)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Salio Lililopo Sasa:</td>
                            <td class="fw-bold text-danger fs-6">TSh <?= number_format($cust['current_balance'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-dark text-center small">
                        <tr>
                            <th style="width: 105px;">Tarehe</th>
                            <th>Maelezo ya Muamala</th>
                            <th style="width: 130px;">Kumbukumbu #</th>
                            <th style="width: 130px;" class="text-end">DEBIT (Mauzo/Deni +)</th>
                            <th style="width: 130px;" class="text-end">CREDIT (Malipo -)</th>
                            <th style="width: 140px;" class="text-end">Salio la Deni (TSh)</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <!-- Opening Balance Row -->
                        <tr class="table-secondary fw-bold">
                            <td class="text-center"><?= date('d/m/Y', strtotime($statement['start_date'])) ?></td>
                            <td colspan="2">
                                <i class="fas fa-door-open me-1 text-secondary"></i> Salio Lililofunguliwa (Opening Balance kabla ya tarehe <?= date('d/m/Y', strtotime($statement['start_date'])) ?>)
                            </td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end text-dark font-monospace"><?= number_format($openingBal, 2) ?></td>
                        </tr>

                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-1"></i> Hakuna miamala yoyote (mauzo wala malipo) iliyofanyika katika kipindi hiki cha tarehe.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td class="text-center font-monospace"><?= date('d/m/Y', strtotime($tx['tx_date'])) ?></td>
                                    <td>
                                        <?php if ($tx['tx_type'] === 'debit'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">Mauzo</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1">Malipo</span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($tx['description']) ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?php if (empty($isPrint)): ?>
                                            <?php if ($tx['tx_type'] === 'debit'): ?>
                                                <a href="/index.php?route=sale_show&id=<?= $tx['ref_id'] ?>" target="_blank" class="text-decoration-none fw-semibold">
                                                    <?= htmlspecialchars($tx['reference']) ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="/index.php?route=payment_receipt&id=<?= $tx['ref_id'] ?>" target="_blank" class="text-decoration-none fw-semibold">
                                                    <?= htmlspecialchars($tx['reference']) ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($tx['reference']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end debit-val font-monospace">
                                        <?= ((float)$tx['debit'] > 0) ? number_format($tx['debit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end credit-val font-monospace">
                                        <?= ((float)$tx['credit'] > 0) ? number_format($tx['credit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end balance-col font-monospace text-dark">
                                        <?= number_format($tx['balance'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="3" class="text-end text-uppercase">Jumla ya Miamala ya Kipindi Hiki:</td>
                            <td class="text-end debit-val font-monospace">TSh <?= number_format($totalDebits, 2) ?></td>
                            <td class="text-end credit-val font-monospace">TSh <?= number_format($totalCredits, 2) ?></td>
                            <td class="text-end text-muted">—</td>
                        </tr>
                        <tr class="table-warning fs-6">
                            <td colspan="4" class="text-end text-uppercase fw-bold">
                                Salio la Mwisho Linalodaiwa (Closing Balance kufikia <?= date('d/m/Y', strtotime($statement['end_date'])) ?>):
                            </td>
                            <td colspan="2" class="text-end fw-bold text-danger font-monospace fs-5">
                                TSh <?= number_format($closingBal, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Financial Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border bg-light text-center py-2">
                        <span class="text-muted small">Salio la Mwanzo</span>
                        <span class="fw-bold font-monospace">TSh <?= number_format($openingBal, 2) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-danger-subtle bg-danger-subtle text-center py-2">
                        <span class="text-danger small fw-bold">Jumla ya Mauzo (Debits)</span>
                        <span class="fw-bold text-danger font-monospace">+ TSh <?= number_format($totalDebits, 2) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-success-subtle bg-success-subtle text-center py-2">
                        <span class="text-success small fw-bold">Jumla ya Malipo (Credits)</span>
                        <span class="fw-bold text-success font-monospace">- TSh <?= number_format($totalCredits, 2) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-danger bg-danger text-white text-center py-2 shadow-sm">
                        <span class="small fw-bold text-white-50">Deni Lililobaki (Closing)</span>
                        <span class="fw-bold font-monospace fs-6 text-white">TSh <?= number_format($closingBal, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Signatures & Stamp Block -->
            <div class="row mt-5 pt-3 border-top g-4">
                <div class="col-4 text-center">
                    <div class="text-muted small mb-4">Imeandaliwa na:</div>
                    <div class="border-bottom border-dark mx-auto" style="width: 80%;"></div>
                    <div class="small fw-bold mt-1"><?= htmlspecialchars(Auth::user()['full_name'] ?? 'Mweka Hazina / Cashier') ?></div>
                    <div class="text-muted" style="font-size: 10px;">Idara ya Fedha & Uhasibu</div>
                </div>
                <div class="col-4 text-center">
                    <div class="text-muted small mb-4">Mteja / Mpokeaji:</div>
                    <div class="border-bottom border-dark mx-auto" style="width: 80%;"></div>
                    <div class="small fw-bold mt-1"><?= htmlspecialchars($cust['name']) ?></div>
                    <div class="text-muted" style="font-size: 10px;">Sahihi & Tarehe</div>
                </div>
                <div class="col-4">
                    <div class="stamp-box">
                        <i class="fas fa-stamp me-2"></i> Muhuri Rasmi wa Kampuni
                    </div>
                </div>
            </div>

            <!-- Notice / Notes -->
            <div class="mt-4 pt-2 text-center text-muted" style="font-size: 11px;">
                <p class="mb-0">
                    Kumbuka: Taarifa hii ya fedha ni halali na inatambuliwa na <strong><?= htmlspecialchars($settings['business_name']) ?></strong>.
                    Kwa maswali yoyote au marekebisho ya rekodi, tafadhali wasiliana nasi ndani ya siku 7 tangu tarehe ya kutolewa.
                </p>
            </div>

<?php if (!empty($isPrint)): ?>
</div>
</body>
</html>
<?php else: ?>
        </div>
    </div>
</div>
<?php endif; ?>
