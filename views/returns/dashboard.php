<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-counterclockwise text-primary me-2"></i>Dashibodi ya Marejesho (Returns & Refunds)
        </h4>
        <p class="text-muted small mb-0">
            Usimamizi kamili wa bidhaa zilizorudishwa, uhakiki wa stoo na marejesho ya fedha kwa wateja
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=returns" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-task me-1"></i> Orodha ya Marejesho
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

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Marejesho ya Leo</span>
                    <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                        <?= number_format($stats['returns_today_count']) ?>
                    </h3>
                    <small class="text-muted">Thamani: <?= Helper::formatCurrency((float)$stats['returns_today_value']) ?></small>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Marejesho ya Wiki Hii</span>
                    <h3 class="fw-bold mb-0 text-info font-monospace mt-1">
                        <?= number_format($stats['returns_week_count']) ?>
                    </h3>
                    <small class="text-muted">Thamani: <?= Helper::formatCurrency((float)$stats['returns_week_value']) ?></small>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3">
                    <i class="bi bi-calendar-week fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Marejesho ya Mwezi</span>
                    <h3 class="fw-bold mb-0 text-purple font-monospace mt-1" style="color: #6f42c1;">
                        <?= number_format($stats['returns_month_count']) ?>
                    </h3>
                    <small class="text-muted">Thamani: <?= Helper::formatCurrency((float)$stats['returns_month_value']) ?></small>
                </div>
                <div class="rounded-circle p-3" style="background-color: #f3e8fd; color: #6f42c1;">
                    <i class="bi bi-calendar3 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Jumla ya Thamani Iliyorejeshwa</span>
                    <h3 class="fw-bold mb-0 text-danger font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$stats['total_returned_value']) ?>
                    </h3>
                    <small class="text-muted">Jumla ya vipande: <?= number_format($stats['total_returned_units']) ?></small>
                </div>
                <div class="rounded-circle bg-danger-subtle text-danger p-3">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Queues & Quality Breakdown Cards -->
<div class="row g-4 mb-4">
    <!-- Workflow Queue Status -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-hourglass-split text-warning me-2"></i>Foleni za Hatua za Marejesho (Workflow Queues)
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-warning-subtle rounded-3 text-center border border-warning-subtle">
                            <span class="small fw-semibold text-warning-emphasis d-block mb-1">Inasubiri Idhini (Requested)</span>
                            <h2 class="fw-bold mb-0 text-warning-emphasis font-monospace"><?= (int)$stats['pending_approval_count'] ?></h2>
                            <small class="text-muted">Inahitaji idhini ya Meneja</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-info-subtle rounded-3 text-center border border-info-subtle">
                            <span class="small fw-semibold text-info-emphasis d-block mb-1">Inasubiri Stoo (Approved)</span>
                            <h2 class="fw-bold mb-0 text-info-emphasis font-monospace"><?= (int)$stats['approved_pending_restock_count'] ?></h2>
                            <small class="text-muted">Storekeeper kupokea</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-primary-subtle rounded-3 text-center border border-primary-subtle">
                            <span class="small fw-semibold text-primary d-block mb-1">Inasubiri Malipo (Pending Refund)</span>
                            <h2 class="fw-bold mb-0 text-primary font-monospace"><?= (int)$stats['pending_refund_count'] ?></h2>
                            <small class="text-muted">Pesa/Deni kurejeshwa</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-success-subtle rounded-3 text-center border border-success-subtle">
                            <span class="small fw-semibold text-success d-block mb-1">Zilizokamilika (Completed)</span>
                            <h2 class="fw-bold mb-0 text-success font-monospace"><?= (int)$stats['completed_count'] ?></h2>
                            <small class="text-muted">Zimefungwa kikamilifu</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Condition Breakdown -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-shield-check text-primary me-2"></i>Uchambuzi wa Hali ya Vipuri Vilivyorejeshwa
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> Inayouzika Tena (Resellable - Imeongezwa Stoo)</span>
                        <span class="font-monospace fw-bold text-success"><?= number_format($stats['resellable_units']) ?> pcs</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <?php $rPct = ($stats['total_returned_units'] > 0) ? ($stats['resellable_units'] / $stats['total_returned_units']) * 100 : 0; ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rPct ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Vyenye Hitilafu/Kuharibika (Damaged)</span>
                        <span class="font-monospace fw-bold text-danger"><?= number_format($stats['damaged_units']) ?> pcs</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <?php $dPct = ($stats['total_returned_units'] > 0) ? ($stats['damaged_units'] / $stats['total_returned_units']) * 100 : 0; ?>
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $dPct ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span><i class="bi bi-x-octagon-fill text-warning me-1"></i> Vibovu / Hitilafu ya Kiwanda (Defective)</span>
                        <span class="font-monospace fw-bold text-warning"><?= number_format($stats['defective_units']) ?> pcs</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <?php $dfPct = ($stats['total_returned_units'] > 0) ? ($stats['defective_units'] / $stats['total_returned_units']) * 100 : 0; ?>
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $dfPct ?>%"></div>
                    </div>
                </div>

                <div class="p-2 bg-light rounded text-muted small mt-3">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    <strong>Kumbuka:</strong> Vipuri vilivyo <em>Resellable</em> pekee ndivyo vinavyoongezwa kwenye hesabu ya stoo ya kawaida. Vipuri <em>Damaged</em> au <em>Defective</em> hurekodiwa bila kuingizwa stoo ya kuuza.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Approvals & Recent Returns Tables -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i>Marejesho ya Hivi Karibuni
                </h6>
                <a href="/index.php?route=returns" class="btn btn-sm btn-link text-decoration-none">Tazama Yote &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentReturns)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                        Hakuna marejesho yaliyorekodiwa hivi karibuni.
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
                                    <th class="text-end">Kiasi cha Fedha</th>
                                    <th class="text-center">Hadhi (Status)</th>
                                    <th class="text-end pe-3">Kitendo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReturns as $r): ?>
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
                                            <a href="/index.php?route=return_show&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                                <i class="bi bi-eye"></i> Fungua
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
