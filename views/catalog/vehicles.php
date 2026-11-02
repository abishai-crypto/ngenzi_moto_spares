<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bicycle text-primary me-2"></i>Pikipiki & Bajaji (Vehicles & Models)</h4>
        <p class="text-muted small mb-0">Usimamizi wa chapa na miundo ya pikipiki na bajaji zinazotumika jijini Dar es Salaam na Tanzania kwa ajili ya uendano wa vipuri.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=product_vehicle_search" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-search-heart"></i>
            <span>Tafuta kwa Chombo</span>
        </a>
        <?php if ($canManage): ?>
            <?php if ($activeTab === 'brands'): ?>
                <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addVehicleBrandModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Sajili Chapa Mpya (Brand)</span>
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addVehicleModelModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Sajili Muundo Mpya (Model)</span>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'models' ? 'active' : 'bg-light border text-dark' ?> fw-semibold" href="/index.php?route=vehicles&tab=models">
            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Miundo ya Vyombo (Models - <?= count($vehicleModels) ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'brands' ? 'active' : 'bg-light border text-dark' ?> fw-semibold" href="/index.php?route=vehicles&tab=brands">
            <i class="bi bi-award-fill me-1"></i> Chapa za Vyombo (Vehicle Brands - <?= count($vehicleBrands) ?>)
        </a>
    </li>
</ul>

<?php if ($activeTab === 'brands'): ?>
    <!-- ================================================================= -->
    <!-- VEHICLE BRANDS TAB -->
    <!-- ================================================================= -->
    <div class="premium-card">
        <div class="premium-card-header">
            <h5><i class="bi bi-tags text-teal"></i> Chapa za Pikipiki na Bajaji</h5>
        </div>
        <div class="table-responsive">
            <table class="custom-table table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Chapa (Brand Name)</th>
                        <th>Aina ya Chombo (Vehicle Type)</th>
                        <th>Maelezo</th>
                        <th class="text-center">Miundo Iliyopo</th>
                        <th class="text-center">Hali</th>
                        <th class="text-end">Vitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicleBrands)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Hakuna chapa ya chombo iliyopatikana.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicleBrands as $vb): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($vb['name']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $typeBadge = 'bg-primary-subtle text-primary';
                                    if ($vb['vehicle_type'] === 'Bajaj') $typeBadge = 'bg-warning-subtle text-dark border border-warning';
                                    elseif ($vb['vehicle_type'] === 'Both') $typeBadge = 'bg-success-subtle text-success border border-success';
                                    ?>
                                    <span class="badge <?= $typeBadge ?> px-2.5 py-1 rounded-pill">
                                        <?= htmlspecialchars($vb['vehicle_type']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($vb['description']) ? htmlspecialchars($vb['description']) : '—' ?>
                                </td>
                                <td class="text-center">
                                    <a href="/index.php?route=vehicles&tab=models&brand_id=<?= $vb['id'] ?>" class="badge bg-light text-dark border rounded-pill px-3 py-1 text-decoration-none">
                                        <?= (int)$vb['model_count'] ?> Miundo
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php if ($vb['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Hai</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">Imezimwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <form action="/index.php?route=vehicle_brand_toggle" method="POST" class="d-inline" onsubmit="return confirm('Badili hali ya chapa hii?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $vb['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-<?= $vb['status'] === 'active' ? 'danger' : 'success' ?>">
                                                <i class="bi <?= $vb['status'] === 'active' ? 'bi-slash-circle' : 'bi-check-circle' ?> me-1"></i>
                                                <?= $vb['status'] === 'active' ? 'Zima' : 'Washa' ?>
                                            </button>
                                        </form>
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

    <!-- Modal: Add Vehicle Brand -->
    <div class="modal fade" id="addVehicleBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="/index.php?route=vehicle_brand_store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Sajili Chapa Mpya ya Chombo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Jina la Chapa (Brand Name) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="mf. Boxer, TVS, Bajaj, Haojue, Honda, Piaggio">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Aina ya Chombo (Type)</label>
                            <select name="vehicle_type" class="form-select">
                                <option value="Motorcycle" selected>Pikipiki Pekee (Motorcycle)</option>
                                <option value="Bajaj">Bajaji Pekee (Three-Wheeler)</option>
                                <option value="Both">Vyote Viwili (Both)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Maelezo</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Maelezo kuhusu chapa hii..."></textarea>
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
                        <button type="submit" class="btn btn-primary-custom">Hifadhi Chapa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ================================================================= -->
    <!-- VEHICLE MODELS TAB -->
    <!-- ================================================================= -->

    <!-- Filters Bar -->
    <div class="premium-card mb-4">
        <div class="premium-card-body p-3">
            <form method="GET" action="/index.php" class="row g-2 align-items-center">
                <input type="hidden" name="route" value="vehicles">
                <input type="hidden" name="tab" value="models">

                <div class="col-md-4">
                    <select name="brand_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Chapa Zote (Brands) --</option>
                        <?php foreach ($vehicleBrands as $vb): ?>
                            <option value="<?= $vb['id'] ?>" <?= $selectedBrandId === (int)$vb['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vb['name']) ?> (<?= $vb['vehicle_type'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Aina Zote (Type) --</option>
                        <option value="Motorcycle" <?= $selectedType === 'Motorcycle' ? 'selected' : '' ?>>Pikipiki (Motorcycle)</option>
                        <option value="Bajaj" <?= $selectedType === 'Bajaj' ? 'selected' : '' ?>>Bajaji (Bajaj)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Hali Zote --</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hai (Active)</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <a href="/index.php?route=vehicles&tab=models" class="btn btn-light border w-100" title="Futa Vichungi">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Futa
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Models Table -->
    <div class="premium-card">
        <div class="premium-card-header">
            <h5><i class="bi bi-bicycle text-teal"></i> Miundo ya Pikipiki na Bajaji (<?= count($vehicleModels) ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="custom-table table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Chapa (Brand)</th>
                        <th>Muundo (Model Name)</th>
                        <th>Aina ya Chombo</th>
                        <th>Uwezo wa Injini (CC)</th>
                        <th>Miaka ya Muundo</th>
                        <th class="text-center">Vipuri Vinavyoendana</th>
                        <th class="text-center">Hali</th>
                        <th class="text-end">Vitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicleModels)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Hakuna muundo wa chombo uliopatikana.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicleModels as $vm): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border fs-6"><?= htmlspecialchars($vm['brand_name']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($vm['model_name']) ?></div>
                                    <?php if (!empty($vm['notes'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($vm['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $vm['vehicle_type'] === 'Bajaj' ? 'bg-warning-subtle text-dark border border-warning' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-2.5 py-1">
                                        <?= htmlspecialchars($vm['vehicle_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= !empty($vm['engine_capacity']) ? htmlspecialchars($vm['engine_capacity']) : '—' ?></strong>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($vm['year_from']) ? $vm['year_from'] . ' - ' . ($vm['year_to'] ?: 'Hadi Sasa') : 'Mifano Yote' ?>
                                </td>
                                <td class="text-center">
                                    <a href="/index.php?route=product_vehicle_search&vehicle_type=<?= $vm['vehicle_type'] ?>&brand_id=<?= $vm['brand_id'] ?>&model_id=<?= $vm['id'] ?>" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 text-decoration-none">
                                        <i class="bi bi-box-seam me-1"></i><?= (int)$vm['compatible_parts_count'] ?> Vipuri
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php if ($vm['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Hai</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">Imezimwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <form action="/index.php?route=vehicle_model_toggle" method="POST" class="d-inline" onsubmit="return confirm('Badili hali ya muundo huu?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $vm['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-<?= $vm['status'] === 'active' ? 'danger' : 'success' ?>">
                                                <i class="bi <?= $vm['status'] === 'active' ? 'bi-slash-circle' : 'bi-check-circle' ?> me-1"></i>
                                                <?= $vm['status'] === 'active' ? 'Zima' : 'Washa' ?>
                                            </button>
                                        </form>
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

    <!-- Modal: Add Vehicle Model -->
    <div class="modal fade" id="addVehicleModelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="/index.php?route=vehicle_model_store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Sajili Muundo Mpya wa Chombo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Chapa ya Chombo (Brand) <span class="text-danger">*</span></label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">-- Chagua Chapa --</option>
                                <?php foreach ($vehicleBrands as $vb): ?>
                                    <option value="<?= $vb['id'] ?>" <?= $selectedBrandId === (int)$vb['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vb['name']) ?> (<?= $vb['vehicle_type'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Jina la Muundo (Model Name) <span class="text-danger">*</span></label>
                            <input type="text" name="model_name" class="form-control" required placeholder="mf. BM 150, HLX 125, King Deluxe, RE 4S">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Aina ya Chombo</label>
                                <select name="vehicle_type" class="form-select">
                                    <option value="Motorcycle">Pikipiki (Motorcycle)</option>
                                    <option value="Bajaj">Bajaji (Three-Wheeler)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Uwezo wa Injini (CC)</label>
                                <input type="text" name="engine_capacity" class="form-control" placeholder="mf. 150cc, 200cc">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Mwaka Kuanzia (Kutoka)</label>
                                <input type="number" name="year_from" class="form-control" placeholder="mf. 2012" min="1990" max="2035">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Hadi Mwaka (Hadi)</label>
                                <input type="number" name="year_to" class="form-control" placeholder="mf. 2024 (Acha wazi kama sasa)" min="1990" max="2035">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Maelezo ya Ziada (Notes)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="mf. Toleo la taa ya duara, kick start, nk."></textarea>
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
                        <button type="submit" class="btn btn-primary-custom">Hifadhi Muundo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
