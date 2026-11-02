<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-box-seam-fill text-primary me-2"></i>Mali & Vipuri Vilivyopo Stoo (Stock List)
        </h4>
        <p class="text-muted small mb-0">Tazama upatikanaji wa bidhaa, idadi iliyopo, hali ya stoo, na msimbo pau (barcode).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=inventory_adjust" class="btn btn-warning btn-sm text-dark fw-semibold d-flex align-items-center gap-1">
            <i class="bi bi-sliders"></i> Rekebisha Stoo
        </a>
        <a href="/index.php?route=inventory_counts" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-clipboard-check"></i> Ukaguzi wa Stoo
        </a>
        <a href="/index.php?route=inventory_history" class="btn btn-light border btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-clock-history"></i> Mienendo ya Stoo
        </a>
    </div>
</div>

<!-- Search & Multi-Attribute Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="inventory">

            <!-- Search by Name, SKU, Barcode -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta (Jina, SKU, Barcode)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="inventorySearchInput" class="form-control border-start-0" 
                           placeholder="Mf. Brake Shoe, BRK-000001, barcode..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Stock Status Filter -->
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Hali ya Stoo</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <option value="in_stock" <?= ($filters['status'] === 'in_stock') ? 'selected' : '' ?>>In Stock (Inapatikana)</option>
                    <option value="low_stock" <?= ($filters['status'] === 'low_stock') ? 'selected' : '' ?>>Low Stock (Chini ya Kiwango)</option>
                    <option value="out_of_stock" <?= ($filters['status'] === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock (Imeisha)</option>
                    <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Hai Tu (Active)</option>
                    <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : '' ?>>Zilizo Fungwa (Inactive)</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Kategoria</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Brand Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Brand ya Kipuri</label>
                <select name="brand_id" class="form-select form-select-sm">
                    <option value="">-- Brand Zote --</option>
                    <?php foreach ($brands as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= ($filters['brand_id'] == $br['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Vehicle Model Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Muundo wa Chombo</label>
                <select name="vehicle_model_id" class="form-select form-select-sm">
                    <option value="">-- Vyombo Vyote --</option>
                    <?php foreach ($vehicleModels as $vm): ?>
                        <option value="<?= $vm['id'] ?>" <?= ($filters['vehicle_model_id'] == $vm['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vm['brand_name'] . ' ' . $vm['model_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-lg-1 col-md-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Chuja">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="/index.php?route=inventory" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Products Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                <h5>Hakuna kipuri kilichopatikana</h5>
                <p class="small mb-3">Jaribu kubadilisha maneno ya utafutaji au machaguo ya vichungi.</p>
                <a href="/index.php?route=inventory" class="btn btn-outline-primary btn-sm">Onyesha Vipuri Vyote</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kipuri / Maelezo</th>
                            <th>Kategoria & Brand</th>
                            <th class="text-center">Idadi Iliyopo (Stock)</th>
                            <th class="text-center">Kiwango cha Chini</th>
                            <th class="text-center">Hali ya Stoo</th>
                            <th class="text-end">Bei ya Kuuza</th>
                            <?php if ($canSeeCosts): ?>
                                <th class="text-end">Bei ya Kununua</th>
                                <th class="text-end">Thamani ya Stoo</th>
                            <?php endif; ?>
                            <th class="text-center">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <?php
                            $qty = (int)$p['quantity'];
                            $minLevel = (int)$p['minimum_stock_level'];

                            if ($qty == 0) {
                                $stockBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">OUT OF STOCK</span>';
                                $qtyClass = 'text-danger fw-bold';
                            } elseif ($qty <= $minLevel) {
                                $stockBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">LOW STOCK</span>';
                                $qtyClass = 'text-warning fw-bold';
                            } else {
                                $stockBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">IN STOCK</span>';
                                $qtyClass = 'text-success fw-bold';
                            }

                            $costValue = $qty * (float)$p['buying_cost'];
                            ?>
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
                                            <a href="/index.php?route=product_show&id=<?= $p['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </a>
                                            <div class="d-flex align-items-center gap-2 small text-muted mt-1">
                                                <span>SKU: <code><?= htmlspecialchars($p['sku']) ?></code></span>
                                                <?php if (!empty($p['barcode'])): ?>
                                                    <span>&bull; Barcode: <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['barcode']) ?></span></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($p['category_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['brand_name'] ?? 'Generic') ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="fs-6 <?= $qtyClass ?>"><?= $qty ?></span>
                                    <span class="small text-muted d-block"><?= htmlspecialchars($p['unit_short'] ?? 'Pcs') ?></span>
                                </td>
                                <td class="text-center small text-muted">
                                    <?= $minLevel ?> <?= htmlspecialchars($p['unit_short'] ?? 'Pcs') ?>
                                </td>
                                <td class="text-center">
                                    <?= $stockBadge ?>
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    <?= Helper::formatMoney((float)$p['selling_price']) ?>
                                </td>
                                <?php if ($canSeeCosts): ?>
                                    <td class="text-end small text-muted">
                                        <?= Helper::formatMoney((float)$p['buying_cost']) ?>
                                    </td>
                                    <td class="text-end small fw-semibold text-primary">
                                        <?= Helper::formatMoney($costValue) ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/index.php?route=product_show&id=<?= $p['id'] ?>" class="btn btn-light border" title="Angalia Taarifa">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <a href="/index.php?route=inventory_adjust&product_id=<?= $p['id'] ?>" class="btn btn-light border text-warning" title="Rekebisha Idadi ya Stoo">
                                                <i class="bi bi-sliders"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="p-3 border-top d-flex align-items-center justify-content-between">
                    <div class="small text-muted">
                        Inaonyesha ukurasa <strong><?= $page ?></strong> kati ya <strong><?= $totalPages ?></strong> (Jumla ya vipuri: <?= $total ?>)
                    </div>
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
