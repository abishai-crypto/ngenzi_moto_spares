<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-images text-primary me-2"></i>Ripoti ya Maktaba ya Picha za Vipuri (Product Image Inventory)
        </h4>
        <p class="text-muted small mb-0">
            Uhakiki wa kina wa picha halisi za vipuri vya pikipiki na bajaji zilizohifadhiwa ndani (Offline Library) bila kutegemea intaneti.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <a href="/index.php?route=reports/export&type=product_images" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Pakua CSV
        </a>
        <a href="/assets/images/product_images.json" download="product_images.json" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-code-square me-1"></i> Pakua JSON Mapping
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Total Products -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="text-muted small fw-semibold">Jumla ya Vipuri</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($counts['total']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-box-seam me-1"></i>Vipuri vyote kwenye DB</div>
        </div>
    </div>

    <!-- Available with Verified Image -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-success border-4">
            <div class="text-success small fw-semibold">AVAILABLE</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= number_format($counts['available']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Picha ipo na inafanya kazi</div>
        </div>
    </div>

    <!-- Image Required -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-warning border-4">
            <div class="text-warning small fw-semibold">IMAGE_REQUIRED</div>
            <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($counts['image_required']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Inasubiri picha halisi</div>
        </div>
    </div>

    <!-- Missing Images -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-danger border-4">
            <div class="text-danger small fw-semibold">MISSING</div>
            <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($counts['missing']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-x-circle-fill text-danger me-1"></i>Faili halijapatikana</div>
        </div>
    </div>

    <!-- Invalid Images -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-secondary border-4">
            <div class="text-secondary small fw-semibold">INVALID</div>
            <div class="fs-4 fw-bold text-secondary mt-1"><?= number_format($counts['invalid']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-file-earmark-x me-1"></i>Faili si picha sahihi</div>
        </div>
    </div>

    <!-- Duplicate Images -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-info border-4">
            <div class="text-info small fw-semibold">DUPLICATE</div>
            <div class="fs-4 fw-bold text-info mt-1"><?= number_format($counts['duplicate']) ?></div>
            <div class="small text-muted mt-1"><i class="bi bi-files me-1"></i>Picha inajirudia</div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="report_product_images">

            <div class="col-md-4 col-sm-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tafuta jina la kipuri, SKU, kategoria..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hali Zote za Picha --</option>
                    <option value="AVAILABLE" <?= ($statusFilter === 'AVAILABLE') ? 'selected' : '' ?>>AVAILABLE (Picha Ipo)</option>
                    <option value="IMAGE_REQUIRED" <?= ($statusFilter === 'IMAGE_REQUIRED') ? 'selected' : '' ?>>IMAGE_REQUIRED (Inahitajika)</option>
                    <option value="MISSING" <?= ($statusFilter === 'MISSING') ? 'selected' : '' ?>>MISSING (Imekosekana)</option>
                    <option value="INVALID" <?= ($statusFilter === 'INVALID') ? 'selected' : '' ?>>INVALID (Isiyo Sahihi)</option>
                    <option value="DUPLICATE" <?= ($statusFilter === 'DUPLICATE') ? 'selected' : '' ?>>DUPLICATE (Inayojirudia)</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Chuja
                </button>
                <a href="/index.php?route=report_product_images" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Image Inventory Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-table text-primary me-2"></i>Orodha ya Hali ya Picha za Vipuri (Matokeo: <?= count($items) ?>)
        </h6>
        <span class="badge bg-light text-secondary border">100% Offline Capable</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-muted">
                <tr>
                    <th style="width: 70px;" class="text-center">Picha</th>
                    <th>Jina la Kipuri</th>
                    <th>SKU / Kategoria</th>
                    <th>Chapa (Brand)</th>
                    <th class="text-center">Hali ya Picha (Status)</th>
                    <th>Njia ya Ndani (Local Image Path)</th>
                    <th class="text-end">Bei ya Kuuza</th>
                    <th class="text-center">Vitendo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Hakuna kipuri kilichopatikana kulingana na vichungi ulivyochagua.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $it): ?>
                        <?php
                        $st = $it['image_status'];
                        $badgeClass = 'bg-secondary text-white';
                        $icon = 'bi-question-circle';
                        if ($st === 'AVAILABLE') {
                            $badgeClass = 'bg-success text-white';
                            $icon = 'bi-check-circle-fill';
                        } elseif ($st === 'IMAGE_REQUIRED') {
                            $badgeClass = 'bg-warning text-dark';
                            $icon = 'bi-exclamation-triangle-fill';
                        } elseif ($st === 'MISSING') {
                            $badgeClass = 'bg-danger text-white';
                            $icon = 'bi-x-circle-fill';
                        } elseif ($st === 'DUPLICATE') {
                            $badgeClass = 'bg-info text-dark';
                            $icon = 'bi-files';
                        } elseif ($st === 'INVALID') {
                            $badgeClass = 'bg-dark text-white';
                            $icon = 'bi-slash-circle-fill';
                        }
                        ?>
                        <tr>
                            <td class="text-center">
                                <div class="rounded border bg-light d-inline-flex align-items-center justify-content-center overflow-hidden" 
                                     style="width: 52px; height: 52px; cursor: pointer;"
                                     onclick="showImagePreview('<?= htmlspecialchars($it['image_url'] ?: '/assets/images/placeholder_product.svg') ?>', '<?= htmlspecialchars(addslashes($it['name'])) ?>', '<?= htmlspecialchars($it['sku']) ?>')">
                                    <img src="<?= htmlspecialchars($it['image_url'] ?: '/assets/images/placeholder_product.svg') ?>" 
                                         alt="<?= htmlspecialchars($it['name']) ?>" 
                                         class="img-fluid" 
                                         style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                         onerror="this.src='/assets/images/placeholder_product.svg';">
                                </div>
                            </td>
                            <td>
                                <a href="/index.php?route=product_show&id=<?= $it['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                    <?= htmlspecialchars($it['name']) ?>
                                </a>
                                <div class="text-muted small">
                                    ID: #<?= $it['id'] ?>
                                </div>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($it['sku']) ?></code>
                                <div class="small text-muted"><?= htmlspecialchars($it['category_name'] ?? 'General') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['brand_name'] ?? 'Generic') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badgeClass ?> px-2.5 py-1.5 font-monospace" style="font-size: 11px;">
                                    <i class="bi <?= $icon ?> me-1"></i><?= $st ?>
                                </span>
                            </td>
                            <td>
                                <code class="text-muted small" style="font-size: 11px; word-break: break-all;">
                                    <?= htmlspecialchars($it['local_path']) ?>
                                </code>
                            </td>
                            <td class="text-end fw-bold font-monospace text-primary">
                                <?= Helper::formatCurrency((float)$it['selling_price']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="/index.php?route=product_show&id=<?= $it['id'] ?>" class="btn btn-outline-secondary" title="Tazama Undani">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/index.php?route=product_edit&id=<?= $it['id'] ?>" class="btn btn-outline-primary" title="Hariri">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold" id="previewTitle">Uhakiki wa Picha</h6>
                    <small class="text-muted font-monospace" id="previewSku"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4 bg-light">
                <img id="previewImg" src="" class="img-fluid rounded border shadow-sm bg-white" style="max-height: 380px; object-fit: contain;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Funga</button>
            </div>
        </div>
    </div>
</div>

<script>
function showImagePreview(src, title, sku) {
    document.getElementById('previewImg').src = src;
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewSku').textContent = 'SKU: ' + sku;
    const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    modal.show();
}
</script>
