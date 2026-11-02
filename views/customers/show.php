<?php
use App\Core\Helper;
use App\Core\Auth;

$canSeeFinancials = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 56px; height: 56px;">
            <?= strtoupper(substr($customer['name'], 0, 1)) ?>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($customer['name']) ?></h4>
                <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($customer['customer_code']) ?></span>
                <?php if ($customer['status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Hai (Active)</span>
                <?php elseif ($customer['status'] === 'blocked'): ?>
                    <span class="badge bg-danger text-white rounded-pill">Amezuiwa</span>
                <?php else: ?>
                    <span class="badge bg-secondary text-white rounded-pill">Amesitishwa</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                <i class="bi bi-tag me-1"></i>Aina: <strong class="text-capitalize text-dark"><?= htmlspecialchars($customer['customer_type']) ?></strong>
                &bull; <i class="bi bi-geo-alt ms-2 me-1"></i>Eneo: <?= htmlspecialchars($customer['area'] ?: ($customer['address'] ?: 'Dar es Salaam')) ?>
                &bull; <i class="bi bi-calendar3 ms-2 me-1"></i>Amesajiliwa: <?= date('d M Y', strtotime($customer['created_at'])) ?>
            </p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canManageVehicles): ?>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="bi bi-plus-circle me-1"></i> Unganisha Chombo
            </button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
            <a href="/index.php?route=customer_edit&id=<?= $customer['id'] ?>" class="btn btn-warning btn-sm text-dark fw-semibold">
                <i class="bi bi-pencil me-1"></i> Hariri
            </a>
        <?php endif; ?>
        <?php if ($canDeactivate): ?>
            <?php if ($customer['status'] === 'active'): ?>
                <form method="POST" action="/index.php?route=customer_toggle_status" onsubmit="return confirm('Sitisha mteja huyu?');" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-pause-circle me-1"></i> Sitisha
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="/index.php?route=customer_toggle_status" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-play-circle me-1"></i> Wezesha
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        <a href="/index.php?route=customers" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Wateja
        </a>
    </div>
</div>

<!-- Customer KPI Statistics Cards (Prompt Item 10) -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <span class="text-muted small fw-medium text-uppercase">Jumla ya Mauzo (Purchases)</span>
            <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($stats['total_sales_count']) ?> Mara</h4>
            <span class="text-muted small">Idadi ya vipande vilivyonunuliwa: <?= number_format($stats['total_items_qty']) ?></span>
        </div>
    </div>
    <?php if ($canSeeFinancials): ?>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <span class="text-muted small fw-medium text-uppercase">Jumla ya Fedha (Total Spent)</span>
                <h4 class="fw-bold text-primary mt-1 mb-0"><?= Helper::formatMoney((float)$stats['total_purchases_amount']) ?></h4>
                <span class="text-muted small">Iliyolipwa: <?= Helper::formatMoney((float)$stats['total_paid_amount']) ?></span>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= (float)$stats['outstanding_balance'] > 0 ? 'border-danger' : 'border-success' ?>">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium text-uppercase">Salio la Deni (Balance Due)</span>
                        <h4 class="fw-bold mt-1 mb-0 <?= (float)$stats['outstanding_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= Helper::formatMoney((float)$stats['outstanding_balance']) ?>
                        </h4>
                        <span class="badge <?= $stats['financial_badge'] ?> mt-1 font-monospace" style="font-size: 10px;">
                            <?= $stats['financial_status'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <span class="text-muted small fw-medium text-uppercase">Vyombo Vilivyosajiliwa</span>
            <h4 class="fw-bold text-info mt-1 mb-0"><?= number_format($stats['vehicles_count']) ?> Chombo</h4>
            <span class="text-muted small">
                Ununuzi wa Mwisho: <?= !empty($stats['last_purchase_date']) ? date('d M Y', strtotime($stats['last_purchase_date'])) : 'Hakuna mauzo' ?>
            </span>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Customer Overview & Notes -->
    <div class="col-lg-4">
        <!-- Overview Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Maelezo ya Mawasiliano
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Simu ya Msingi:</span>
                        <span class="fw-semibold">
                            <?= !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : '--' ?>
                        </span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Simu Mbadala:</span>
                        <span class="fw-semibold">
                            <?= !empty($customer['alternative_phone']) ? htmlspecialchars($customer['alternative_phone']) : '--' ?>
                        </span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Barua Pepe:</span>
                        <span><?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : '--' ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Eneo / Mtaa:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($customer['area'] ?: 'Dar es Salaam') ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Anuani:</span>
                        <span><?= htmlspecialchars($customer['address'] ?: '--') ?></span>
                    </li>
                    <?php if ($canSeeFinancials): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted">Ukomo wa Mkopo:</span>
                            <span class="fw-bold text-dark"><?= Helper::formatMoney((float)$customer['credit_limit']) ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Imeingizwa Na:</span>
                        <span><?= htmlspecialchars($customer['created_by_name'] ?? 'Msimamizi') ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Notes Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-chat-left-text text-primary me-2"></i>Maelezo ya Mteja (Customer Notes)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($customer['notes'])): ?>
                    <div class="p-3 bg-light rounded text-dark small border">
                        <?= nl2br(htmlspecialchars($customer['notes'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Hakuna maelezo ya ziada yaliyowekwa kwa mteja huyu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Vehicles, Compatible Spares, Sales, and Payments -->
    <div class="col-lg-8">
        <!-- Tabs Navigation -->
        <ul class="nav nav-pills mb-3 bg-white p-2 rounded-3 shadow-sm border" id="customerTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-2 px-3 fw-semibold small" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button" role="tab">
                    <i class="bi bi-bicycle me-1"></i> Vyombo vya Mteja (<?= count($vehicles) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small" id="compatible-tab" data-bs-toggle="tab" data-bs-target="#compatible" type="button" role="tab">
                    <i class="bi bi-wrench-adjustable me-1"></i> Vipuri Vinavyoendana (<?= count($compatibleParts) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab">
                    <i class="bi bi-receipt me-1"></i> Historia ya Mauzo (<?= count($purchaseHistory) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                    <i class="bi bi-credit-card me-1"></i> Historia ya Malipo (<?= count($paymentHistory) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Marejesho (<?= count($returnsHistory ?? []) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="customerTabContent">
            <!-- 1. Customer Vehicles Tab (Prompt Item 19, 20, 21) -->
            <div class="tab-pane fade show active" id="vehicles" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-bicycle text-primary me-2"></i>Vyombo Vilivyosajiliwa na Mteja
                        </h6>
                        <?php if ($canManageVehicles): ?>
                            <button type="button" class="btn btn-sm btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                                <i class="bi bi-plus-lg me-1"></i> Unganisha Chombo
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($vehicles)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-bicycle fs-2 d-block mb-2 text-secondary"></i>
                                Bado hakuna chombo kilichounganishwa na mteja huyu.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th>Nambari ya Usajili</th>
                                            <th>Muundo wa Chombo</th>
                                            <th>Chapa (Brand)</th>
                                            <th>Aina</th>
                                            <th>Mwaka / CC</th>
                                            <th>Maelezo</th>
                                            <th class="text-end">Vitendo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicles as $v): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-6">
                                                        <?= htmlspecialchars($v['registration_number'] ?: 'BILA NAMBA') ?>
                                                    </span>
                                                </td>
                                                <td class="fw-bold text-dark">
                                                    <?= htmlspecialchars($v['model_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($v['brand_name']) ?></td>
                                                <td>
                                                    <span class="badge <?= $v['vehicle_type'] === 'Bajaj' ? 'bg-warning-subtle text-dark' : 'bg-info-subtle text-info' ?>">
                                                        <?= htmlspecialchars($v['vehicle_type']) ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= htmlspecialchars($v['engine_capacity'] ?? '--') ?>
                                                    <?php if (!empty($v['year_from'])): ?>
                                                        &bull; <?= $v['year_from'] ?>-<?= $v['year_to'] ?: 'Sasa' ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= htmlspecialchars($v['notes'] ?? '--') ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($canManageVehicles): ?>
                                                        <form method="POST" action="/index.php?route=customer_remove_vehicle" onsubmit="return confirm('Ondoa chombo hiki?');" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                                            <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                                            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Ondoa chombo hiki">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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

            <!-- 2. Compatible Spares in Stock (Prompt Item 32) -->
            <div class="tab-pane fade" id="compatible" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-wrench-adjustable text-primary me-2"></i>Vipuri Vilivyopo Stoo Vinavyofaa Vyombo Vya Mteja
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($compatibleParts)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                Hakuna vipuri vinavyoendana vilivyosajiliwa stoo kwa sasa, au mteja hana vyombo vilivyosajiliwa.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th>Kipuri (SKU)</th>
                                            <th>Kategoria & Brand</th>
                                            <th>Inafaa Chombo Hiki</th>
                                            <th class="text-center">Stoo Iliyopo</th>
                                            <th class="text-end">Bei ya Kuuza</th>
                                            <th class="text-center">Shelfu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($compatibleParts as $cp): ?>
                                            <tr>
                                                <td>
                                                    <a href="/index.php?route=product_show&id=<?= $cp['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                        <?= htmlspecialchars($cp['name']) ?>
                                                    </a>
                                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($cp['sku']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($cp['category_name']) ?></span>
                                                    <div class="small text-muted"><?= htmlspecialchars($cp['brand_name'] ?? 'Generic') ?></div>
                                                </td>
                                                <td class="small text-primary fw-semibold">
                                                    <?= htmlspecialchars($cp['for_vehicles']) ?>
                                                </td>
                                                <td class="text-center fw-bold <?= (int)$cp['current_stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= (int)$cp['current_stock'] ?> <?= htmlspecialchars($cp['unit_short']) ?>
                                                </td>
                                                <td class="text-end fw-bold text-dark">
                                                    <?= Helper::formatMoney((float)$cp['selling_price']) ?>
                                                </td>
                                                <td class="text-center small text-muted">
                                                    <?= htmlspecialchars($cp['shelf_location'] ?: '--') ?>
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

            <!-- 3. Purchase History Tab (Prompt Item 11) -->
            <div class="tab-pane fade" id="purchases" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-receipt text-primary me-2"></i>Historia ya Manunuzi (Sales Invoices)
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($purchaseHistory)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2 text-secondary"></i>
                                Bado hakuna mauzo yaliyofanyika kwa mteja huyu.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th>Tarehe</th>
                                            <th>Nambari ya Ankara (Invoice)</th>
                                            <th class="text-center">Vipuri</th>
                                            <th class="text-end">Jumla ya Fedha</th>
                                            <th class="text-end">Iliyolipwa</th>
                                            <th class="text-end">Deni (Balance)</th>
                                            <th class="text-center">Hali</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($purchaseHistory as $ph): ?>
                                            <tr>
                                                <td class="small text-muted"><?= date('d M Y, H:i', strtotime($ph['sale_date'])) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border font-monospace">
                                                        <?= htmlspecialchars($ph['invoice_no']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center small text-muted"><?= $ph['item_count'] ?> vipande</td>
                                                <td class="text-end fw-bold text-dark"><?= Helper::formatMoney((float)$ph['total_amount']) ?></td>
                                                <td class="text-end text-success fw-semibold"><?= Helper::formatMoney((float)$ph['paid_amount']) ?></td>
                                                <td class="text-end fw-bold <?= (float)$ph['balance_due'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                                    <?= Helper::formatMoney((float)$ph['balance_due']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?= $ph['payment_status'] === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                                        <?= strtoupper($ph['payment_status']) ?>
                                                    </span>
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

            <!-- 4. Payment History Tab -->
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-credit-card text-primary me-2"></i>Historia ya Malipo ya Madeni (Customer Payments)
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($paymentHistory)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-cash-stack fs-2 d-block mb-2 text-secondary"></i>
                                Hakuna kumbukumbu za malipo ya madeni zilizorekodiwa kwa mteja huyu.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th>Tarehe</th>
                                            <th>Njia ya Malipo</th>
                                            <th>Nambari ya Rejea</th>
                                            <th class="text-end">Kiasi Kilicholipwa</th>
                                            <th>Imepokelewa Na</th>
                                            <th>Maelezo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $pm): ?>
                                            <tr>
                                                <td class="small text-muted"><?= date('d M Y, H:i', strtotime($pm['payment_date'])) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pm['payment_method']) ?></span></td>
                                                <td class="font-monospace small"><?= htmlspecialchars($pm['reference_no'] ?? '--') ?></td>
                                                <td class="text-end fw-bold text-success fs-6"><?= Helper::formatMoney((float)$pm['amount']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($pm['received_by_name'] ?? 'Mweka Hazina') ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($pm['notes'] ?? '--') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 5. Returns History Tab (Phase 11) -->
            <div class="tab-pane fade" id="returns" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-arrow-counterclockwise text-primary me-2"></i>Historia ya Marejesho ya Bidhaa (Customer Returns)
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($returnsHistory)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Hakuna kumbukumbu za marejesho zilizorekodiwa kwa mteja huyu.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0">
                                    <thead class="table-light small text-muted">
                                        <tr>
                                            <th class="ps-3">Rejesho #</th>
                                            <th>Ankara</th>
                                            <th>Tarehe</th>
                                            <th>Sababu</th>
                                            <th class="text-center">Vipuri</th>
                                            <th class="text-end">Kiasi cha Fedha</th>
                                            <th class="text-center">Hadhi</th>
                                            <th class="text-end pe-3">Kitendo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($returnsHistory as $ret): ?>
                                            <tr>
                                                <td class="ps-3 font-monospace fw-bold text-primary">
                                                    <a href="/index.php?route=return_show&id=<?= $ret['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($ret['return_number']) ?>
                                                    </a>
                                                </td>
                                                <td class="font-monospace small">
                                                    <a href="/index.php?route=sale_show&id=<?= $ret['sale_id'] ?>" class="text-decoration-none">
                                                        #<?= htmlspecialchars($ret['invoice_no']) ?>
                                                    </a>
                                                </td>
                                                <td class="small text-muted"><?= date('d M Y', strtotime($ret['return_date'])) ?></td>
                                                <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars(str_replace('_', ' ', $ret['return_type'])) ?></span></td>
                                                <td class="text-center font-monospace small"><?= $ret['total_units_returned'] ?> pcs</td>
                                                <td class="text-end fw-bold text-dark font-monospace"><?= Helper::formatMoney((float)$ret['refund_amount']) ?></td>
                                                <td class="text-center"><span class="badge bg-primary-subtle text-primary border text-uppercase" style="font-size:10px;"><?= $ret['status'] ?></span></td>
                                                <td class="text-end pe-3">
                                                    <a href="/index.php?route=return_show&id=<?= $ret['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                        <i class="bi bi-eye"></i>
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
        </div>
    </div>
</div>

<!-- Modal: Add Vehicle (Prompt Item 19) -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addVehicleModalLabel">
                    <i class="bi bi-bicycle text-primary me-2"></i>Unganisha Chombo cha Mteja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=customer_add_vehicle">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Muundo wa Chombo (Vehicle Model) <span class="text-danger">*</span></label>
                        <select name="vehicle_model_id" class="form-select" required>
                            <option value="">-- Chagua Muundo wa Pikipiki au Bajaji --</option>
                            <?php foreach ($vehicleModels as $vm): ?>
                                <option value="<?= $vm['id'] ?>">
                                    <?= htmlspecialchars($vm['brand_name'] . ' ' . $vm['model_name'] . ' (' . $vm['vehicle_type'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nambari ya Usajili (Registration Number / Namba ya Plate)</label>
                        <input type="text" name="registration_number" class="form-control text-uppercase" placeholder="Mf. T 123 ABC au MC 456 DEF">
                        <small class="text-muted" style="font-size: 11px;">Inaweza kutumika kutafuta mteja au vipuri kwa haraka.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Namba ya Chassis</label>
                            <input type="text" name="chassis_number" class="form-control form-control-sm" placeholder="Chassis No">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Namba ya Injini</label>
                            <input type="text" name="engine_number" class="form-control form-control-sm" placeholder="Engine No">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada kuhusu Chombo</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Mf. Pikipiki ya mkataba, dereva ni Ali, n.k."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary-custom btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Hifadhi Chombo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
