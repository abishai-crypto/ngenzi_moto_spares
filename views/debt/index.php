<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-bank2 text-danger me-2"></i>Dashibodi ya Madeni ya Wateja (Debt Management)
        </h4>
        <p class="text-muted small mb-0">Fuatilia wateja wanaodaiwa, madeni yaliyoiva, uzeekaji wa madeni na makusanyo.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=debt_aging" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-hourglass-split"></i> Ripoti ya Uzeekaji (Aging)
        </a>
        <a href="/index.php?route=debt_collect" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-cash-coin"></i> Pokea Malipo ya Deni
        </a>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <!-- Total Outstanding Debt -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Jumla ya Madeni Yote</span>
                    <h3 class="fw-bold mb-0 text-danger font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$stats['total_outstanding_debt']) ?>
                    </h3>
                    <small class="text-muted"><?= $stats['total_debtors_count'] ?> wateja wanaodaiwa</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Debt -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Yaliyopitiliza (Overdue)</span>
                    <h3 class="fw-bold mb-0 text-warning font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$stats['total_overdue_debt']) ?>
                    </h3>
                    <small class="text-muted">Muda wa malipo umepita</small>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Today / 7 Days -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Yanaiva Siku 7 Hizi</span>
                    <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$stats['due_in_7_days_amount']) ?>
                    </h3>
                    <small class="text-muted">Leo: <?= Helper::formatCurrency((float)$stats['due_today_amount']) ?></small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-calendar-event fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue > 30 Days -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Zaidi ya Siku 30</span>
                    <h3 class="fw-bold mb-0 text-dark font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$stats['overdue_over_30_days_amount']) ?>
                    </h3>
                    <small class="text-muted">Tahadhari kali ya madeni</small>
                </div>
                <div class="bg-dark bg-opacity-10 p-3 rounded-circle text-dark">
                    <i class="bi bi-shield-exclamation fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debtors Directory Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person-lines-fill text-danger me-2"></i>Orodha ya Wateja Wanaodaiwa (Debtors Directory)
        </h6>
        <form method="GET" action="/index.php" class="d-flex gap-2">
            <input type="hidden" name="route" value="debts">
            <div class="input-group input-group-sm" style="max-width: 300px;">
                <input type="text" name="search" class="form-control" placeholder="Tafuta jina au simu..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (empty($debtors)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle-fill text-success display-4"></i>
                <p class="text-muted mt-2 mb-0">Hakuna wateja wenye madeni yanayodaiwa kwa sasa!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Kodi ya Mteja</th>
                            <th>Jina la Mteja</th>
                            <th>Simu</th>
                            <th class="text-end">Ukomo (Limit)</th>
                            <th class="text-end">Deni Lililopo</th>
                            <th class="text-end">Mkopo Uliobaki</th>
                            <th class="text-center">Hali ya Uzeekaji</th>
                            <th class="text-end pe-3">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debtors as $d): ?>
                            <?php 
                                $totDebt = (float)$d['total_debt'];
                                $limit = (float)$d['credit_limit'];
                                $avail = max(0.00, $limit - $totDebt);
                                $hasOverdue = ((float)$d['overdue_1_7'] > 0 || (float)$d['overdue_8_30'] > 0 || (float)$d['overdue_31_60'] > 0 || (float)$d['overdue_61_90'] > 0 || (float)$d['overdue_90_plus'] > 0);
                            ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="/index.php?route=debt_customer_profile&id=<?= $d['customer_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($d['customer_code']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['customer_name']) ?></div>
                                    <small class="text-muted">Masharti: <?= htmlspecialchars($d['payment_terms'] ?? '30 days') ?></small>
                                </td>
                                <td class="font-monospace small">
                                    <?= htmlspecialchars($d['customer_phone'] ?? '-') ?>
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    <?= Helper::formatCurrency($limit) ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-danger">
                                    <?= Helper::formatCurrency($totDebt) ?>
                                </td>
                                <td class="text-end font-monospace <?= ($avail <= 0) ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= Helper::formatCurrency($avail) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasOverdue): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-clock-history me-1"></i> Limepitiliza
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check2 me-1"></i> Halijapita
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/index.php?route=debt_collect&customer_id=<?= $d['customer_id'] ?>" class="btn btn-primary-custom" title="Pokea Malipo">
                                            <i class="bi bi-cash me-1"></i> Lipa
                                        </a>
                                        <a href="/index.php?route=debt_customer_profile&id=<?= $d['customer_id'] ?>" class="btn btn-outline-secondary" title="Wasifu wa Deni">
                                            <i class="bi bi-file-earmark-person"></i>
                                        </a>
                                        <a href="/index.php?route=debt_statement&customer_id=<?= $d['customer_id'] ?>" class="btn btn-outline-info" title="Taarifa ya Akaunti (Statement)">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
