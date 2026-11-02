<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Ripoti ya Marejesho & Return Rate % (Returns Report)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla Iliyorejeshwa: <strong class="text-danger">TSh <?= number_format($sums['total_refunded'], 2) ?></strong>
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
            <input type="hidden" name="route" value="reports/returns">

            <div class="col-md-3 col-sm-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hadhi Zote --</option>
                    <option value="approved" <?= ($filters['status'] === 'approved') ? 'selected' : '' ?>>Imeidhinishwa</option>
                    <option value="requested" <?= ($filters['status'] === 'requested') ? 'selected' : '' ?>>Inasubiri Idhini</option>
                    <option value="refunded" <?= ($filters['status'] === 'refunded') ? 'selected' : '' ?>>Imelipwa</option>
                    <option value="rejected" <?= ($filters['status'] === 'rejected') ? 'selected' : '' ?>>Imekataliwa</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>

            <div class="col-md-3 col-sm-6 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                <a href="/index.php?route=reports/returns" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabs: Return Transactions vs Return Rate -->
<ul class="nav nav-tabs nav-fill mb-4" id="returnsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="trans-tab" data-bs-toggle="tab" data-bs-target="#transPane" type="button" role="tab">
            <i class="bi bi-receipt me-1"></i> Orodha ya Marejesho (Transactions)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="rate-tab" data-bs-toggle="tab" data-bs-target="#ratePane" type="button" role="tab">
            <i class="bi bi-percent me-1"></i> Kiwango cha Marejesho kwa Bidhaa (Return Rate %)
        </button>
    </li>
</ul>

<div class="tab-content" id="returnsTabsContent">
    <!-- 1. RETURN TRANSACTIONS TAB -->
    <div class="tab-pane fade show active" id="transPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tarehe</th>
                                <th>Namba ya Rejesho</th>
                                <th>Ankara ya Awali</th>
                                <th>Mteja</th>
                                <th>Sababu</th>
                                <th class="text-center">Vipuri</th>
                                <th class="text-end">Kiasi cha Fedha (TSh)</th>
                                <th class="text-center">Njia</th>
                                <th class="text-end pe-3">Hadhi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($returns)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">Hakuna marejesho katika kipindi hiki.</td></tr>
                            <?php else: ?>
                                <?php foreach ($returns as $r): ?>
                                    <tr>
                                        <td class="ps-3 text-nowrap"><?= date('d/m/Y H:i', strtotime($r['return_date'])) ?></td>
                                        <td><span class="badge bg-light text-danger border font-monospace"><?= htmlspecialchars($r['return_number']) ?></span></td>
                                        <td><span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($r['invoice_no']) ?></span></td>
                                        <td><?= htmlspecialchars($r['customer_name'] ?? 'Walk-in') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($r['reason']) ?></span></td>
                                        <td class="text-center fw-bold"><?= number_format($r['total_qty_returned']) ?> pcs</td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($r['refund_amount'], 2) ?></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['refund_method'] ?? 'N/A') ?></span></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success text-white px-2 py-1"><?= ucfirst($r['status']) ?></span>
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
                                        <a class="page-link" href="/index.php?route=reports/returns&page=<?= $i ?>&status=<?= urlencode($filters['status']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. RETURN RATE % TAB -->
    <div class="tab-pane fade" id="ratePane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-down text-danger me-2"></i>Kiwango cha Marejesho kwa Kila Kipuri (Return Rate %)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th>Kategoria</th>
                                <th class="text-center">Vilivyouzwa</th>
                                <th class="text-center">Vilivyorejeshwa</th>
                                <th class="text-end pe-3">Kiwango cha Kurejesha (Return Rate %)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($returnRates)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Hakuna taarifa za marejesho.</td></tr>
                            <?php else: ?>
                                <?php foreach ($returnRates as $rr): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($rr['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($rr['sku']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($rr['category_name'] ?? '—') ?></td>
                                        <td class="text-center fw-semibold"><?= number_format($rr['total_sold_qty']) ?> pcs</td>
                                        <td class="text-center fw-bold text-danger"><?= number_format($rr['total_returned_qty']) ?> pcs</td>
                                        <td class="text-end pe-3">
                                            <span class="badge <?= ($rr['return_rate'] > 10) ? 'bg-danger text-white' : (($rr['return_rate'] > 0) ? 'bg-warning text-dark' : 'bg-success text-white') ?> fw-bold px-2 py-1">
                                                <?= number_format($rr['return_rate'], 2) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
