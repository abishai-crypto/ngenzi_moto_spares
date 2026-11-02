<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-boxes text-warning me-2"></i>Ripoti za Stoo, Thamani & Mienendo (Inventory Reports)
        </h4>
        <p class="text-muted small mb-0">
            Jumla ya Vipuri: <strong><?= number_format($sums['total_units']) ?> pcs</strong> &bull; Thamani ya Stoo: <strong class="text-primary">TSh <?= number_format($sums['total_cost_value'], 2) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <a href="/index.php?route=reports/export&type=inventory" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Pakua CSV
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Tabs: Valuation, Fast-Moving, Slow-Moving, Dead Stock, Reorder -->
<ul class="nav nav-tabs nav-fill mb-4" id="inventoryTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab === 'current' || empty($tab)) ? 'active' : '' ?> fw-semibold" id="valuation-tab" data-bs-toggle="tab" data-bs-target="#valuationPane" type="button" role="tab">
            <i class="bi bi-box-seam me-1"></i> Thamani ya Stoo (Valuation)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab === 'fast') ? 'active' : '' ?> fw-semibold" id="fast-tab" data-bs-toggle="tab" data-bs-target="#fastPane" type="button" role="tab">
            <i class="bi bi-lightning-charge me-1"></i> Vinavyotoka Haraka (Fast)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab === 'slow') ? 'active' : '' ?> fw-semibold" id="slow-tab" data-bs-toggle="tab" data-bs-target="#slowPane" type="button" role="tab">
            <i class="bi bi-hourglass-split me-1"></i> Vinavyochelewa (Slow)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab === 'dead') ? 'active' : '' ?> fw-semibold" id="dead-tab" data-bs-toggle="tab" data-bs-target="#deadPane" type="button" role="tab">
            <i class="bi bi-slash-circle me-1"></i> Mzigo Uliolala (Dead Stock)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab === 'reorder') ? 'active' : '' ?> fw-semibold" id="reorder-tab" data-bs-toggle="tab" data-bs-target="#reorderPane" type="button" role="tab">
            <i class="bi bi-cart-plus me-1"></i> Agiza Upya (Reorder List)
        </button>
    </li>
</ul>

<div class="tab-content" id="inventoryTabsContent">
    <!-- 1. INVENTORY VALUATION TAB -->
    <div class="tab-pane fade <?= ($tab === 'current' || empty($tab)) ? 'show active' : '' ?>" id="valuationPane" role="tabpanel">
        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/index.php" class="row g-2 align-items-center">
                    <input type="hidden" name="route" value="reports/inventory">
                    <input type="hidden" name="tab" value="current">

                    <div class="col-md-3 col-sm-6">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Jina, SKU..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">-- Kategoria Zote --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">-- Chapa Zote --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($filters['brand_id'] == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <select name="stock_status" class="form-select form-select-sm">
                            <option value="">-- Hali ya Stoo --</option>
                            <option value="in_stock" <?= ($filters['stock_status'] === 'in_stock') ? 'selected' : '' ?>>Ipo ya Kutosha</option>
                            <option value="low" <?= ($filters['stock_status'] === 'low') ? 'selected' : '' ?>>Inakaribia Kuisha (Low)</option>
                            <option value="out" <?= ($filters['stock_status'] === 'out') ? 'selected' : '' ?>>Imeisha Kabisa (Out)</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-12 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                        <a href="/index.php?route=reports/inventory" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Kipuri (Spare Part)</th>
                                <th>Kategoria / Chapa</th>
                                <th class="text-center">Stoo Iliyopo</th>
                                <th class="text-center">Kiwango cha Chini</th>
                                <th class="text-end">Bei ya Kununua</th>
                                <th class="text-end">Bei ya Kuuza</th>
                                <th class="text-end pe-3">Thamani ya Stoo (TSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Hakuna vipuri vilivyopatikana.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $idx => $inv): ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($inv['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($inv['sku']) ?></small>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?= htmlspecialchars($inv['category_name'] ?? '—') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($inv['brand_name'] ?? '—') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($inv['current_stock'] <= 0): ?>
                                                <span class="badge bg-danger text-white">Imeisha (0)</span>
                                            <?php elseif ($inv['current_stock'] <= $inv['minimum_stock']): ?>
                                                <span class="badge bg-warning text-dark"><?= number_format($inv['current_stock']) ?> (Inaisha)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($inv['current_stock']) ?> pcs</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-muted"><?= number_format($inv['minimum_stock']) ?></td>
                                        <td class="text-end text-muted"><?= number_format($inv['unit_cost'], 2) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($inv['selling_price'], 2) ?></td>
                                        <td class="text-end pe-3 fw-bold text-primary"><?= number_format($inv['inventory_value'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="small text-muted">Ukurasa <?= $page ?> kati ya <?= $totalPages ?></div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="/index.php?route=reports/inventory&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&category_id=<?= urlencode($filters['category_id']) ?>&brand_id=<?= urlencode($filters['brand_id']) ?>&stock_status=<?= urlencode($filters['stock_status']) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. FAST MOVING TAB -->
    <div class="tab-pane fade <?= ($tab === 'fast') ? 'show active' : '' ?>" id="fastPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-lightning-charge text-warning me-2"></i>Vipuri Vinavyouzika Zaidi (Fast-Moving Products)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th>Kategoria</th>
                                <th class="text-center">Vilivyouzwa</th>
                                <th class="text-center">Mara za Kuuzwa</th>
                                <th class="text-end">Mapato Yaliyopatikana</th>
                                <th class="text-end pe-3">Stoo Iliyopo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fastMoving as $fm): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($fm['product_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($fm['sku']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($fm['category_name'] ?? '—') ?></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($fm['total_qty_sold']) ?></td>
                                    <td class="text-center"><?= number_format($fm['sales_frequency']) ?> mara</td>
                                    <td class="text-end fw-bold text-dark">TSh <?= number_format($fm['total_revenue'], 2) ?></td>
                                    <td class="text-end pe-3"><span class="badge bg-light text-dark border"><?= number_format($fm['current_stock']) ?> pcs</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. SLOW MOVING TAB -->
    <div class="tab-pane fade <?= ($tab === 'slow') ? 'show active' : '' ?>" id="slowPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-split text-info me-2"></i>Vipuri Vinavyochelewa Kuuzika (Slow-Moving Products &gt;30 Siku)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th>Kategoria</th>
                                <th class="text-center">Stoo Iliyopo</th>
                                <th class="text-end">Thamani ya Mali</th>
                                <th class="text-center">Siku Tangu Kuuzwa Mwisho</th>
                                <th class="text-end pe-3">Tarehe ya Mwisho Kuuzwa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slowMoving as $sm): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($sm['product_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($sm['sku']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($sm['category_name'] ?? '—') ?></td>
                                    <td class="text-center fw-semibold"><?= number_format($sm['current_stock']) ?> pcs</td>
                                    <td class="text-end text-muted">TSh <?= number_format($sm['inventory_value'], 2) ?></td>
                                    <td class="text-center"><span class="badge bg-warning-subtle text-warning fw-bold">Siku <?= $sm['days_since_last_sale'] ?></span></td>
                                    <td class="text-end pe-3"><?= !empty($sm['last_sale_date']) ? date('d/m/Y', strtotime($sm['last_sale_date'])) : 'Haijawahi kuuzwa' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. DEAD STOCK TAB -->
    <div class="tab-pane fade <?= ($tab === 'dead') ? 'show active' : '' ?>" id="deadPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-slash-circle text-danger me-2"></i>Mzigo Uliolala Bila Mauzo Zaidi ya Siku 90 (Dead Stock)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th>Kategoria / Chapa</th>
                                <th class="text-center">Stoo Iliyopo</th>
                                <th class="text-end">Bei ya Kununua</th>
                                <th class="text-end pe-3">Thamani Iliyolala (Capital Locked)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deadStock)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Hakuna mzigo uliolala zaidi ya siku 90.</td></tr>
                            <?php else: ?>
                                <?php foreach ($deadStock as $ds): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ds['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($ds['sku']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($ds['category_name'] ?? '—') ?> / <?= htmlspecialchars($ds['brand_name'] ?? '—') ?></td>
                                        <td class="text-center fw-bold text-danger"><?= number_format($ds['current_stock']) ?> pcs</td>
                                        <td class="text-end"><?= number_format($ds['buying_price'], 2) ?></td>
                                        <td class="text-end pe-3 fw-bold text-danger">TSh <?= number_format($ds['inventory_value'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. REORDER LIST TAB -->
    <div class="tab-pane fade <?= ($tab === 'reorder') ? 'show active' : '' ?>" id="reorderPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-cart-plus text-primary me-2"></i>Mapendekezo ya Kuagiza Mzigo Mpya (Reorder Suggestions)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th>Kategoria</th>
                                <th class="text-center">Stoo ya Sasa</th>
                                <th class="text-center">Kiwango cha Kuagiza</th>
                                <th class="text-center">Pendekezo la Idadi (Suggested)</th>
                                <th class="text-end pe-3">Kadirio la Gharama (TSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reorderList)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Vipuri vyote vipo katika viwango salama vya stoo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reorderList as $ro): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ro['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($ro['sku']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($ro['category_name'] ?? '—') ?></td>
                                        <td class="text-center text-danger fw-bold"><?= number_format($ro['current_stock']) ?></td>
                                        <td class="text-center text-muted"><?= number_format($ro['reorder_level']) ?></td>
                                        <td class="text-center fw-bold text-primary"><?= number_format($ro['suggested_reorder_qty']) ?> pcs</td>
                                        <td class="text-end pe-3 fw-semibold text-dark">TSh <?= number_format($ro['estimated_reorder_cost'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
