<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-buildings text-primary me-2"></i>Gereji & Karakana (Garages & Workshops)
        </h4>
        <p class="text-muted small mb-0">Usimamizi wa gereji za pikipiki na bajaji, wamiliki wao, maeneo yao, na mafundi walioajiriwa.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="/index.php?route=garage_create" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> Sajili Gereji Mpya
            </a>
        <?php endif; ?>
        <a href="/index.php?route=mechanics" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-wrench"></i> Mafundi
        </a>
        <a href="/index.php?route=customers" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-people"></i> Wateja
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="garages">

            <!-- Search -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Gereji (Jina, Kodi, Mmiliki, Simu, Mahali)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Mf. Golani, GAR-000001, 0712..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Garage Type Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Aina ya Karakana</label>
                <select name="garage_type" class="form-select form-select-sm">
                    <option value="">-- Aina Zote --</option>
                    <option value="Motorcycle Garage" <?= ($filters['garage_type'] === 'Motorcycle Garage') ? 'selected' : '' ?>>Gereji ya Pikipiki (Motorcycle)</option>
                    <option value="Bajaj Garage" <?= ($filters['garage_type'] === 'Bajaj Garage') ? 'selected' : '' ?>>Gereji ya Bajaji (Bajaj/Tricycle)</option>
                    <option value="Mixed Workshop" <?= ($filters['garage_type'] === 'Mixed Workshop') ? 'selected' : '' ?>>Karakana ya Pamoja (Mixed)</option>
                    <option value="Electrical Workshop" <?= ($filters['garage_type'] === 'Electrical Workshop') ? 'selected' : '' ?>>Karakana ya Umeme (Auto-Electrical)</option>
                    <option value="Engine Workshop" <?= ($filters['garage_type'] === 'Engine Workshop') ? 'selected' : '' ?>>Karakana ya Injini (Engine Overhaul)</option>
                </select>
            </div>

            <!-- Area Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Eneo / Mahali</label>
                <input type="text" name="area" class="form-control form-control-sm" placeholder="Mf. Kariakoo, Ilala..." value="<?= htmlspecialchars($filters['area'] ?? '') ?>">
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Hadhi</label>
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
                <a href="/index.php?route=garages" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Garages Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-building text-primary me-2"></i>Gereji Zote (Jumla: <?= number_format($total) ?>)
        </h6>
        <span class="badge bg-light text-muted border">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($garages)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-buildings fs-1 d-block mb-2 text-secondary"></i>
                Hakuna gereji iliyopatikana kulingana na vigezo vilivyochaguliwa.
                <?php if ($canCreate): ?>
                    <div class="mt-3">
                        <a href="/index.php?route=garage_create" class="btn btn-sm btn-primary-custom">
                            <i class="bi bi-plus-lg me-1"></i> Sajili Gereji Mpya Sasa
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kodi ya Gereji</th>
                            <th>Jina la Gereji</th>
                            <th>Mmiliki / Mhusika</th>
                            <th>Mawasiliano</th>
                            <th>Mahali / Eneo</th>
                            <th>Aina ya Gereji</th>
                            <th class="text-center">Mafundi</th>
                            <th class="text-center">Hadhi</th>
                            <th class="text-end">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($garages as $g): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        <?= htmlspecialchars($g['garage_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/index.php?route=garage_show&id=<?= $g['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($g['garage_name']) ?>
                                    </a>
                                    <?php if (!empty($g['customer_code'])): ?>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            <i class="bi bi-person-check text-success me-1"></i>Mteja: 
                                            <a href="/index.php?route=customer_show&id=<?= $g['customer_id'] ?>" class="text-decoration-none">
                                                <code><?= htmlspecialchars($g['customer_code']) ?></code>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small fw-semibold text-dark">
                                        <?= htmlspecialchars($g['owner_name'] ?: '--') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="tel:<?= htmlspecialchars($g['phone']) ?>" class="text-decoration-none text-dark small fw-medium">
                                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($g['phone']) ?>
                                    </a>
                                    <?php if (!empty($g['alternative_phone'])): ?>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Simu 2: <?= htmlspecialchars($g['alternative_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($g['location']) ?>
                                    <?php if (!empty($g['area']) && $g['area'] !== $g['location']): ?>
                                        (<?= htmlspecialchars($g['area']) ?>)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($g['garage_type'] ?: ($g['specialization'] ?: 'Gereji ya Kawaida')) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-wrench me-1"></i><?= (int)$g['mechanics_count'] ?> Mafundi
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($g['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hai (Active)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted border px-2 py-1">Imezimwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Chaguo
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item py-2 small" href="/index.php?route=garage_show&id=<?= $g['id'] ?>">
                                                    <i class="bi bi-eye text-primary me-2"></i> Tazama Wasifu
                                                </a>
                                            </li>
                                            <?php if ($canEdit): ?>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="/index.php?route=garage_edit&id=<?= $g['id'] ?>">
                                                        <i class="bi bi-pencil text-warning me-2"></i> Hariri Gereji
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($canDeactivate): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form method="POST" action="/index.php?route=garage_toggle_status" onsubmit="return confirm('Badilisha hadhi ya gereji hii?');">
                                                        <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= $g['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                        <button type="submit" class="dropdown-item py-2 small <?= $g['status'] === 'active' ? 'text-danger' : 'text-success' ?>">
                                                            <i class="bi <?= $g['status'] === 'active' ? 'bi-pause-circle text-danger' : 'bi-play-circle text-success' ?> me-2"></i>
                                                            <?= $g['status'] === 'active' ? 'Sitisha Gereji' : 'Wezesha Gereji' ?>
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
