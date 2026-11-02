<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-clock-history text-primary me-2"></i>Kumbukumbu za Mienendo ya Stoo (Stock History)
        </h4>
        <p class="text-muted small mb-0">Historia kamili na isiyofutika ya kila kipuri kilichoingia, kutoka, kurekebishwa au kuhesabiwa.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-spreadsheet"></i> Pakua CSV
        </a>
        <a href="/index.php?route=inventory_adjust" class="btn btn-warning btn-sm text-dark fw-semibold d-flex align-items-center gap-1">
            <i class="bi bi-sliders"></i> Rekebisha Stoo
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="inventory_history">

            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Kipuri / SKU</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Jina au SKU..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Aina ya Mwenendo</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- Aina Zote --</option>
                    <option value="purchase" <?= ($filters['type'] === 'purchase') ? 'selected' : '' ?>>Manunuzi (Purchase)</option>
                    <option value="sale" <?= ($filters['type'] === 'sale') ? 'selected' : '' ?>>Mauzo (Sale)</option>
                    <option value="adjustment_in" <?= ($filters['type'] === 'adjustment_in') ? 'selected' : '' ?>>Marekebisho (+) In</option>
                    <option value="adjustment_out" <?= ($filters['type'] === 'adjustment_out') ? 'selected' : '' ?>>Marekebisho (-) Out</option>
                    <option value="damage" <?= ($filters['type'] === 'damage') ? 'selected' : '' ?>>Kipuri Kilichoharibika</option>
                    <option value="loss" <?= ($filters['type'] === 'loss') ? 'selected' : '' ?>>Kipuri Kilichopotea</option>
                    <option value="stock_count_adjustment" <?= ($filters['type'] === 'stock_count_adjustment') ? 'selected' : '' ?>>Ukaguzi wa Stoo</option>
                    <option value="opening_stock" <?= ($filters['type'] === 'opening_stock') ? 'selected' : '' ?>>Stoo ya Mwanzo</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Kuanzia Tarehe</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Hadi Tarehe</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Mtumiaji</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">-- Watumiaji Wote --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-1 col-md-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Chuja">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="/index.php?route=inventory_history" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Movements Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (empty($movements)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clock-history fs-1 d-block mb-3 text-secondary"></i>
                <h5>Hakuna mienendo ya stoo iliyopatikana</h5>
                <p class="small mb-3">Hakuna kumbukumbu za mienendo inayolingana na vigezo ulivyochagua.</p>
                <a href="/index.php?route=inventory_history" class="btn btn-outline-primary btn-sm">Onyesha Historia Yote</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Tarehe & Saa</th>
                            <th>Kipuri (SKU)</th>
                            <th>Aina ya Mwenendo</th>
                            <th class="text-center">Kabla</th>
                            <th class="text-center">Mabadiliko</th>
                            <th class="text-center">Baada</th>
                            <?php if ($canSeeCosts): ?>
                                <th class="text-end">Gharama</th>
                            <?php endif; ?>
                            <th>Rejea / Kumbukumbu</th>
                            <th>Mtumiaji</th>
                            <th>Maelezo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $m): ?>
                            <?php
                            $typeBadges = [
                                'purchase'               => ['bg' => 'success-subtle text-success border border-success-subtle', 'label' => 'Manunuzi (Purchase)'],
                                'sale'                   => ['bg' => 'primary-subtle text-primary border border-primary-subtle', 'label' => 'Mauzo (Sale)'],
                                'adjustment_in'          => ['bg' => 'info-subtle text-info border border-info-subtle', 'label' => 'Marekebisho (+) In'],
                                'adjustment_out'         => ['bg' => 'secondary-subtle text-secondary border', 'label' => 'Marekebisho (-) Out'],
                                'adjustment'             => ['bg' => 'secondary-subtle text-secondary border', 'label' => 'Marekebisho (Adjustment)'],
                                'damage'                 => ['bg' => 'danger-subtle text-danger border border-danger-subtle', 'label' => 'Kimeharibika (Damage)'],
                                'loss'                   => ['bg' => 'danger-subtle text-danger border border-danger-subtle', 'label' => 'Kimepotea (Loss)'],
                                'stock_count'            => ['bg' => 'warning-subtle text-warning border border-warning-subtle', 'label' => 'Ukaguzi wa Stoo'],
                                'stock_count_adjustment' => ['bg' => 'warning-subtle text-warning border border-warning-subtle', 'label' => 'Ukaguzi wa Stoo'],
                                'opening_stock'          => ['bg' => 'light text-dark border', 'label' => 'Stoo ya Mwanzo']
                            ];
                            $badge = $typeBadges[$m['transaction_type']] ?? ['bg' => 'secondary', 'label' => $m['transaction_type']];
                            ?>
                            <tr>
                                <td class="small text-muted" style="white-space:nowrap;">
                                    <?= date('d M Y', strtotime($m['created_at'])) ?>
                                    <div class="text-muted" style="font-size:0.75rem;"><?= date('H:i:s', strtotime($m['created_at'])) ?></div>
                                </td>
                                <td>
                                    <a href="/index.php?route=product_show&id=<?= $m['product_id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars($m['product_name']) ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($m['product_sku']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $badge['bg'] ?>"><?= $badge['label'] ?></span>
                                </td>
                                <td class="text-center text-muted font-monospace"><?= $m['quantity_before'] ?></td>
                                <td class="text-center fw-bold font-monospace <?= $m['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($m['quantity'] > 0 ? '+' : '') . $m['quantity'] ?>
                                </td>
                                <td class="text-center fw-bold text-dark font-monospace"><?= $m['quantity_after'] ?></td>
                                <?php if ($canSeeCosts): ?>
                                    <td class="text-end small text-muted">
                                        <?= $m['unit_cost'] ? Helper::formatMoney((float)$m['unit_cost']) : '--' ?>
                                    </td>
                                <?php endif; ?>
                                <td class="small">
                                    <?php if (!empty($m['reference_type'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= strtoupper($m['reference_type']) ?> <?= $m['reference_id'] ? '#' . $m['reference_id'] : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($m['user_name'] ?? $m['user_username'] ?? 'System') ?>
                                </td>
                                <td class="small text-muted" style="max-width:220px;">
                                    <?= htmlspecialchars($m['notes'] ?? '--') ?>
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
                        Ukurasa <strong><?= $page ?></strong> kati ya <strong><?= $totalPages ?></strong> (Jumla ya mienendo: <?= $total ?>)
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
