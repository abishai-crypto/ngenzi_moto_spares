<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-counterclockwise text-primary me-2"></i>Orodha ya Marejesho (Returns Directory)
        </h4>
        <p class="text-muted small mb-0">
            Jumla ya marejesho: <strong><?= number_format($totalReturns) ?></strong> &bull; Ukurasa <?= $currentPage ?> kati ya <?= $totalPages ?>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=returns_dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=return_reports" class="btn btn-outline-info btn-sm">
            <i class="bi bi-graph-up-arrow me-1"></i> Ripoti & Return Rate
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'cashier']) || Auth::hasPermission('returns.create')): ?>
            <a href="/index.php?route=return_create" class="btn btn-primary-custom btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Anzisha Rejesho Jipya
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="returns">

            <div class="col-md-3 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tafuta Rejesho, Ankara, Mteja..." 
                           value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hadhi Zote (All Status) --</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Rasimu (Draft)</option>
                    <option value="requested" <?= $filters['status'] === 'requested' ? 'selected' : '' ?>>Inasubiri Idhini (Requested)</option>
                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Imeidhinishwa (Approved)</option>
                    <option value="received" <?= $filters['status'] === 'received' ? 'selected' : '' ?>>Imepokelewa Stoo (Received)</option>
                    <option value="refunded" <?= $filters['status'] === 'refunded' ? 'selected' : '' ?>>Imelipwa (Refunded)</option>
                    <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Imekamilika (Completed)</option>
                    <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Imekataliwa (Rejected)</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Imeghairiwa (Cancelled)</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="return_type" class="form-select form-select-sm">
                    <option value="">-- Aina / Sababu Zote --</option>
                    <?php foreach ($returnReasons as $rsn): ?>
                        <option value="<?= htmlspecialchars(trim($rsn)) ?>" <?= $filters['return_type'] === trim($rsn) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(str_replace('_', ' ', trim($rsn))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <input type="date" name="date_from" class="form-control form-control-sm" title="Kuanzia Tarehe" 
                       value="<?= htmlspecialchars($filters['date_from']) ?>" placeholder="Kuanzia Tarehe">
            </div>

            <div class="col-md-2 col-sm-6">
                <input type="date" name="date_to" class="form-control form-control-sm" title="Hadi Tarehe" 
                       value="<?= htmlspecialchars($filters['date_to']) ?>" placeholder="Hadi Tarehe">
            </div>

            <div class="col-md-1 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Chuja">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="/index.php?route=returns" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Returns Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (empty($returns)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                Hakuna rekodi za marejesho zilizopatikana kwa vigezo hivi.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Nambari ya Rejesho</th>
                            <th>Ankara ya Mauzo</th>
                            <th>Mteja</th>
                            <th>Tarehe</th>
                            <th>Sababu / Aina</th>
                            <th class="text-center">Vipuri</th>
                            <th class="text-end">Thamani ya Rejesho</th>
                            <th class="text-center">Hali ya Malipo</th>
                            <th class="text-center">Hadhi (Status)</th>
                            <th class="text-end pe-3">Kitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $r): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">
                                    <a href="/index.php?route=return_show&id=<?= $r['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($r['return_number']) ?>
                                    </a>
                                </td>
                                <td class="font-monospace small">
                                    <a href="/index.php?route=sale_show&id=<?= $r['sale_id'] ?>" class="text-decoration-none">
                                        #<?= htmlspecialchars($r['invoice_no']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($r['customer_name'] ?? 'Mteja wa Kawaida') ?>
                                    <?php if (!empty($r['customer_code'])): ?>
                                        <small class="text-muted font-monospace d-block" style="font-size: 11px;"><?= htmlspecialchars($r['customer_code']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d/m/Y', strtotime($r['return_date'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small">
                                        <?= htmlspecialchars(str_replace('_', ' ', $r['return_type'])) ?>
                                    </span>
                                </td>
                                <td class="text-center font-monospace small fw-semibold">
                                    <?= $r['total_units_returned'] ?> pcs (<?= $r['items_count'] ?>)
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    <?= Helper::formatCurrency((float)$r['refund_amount']) ?>
                                    <?php if ((float)$r['return_fee'] > 0): ?>
                                        <small class="text-muted d-block" style="font-size: 10px;">Tozo: <?= Helper::formatCurrency((float)$r['return_fee']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['refund_status'] === 'refunded'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small">Imerudishwa</span>
                                    <?php elseif ($r['refund_status'] === 'debt_reduced'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle small">Deni Limepunguzwa</span>
                                    <?php elseif ($r['refund_status'] === 'credit_issued'): ?>
                                        <span class="badge bg-purple-subtle text-purple border small" style="background:#f3e8fd;color:#6f42c1;">Credit Note</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle small">Haijalipwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badges = [
                                        'draft'     => 'bg-secondary-subtle text-secondary border',
                                        'requested' => 'bg-warning-subtle text-dark border border-warning',
                                        'approved'  => 'bg-info-subtle text-info border border-info',
                                        'received'  => 'bg-primary-subtle text-primary border border-primary',
                                        'refunded'  => 'bg-success-subtle text-success border border-success',
                                        'completed' => 'bg-success text-white',
                                        'rejected'  => 'bg-danger-subtle text-danger border border-danger',
                                        'cancelled' => 'bg-danger text-white'
                                    ];
                                    $badgeClass = $badges[$r['status']] ?? 'bg-light text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> text-uppercase" style="font-size: 11px;">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/index.php?route=return_show&id=<?= $r['id'] ?>" class="btn btn-outline-primary" title="Tazama">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/index.php?route=return_receipt&id=<?= $r['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Risiti (Thermal)">
                                            <i class="bi bi-printer"></i>
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
            <small class="text-muted">Ukurasa <strong><?= $currentPage ?></strong> kati ya <strong><?= $totalPages ?></strong></small>
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/index.php?route=returns&page=<?= $currentPage - 1 ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&return_type=<?= urlencode($filters['return_type']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>">
                                Kabla
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="/index.php?route=returns&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&return_type=<?= urlencode($filters['return_type']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/index.php?route=returns&page=<?= $currentPage + 1 ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&return_type=<?= urlencode($filters['return_type']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>">
                                Mbele
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
