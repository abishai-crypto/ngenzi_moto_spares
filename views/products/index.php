<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-box-seam-fill text-primary me-2"></i>Katalogi ya Vipuri Vyote (Products)</h4>
        <p class="text-muted small mb-0">Usimamizi kamili wa vipuri vya pikipiki na bajaji, bei elekezi za ununuzi na kuuzia, na uendano wa vyombo.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=product_vehicle_search" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-search-heart"></i>
            <span>Tafuta kwa Chombo</span>
        </a>
        <?php if ($canManage): ?>
            <a href="/index.php?route=product_create" class="btn btn-primary-custom d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Sajili Kipuri Kipya</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Advanced Filter Card -->
<div class="premium-card mb-4">
    <div class="premium-card-body p-3">
        <form method="GET" action="/index.php" class="row g-2">
            <input type="hidden" name="route" value="products">

            <!-- Search input -->
            <div class="col-lg-3 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tafuta jina, SKU, barcode, shelf..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <select name="category_id" class="form-select">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Brand Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <select name="brand_id" class="form-select">
                    <option value="">-- Brands Zote --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $filters['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Vehicle Brand Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <select name="vehicle_brand_id" class="form-select">
                    <option value="">-- Vyombo (Chapa) --</option>
                    <?php foreach ($vehicleBrands as $vb): ?>
                        <option value="<?= $vb['id'] ?>" <?= $filters['vehicle_brand_id'] == $vb['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vb['name']) ?> (<?= $vb['vehicle_type'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Stock Status Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <select name="stock_status" class="form-select">
                    <option value="">-- Hali ya Stoo --</option>
                    <option value="in_stock" <?= $filters['stock_status'] === 'in_stock' ? 'selected' : '' ?>>Stoo Ipo (In Stock)</option>
                    <option value="low_stock" <?= $filters['stock_status'] === 'low_stock' ? 'selected' : '' ?>>Inakaribia Kuisha (Low)</option>
                    <option value="out_of_stock" <?= $filters['stock_status'] === 'out_of_stock' ? 'selected' : '' ?>>Imeisha (Out of Stock)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-1 col-md-4 col-sm-6 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom w-100" title="Chuja">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="/index.php?route=products" class="btn btn-light border" title="Futa Vichungi">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="premium-card">
    <div class="premium-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5><i class="bi bi-boxes text-teal"></i> Orodha ya Vipuri (Jumla: <?= number_format($totalCount) ?>)</h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-cash-coin me-1"></i>Bei & Faida Halisi ya Mauzo</span>
        </div>
        <div>
            <span class="badge bg-light text-dark border">Ukurasa <?= $currentPage ?> kati ya <?= max(1, $totalPages) ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Kipuri / SKU</th>
                    <th>Kategoria & Brand</th>
                    <th>Vyombo Vinavyoendana</th>
                    <th class="text-end">Bei ya Kununua</th>
                    <th class="text-end">Bei ya Kuuza</th>
                    <th class="text-end">Kiasi cha Faida</th>
                    <th class="text-center">Stoo</th>
                    <th class="text-center">Hali</th>
                    <th class="text-end">Vitendo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Hakuna kipuri kilichopatikana kulingana na vigezo ulivyochagua.
                            <?php if ($canManage): ?>
                                <div class="mt-2">
                                    <a href="/index.php?route=product_create" class="btn btn-sm btn-primary-custom">
                                        <i class="bi bi-plus-lg me-1"></i> Sajili Kipuri Mpya Sasa
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php $m = $p['margin_analysis']; ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-thumb-box rounded border bg-light d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:42px; height:42px;">
                                        <img src="<?= htmlspecialchars($p['image_url'] ?: '/assets/images/placeholder_product.svg') ?>" 
                                             alt="<?= htmlspecialchars($p['name']) ?>" 
                                             class="img-fluid" 
                                             style="max-width:100%; max-height:100%; object-fit:contain;"
                                             onerror="this.src='/assets/images/placeholder_product.svg';">
                                    </div>
                                    <div>
                                        <a href="/index.php?route=product_show&id=<?= $p['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </a>
                                        <div class="small text-muted">
                                            <code><?= htmlspecialchars($p['sku']) ?></code>
                                            <?php if (!empty($p['product_type'])): ?>
                                                &bull; <span class="badge bg-light text-dark border" style="font-size:10px;"><?= htmlspecialchars($p['product_type']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['barcode'])): ?>
                                                &bull; <span title="Barcode"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($p['barcode']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['shelf_location'])): ?>
                                                &bull; <span class="badge bg-light text-dark border" style="font-size:10px;">Shelfu: <?= htmlspecialchars($p['shelf_location']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><span class="badge bg-secondary-subtle text-dark border"><?= htmlspecialchars($p['category_name']) ?></span></div>
                                <small class="text-muted"><?= htmlspecialchars($p['brand_name'] ?? 'Generic') ?></small>
                            </td>
                            <td>
                                <?php if ($p['vehicle_compatibility_count'] > 0): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1" title="<?= htmlspecialchars($p['compatible_models_summary'] ?? '') ?>">
                                        <i class="bi bi-bicycle me-1"></i><?= $p['vehicle_compatibility_count'] ?> Mifano
                                    </span>
                                    <div class="text-muted text-truncate" style="max-width:180px; font-size:11px;">
                                        <?= htmlspecialchars(substr($p['compatible_models_summary'] ?? '', 0, 35)) ?>...
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Yote / Haijawekwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold text-muted">
                                <?= $canSeeCosts ? Helper::formatMoney((float)$p['buying_price']) : '<span class="badge bg-light text-muted border">*** (Imezuiwa)</span>' ?>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">
                                <?= Helper::formatMoney((float)$p['selling_price']) ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canSeeCosts): ?>
                                    <?php $profitVal = (float)$m['gross_profit']; ?>
                                    <span class="badge <?= $profitVal >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> px-2 py-1 fw-bold">
                                        <?= $profitVal >= 0 ? '+' : '' ?><?= Helper::formatMoney($profitVal) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Imehifadhiwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $stock = (int)$p['current_stock'];
                                $reorder = (int)$p['reorder_level'];
                                if ($stock <= 0) {
                                    $stockBadge = 'bg-danger text-white';
                                    $stockIcon = 'bi-x-circle-fill';
                                    $statusLabel = 'OUT OF STOCK';
                                    $labelColor = 'text-danger';
                                } elseif ($stock <= $reorder) {
                                    $stockBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                    $stockIcon = 'bi-exclamation-triangle-fill';
                                    $statusLabel = 'LOW STOCK';
                                    $labelColor = 'text-warning';
                                } else {
                                    $stockBadge = 'bg-success-subtle text-success border border-success-subtle';
                                    $stockIcon = 'bi-check-circle-fill';
                                    $statusLabel = 'IN STOCK';
                                    $labelColor = 'text-success';
                                }
                                ?>
                                <span class="badge <?= $stockBadge ?> px-2.5 py-1 rounded-pill fw-semibold">
                                    <i class="bi <?= $stockIcon ?> me-1"></i><?= $stock ?> <?= htmlspecialchars($p['unit_short']) ?>
                                </span>
                                <div style="font-size:10px;" class="fw-bold <?= $labelColor ?> mt-1"><?= $statusLabel ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($p['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size:11px;">Hai</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1" style="font-size:11px;">Imezimwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Chaguo
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item py-2 small" href="/index.php?route=product_show&id=<?= $p['id'] ?>">
                                                <i class="bi bi-eye me-2 text-info"></i> Tazama Undani
                                            </a>
                                        </li>
                                        <?php if ($canManage): ?>
                                            <li>
                                                <a class="dropdown-item py-2 small" href="/index.php?route=product_edit&id=<?= $p['id'] ?>">
                                                    <i class="bi bi-pencil me-2 text-primary"></i> Hariri Kipuri
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="/index.php?route=product_toggle" method="POST" onsubmit="return confirm('Je, una uhakika wa kubadilisha hali ya kipuri hiki?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="dropdown-item py-2 small <?= $p['status'] === 'active' ? 'text-danger' : 'text-success' ?>">
                                                        <i class="bi <?= $p['status'] === 'active' ? 'bi-slash-circle' : 'bi-check-circle' ?> me-2"></i>
                                                        <?= $p['status'] === 'active' ? 'Zima Kipuri (Inactive)' : 'Washa Kipuri (Active)' ?>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">Inaonyesha vipuri <?= ($offset + 1) ?> - <?= min($offset + $limit, $totalCount) ?> kati ya <?= $totalCount ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $currentPage === $i ? 'active' : '' ?>">
                            <a class="page-link" href="/index.php?route=products&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&category_id=<?= $filters['category_id'] ?>&brand_id=<?= $filters['brand_id'] ?>&status=<?= $filters['status'] ?>&stock_status=<?= $filters['stock_status'] ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
