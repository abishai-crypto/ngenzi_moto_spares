<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bicycle text-primary me-2"></i>Tafuta Vipuri kwa Muundo wa Chombo (Vehicle Lookup)</h4>
        <p class="text-muted small mb-0">Chagua aina ya chombo, chapa, na muundo ili kuona vipuri vyote vinavyoendana moja kwa moja.</p>
    </div>
    <a href="/index.php?route=products" class="btn btn-light border">
        <i class="bi bi-box-seam me-1"></i> Vipuri Vyote
    </a>
</div>

<!-- Cascading Vehicle Selection Filter Card -->
<div class="premium-card mb-4 border-primary">
    <div class="premium-card-body p-4">
        <form method="GET" action="/index.php" id="vehicleSearchForm">
            <input type="hidden" name="route" value="product_vehicle_search">

            <div class="row g-3 align-items-end">
                <!-- 1. Vehicle Type -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small text-muted">1. Aina ya Chombo (Type)</label>
                    <select name="vehicle_type" id="typeSelect" class="form-select form-select-lg" onchange="this.form.submit()">
                        <option value="">-- Chagua Aina --</option>
                        <option value="Motorcycle" <?= $selectedType === 'Motorcycle' ? 'selected' : '' ?>>🏍️ Pikipiki (Motorcycle)</option>
                        <option value="Bajaj" <?= $selectedType === 'Bajaj' ? 'selected' : '' ?>>🛺 Bajaji (Three-Wheeler)</option>
                    </select>
                </div>

                <!-- 2. Vehicle Brand -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small text-muted">2. Chapa ya Chombo (Brand)</label>
                    <select name="brand_id" id="brandSelect" class="form-select form-select-lg" <?= empty($selectedType) ? 'disabled' : '' ?> onchange="this.form.submit()">
                        <option value="">-- Chagua Chapa --</option>
                        <?php foreach ($vehicleBrands as $vb): ?>
                            <option value="<?= $vb['id'] ?>" <?= $selectedBrandId === (int)$vb['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vb['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Vehicle Model -->
                <div class="col-md-4 col-sm-8">
                    <label class="form-label fw-bold small text-muted">3. Muundo Mahususi (Model)</label>
                    <select name="model_id" id="modelSelect" class="form-select form-select-lg" <?= empty($selectedBrandId) ? 'disabled' : '' ?> onchange="this.form.submit()">
                        <option value="">-- Chagua Muundo (Model) --</option>
                        <?php foreach ($vehicleModels as $vm): ?>
                            <option value="<?= $vm['id'] ?>" <?= $selectedModelId === (int)$vm['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vm['model_name']) ?> (<?= htmlspecialchars($vm['engine_capacity'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-md-2 col-sm-4">
                    <a href="/index.php?route=product_vehicle_search" class="btn btn-light border btn-lg w-100" title="Weka Upya">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Rudisha
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Section -->
<?php if (!empty($chosenModel)): ?>
    <div class="premium-card">
        <div class="premium-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    Vipuri Vinavyoendana na: <span class="text-primary"><?= htmlspecialchars($chosenModel['brand_name']) ?> <?= htmlspecialchars($chosenModel['model_name']) ?></span>
                </h5>
                <small class="text-muted">
                    Aina: <?= htmlspecialchars($chosenModel['vehicle_type']) ?> &bull; 
                    Uwezo: <?= htmlspecialchars($chosenModel['engine_capacity'] ?? 'N/A') ?> &bull; 
                    Mwaka: <?= $chosenModel['year_from'] ? $chosenModel['year_from'] . '-' . ($chosenModel['year_to'] ?: 'Sasa') : 'Mifano Yote' ?>
                </small>
            </div>
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-1.5 fs-6">
                    <?= count($compatibleProducts) ?> Vipuri Vimepatikana
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kipuri / Jina</th>
                        <th>Kategoria</th>
                        <th>Brand</th>
                        <th class="text-end">Bei ya Kuuza</th>
                        <th class="text-center">Stoo</th>
                        <th class="text-center">Eneo la Shelfu</th>
                        <th class="text-end">Kitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($compatibleProducts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                                Bado hakuna vipuri vilivyosajiliwa kuendana na <strong><?= htmlspecialchars($chosenModel['brand_name']) ?> <?= htmlspecialchars($chosenModel['model_name']) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($compatibleProducts as $cp): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="/index.php?route=product_show&id=<?= $cp['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                            <?= htmlspecialchars($cp['name']) ?>
                                        </a>
                                    </div>
                                    <small class="text-muted"><code><?= htmlspecialchars($cp['sku']) ?></code></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($cp['category_name']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($cp['brand_name'] ?? 'Generic') ?>
                                </td>
                                <td class="text-end fw-bold text-primary fs-6">
                                    <?= Helper::formatMoney((float)$cp['selling_price']) ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $stock = (int)$cp['current_stock'];
                                    $reorder = (int)$cp['reorder_level'];
                                    $badge = ($stock <= 0) ? 'bg-danger' : (($stock <= $reorder) ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle');
                                    ?>
                                    <span class="badge <?= $badge ?> px-2.5 py-1 rounded-pill">
                                        <?= $stock ?> <?= htmlspecialchars($cp['unit_short']) ?>
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    <?= !empty($cp['shelf_location']) ? htmlspecialchars($cp['shelf_location']) : '—' ?>
                                </td>
                                <td class="text-end">
                                    <a href="/index.php?route=product_show&id=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye me-1"></i> Tazama
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <!-- Guidance Banner if no vehicle model is selected yet -->
    <div class="premium-card p-5 text-center bg-light">
        <div class="mb-3">
            <span class="p-3 bg-white rounded-circle shadow-sm border d-inline-block">
                <i class="bi bi-bicycle fs-1 text-primary"></i>
            </span>
        </div>
        <h5 class="fw-bold">Chagua Chombo Kwanza Kuanza Utafutaji</h5>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            Tumia machaguo hapo juu: Anza kwa kuchagua <strong>Pikipiki au Bajaji</strong>, kisha chagua <strong>Chapa</strong> (mf. Boxer, TVS), na mwisho <strong>Muundo</strong> (mf. BM 150, HLX 125, TVS King) ili kuona vipuri vinavyoendana.
        </p>
    </div>
<?php endif; ?>
