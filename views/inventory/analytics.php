<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Uchambuzi wa Mienendo ya Vipuri (Stock Movement Analytics)
        </h4>
        <p class="text-muted small mb-0">Tambua vipuri vinavyotembea kwa kasi (Fast), vinavyosuasua (Slow), na vilivyolala (Dead Stock).</p>
    </div>
    <!-- Period Buttons -->
    <div class="btn-group" role="group">
        <a href="/index.php?route=inventory_analytics&period=7d" class="btn btn-sm <?= ($period === '7d') ? 'btn-primary-custom' : 'btn-outline-secondary' ?>">
            Siku 7 Zilizopita
        </a>
        <a href="/index.php?route=inventory_analytics&period=30d" class="btn btn-sm <?= ($period === '30d') ? 'btn-primary-custom' : 'btn-outline-secondary' ?>">
            Mwezi 1 (Siku 30)
        </a>
        <a href="/index.php?route=inventory_analytics&period=90d" class="btn btn-sm <?= ($period === '90d') ? 'btn-primary-custom' : 'btn-outline-secondary' ?>">
            Miezi 3 (Siku 90)
        </a>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="analyticsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="fast-tab" data-bs-toggle="pill" data-bs-target="#fast" type="button" role="tab">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Vinavyouzika kwa Kasi (Fast Moving)
            <span class="badge bg-white text-dark ms-2"><?= count($analytics['fast_moving']) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="slow-tab" data-bs-toggle="pill" data-bs-target="#slow" type="button" role="tab">
            <i class="bi bi-hourglass-split text-info me-1"></i> Vinavyosuasua (Slow Moving)
            <span class="badge bg-white text-dark ms-2"><?= count($analytics['slow_moving']) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="dead-tab" data-bs-toggle="pill" data-bs-target="#dead" type="button" role="tab">
            <i class="bi bi-snow text-danger me-1"></i> Mali Iliyolala / Haitembei (Dead Stock)
            <span class="badge bg-white text-dark ms-2"><?= count($analytics['dead_stock']) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="analyticsTabsContent">
    <!-- Fast Moving Tab -->
    <div class="tab-pane fade show active" id="fast" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-fire text-danger me-1"></i> Vipuri 15 Vinavyoongoza kwa Mauzo Katika Siku <?= $analytics['days'] ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($analytics['fast_moving'])): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-graph-down fs-1 d-block mb-2 text-secondary"></i>
                        Hakuna rekodi za mauzo ya hivi karibuni katika kipindi hiki cha siku <?= $analytics['days'] ?>.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Kipuri</th>
                                    <th>Kategoria</th>
                                    <th class="text-center">Vipande Vilivyouzwa</th>
                                    <th class="text-center">Mara Ngapi Viliuzwa</th>
                                    <th class="text-center">Stoo Iliyopo Sasa</th>
                                    <th>Tarehe ya Mwisho Kuuzwa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['fast_moving'] as $fm): ?>
                                    <tr>
                                        <td>
                                            <a href="/index.php?route=product_show&id=<?= $fm['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($fm['name']) ?>
                                            </a>
                                            <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($fm['sku']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($fm['category_name']) ?></td>
                                        <td class="text-center fw-bold fs-6 text-success">
                                            <?= (int)$fm['total_sold'] ?> <?= htmlspecialchars($fm['unit_short'] ?? 'Pcs') ?>
                                        </td>
                                        <td class="text-center small text-muted">
                                            <?= (int)$fm['sales_count'] ?> mara
                                        </td>
                                        <td class="text-center fw-semibold text-dark">
                                            <?= (int)$fm['current_stock'] ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= $fm['last_sale_date'] ? date('d M Y', strtotime($fm['last_sale_date'])) : '--' ?>
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

    <!-- Slow Moving Tab -->
    <div class="tab-pane fade" id="slow" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-speedometer text-info me-1"></i> Vipuri Vilivyouzwa Vipande 1 hadi 3 Katika Siku <?= $analytics['days'] ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($analytics['slow_moving'])): ?>
                    <div class="p-5 text-center text-muted">
                        Hakuna vipuri vinavyoangukia kundi hili katika kipindi ulichochagua.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Kipuri</th>
                                    <th>Kategoria</th>
                                    <th class="text-center">Vipande Vilivyouzwa</th>
                                    <th class="text-center">Stoo Iliyopo Sasa</th>
                                    <th>Tarehe ya Mwisho Kuuzwa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['slow_moving'] as $sm): ?>
                                    <tr>
                                        <td>
                                            <a href="/index.php?route=product_show&id=<?= $sm['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($sm['name']) ?>
                                            </a>
                                            <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($sm['sku']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($sm['category_name']) ?></td>
                                        <td class="text-center fw-semibold text-warning">
                                            <?= (int)$sm['total_sold'] ?> <?= htmlspecialchars($sm['unit_short'] ?? 'Pcs') ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark">
                                            <?= (int)$sm['current_stock'] ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= $sm['last_sale_date'] ? date('d M Y', strtotime($sm['last_sale_date'])) : '--' ?>
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

    <!-- Dead Stock Tab -->
    <div class="tab-pane fade" id="dead" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-danger">
                    <i class="bi bi-snow2 me-1"></i> Vipuri Vilivyopo Stoo Bila Mauzo Yoyote Katika Siku <?= $analytics['days'] ?> (Dead Stock)
                </h6>
                <span class="small text-muted">Husaidia uongozi kujua mtaji uliofungwa usioingiza fedha</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($analytics['dead_stock'])): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                        Nzuri sana! Hakuna kipuri kilicholala stoo bila mauzo katika siku <?= $analytics['days'] ?> zilizopita.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>Kipuri</th>
                                    <th>Kategoria</th>
                                    <th class="text-center">Idadi Stoo</th>
                                    <?php if ($canSeeCosts): ?>
                                        <th class="text-end">Mtaji Uliofungwa (Tied Capital)</th>
                                    <?php endif; ?>
                                    <th>Mauzo ya Mwisho</th>
                                    <th class="text-center">Hali</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['dead_stock'] as $ds): ?>
                                    <tr>
                                        <td>
                                            <a href="/index.php?route=product_show&id=<?= $ds['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars($ds['name']) ?>
                                            </a>
                                            <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($ds['sku']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($ds['category_name']) ?></td>
                                        <td class="text-center fw-bold fs-6 text-dark">
                                            <?= (int)$ds['current_stock'] ?> <?= htmlspecialchars($ds['unit_short'] ?? 'Pcs') ?>
                                        </td>
                                        <?php if ($canSeeCosts): ?>
                                            <td class="text-end fw-semibold text-danger">
                                                <?= Helper::formatMoney((float)$ds['tied_capital']) ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="small text-muted">
                                            <?= $ds['last_sale_date'] ? date('d M Y', strtotime($ds['last_sale_date'])) : 'Hakuna mauzo yaliyowahi kurekodiwa' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">DEAD STOCK</span>
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
