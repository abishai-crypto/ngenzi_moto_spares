<?php
use App\Core\Helper;
use App\Core\Auth;

$canSeeFinancials = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-people text-primary me-2"></i>Orodha ya Wateja (Customer Directory)
        </h4>
        <p class="text-muted small mb-0">Usimamizi wa wateja wote, kodi zao, nambari za mawasiliano, na vyombo vyao.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="/index.php?route=customer_create" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-person-plus-fill"></i> Sajili Mteja Mpya
            </a>
        <?php endif; ?>
        <a href="/index.php?route=customers_dashboard" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-graph-up"></i> Dashibodi
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="customers">

            <!-- Search -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Mteja (Jina, Nambari ya Simu, Kodi, Eneo)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Mf. John, 071234, CUS-000001, Kimara..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Customer Type Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Aina ya Mteja (Customer Type)</label>
                <select name="customer_type" class="form-select form-select-sm">
                    <option value="">-- Aina Zote --</option>
                    <option value="online" <?= ($filters['customer_type'] === 'online') ? 'selected' : '' ?>>Mteja wa Mtandaoni (Online Customer)</option>
                    <option value="individual" <?= ($filters['customer_type'] === 'individual') ? 'selected' : '' ?>>Mteja wa Kawaida (Individual)</option>
                    <option value="mechanic" <?= ($filters['customer_type'] === 'mechanic') ? 'selected' : '' ?>>Fundi Pikipiki/Bajaji (Mechanic)</option>
                    <option value="garage" <?= ($filters['customer_type'] === 'garage') ? 'selected' : '' ?>>Gereji / Karakana (Garage)</option>
                    <option value="business" <?= ($filters['customer_type'] === 'business') ? 'selected' : '' ?>>Biashara / Kampuni (Business)</option>
                    <option value="walk_in" <?= ($filters['customer_type'] === 'walk_in') ? 'selected' : '' ?>>Walk-in Customer</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Hadhi (Status)</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hadhi Zote --</option>
                    <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Hai (Active)</option>
                    <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                    <option value="blocked" <?= ($filters['status'] === 'blocked') ? 'selected' : '' ?>>Imezuiwa (Blocked)</option>
                </select>
            </div>

            <!-- Area Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Eneo (Area)</label>
                <input type="text" name="area" class="form-control form-control-sm" placeholder="Mf. Kariakoo, Temeke..." value="<?= htmlspecialchars($filters['area'] ?? '') ?>">
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-1 col-md-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Chuja Wateja">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="/index.php?route=customers" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people-fill text-primary me-2"></i>Wateja Wote (Jumla: <?= number_format($total) ?>)
        </h6>
        <span class="badge bg-light text-muted border">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($customers)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                Hakuna mteja aliyepatikana kulingana na vigezo ulivyoweka.
                <?php if ($canCreate): ?>
                    <div class="mt-3">
                        <a href="/index.php?route=customer_create" class="btn btn-sm btn-primary-custom">
                            <i class="bi bi-plus-lg me-1"></i> Sajili Mteja Mpya Sasa
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kodi ya Mteja</th>
                            <th>Jina Kamili</th>
                            <th>Mawasiliano</th>
                            <th>Aina ya Mteja</th>
                            <th>Eneo</th>
                            <th class="text-center">Vyombo</th>
                            <?php if ($canSeeFinancials): ?>
                                <th class="text-end">Jumla ya Mauzo</th>
                                <th class="text-end">Deni Lililopo</th>
                            <?php endif; ?>
                            <th class="text-center">Hadhi</th>
                            <th class="text-end">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        <?= htmlspecialchars($c['customer_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/index.php?route=customer_show&id=<?= $c['id'] ?>" class="fw-semibold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </a>
                                    <?php if (!empty($c['notes'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;">
                                            <i class="bi bi-chat-left-text me-1 text-secondary"></i><?= htmlspecialchars($c['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <?php if (!empty($c['phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($c['phone']) ?>" class="text-decoration-none text-dark small fw-medium">
                                                <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($c['phone']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">--</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($c['alternative_phone'])): ?>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Simu 2: <?= htmlspecialchars($c['alternative_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($c['email'])): ?>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($c['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeColors = [
                                        'online'     => 'bg-success-subtle text-success border border-success-subtle fw-bold',
                                        'mechanic'   => 'bg-info-subtle text-info border border-info-subtle',
                                        'garage'     => 'bg-warning-subtle text-dark border border-warning-subtle',
                                        'business'   => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'walk_in'    => 'bg-secondary-subtle text-secondary border',
                                        'individual' => 'bg-light text-dark border',
                                        'retail'     => 'bg-light text-dark border'
                                    ];
                                    $badgeCls = $typeColors[$c['customer_type']] ?? 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?= $badgeCls ?> text-capitalize">
                                        <?= $c['customer_type'] === 'online' ? '<i class="bi bi-globe me-1"></i>Mtandaoni' : htmlspecialchars($c['customer_type']) ?>
                                    </span>
                                    <?php if (!empty($c['is_online_registered']) && $c['customer_type'] !== 'online'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size:10px;">
                                            <i class="bi bi-globe"></i> Online
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($c['area'] ?: ($c['address'] ?: 'Dar es Salaam')) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark border rounded-pill px-2.5 py-1">
                                        <i class="bi bi-bicycle me-1 text-primary"></i><?= (int)$c['vehicle_count'] ?>
                                    </span>
                                </td>
                                <?php if ($canSeeFinancials): ?>
                                    <td class="text-end small fw-semibold text-dark">
                                        <?= Helper::formatMoney((float)$c['total_purchases_amount']) ?>
                                    </td>
                                    <td class="text-end small fw-bold <?= (float)$c['total_balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= (float)$c['total_balance_due'] > 0 ? Helper::formatMoney((float)$c['total_balance_due']) : 'TSh 0.00' ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <?php if ($c['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hai (Active)</span>
                                    <?php elseif ($c['status'] === 'blocked'): ?>
                                        <span class="badge bg-danger text-white px-2 py-1">Amezuiwa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted border px-2 py-1">Amesitishwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Chaguo
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item py-2 small" href="/index.php?route=customer_show&id=<?= $c['id'] ?>">
                                                    <i class="bi bi-eye text-primary me-2"></i> Tazama Wasifu
                                                </a>
                                            </li>
                                            <?php if ($canEdit): ?>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="/index.php?route=customer_edit&id=<?= $c['id'] ?>">
                                                        <i class="bi bi-pencil text-warning me-2"></i> Hariri Mteja
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($canDeactivate): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <?php if ($c['status'] === 'active'): ?>
                                                    <li>
                                                        <form method="POST" action="/index.php?route=customer_toggle_status" onsubmit="return confirm('Je, una uhakika unataka kumsitisha mteja huyu?');">
                                                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                            <input type="hidden" name="status" value="inactive">
                                                            <button type="submit" class="dropdown-item py-2 small text-danger">
                                                                <i class="bi bi-pause-circle me-2"></i> Sitisha Mteja
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form method="POST" action="/index.php?route=customer_toggle_status">
                                                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                            <input type="hidden" name="status" value="active">
                                                            <button type="submit" class="dropdown-item py-2 small text-success">
                                                                <i class="bi bi-play-circle me-2"></i> Wezesha Mteja (Active)
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
                    <span class="small text-muted">Ukurasa <?= $page ?> kati ya <?= $totalPages ?> (Jumla: <?= number_format($total) ?> wateja)</span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/index.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Nyuma</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="/index.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/index.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Mbele &raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
