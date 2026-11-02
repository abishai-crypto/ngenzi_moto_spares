<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-receipt text-primary me-2"></i>Ripoti ya Mauzo (Sales Report)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Ankara: <strong><?= number_format($total) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <a href="/index.php?route=reports/export&type=sales&period=<?= urlencode($range['period']) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Pakua CSV (Excel)
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/sales">

            <div class="col-md-3 col-sm-6">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Ankara, Mteja, Simu..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">-- Wateja Wote --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="cashier_id" class="form-select form-select-sm">
                    <option value="">-- Cashier Wote --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filters['cashier_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">-- Njia Zote --</option>
                    <option value="Cash" <?= ($filters['payment_method'] === 'Cash') ? 'selected' : '' ?>>Taslimu</option>
                    <option value="Mobile Money" <?= ($filters['payment_method'] === 'Mobile Money') ? 'selected' : '' ?>>Mitandao</option>
                    <option value="Bank" <?= ($filters['payment_method'] === 'Bank') ? 'selected' : '' ?>>Benki</option>
                    <option value="Card" <?= ($filters['payment_method'] === 'Card') ? 'selected' : '' ?>>Kadi</option>
                    <option value="Credit" <?= ($filters['payment_method'] === 'Credit') ? 'selected' : '' ?>>Mkopo</option>
                </select>
            </div>

            <div class="col-md-1 col-sm-6">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">-- Hali --</option>
                    <option value="paid" <?= ($filters['payment_status'] === 'paid') ? 'selected' : '' ?>>Imelipwa</option>
                    <option value="partially_paid" <?= ($filters['payment_status'] === 'partially_paid') ? 'selected' : '' ?>>Nusu</option>
                    <option value="credit" <?= ($filters['payment_status'] === 'credit') ? 'selected' : '' ?>>Mkopo</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                <a href="/index.php?route=reports/sales" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards for Filtered Results -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
            <span class="small text-muted">Mauzo Ghafi (Gross Sales)</span>
            <h5 class="fw-bold text-dark mb-0">TSh <?= number_format($sums['total_gross'], 2) ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
            <span class="small text-muted">Punguzo (Discounts)</span>
            <h5 class="fw-bold text-danger mb-0">TSh <?= number_format($sums['total_discounts'], 2) ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-primary-subtle text-primary border border-primary-subtle">
            <span class="small fw-semibold">Mauzo Halisi (Net Sales)</span>
            <h5 class="fw-bold mb-0">TSh <?= number_format($sums['total_net'], 2) ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
            <span class="small text-muted">Madeni Yaliyobaki (Credit Due)</span>
            <h5 class="fw-bold text-danger mb-0">TSh <?= number_format($sums['total_balance'], 2) ?></h5>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tarehe</th>
                        <th>Ankara (Invoice)</th>
                        <th>Mteja</th>
                        <th class="text-center">Vipuri</th>
                        <th class="text-end">Ghafi (TSh)</th>
                        <th class="text-end">Punguzo</th>
                        <th class="text-end">Halisi (TSh)</th>
                        <th class="text-end">Kilicholipwa</th>
                        <th>Njia</th>
                        <th>Cashier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna rekodi za mauzo zilizopatikana kwa vigezo hivi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td class="ps-3 text-nowrap"><?= date('d/m/Y', strtotime($s['sale_date'])) ?></td>
                                <td>
                                    <span class="badge bg-light text-primary border font-monospace">
                                        <?= htmlspecialchars($s['invoice_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($s['customer_name'] ?? 'Mteja wa Kawaida (Walk-in)') ?></div>
                                    <?php if (!empty($s['customer_phone'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($s['customer_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= number_format($s['items_count']) ?></span>
                                </td>
                                <td class="text-end"><?= number_format($s['gross_sales'], 2) ?></td>
                                <td class="text-end text-danger"><?= ($s['discount_amount'] > 0) ? '(' . number_format($s['discount_amount'], 2) . ')' : '—' ?></td>
                                <td class="text-end fw-bold text-dark"><?= number_format($s['net_sales'], 2) ?></td>
                                <td class="text-end text-success fw-semibold"><?= number_format($s['paid_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['payment_method']) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($s['cashier_name'] ?? 'System') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="small text-muted">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="/index.php?route=reports/sales&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&customer_id=<?= urlencode($filters['customer_id']) ?>&cashier_id=<?= urlencode($filters['cashier_id']) ?>&payment_method=<?= urlencode($filters['payment_method']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
