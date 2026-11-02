<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Ripoti & Uchambuzi wa Marejesho (Return Rate Analysis)
        </h4>
        <p class="text-muted small mb-0">
            Tathmini ya viwango vya marejesho kwa vipuri, sababu za kurejesha, kategoria na wateja
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=returns_dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=returns" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-list-task me-1"></i> Orodha ya Marejesho
        </a>
        <button onclick="window.print();" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha Ripoti
        </button>
    </div>
</div>

<!-- Report Navigation Tabs & Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=product&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'product' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam me-1"></i> Kwa Kipuri (Return Rate %)
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=date&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'date' ? 'active' : '' ?>">
                    <i class="bi bi-calendar3 me-1"></i> Kwa Tarehe
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=category&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'category' ? 'active' : '' ?>">
                    <i class="bi bi-tags me-1"></i> Kwa Kategoria
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=brand&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'brand' ? 'active' : '' ?>">
                    <i class="bi bi-award me-1"></i> Kwa Brand
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=customer&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'customer' ? 'active' : '' ?>">
                    <i class="bi bi-person me-1"></i> Kwa Mteja
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="/index.php?route=return_reports&group_by=reason&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                   class="nav-link btn-sm <?= $groupBy === 'reason' ? 'active' : '' ?>">
                    <i class="bi bi-pie-chart me-1"></i> Kwa Sababu
                </a>
            </li>
        </ul>

        <!-- Date Range Filter Form -->
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="return_reports">
            <input type="hidden" name="group_by" value="<?= htmlspecialchars($groupBy) ?>">

            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">Kuanzia:</span>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
            </div>

            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">Hadi:</span>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
            </div>

            <div class="col-md-2 col-sm-6 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                    <i class="bi bi-filter me-1"></i> Chuja
                </button>
                <a href="/index.php?route=return_reports&group_by=<?= htmlspecialchars($groupBy) ?>" class="btn btn-light border btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Results Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-table text-primary me-2"></i>Matokeo ya Ripoti: <span class="text-capitalize"><?= htmlspecialchars($groupBy) ?></span>
        </h6>
        <span class="badge bg-light text-dark border">Mistari: <?= count($reportData) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($reportData)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                Hakuna data ya marejesho iliyopatikana kwa kipindi hiki.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <?php if ($groupBy === 'product'): ?>
                                <th class="ps-3" style="width: 25%;">Kipuri (Product)</th>
                                <th>Kategoria / Brand</th>
                                <th class="text-center">Vilivyouzwa (Sold)</th>
                                <th class="text-center">Vilivyorudishwa (Ret.)</th>
                                <th class="text-center">Kiwango cha Rejesho (Return Rate)</th>
                                <th class="text-center">Inayouzika / Mbovu</th>
                                <th class="text-end pe-3">Thamani ya Marejesho</th>
                            <?php elseif ($groupBy === 'date'): ?>
                                <th class="ps-3">Tarehe</th>
                                <th class="text-center">Jumla ya Marejesho</th>
                                <th class="text-center">Jumla ya Vipuri</th>
                                <th class="text-center">Inayouzika (Resellable)</th>
                                <th class="text-center">Mbovu / Hitilafu</th>
                                <th class="text-end pe-3">Thamani ya Siku</th>
                            <?php elseif ($groupBy === 'customer'): ?>
                                <th class="ps-3">Mteja</th>
                                <th>Kodi ya Mteja</th>
                                <th class="text-center">Mara za Rejesho</th>
                                <th class="text-center">Jumla ya Vipuri</th>
                                <th class="text-end pe-3">Thamani Iliyorejeshwa</th>
                            <?php else: ?>
                                <th class="ps-3">Kikundi (<?= ucfirst($groupBy) ?>)</th>
                                <th class="text-center">Mara za Rejesho</th>
                                <th class="text-center">Jumla ya Vipuri Vilivyorudishwa</th>
                                <th class="text-end pe-3">Thamani ya Marejesho</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotalValue = 0;
                        $grandTotalUnits = 0;
                        foreach ($reportData as $row): 
                            $val = (float)($row['total_returned_value'] ?? 0);
                            $units = (int)($row['total_units_returned'] ?? 0);
                            $grandTotalValue += $val;
                            $grandTotalUnits += $units;
                        ?>
                            <tr>
                                <?php if ($groupBy === 'product'): ?>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 13px;">
                                            <a href="/index.php?route=product_show&id=<?= $row['report_key'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($row['label']) ?>
                                            </a>
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 11px;">SKU: <?= htmlspecialchars($row['sku'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_name'] ?? 'General') ?></span>
                                        <?php if (!empty($row['brand_name'])): ?>
                                            <small class="text-muted ms-1"><?= htmlspecialchars($row['brand_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((int)$row['total_units_sold']) ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-danger">
                                        <?= number_format((int)$row['total_units_returned']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $rate = (float)($row['return_rate'] ?? 0);
                                        $rateBadge = ($rate >= 10) ? 'bg-danger text-white' : (($rate >= 5) ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle');
                                        ?>
                                        <span class="badge <?= $rateBadge ?> font-monospace px-2 py-1">
                                            <?= number_format($rate, 1) ?>%
                                        </span>
                                    </td>
                                    <td class="text-center small">
                                        <span class="text-success fw-bold"><?= (int)$row['resellable_units'] ?></span> / 
                                        <span class="text-danger fw-bold"><?= ((int)$row['damaged_units'] + (int)$row['defective_units']) ?></span>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency($val) ?>
                                    </td>

                                <?php elseif ($groupBy === 'date'): ?>
                                    <td class="ps-3 font-monospace fw-bold">
                                        <?= date('d/m/Y', strtotime($row['label'])) ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((int)$row['total_returns_count']) ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold">
                                        <?= number_format((int)$row['total_units_returned']) ?>
                                    </td>
                                    <td class="text-center text-success font-monospace fw-bold">
                                        <?= number_format((int)$row['resellable_units']) ?>
                                    </td>
                                    <td class="text-center text-danger font-monospace fw-bold">
                                        <?= number_format((int)$row['damaged_defective_units']) ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency($val) ?>
                                    </td>

                                <?php elseif ($groupBy === 'customer'): ?>
                                    <td class="ps-3 fw-semibold">
                                        <?php if (!empty($row['report_key'])): ?>
                                            <a href="/index.php?route=customer_show&id=<?= $row['report_key'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($row['label']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($row['label']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        <?= htmlspecialchars($row['customer_code'] ?? '—') ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((int)$row['total_returns_count']) ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold">
                                        <?= number_format((int)$row['total_units_returned']) ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency($val) ?>
                                    </td>

                                <?php else: ?>
                                    <td class="ps-3 fw-semibold">
                                        <?= htmlspecialchars(str_replace('_', ' ', $row['label'])) ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= number_format((int)$row['total_returns_count']) ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold">
                                        <?= number_format((int)$row['total_units_returned']) ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency($val) ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light border-top">
                        <tr>
                            <th class="ps-3">JUMLA KUU:</th>
                            <th></th>
                            <?php if ($groupBy === 'product'): ?>
                                <th></th>
                                <th class="text-center font-monospace fw-bold text-danger"><?= number_format($grandTotalUnits) ?></th>
                                <th></th>
                                <th></th>
                            <?php elseif ($groupBy === 'date'): ?>
                                <th></th>
                                <th class="text-center font-monospace fw-bold"><?= number_format($grandTotalUnits) ?></th>
                                <th></th>
                                <th></th>
                            <?php elseif ($groupBy === 'customer'): ?>
                                <th></th>
                                <th class="text-center font-monospace fw-bold"><?= number_format($grandTotalUnits) ?></th>
                            <?php else: ?>
                                <th></th>
                                <th class="text-center font-monospace fw-bold"><?= number_format($grandTotalUnits) ?></th>
                            <?php endif; ?>
                            <th class="text-end pe-3 font-monospace fw-bold text-primary fs-6">
                                <?= Helper::formatCurrency($grandTotalValue) ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
