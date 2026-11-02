<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-cart-plus text-warning me-2"></i>Mapendekezo ya Kuagiza Vipuri (Reorder Suggestions)
        </h4>
        <p class="text-muted small mb-0">Vipuri vilivyofikia au kushuka chini ya kiwango cha kuagiza (Reorder Point).</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i> Chapisha Orodha
        </button>
        <a href="/index.php?route=inventory" class="btn btn-light border btn-sm">
            <i class="bi bi-boxes me-1"></i> Orodha ya Stoo
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">Vipuri Vinavyopendekezwa Kuagizwa Kutoka kwa Wasambazaji</h6>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
            <?= count($suggestions) ?> Vipuri
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($suggestions)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                Hongera! Hakuna kipuri kilichofikia kiwango cha kuagiza (Reorder level). Stoo ipo salama.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Kipuri & SKU</th>
                            <th>Kategoria & Brand</th>
                            <th class="text-center">Stoo Iliyopo</th>
                            <th class="text-center">Kiwango cha Kuagiza (Reorder Level)</th>
                            <th class="text-center">Idadi Inayopendekezwa (Suggested)</th>
                            <th>Msambazaji Anayependekezwa (Supplier)</th>
                            <?php if ($canSeeCosts): ?>
                                <th class="text-end">Bei ya Kununua (Last Cost)</th>
                                <th class="text-end">Makadirio ya Gharama</th>
                            <?php endif; ?>
                            <th class="text-center">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suggestions as $sug): ?>
                            <?php
                            $suggestedQty = (int)$sug['suggested_reorder_qty'];
                            $unitCost = (float)($sug['last_purchase_cost'] ?? $sug['buying_cost']);
                            $estimatedCost = $suggestedQty * $unitCost;
                            ?>
                            <tr>
                                <td>
                                    <a href="/index.php?route=product_show&id=<?= $sug['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars($sug['name']) ?>
                                    </a>
                                    <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($sug['sku']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($sug['category_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($sug['brand_name'] ?? 'Generic') ?></div>
                                </td>
                                <td class="text-center fw-bold fs-6 text-danger">
                                    <?= (int)$sug['quantity'] ?> <span class="small fw-normal text-muted"><?= $sug['unit_short'] ?></span>
                                </td>
                                <td class="text-center small text-muted">
                                    <?= (int)$sug['reorder_level'] ?> <?= $sug['unit_short'] ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 px-3 py-1">
                                        +<?= $suggestedQty ?> <?= $sug['unit_short'] ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($sug['preferred_supplier_name'] ?? 'Mkuu wa Stoo') ?>
                                </td>
                                <?php if ($canSeeCosts): ?>
                                    <td class="text-end text-muted small">
                                        <?= Helper::formatMoney($unitCost) ?>
                                    </td>
                                    <td class="text-end fw-semibold text-primary">
                                        <?= Helper::formatMoney($estimatedCost) ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <a href="/index.php?route=inventory_adjust&product_id=<?= $sug['id'] ?>" class="btn btn-sm btn-light border text-warning" title="Rekebisha au Ingiza Stoo">
                                        <i class="bi bi-sliders me-1"></i> Rekebisha
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
