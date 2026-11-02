<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-bag-check-fill text-secondary me-2"></i>Ripoti ya Manunuzi ya Mzigo (Purchases & Suppliers)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Thamani: <strong class="text-primary">TSh <?= number_format($sums['total_purchased'], 2) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/purchases">

            <div class="col-md-4 col-sm-6">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">-- Wasambazaji Wote --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filters['supplier_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>

            <div class="col-md-2 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                <a href="/index.php?route=reports/purchases" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Purchases Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-ul me-2"></i>Orodha ya Ankara za Manunuzi</h6>
        <span class="badge bg-light text-dark border">Deni Lililobaki: TSh <?= number_format($sums['total_balance'], 2) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tarehe</th>
                        <th>Namba ya Oda</th>
                        <th>Msambazaji (Supplier)</th>
                        <th class="text-center">Aina za Vipuri</th>
                        <th class="text-center">Jumla ya Idadi</th>
                        <th class="text-end">Thamani ya Mzigo (TSh)</th>
                        <th class="text-end">Kilicholipwa</th>
                        <th class="text-end pe-3">Baki ya Deni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Hakuna manunuzi katika kipindi hiki.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $p): ?>
                            <tr>
                                <td class="ps-3 text-nowrap"><?= date('d/m/Y', strtotime($p['purchase_date'])) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($p['purchase_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['supplier_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['supplier_phone'] ?? '—') ?></small>
                                </td>
                                <td class="text-center"><?= number_format($p['products_count']) ?></td>
                                <td class="text-center fw-semibold"><?= number_format($p['total_quantity']) ?> pcs</td>
                                <td class="text-end fw-bold text-primary"><?= number_format($p['purchase_value'], 2) ?></td>
                                <td class="text-end text-success"><?= number_format($p['paid_amount'], 2) ?></td>
                                <td class="text-end pe-3 fw-bold <?= ($p['balance_due'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                    <?= number_format($p['balance_due'], 2) ?>
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
                                <a class="page-link" href="/index.php?route=reports/purchases&page=<?= $i ?>&supplier_id=<?= urlencode($filters['supplier_id']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Supplier Activity Summary -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white p-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-building me-2"></i>Muhtasari kwa Kila Msambazaji (Supplier Performance)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Msambazaji</th>
                        <th>Mawasiliano</th>
                        <th class="text-center">Jumla ya Oda</th>
                        <th class="text-end">Thamani ya Manunuzi (TSh)</th>
                        <th class="text-end">Iliyolipwa</th>
                        <th class="text-end pe-3">Deni Lililopo (TSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplierList)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Hakuna taarifa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supplierList as $s): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($s['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? '—') ?> (<?= htmlspecialchars($s['contact_person'] ?? '—') ?>)</td>
                                <td class="text-center fw-semibold"><?= number_format($s['purchases_count']) ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($s['total_purchase_value'], 2) ?></td>
                                <td class="text-end text-success"><?= number_format($s['total_paid'], 2) ?></td>
                                <td class="text-end pe-3 fw-bold <?= ($s['total_outstanding'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                    <?= number_format($s['total_outstanding'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
