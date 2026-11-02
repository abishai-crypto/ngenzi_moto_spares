<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-boxes text-primary me-2"></i>Dashibodi ya Stoo & Mali (Inventory)
        </h4>
        <p class="text-muted small mb-0">Usimamizi thabiti wa mienendo ya vipuri, upatikanaji wa bidhaa, na thamani ya stoo kwa wakati halisi.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=inventory" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-list-ul"></i> Orodha ya Stoo
        </a>
        <a href="/index.php?route=inventory_adjust" class="btn btn-warning btn-sm text-dark fw-semibold d-flex align-items-center gap-1">
            <i class="bi bi-sliders"></i> Rekebisha Stoo
        </a>
        <a href="/index.php?route=inventory_counts" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-clipboard-check"></i> Ukaguzi wa Stoo
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Vipuri Vilivyopo (Products)</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['total_products']) ?></h3>
                    <span class="text-muted small">Aina za vipuri stoo</span>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-4">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Jumla ya Vipande (Units)</span>
                    <h3 class="fw-bold mb-0 mt-1 text-success"><?= number_format($stats['total_units']) ?></h3>
                    <span class="text-muted small">Idadi kamili ya stoo</span>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3 fs-4">
                    <i class="bi bi-layers-half"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Vipuri Chini ya Kiwango (Low)</span>
                    <h3 class="fw-bold mb-0 mt-1 <?= $stats['low_stock_count'] > 0 ? 'text-warning' : 'text-muted' ?>">
                        <?= number_format($stats['low_stock_count']) ?>
                    </h3>
                    <span class="text-muted small">Vinahitaji kuagizwa haraka</span>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3 fs-4">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium text-uppercase">Vipuri Vilivyoisha (Out)</span>
                    <h3 class="fw-bold mb-0 mt-1 <?= $stats['out_of_stock_count'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= number_format($stats['out_of_stock_count']) ?>
                    </h3>
                    <span class="text-muted small">Stoo = 0 (Havipatikani)</span>
                </div>
                <div class="rounded-circle bg-danger-subtle text-danger p-3 fs-4">
                    <i class="bi bi-x-octagon-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canSeeCosts): ?>
<!-- Financial Valuation KPI Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-4 border-primary">
            <span class="text-muted small fw-semibold">THAMANI YA STOO KWA BEI YA KUNUNUA (COST VALUE)</span>
            <h4 class="fw-bold text-primary mt-1 mb-0"><?= Helper::formatMoney($stats['total_cost_value']) ?></h4>
            <span class="text-muted small">Mtaji uliofungwa kwenye bidhaa</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-4 border-success">
            <span class="text-muted small fw-semibold">THAMANI YA STOO KWA BEI YA KUUZA (RETAIL VALUE)</span>
            <h4 class="fw-bold text-success mt-1 mb-0"><?= Helper::formatMoney($stats['total_retail_value']) ?></h4>
            <span class="text-muted small">Mauzo yanayotarajiwa</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-4 border-info">
            <span class="text-muted small fw-semibold">FAIDA INAYOTARAJIWA (POTENTIAL GROSS PROFIT)</span>
            <h4 class="fw-bold text-info mt-1 mb-0"><?= Helper::formatMoney($stats['potential_profit']) ?></h4>
            <span class="text-muted small">Mapato ya faida ghafi (Margin ~30%)</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions & Alert Columns -->
<!-- Quick Actions & Alert Columns -->
<div class="row g-4 mb-4">
    <!-- Low Stock Alert Box -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-warning d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> Tahadhari ya Vipuri Vinavyoelekea Kuisha (Low Stock)
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <a href="/index.php?route=inventory_reorder" class="btn btn-sm btn-outline-warning text-dark">
                        <i class="bi bi-cart-plus me-1"></i> Mapendekezo ya Kuagiza (Reorder)
                    </a>
                    <a href="/index.php?route=inventory&status=low_stock" class="btn btn-sm btn-light border">
                        View Inventory &rarr;
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-2 mb-2 d-block"></i>
                        Hakuna vipuri vilivyo chini ya kiwango cha chini stoo.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Kipuri (Product)</th>
                                    <th>SKU</th>
                                    <th class="text-center">Iliyopo (Current)</th>
                                    <th class="text-center">Kiwango cha Chini (Min)</th>
                                    <th class="text-center">Reorder Level</th>
                                    <th>Msambazaji (Supplier)</th>
                                    <th class="text-end">Gharama (Last Cost)</th>
                                    <th class="text-center">Hali</th>
                                    <th class="text-end">Kitendo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $lp): ?>
                                    <tr>
                                        <td>
                                            <a href="/index.php?route=product_show&id=<?= $lp['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($lp['name']) ?>
                                            </a>
                                            <div class="small text-muted"><?= htmlspecialchars($lp['category_name']) ?></div>
                                        </td>
                                        <td><code><?= htmlspecialchars($lp['sku']) ?></code></td>
                                        <td class="text-center fw-bold text-warning fs-6">
                                            <?= $lp['quantity'] ?> <span class="small fw-normal text-muted"><?= $lp['unit_short'] ?></span>
                                        </td>
                                        <td class="text-center text-muted">
                                            <?= $lp['minimum_stock_level'] ?> <?= $lp['unit_short'] ?>
                                        </td>
                                        <td class="text-center text-muted">
                                            <?= (int)($lp['reorder_level'] ?? 10) ?> <?= $lp['unit_short'] ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($lp['last_supplier_name'] ?? 'Mkuu wa Stoo') ?>
                                        </td>
                                        <td class="text-end small">
                                            <?= $canSeeCosts ? Helper::formatMoney((float)$lp['last_purchase_price']) : '<span class="text-muted">***</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">LOW STOCK</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/index.php?route=inventory&search=<?= urlencode($lp['sku']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;">
                                                View Inventory
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Out of Stock Alert Box -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-danger d-flex align-items-center gap-2">
                    <i class="bi bi-x-octagon-fill"></i> Vipuri Vilivyoisha Kabisa (Out of Stock)
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <a href="/index.php?route=inventory_reorder" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-cart-x me-1"></i> Agiza Vipuri Vilivyoisha
                    </a>
                    <a href="/index.php?route=inventory&status=out_of_stock" class="btn btn-sm btn-light border">
                        View Inventory &rarr;
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($outOfStockProducts)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-2 mb-2 d-block"></i>
                        Hakuna kipuri kilichoisha stoo. Bidhaa zote zina idadi chanya.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Kipuri (Product)</th>
                                    <th>SKU</th>
                                    <th>Kategoria</th>
                                    <th>Chapa (Brand)</th>
                                    <th>Msambazaji wa Mwisho</th>
                                    <th class="text-end">Gharama ya Mwisho</th>
                                    <th class="text-center">Mauzo ya Mwisho</th>
                                    <th class="text-end">Hali</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($outOfStockProducts as $op): ?>
                                    <tr>
                                        <td>
                                            <a href="/index.php?route=product_show&id=<?= $op['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($op['name']) ?>
                                            </a>
                                        </td>
                                        <td><code><?= htmlspecialchars($op['sku']) ?></code></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($op['category_name']) ?></span></td>
                                        <td><?= htmlspecialchars($op['brand_name'] ?? 'Generic') ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($op['last_supplier_name'] ?? 'Haijarekodiwa') ?></td>
                                        <td class="text-end small">
                                            <?= $canSeeCosts ? Helper::formatMoney((float)$op['last_purchase_price']) : '<span class="text-muted">***</span>' ?>
                                        </td>
                                        <td class="text-center small text-muted">
                                            <?= !empty($op['last_sale_date']) ? date('d M Y', strtotime($op['last_sale_date'])) : 'Hakuna mauzo' ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">OUT OF STOCK</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Stock Movements -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Mienendo ya Hivi Karibuni ya Stoo (Recent Movements)
        </h6>
        <a href="/index.php?route=inventory_history" class="btn btn-sm btn-light border">Historia Kamili &rarr;</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($stats['recent_movements'])): ?>
            <div class="p-4 text-center text-muted">Bado hakuna mienendo ya stoo iliyorekodiwa.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Tarehe / Saa</th>
                            <th>Kipuri</th>
                            <th>Aina ya Mwenendo</th>
                            <th class="text-center">Kabla</th>
                            <th class="text-center">Mabadiliko</th>
                            <th class="text-center">Baada</th>
                            <th>Mtumiaji</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_movements'] as $mv): ?>
                            <tr>
                                <td class="small text-muted"><?= date('d M Y, H:i', strtotime($mv['created_at'])) ?></td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($mv['product_name']) ?></span>
                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($mv['product_sku']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $typeBadges = [
                                        'purchase'               => ['bg' => 'success-subtle text-success border border-success-subtle', 'label' => 'Manunuzi (Purchase)'],
                                        'sale'                   => ['bg' => 'primary-subtle text-primary border border-primary-subtle', 'label' => 'Mauzo (Sale)'],
                                        'adjustment_in'          => ['bg' => 'info-subtle text-info border border-info-subtle', 'label' => 'Marekebisho (+)'],
                                        'adjustment_out'         => ['bg' => 'secondary-subtle text-secondary border', 'label' => 'Marekebisho (-)'],
                                        'damage'                 => ['bg' => 'danger-subtle text-danger border border-danger-subtle', 'label' => 'Imeharibika (Damage)'],
                                        'loss'                   => ['bg' => 'danger-subtle text-danger border border-danger-subtle', 'label' => 'Imepotea (Loss)'],
                                        'stock_count'            => ['bg' => 'warning-subtle text-warning border border-warning-subtle', 'label' => 'Ukaguzi (Count)'],
                                        'stock_count_adjustment' => ['bg' => 'warning-subtle text-warning border border-warning-subtle', 'label' => 'Ukaguzi wa Stoo'],
                                        'opening_stock'          => ['bg' => 'light text-dark border', 'label' => 'Stoo ya Mwanzo']
                                    ];
                                    $badge = $typeBadges[$mv['transaction_type']] ?? ['bg' => 'secondary', 'label' => $mv['transaction_type']];
                                    ?>
                                    <span class="badge <?= $badge['bg'] ?>"><?= $badge['label'] ?></span>
                                </td>
                                <td class="text-center text-muted"><?= $mv['quantity_before'] ?></td>
                                <td class="text-center fw-bold <?= $mv['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($mv['quantity'] > 0 ? '+' : '') . $mv['quantity'] ?>
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $mv['quantity_after'] ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($mv['user_name'] ?? 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
