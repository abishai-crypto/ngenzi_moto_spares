<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-wrench-adjustable text-primary me-2"></i>Orodha ya Mafundi (Mechanics Directory)
        </h4>
        <p class="text-muted small mb-0">Usimamizi wa mafundi wa pikipiki na bajaji, gereji wanazofanyia kazi, na utaalamu wao.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="/index.php?route=mechanic_create" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> Sajili Fundi Mpya
            </a>
        <?php endif; ?>
        <a href="/index.php?route=customers" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-people"></i> Wateja
        </a>
        <a href="/index.php?route=garages" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-building"></i> Gereji
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="mechanics">

            <!-- Search -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Fundi (Jina, Simu, Kodi, Eneo)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Mf. Peter, 071122, MECH-000001..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Garage Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Gereji Anayofanyia Kazi</label>
                <select name="garage_id" class="form-select form-select-sm">
                    <option value="">-- Gereji Zote --</option>
                    <?php foreach ($garages as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($filters['garage_id'] == $g['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['garage_name']) ?> (<?= htmlspecialchars($g['location']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Specialization Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Utaalamu (Specialization)</label>
                <input type="text" name="specialization" class="form-control form-control-sm" placeholder="Mf. Boxer, Engine, Wiring..." value="<?= htmlspecialchars($filters['specialization'] ?? '') ?>">
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Hadhi (Status)</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Hai (Active)</option>
                    <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-1 col-md-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Chuja">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="/index.php?route=mechanics" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Mechanics Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-wrench text-primary me-2"></i>Mafundi Waliosajiliwa (Jumla: <?= number_format($total) ?>)
        </h6>
        <span class="badge bg-light text-muted border">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($mechanics)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-wrench-adjustable fs-1 d-block mb-2 text-secondary"></i>
                Hakuna fundi aliyepatikana kulingana na vigezo vilivyochaguliwa.
                <?php if ($canCreate): ?>
                    <div class="mt-3">
                        <a href="/index.php?route=mechanic_create" class="btn btn-sm btn-primary-custom">
                            <i class="bi bi-plus-lg me-1"></i> Sajili Fundi Mpya Sasa
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kodi ya Fundi</th>
                            <th>Jina Kamili</th>
                            <th>Nambari ya Simu</th>
                            <th>Gereji Anayofanyia Kazi</th>
                            <th>Utaalamu (Specialization)</th>
                            <th>Eneo</th>
                            <th class="text-center">Mauzo</th>
                            <th class="text-center">Hadhi</th>
                            <th class="text-end">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mechanics as $m): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        <?= htmlspecialchars($m['mechanic_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/index.php?route=mechanic_show&id=<?= $m['id'] ?>" class="fw-semibold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($m['name']) ?>
                                    </a>
                                    <?php if (!empty($m['customer_code'])): ?>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            <i class="bi bi-person-check text-success me-1"></i>Akaunti ya Mteja: 
                                            <a href="/index.php?route=customer_show&id=<?= $m['customer_id'] ?>" class="text-decoration-none">
                                                <code><?= htmlspecialchars($m['customer_code']) ?></code>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="tel:<?= htmlspecialchars($m['phone']) ?>" class="text-decoration-none text-dark small fw-medium">
                                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($m['phone']) ?>
                                    </a>
                                    <?php if (!empty($m['alternative_phone'])): ?>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Simu 2: <?= htmlspecialchars($m['alternative_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['garage_name'])): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                            <i class="bi bi-building me-1"></i><?= htmlspecialchars($m['garage_name']) ?>
                                        </span>
                                        <div class="small text-muted" style="font-size: 11px;"><?= htmlspecialchars($m['garage_location']) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Fundi Huru (Bila Gereji)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-dark fw-medium">
                                        <?= htmlspecialchars($m['specialization'] ?: 'Ufundi wa Jumla') ?>
                                    </span>
                                    <?php if (!empty($m['years_of_experience'])): ?>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            Uzoefu: <?= $m['years_of_experience'] ?> miaka
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($m['area'] ?: '--') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark border rounded-pill px-2">
                                        <?= (int)$m['total_sales_count'] ?> ankara
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($m['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hai (Active)</span>
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
                                                <a class="dropdown-item py-2 small" href="/index.php?route=mechanic_show&id=<?= $m['id'] ?>">
                                                    <i class="bi bi-eye text-primary me-2"></i> Tazama Wasifu
                                                </a>
                                            </li>
                                            <?php if ($canEdit): ?>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="/index.php?route=mechanic_edit&id=<?= $m['id'] ?>">
                                                        <i class="bi bi-pencil text-warning me-2"></i> Hariri Fundi
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($canDeactivate): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form method="POST" action="/index.php?route=mechanic_toggle_status" onsubmit="return confirm('Badilisha hadhi ya fundi huyu?');">
                                                        <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= $m['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                        <button type="submit" class="dropdown-item py-2 small <?= $m['status'] === 'active' ? 'text-danger' : 'text-success' ?>">
                                                            <i class="bi <?= $m['status'] === 'active' ? 'bi-pause-circle text-danger' : 'bi-play-circle text-success' ?> me-2"></i>
                                                            <?= $m['status'] === 'active' ? 'Sitisha Fundi' : 'Wezesha Fundi' ?>
                                                        </button>
                                                    </form>
                                                </li>
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
                    <span class="small text-muted">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></span>
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
