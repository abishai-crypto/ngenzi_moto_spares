<?php
use App\Core\Helper;
use App\Core\Auth;

$canSeeFinancials = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-people-fill text-primary me-2"></i>Dashibodi ya Wateja, Mafundi & Gereji
        </h4>
        <p class="text-muted small mb-0">Usimamizi thabiti wa wateja wa rejareja, mafundi, karakana/gereji na vyombo vyao.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="/index.php?route=customer_create" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-person-plus-fill"></i> Sajili Mteja Mpya
            </a>
        <?php endif; ?>
        <a href="/index.php?route=customers" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-person-lines-fill"></i> Orodha ya Wateja
        </a>
        <a href="/index.php?route=mechanics" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-wrench-adjustable"></i> Mafundi
        </a>
        <a href="/index.php?route=garages" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-building"></i> Gereji / Karakana
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Jumla ya Wateja</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['total_customers']) ?></h3>
                    <span class="text-success small fw-semibold">
                        <i class="bi bi-check-circle me-1"></i><?= number_format($stats['active_customers']) ?> Wateja Hai
                    </span>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-4">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Mafundi (Mechanics)</span>
                    <h3 class="fw-bold mb-0 mt-1 text-info"><?= number_format($stats['mechanics_count']) ?></h3>
                    <a href="/index.php?route=mechanics" class="text-decoration-none small text-info fw-semibold">
                        Tazama Orodha &rarr;
                    </a>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3 fs-4">
                    <i class="bi bi-wrench"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Gereji & Karakana</span>
                    <h3 class="fw-bold mb-0 mt-1 text-warning"><?= number_format($stats['garages_count']) ?></h3>
                    <a href="/index.php?route=garages" class="text-decoration-none small text-warning fw-semibold">
                        Tazama Gereji &rarr;
                    </a>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3 fs-4">
                    <i class="bi bi-buildings"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Wateja Wapya Mwezi Huu</span>
                    <h3 class="fw-bold mb-0 mt-1 text-success">+<?= number_format($stats['new_this_month']) ?></h3>
                    <span class="text-muted small">Usajili wa mwezi huu</span>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3 fs-4">
                    <i class="bi bi-person-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canSeeFinancials): ?>
<!-- Financial Debt Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-danger">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Wateja Wenye Madeni (Customers with Balance)</span>
                    <h4 class="fw-bold text-danger mt-1 mb-0"><?= number_format($stats['with_debt_count']) ?> Mteja</h4>
                    <span class="text-muted small">Wateja waliobakiwa na salio la mauzo</span>
                </div>
                <div class="rounded-circle bg-danger-subtle text-danger p-3 fs-4">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Jumla ya Madeni ya Wateja (Total Debt)</span>
                    <h4 class="fw-bold text-primary mt-1 mb-0"><?= Helper::formatMoney($stats['total_debt_amount']) ?></h4>
                    <span class="text-muted small">Madai yanayosubiri kukusanywa</span>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-4">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Customers Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Wateja Waliosajiliwa Hivi Karibuni (Recent Customers)
        </h6>
        <a href="/index.php?route=customers" class="btn btn-sm btn-light border">Tazama Wote &rarr;</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($stats['recent_customers'])): ?>
            <div class="p-4 text-center text-muted">Bado hakuna wateja waliosajiliwa kwenye mfumo.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kodi ya Mteja</th>
                            <th>Jina Kamili</th>
                            <th>Nambari ya Simu</th>
                            <th>Aina ya Mteja</th>
                            <th>Eneo (Area)</th>
                            <th class="text-center">Vyombo</th>
                            <th class="text-center">Hadhi</th>
                            <th class="text-end">Kitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_customers'] as $rc): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        <?= htmlspecialchars($rc['customer_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/index.php?route=customer_show&id=<?= $rc['id'] ?>" class="fw-semibold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($rc['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($rc['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($rc['phone']) ?>" class="text-decoration-none text-muted small">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($rc['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeColors = [
                                        'mechanic'   => 'bg-info-subtle text-info border border-info-subtle',
                                        'garage'     => 'bg-warning-subtle text-dark border border-warning-subtle',
                                        'business'   => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'walk_in'    => 'bg-secondary-subtle text-secondary border',
                                        'individual' => 'bg-light text-dark border',
                                        'retail'     => 'bg-light text-dark border'
                                    ];
                                    $badgeCls = $typeColors[$rc['customer_type']] ?? 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?= $badgeCls ?> text-capitalize">
                                        <?= htmlspecialchars($rc['customer_type']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($rc['area'] ?: ($rc['address'] ?: 'Dar es Salaam')) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark border rounded-pill px-2">
                                        <i class="bi bi-bicycle me-1"></i><?= (int)$rc['vehicle_count'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($rc['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hai (Active)</span>
                                    <?php elseif ($rc['status'] === 'blocked'): ?>
                                        <span class="badge bg-danger text-white px-2 py-1">Amezuiwa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted border px-2 py-1">Amesitishwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="/index.php?route=customer_show&id=<?= $rc['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;">
                                        Tazama Wasifu
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
