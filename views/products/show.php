<?php
use App\Core\Helper;
use App\Core\Auth;

$m = $product['margin_analysis'];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($product['name']) ?></h4>
            <?php if ($product['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">Hai (Inauzwa)</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">Imezimwa (Inactive)</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">
            SKU: <code><?= htmlspecialchars($product['sku']) ?></code> &bull; 
            Kategoria: <strong><?= htmlspecialchars($product['category_name']) ?></strong> &bull; 
            Brand: <strong><?= htmlspecialchars($product['brand_name'] ?? 'Generic') ?></strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=products" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Orodha
        </a>
        <?php if ($canManage): ?>
            <a href="/index.php?route=product_edit&id=<?= $product['id'] ?>" class="btn btn-primary-custom d-flex align-items-center gap-1">
                <i class="bi bi-pencil-square"></i>
                <span>Hariri Kipuri</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Financial & Stock KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Selling Price Card -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card kpi-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-title">Bei Halisi ya Kuuza</div>
                    <div class="kpi-value text-primary"><?= Helper::formatMoney((float)$product['selling_price']) ?></div>
                    <div class="kpi-subtext">Bei ya Chini: <?= Helper::formatMoney((float)$product['minimum_selling_price']) ?></div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-tag-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Buying Cost Card (Role-Protected) -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-title">Bei ya Kununua (Cost)</div>
                    <?php if ($canSeeCosts): ?>
                        <div class="kpi-value fs-5 text-dark"><?= Helper::formatMoney((float)$product['buying_price']) ?></div>
                        <div class="kpi-subtext">30% Rec: <?= Helper::formatMoney((float)$product['recommended_selling_price']) ?></div>
                    <?php else: ?>
                        <div class="kpi-value fs-5 text-muted">••••••••</div>
                        <div class="kpi-subtext text-muted"><i class="bi bi-lock-fill me-1"></i>Haki Maalumu ya Kifedha</div>
                    <?php endif; ?>
                </div>
                <div class="kpi-icon bg-light text-secondary">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Realized Profit Amount Card -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card <?= ($canSeeCosts && (float)$m['gross_profit'] >= 0) ? 'kpi-success' : 'kpi-warning' ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-title">Kiasi cha Faida (Profit Amount)</div>
                    <?php if ($canSeeCosts): ?>
                        <?php $profitVal = (float)$m['gross_profit']; ?>
                        <div class="kpi-value <?= $profitVal >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $profitVal >= 0 ? '+' : '' ?><?= Helper::formatMoney($profitVal) ?>
                        </div>
                        <div class="kpi-subtext">
                            Faida ya Mauzo kwa kipande/kipimo
                        </div>
                    <?php else: ?>
                        <div class="kpi-value text-muted">••••••••</div>
                        <div class="kpi-subtext text-muted"><i class="bi bi-shield-lock me-1"></i>Imehifadhiwa</div>
                    <?php endif; ?>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Stock Card -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-title">Stoo Halisi</div>
                    <div class="kpi-value fs-5 <?= (int)$product['current_stock'] <= (int)$product['reorder_level'] ? 'text-danger' : 'text-teal' ?>">
                        <?= (int)$product['current_stock'] ?> <?= htmlspecialchars($product['unit_short']) ?>
                    </div>
                    <div class="kpi-subtext">Reorder Level: <?= (int)$product['reorder_level'] ?> <?= htmlspecialchars($product['unit_short']) ?></div>
                </div>
                <div class="kpi-icon bg-light text-secondary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Specs & Vehicle Compatibility -->
    <div class="col-lg-7">

        <!-- Product Visual Identification Card -->
        <div class="premium-card mb-4">
            <div class="premium-card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-image text-primary"></i> Muonekano Halisi wa Kipuri (Physical Appearance)</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                    <i class="bi bi-hdd-network me-1"></i>Maktaba ya Ndani (Offline Library)
                </span>
            </div>
            <div class="premium-card-body p-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-5 text-center">
                        <div class="p-2 bg-light rounded-3 border d-flex align-items-center justify-content-center position-relative" style="min-height: 220px;">
                            <img id="staffProductImg" src="<?= htmlspecialchars($product['image_url'] ?: '/assets/images/placeholder_product.svg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="img-fluid rounded" 
                                 style="max-height: 200px; object-fit: contain;"
                                 onerror="this.src='/assets/images/placeholder_product.svg';">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                        <div class="small text-muted mb-2">
                            <code><?= htmlspecialchars($product['sku']) ?></code> &bull; Kategoria: <strong><?= htmlspecialchars($product['category_name']) ?></strong>
                        </div>
                        <p class="small text-secondary mb-3">
                            Picha hii imehifadhiwa ndani ya mfumo (Local Offline Storage) ili kuwawezesha mafundi, wafanyakazi wa stoo na wauzaji kutambua kipuri hiki kwa usahihi kabla ya kuuza au kupokea mzigo.
                        </p>
                        <div class="p-2 bg-light rounded border small">
                            <div class="text-muted" style="font-size:11px;">Njia ya Faili (Local Path):</div>
                            <code class="text-dark" style="font-size:11px; word-break:break-all;"><?= htmlspecialchars($product['image_url'] ?: 'assets/images/placeholder_product.svg') ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Specifications Card -->
        <div class="premium-card mb-4">
            <div class="premium-card-header">
                <h5><i class="bi bi-card-checklist text-primary"></i> Maelezo Kamili ya Kipuri</h5>
            </div>
            <div class="premium-card-body p-0">
                <table class="table mb-0 align-middle">
                    <tbody>
                        <tr>
                            <td class="text-muted small px-3 py-2.5" style="width: 35%;">Jina la Kipuri</td>
                            <td class="fw-bold px-3 py-2.5"><?= htmlspecialchars($product['name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Nambari ya Utambulisho (SKU)</td>
                            <td class="px-3 py-2.5"><code><?= htmlspecialchars($product['sku']) ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Nambari ya Barcode</td>
                            <td class="px-3 py-2.5"><?= !empty($product['barcode']) ? htmlspecialchars($product['barcode']) : '<span class="text-muted">Hakuna</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Kategoria</td>
                            <td class="px-3 py-2.5"><span class="badge bg-light text-dark border"><?= htmlspecialchars($product['category_name']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Chapa ya Kipuri (Brand)</td>
                            <td class="px-3 py-2.5"><?= htmlspecialchars($product['brand_name'] ?? 'Generic') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Kipimo cha Hesabu (Unit)</td>
                            <td class="px-3 py-2.5"><?= htmlspecialchars($product['unit_name']) ?> (<?= htmlspecialchars($product['unit_short']) ?>)</td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Eneo la Shelfu / Droo</td>
                            <td class="px-3 py-2.5"><?= !empty($product['shelf_location']) ? htmlspecialchars($product['shelf_location']) : '<span class="text-muted">Haijawekwa</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Waranti</td>
                            <td class="px-3 py-2.5"><?= (int)$product['warranty_days'] > 0 ? (int)$product['warranty_days'] . ' Siku' : 'Hakuna waranti' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small px-3 py-2.5">Maelezo (Description)</td>
                            <td class="px-3 py-2.5"><?= !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vehicle Compatibility Card -->
        <div class="premium-card">
            <div class="premium-card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-bicycle text-teal"></i> Vyombo Vinavyoendana (<?= count($product['compatible_vehicles']) ?>)</h5>
                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">Relational Mapping</span>
            </div>
            <div class="premium-card-body p-3">
                <?php if (empty($product['compatible_vehicles'])): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bicycle fs-2 d-block mb-1"></i>
                        Hakuna muundo maalum wa chombo uliounganishwa na kipuri hiki.
                        <?php if ($canManage): ?>
                            <div class="mt-2">
                                <a href="/index.php?route=product_edit&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus me-1"></i> Ongeza Uendano wa Vyombo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($product['compatible_vehicles'] as $v): ?>
                            <div class="col-md-6">
                                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-bold small"><?= htmlspecialchars($v['brand_name']) ?> <?= htmlspecialchars($v['model_name']) ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <span class="badge <?= $v['vehicle_type'] === 'Bajaj' ? 'bg-warning-subtle text-dark' : 'bg-info-subtle text-info' ?> py-0 px-1 me-1">
                                                <?= htmlspecialchars($v['vehicle_type']) ?>
                                            </span>
                                            <?= htmlspecialchars($v['engine_capacity'] ?? '') ?>
                                            <?php if (!empty($v['year_from'])): ?> &bull; <?= $v['year_from'] ?>-<?= $v['year_to'] ?: 'Sasa' ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Stock Movement History Card (Phase 5) -->
        <div class="premium-card mb-4">
            <div class="premium-card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-box-seam text-primary"></i> Historia ya Mienendo ya Stoo (Stock History)</h5>
                <a href="/index.php?route=inventory_adjust&product_id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-warning text-dark">
                    <i class="bi bi-sliders me-1"></i> Rekebisha Stoo
                </a>
            </div>
            <div class="premium-card-body p-0">
                <?php if (empty($stockHistory)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-clock-history fs-3 d-block mb-1 text-secondary"></i>
                        Hakuna mienendo ya stoo iliyorekodiwa kwa kipuri hiki bado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Tarehe</th>
                                    <th>Mwenendo</th>
                                    <th class="text-center">Kiasi</th>
                                    <th class="text-center">Baki (Balance)</th>
                                    <th>Rejea</th>
                                    <th>Mtumiaji</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockHistory as $sh): ?>
                                    <?php
                                    $shBadges = [
                                        'purchase'               => ['bg' => 'bg-success-subtle text-success border border-success-subtle', 'lbl' => 'Purchase (+)'],
                                        'sale'                   => ['bg' => 'bg-primary-subtle text-primary border border-primary-subtle', 'lbl' => 'Sale (-)'],
                                        'adjustment_in'          => ['bg' => 'bg-info-subtle text-info border border-info-subtle', 'lbl' => 'Adjustment (+)'],
                                        'adjustment_out'         => ['bg' => 'bg-secondary-subtle text-secondary border', 'lbl' => 'Adjustment (-)'],
                                        'damage'                 => ['bg' => 'bg-danger-subtle text-danger border border-danger-subtle', 'lbl' => 'Damage (-)'],
                                        'loss'                   => ['bg' => 'bg-danger-subtle text-danger border border-danger-subtle', 'lbl' => 'Loss (-)'],
                                        'stock_count_adjustment' => ['bg' => 'bg-warning-subtle text-warning border border-warning-subtle', 'lbl' => 'Stock Count'],
                                        'opening_stock'          => ['bg' => 'bg-light text-dark border', 'lbl' => 'Opening Stock']
                                    ];
                                    $b = $shBadges[$sh['transaction_type']] ?? ['bg' => 'bg-light text-dark border', 'lbl' => $sh['transaction_type']];
                                    ?>
                                    <tr>
                                        <td class="small text-muted" style="font-size:11px; white-space:nowrap;">
                                            <?= date('d/m/Y H:i', strtotime($sh['created_at'])) ?>
                                        </td>
                                        <td><span class="badge <?= $b['bg'] ?>"><?= $b['lbl'] ?></span></td>
                                        <td class="text-center font-monospace fw-bold <?= $sh['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= ($sh['quantity'] > 0 ? '+' : '') . $sh['quantity'] ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold text-dark">
                                            <?= $sh['quantity_after'] ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= $sh['reference_type'] ? htmlspecialchars(strtoupper($sh['reference_type']) . ($sh['reference_id'] ? ' #' . $sh['reference_id'] : '')) : '--' ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($sh['user_name'] ?? $sh['user_username'] ?? 'System') ?>
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

    <!-- Right Column: Price & Cost History Timelines -->
    <div class="col-lg-5">

        <!-- Price History Card -->
        <div class="premium-card mb-4">
            <div class="premium-card-header">
                <h5><i class="bi bi-clock-history text-primary"></i> Kumbukumbu ya Bei (Price History)</h5>
            </div>
            <div class="premium-card-body p-0">
                <?php if (empty($priceHistory)): ?>
                    <div class="text-center py-4 text-muted small">Hakuna historia ya mabadiliko ya bei.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="custom-table table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Muda</th>
                                    <th>Bei ya Kuuza</th>
                                    <th>Sababu / Msimamizi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($priceHistory as $ph): ?>
                                    <tr>
                                        <td class="small text-muted" style="font-size:11px;">
                                            <?= date('d/m/y H:i', strtotime($ph['created_at'])) ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= Helper::formatMoney((float)$ph['new_selling_price']) ?></span>
                                            <?php if ((float)$ph['old_selling_price'] > 0): ?>
                                                <div class="text-muted text-decoration-line-through" style="font-size:10px;">
                                                    <?= Helper::formatMoney((float)$ph['old_selling_price']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div><?= htmlspecialchars($ph['reason'] ?? 'Mabadiliko ya kawaida') ?></div>
                                            <small class="text-muted" style="font-size:10px;"><?= htmlspecialchars($ph['changed_by_name'] ?? 'Mfumo') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Returns & Return Rate Analysis Card (Phase 11) -->
        <div class="premium-card mb-4">
            <div class="premium-card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-arrow-counterclockwise text-danger"></i> Historia & Kiwango cha Marejesho (Return Rate)</h5>
                <?php 
                $rRate = (float)($returnStats['return_rate_percent'] ?? 0);
                $badgeClr = ($rRate >= 10) ? 'bg-danger text-white' : (($rRate >= 5) ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle');
                ?>
                <span class="badge <?= $badgeClr ?> font-monospace px-2 py-1">
                    Return Rate: <?= number_format($rRate, 1) ?>%
                </span>
            </div>
            <div class="premium-card-body p-3">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <small class="text-muted d-block" style="font-size:11px;">Vilivyouzwa (Sold)</small>
                            <span class="fw-bold font-monospace text-primary fs-6"><?= number_format((int)($returnStats['total_units_sold'] ?? 0)) ?> pcs</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <small class="text-muted d-block" style="font-size:11px;">Vilivyorudishwa (Ret.)</small>
                            <span class="fw-bold font-monospace text-danger fs-6"><?= number_format((int)($returnStats['total_units_returned'] ?? 0)) ?> pcs</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <small class="text-muted d-block" style="font-size:11px;">Hali: Nzima / Mbovu</small>
                            <span class="fw-bold font-monospace text-dark fs-6">
                                <span class="text-success"><?= (int)($returnStats['resellable_units'] ?? 0) ?></span> / 
                                <span class="text-danger"><?= (int)($returnStats['damaged_units'] ?? 0) + (int)($returnStats['defective_units'] ?? 0) ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (empty($returnHistory)): ?>
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                        Hakuna historia ya marejesho kwa kipuri hiki.
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Rejesho #</th>
                                    <th>Tarehe</th>
                                    <th>Mteja</th>
                                    <th class="text-center">Idadi</th>
                                    <th>Hali</th>
                                    <th class="text-end">Thamani</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($returnHistory as $rh): ?>
                                    <tr>
                                        <td class="font-monospace fw-bold small">
                                            <a href="/index.php?route=return_show&id=<?= $rh['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($rh['return_number']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= date('d/m/y', strtotime($rh['return_date'])) ?></td>
                                        <td class="small"><?= htmlspecialchars($rh['customer_name'] ?? 'Mteja') ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= $rh['return_quantity'] ?></td>
                                        <td>
                                            <span class="badge <?= $rh['condition_status'] === 'resellable' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> py-0 px-1" style="font-size:10px;">
                                                <?= htmlspecialchars($rh['condition_status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace small fw-bold text-dark">
                                            <?= Helper::formatMoney((float)$rh['return_item_subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cost History Card (Restricted to Authorized Financial Roles) -->
        <?php if ($canSeeCosts): ?>
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5><i class="bi bi-cash-coin text-teal"></i> Historia ya Gharama (Cost History)</h5>
                </div>
                <div class="premium-card-body p-0">
                    <?php if (empty($costHistory)): ?>
                        <div class="text-center py-4 text-muted small">Hakuna historia ya mabadiliko ya gharama.</div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="custom-table table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Muda</th>
                                        <th>Gharama Mpya</th>
                                        <th>Sababu / Msimamizi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($costHistory as $ch): ?>
                                        <tr>
                                            <td class="small text-muted" style="font-size:11px;">
                                                <?= date('d/m/y H:i', strtotime($ch['created_at'])) ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= Helper::formatMoney((float)$ch['new_cost']) ?></span>
                                                <?php if ((float)$ch['old_cost'] > 0): ?>
                                                    <div class="text-muted text-decoration-line-through" style="font-size:10px;">
                                                        <?= Helper::formatMoney((float)$ch['old_cost']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <div><?= htmlspecialchars($ch['reason'] ?? 'Manunuzi/Marekebisho') ?></div>
                                                <small class="text-muted" style="font-size:10px;"><?= htmlspecialchars($ch['changed_by_name'] ?? 'Mfumo') ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
