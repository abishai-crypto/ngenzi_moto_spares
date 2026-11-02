<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-receipt text-primary me-2"></i>Historia ya Mauzo (Sales History)
        </h4>
        <p class="text-muted small mb-0">Tazama ankara zilizotolewa, hali ya malipo, risiti na ripoti za kila siku.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=pos" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-cart-plus"></i> Mashine ya POS
        </a>
        <a href="/index.php?route=sales_daily_summary" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-graph-up"></i> Ripoti ya Kila Siku
        </a>
    </div>
</div>

<!-- KPI Cards Row (Today) -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Mauzo ya Leo</span>
                    <h4 class="fw-bold mb-0 text-primary font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$todaySummary['total_revenue']) ?>
                    </h4>
                    <small class="text-muted"><?= $todaySummary['total_sales_count'] ?> ankara zilizokamilika</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Pesa Zilizopokelewa</span>
                    <h4 class="fw-bold mb-0 text-success font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$todaySummary['total_paid']) ?>
                    </h4>
                    <small class="text-muted">Fedha taslimu & Simu</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Madeni ya Leo (Credit)</span>
                    <h4 class="fw-bold mb-0 text-danger font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$todaySummary['total_balance_due']) ?>
                    </h4>
                    <small class="text-muted"><?= $todaySummary['credit_sales_count'] ?> mauzo ya mkopo</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                    <i class="bi bi-credit-card-2-front fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Faida ya Leo (Margin)</span>
                    <h4 class="fw-bold mb-0 <?= $canViewProfit ? 'text-info' : 'text-muted' ?> font-monospace mt-1">
                        <?= $canViewProfit ? Helper::formatCurrency((float)$todaySummary['total_gross_profit']) : '***' ?>
                    </h4>
                    <small class="text-muted"><?= $canViewProfit ? "Margin: {$todaySummary['margin_percent']}%" : 'Imezuiwa kwa Cashier' ?></small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                    <i class="bi bi-pie-chart fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="sales">

            <!-- Search -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Ankara / Mteja</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="INV-000001, Simu, Jina..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Date From -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-muted mb-1">Kuanzia Tarehe</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>

            <!-- Date To -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-muted mb-1">Mpaka Tarehe</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>

            <!-- Payment Status -->
            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small fw-semibold text-muted mb-1">Hali ya Malipo</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <option value="paid" <?= ($filters['payment_status'] === 'paid') ? 'selected' : '' ?>>Imelipwa Kamili</option>
                    <option value="partially_paid" <?= ($filters['payment_status'] === 'partially_paid') ? 'selected' : '' ?>>Nusu Malipo</option>
                    <option value="credit" <?= ($filters['payment_status'] === 'credit') ? 'selected' : '' ?>>Mkopo (Credit)</option>
                    <option value="unpaid" <?= ($filters['payment_status'] === 'unpaid') ? 'selected' : '' ?>>Haijalipwa</option>
                </select>
            </div>

            <!-- Sale Status -->
            <div class="col-lg-1 col-md-4 col-6">
                <label class="form-label small fw-semibold text-muted mb-1">Hali</label>
                <select name="sale_status" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <option value="completed" <?= ($filters['sale_status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= ($filters['sale_status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-lg-2 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm flex-fill">
                    <i class="bi bi-funnel me-1"></i> Chuja
                </button>
                <a href="/index.php?route=sales" class="btn btn-outline-secondary btn-sm" title="Safisha">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Sales Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-table text-primary me-2"></i>Orodha ya Mauzo (Jumla: <?= number_format($totalSales) ?>)
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sales)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-receipt-cutoff display-6 text-secondary d-block mb-2"></i>
                <p class="mb-1">Hakuna ankara ya mauzo iliyopatikana kwa vigezo hivi.</p>
                <a href="/index.php?route=pos" class="btn btn-primary-custom btn-sm mt-2">
                    <i class="bi bi-cart-plus me-1"></i> Fanya Mauzo Mpya (POS)
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-muted small">
                            <th class="ps-3">Ankara #</th>
                            <th>Tarehe & Saa</th>
                            <th>Mteja</th>
                            <th>Muuzaji (Cashier)</th>
                            <th class="text-end">Jumla (Total)</th>
                            <th class="text-end">Iliyolipwa</th>
                            <th class="text-end">Deni (Balance)</th>
                            <th>Njia</th>
                            <th>Hali</th>
                            <th class="text-end pe-3">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td class="ps-3 font-monospace small fw-bold text-primary">
                                    <a href="/index.php?route=sale_show&id=<?= $s['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($s['invoice_no']) ?>
                                    </a>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($s['sale_date'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['customer_id'])): ?>
                                        <a href="/index.php?route=customer_show&id=<?= $s['customer_id'] ?>" class="text-dark fw-semibold text-decoration-none hover-primary">
                                            <?= htmlspecialchars($s['customer_name'] ?? 'Mteja') ?>
                                        </a>
                                        <?php if (!empty($s['customer_code'])): ?>
                                            <span class="badge bg-light text-muted font-monospace" style="font-size: 10px;">
                                                <?= htmlspecialchars($s['customer_code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Walk-in Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary">
                                    <?= htmlspecialchars($s['cashier_name'] ?? 'System') ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    <?= Helper::formatCurrency((float)$s['total_amount']) ?>
                                </td>
                                <td class="text-end font-monospace text-success small">
                                    <?= Helper::formatCurrency((float)$s['paid_amount']) ?>
                                </td>
                                <td class="text-end font-monospace <?= (float)$s['balance_due'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?> small">
                                    <?= (float)$s['balance_due'] > 0 ? Helper::formatCurrency((float)$s['balance_due']) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($s['payment_method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($s['sale_status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cancelled</span>
                                    <?php elseif ($s['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Imelipwa</span>
                                    <?php elseif ($s['payment_status'] === 'partially_paid'): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Nusu</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Mkopo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/index.php?route=sale_show&id=<?= $s['id'] ?>" class="btn btn-light border py-1 px-2" title="Angalia Taarifa">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        <a href="/index.php?route=sale_receipt&id=<?= $s['id'] ?>" target="_blank" class="btn btn-light border py-1 px-2" title="Risiti (Thermal)">
                                            <i class="bi bi-printer text-success"></i>
                                        </a>
                                        <a href="/index.php?route=sale_invoice&id=<?= $s['id'] ?>" target="_blank" class="btn btn-light border py-1 px-2" title="Ankara (Invoice)">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Ukurasa <?= $currentPage ?> kati ya <?= $totalPages ?></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="/index.php?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
