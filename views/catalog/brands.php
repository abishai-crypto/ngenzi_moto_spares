<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-award-fill text-primary me-2"></i>Chapa za Vipuri (Spare Part Brands)</h4>
        <p class="text-muted small mb-0">Usimamizi wa wazalishaji na chapa za vipuri vya pikipiki na bajaji (Bosch, NGK, Boxer, TVS, Rolon, nk.).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=products" class="btn btn-light border">
            <i class="bi bi-box-seam me-1"></i> Vipuri Vyote
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Sajili Brand Mpya</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Brands Table Card -->
<div class="premium-card">
    <div class="premium-card-header d-flex justify-content-between align-items-center">
        <h5><i class="bi bi-shield-check text-teal"></i> Orodha ya Chapa (<?= count($brands) ?>)</h5>
        <div class="d-flex gap-2">
            <a href="/index.php?route=brands" class="btn btn-sm <?= empty($status) ? 'btn-primary' : 'btn-light border' ?>">Zote</a>
            <a href="/index.php?route=brands&status=active" class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-light border' ?>">Hai</a>
            <a href="/index.php?route=brands&status=inactive" class="btn btn-sm <?= $status === 'inactive' ? 'btn-primary' : 'btn-light border' ?>">Zilizozimwa</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="custom-table table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Chapa (Brand Name)</th>
                    <th>Nchi ya Asili (Country)</th>
                    <th>Maelezo</th>
                    <th class="text-center">Vipuri Vilivyomo</th>
                    <th class="text-center">Hali</th>
                    <th class="text-end">Vitendo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Hakuna brand iliyopatikana.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($brands as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($b['name']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= !empty($b['country']) ? htmlspecialchars($b['country']) : 'Global / Asili Yoyote' ?></span>
                            </td>
                            <td class="small text-muted">
                                <?= !empty($b['description']) ? htmlspecialchars($b['description']) : '—' ?>
                            </td>
                            <td class="text-center">
                                <a href="/index.php?route=products&brand_id=<?= $b['id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 text-decoration-none">
                                    <?= (int)$b['product_count'] ?> Vipuri
                                </a>
                            </td>
                            <td class="text-center">
                                <?php if ($b['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Hai</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">Imezimwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canManage): ?>
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editBrandModal<?= $b['id'] ?>" title="Hariri">
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                        <form action="/index.php?route=brand_toggle" method="POST" class="d-inline" onsubmit="return confirm('Badili hali ya brand hii?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-<?= $b['status'] === 'active' ? 'danger' : 'success' ?>" title="<?= $b['status'] === 'active' ? 'Zima' : 'Washa' ?>">
                                                <i class="bi <?= $b['status'] === 'active' ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Edit Brand Modal -->
                                    <div class="modal fade" id="editBrandModal<?= $b['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <form action="/index.php?route=brand_update" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Hariri Brand: <?= htmlspecialchars($b['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Jina la Brand <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($b['name']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Nchi ya Asili</label>
                                                            <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($b['country'] ?? '') ?>" placeholder="mf. India, Japan, China, Germany">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Maelezo</label>
                                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($b['description'] ?? '') ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Hali</label>
                                                            <select name="status" class="form-select">
                                                                <option value="active" <?= $b['status'] === 'active' ? 'selected' : '' ?>>Hai (Active)</option>
                                                                <option value="inactive" <?= $b['status'] === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
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

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/index.php?route=brand_store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Sajili Brand Mpya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jina la Brand <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="mf. Bosch, NGK, Rolon, Endurance">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nchi ya Asili (Si lazima)</label>
                        <input type="text" name="country" class="form-control" placeholder="mf. India, Japan, China">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Maelezo</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Maelezo ya bidhaa za brand hii..."></textarea>
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
                    <button type="submit" class="btn btn-primary-custom">Sajili Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>
