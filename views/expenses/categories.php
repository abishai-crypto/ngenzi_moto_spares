<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-tags text-primary me-2"></i>Kategoria za Matumizi (Expense Categories)
        </h4>
        <p class="text-muted small mb-0">Usimamizi wa vyanzo na makundi ya matumizi ya biashara</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=expenses" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Matumizi
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('expenses.create')): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="bi bi-plus-circle me-1"></i> Kategoria Mpya
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="expenses/categories">

            <div class="col-md-6 col-sm-8">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tafuta jina au kodi ya kategoria..." 
                           value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
            </div>

            <div class="col-md-3 col-sm-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hali Zote --</option>
                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Inafanya Kazi (Active)</option>
                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Imesitishwa (Inactive)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-funnel me-1"></i> Chuja</button>
                <a href="/index.php?route=expenses/categories" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Jina la Kategoria</th>
                        <th>Kodi</th>
                        <th>Maelezo</th>
                        <th class="text-center">Matumizi Yaliyopo</th>
                        <th class="text-end">Jumla Iliyotumika (TSh)</th>
                        <th class="text-center">Hali</th>
                        <th class="text-end pe-3">Vitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna kategoria za matumizi zilizopatikana.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $index + 1 ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($cat['name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= htmlspecialchars($cat['code'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                        <?= number_format($cat['total_expenses_count']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    <?= number_format($cat['total_amount_spent'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($cat['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Inatumika</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Imesitishwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('expenses.edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $cat['id'] ?>" title="Hariri">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/index.php?route=expenses/categories/toggle" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kubadilisha hali ya kategoria hii?');">
                                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= ($cat['status'] === 'active') ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= ($cat['status'] === 'active') ? 'Sitisha' : 'Washa' ?>">
                                                <i class="bi <?= ($cat['status'] === 'active') ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal for each category -->
                            <div class="modal fade" id="editModal<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="/index.php?route=expenses/categories/update" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1"></i> Hariri Kategoria</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Jina la Kategoria <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Kodi ya Kategoria (Code)</label>
                                                    <input type="text" name="code" class="form-control font-monospace" value="<?= htmlspecialchars($cat['code'] ?? '') ?>" placeholder="Mf. RENT, TRANS, ELEC">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Maelezo</label>
                                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Hali</label>
                                                    <select name="status" class="form-select">
                                                        <option value="active" <?= ($cat['status'] === 'active') ? 'selected' : '' ?>>Inafanya Kazi (Active)</option>
                                                        <option value="inactive" <?= ($cat['status'] === 'inactive') ? 'selected' : '' ?>>Imesitishwa (Inactive)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Funga</button>
                                                <button type="submit" class="btn btn-primary fw-semibold">Hifadhi Mabadiliko</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/index.php?route=expenses/categories/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle me-1"></i> Ongeza Kategoria Mpya ya Matumizi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jina la Kategoria <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Mf. Transport & Logistics (Usafiri)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kodi ya Kategoria (Code)</label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="Mf. TRANS">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Maelezo</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Maelezo mafupi kuhusu matumizi ya kategoria hii..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Unda Kategoria</button>
                </div>
            </form>
        </div>
    </div>
</div>
