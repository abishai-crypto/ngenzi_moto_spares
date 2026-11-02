<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-tags-fill text-primary me-2"></i>Kategoria za Vipuri (Categories)</h4>
        <p class="text-muted small mb-0">Usimamizi wa makundi ya vipuri vya pikipiki na bajaji (Injini, Breki, Klachi, n.k.).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=products" class="btn btn-light border">
            <i class="bi bi-box-seam me-1"></i> Vipuri Vyote
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Sajili Kategoria Mpya</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Categories Table Card -->
<div class="premium-card">
    <div class="premium-card-header d-flex justify-content-between align-items-center">
        <h5><i class="bi bi-folder2-open text-teal"></i> Orodha ya Kategoria (<?= count($categories) ?>)</h5>
        <div class="d-flex gap-2">
            <a href="/index.php?route=categories" class="btn btn-sm <?= empty($status) ? 'btn-primary' : 'btn-light border' ?>">Zote</a>
            <a href="/index.php?route=categories&status=active" class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-light border' ?>">Hai</a>
            <a href="/index.php?route=categories&status=inactive" class="btn btn-sm <?= $status === 'inactive' ? 'btn-primary' : 'btn-light border' ?>">Zilizozimwa</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="custom-table table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Kodi</th>
                    <th>Jina la Kategoria</th>
                    <th>Maelezo</th>
                    <th class="text-center">Vipuri Vilivyomo</th>
                    <th class="text-center">Hali</th>
                    <th class="text-end">Vitendo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Hakuna kategoria iliyopatikana.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <code><?= htmlspecialchars($cat['code'] ?? '—') ?></code>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($cat['name']) ?></div>
                            </td>
                            <td class="small text-muted">
                                <?= !empty($cat['description']) ? htmlspecialchars($cat['description']) : '—' ?>
                            </td>
                            <td class="text-center">
                                <a href="/index.php?route=products&category_id=<?= $cat['id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 text-decoration-none" title="Tazama vipuri">
                                    <?= (int)$cat['product_count'] ?> Vipuri
                                </a>
                            </td>
                            <td class="text-center">
                                <?php if ($cat['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Hai</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">Imezimwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canManage): ?>
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editCatModal<?= $cat['id'] ?>" title="Hariri">
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                        <form action="/index.php?route=category_toggle" method="POST" class="d-inline" onsubmit="return confirm('Badili hali ya kategoria hii?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-<?= $cat['status'] === 'active' ? 'danger' : 'success' ?>" title="<?= $cat['status'] === 'active' ? 'Zima' : 'Washa' ?>">
                                                <i class="bi <?= $cat['status'] === 'active' ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Edit Category Modal -->
                                    <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <form action="/index.php?route=category_update" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Hariri Kategoria: <?= htmlspecialchars($cat['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Jina la Kategoria <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($cat['name']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Kodi ya Kategoria (mf. BRK, ENG)</label>
                                                            <input type="text" name="code" class="form-control text-uppercase" value="<?= htmlspecialchars($cat['code'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Maelezo</label>
                                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Hali</label>
                                                            <select name="status" class="form-select">
                                                                <option value="active" <?= $cat['status'] === 'active' ? 'selected' : '' ?>>Hai (Active)</option>
                                                                <option value="inactive" <?= $cat['status'] === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                                                        <button type="submit" class="btn btn-primary-custom">Hifadhi Mabadiliko</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Tazama tu</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/index.php?route=category_store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Sajili Kategoria Mpya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jina la Kategoria <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="mf. Brake System (Mfumo wa Breki)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kodi ya Kategoria (Herufi 3-4)</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="mf. BRK (Acha wazi ili kujiunda yenyewe)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Maelezo</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Maelezo ya kategoria hii ya vipuri..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Hali</label>
                        <select name="status" class="form-select">
                            <option value="active" selected>Hai (Active)</option>
                            <option value="inactive">Imezimwa (Inactive)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary-custom">Sajili Kategoria</button>
                </div>
            </form>
        </div>
    </div>
</div>
