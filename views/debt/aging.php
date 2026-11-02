<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-hourglass-split text-danger me-2"></i>Ripoti ya Uzeekaji wa Madeni (Debt Aging Report)
        </h4>
        <p class="text-muted small mb-0">Mgawanyo wa madeni ya wateja kulingana na muda uliopita tangu tarehe ya mwisho ya kulipa (Due Date).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=debts" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Dashibodi ya Madeni
        </a>
        <button onclick="window.print();" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i> Chapisha Ripoti
        </button>
    </div>
</div>

<!-- Customer Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4 no-print-bar">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="debt_aging">

            <div class="col-md-8">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja kwa Mteja Maalum</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">-- Wateja Wote Wanaodaiwa --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($customerId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['customer_code']) ?> - <?= htmlspecialchars($c['phone'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm flex-fill">
                    <i class="bi bi-filter"></i> Onyesha
                </button>
                <a href="/index.php?route=debt_aging" class="btn btn-outline-secondary btn-sm">
                    Wote
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Aging Matrix Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-table text-primary me-2"></i>Jedwali la Uzeekaji wa Madeni kwa Siku
        </h6>
        <span class="small text-muted font-monospace">Tarehe ya Leo: <?= date('d M Y') ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($agingRows)): ?>
            <div class="text-center py-5 text-muted">
                Hakuna madeni yaliyorekodiwa kwa kigezo hiki.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light small text-muted text-center">
                        <tr>
                            <th class="text-start ps-3">Mteja</th>
                            <th>Muda Haujafika<br><small class="text-muted fw-normal">(Current / Not Due)</small></th>
                            <th>Siku 1–7<br><small class="text-muted fw-normal">(Overdue)</small></th>
                            <th>Siku 8–30<br><small class="text-muted fw-normal">(Overdue)</small></th>
                            <th>Siku 31–60<br><small class="text-muted fw-normal">(Overdue)</small></th>
                            <th>Siku 61–90<br><small class="text-muted fw-normal">(Overdue)</small></th>
                            <th>Siku 90+<br><small class="text-muted fw-normal">(Overdue)</small></th>
                            <th class="text-end pe-3 bg-light fw-bold">Jumla ya Deni (TSh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $totCurrent = 0.00;
                            $tot1_7     = 0.00;
                            $tot8_30    = 0.00;
                            $tot31_60   = 0.00;
                            $tot61_90   = 0.00;
                            $tot90Plus  = 0.00;
                            $totGrand   = 0.00;
                        ?>
                        <?php foreach ($agingRows as $row): ?>
                            <?php 
                                $cur = (float)$row['current_not_due'];
                                $o1  = (float)$row['overdue_1_7'];
                                $o2  = (float)$row['overdue_8_30'];
                                $o3  = (float)$row['overdue_31_60'];
                                $o4  = (float)$row['overdue_61_90'];
                                $o5  = (float)$row['overdue_90_plus'];
                                $rowTot = (float)$row['total_debt'];

                                $totCurrent += $cur;
                                $tot1_7     += $o1;
                                $tot8_30    += $o2;
                                $tot31_60   += $o3;
                                $tot61_90   += $o4;
                                $tot90Plus  += $o5;
                                $totGrand   += $rowTot;
                            ?>
                            <tr>
                                <td class="ps-3 text-start">
                                    <div class="fw-bold text-dark">
                                        <a href="/index.php?route=debt_customer_profile&id=<?= $row['customer_id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($row['customer_name']) ?>
                                        </a>
                                    </div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($row['customer_code']) ?> | <?= htmlspecialchars($row['customer_phone'] ?? '-') ?></small>
                                </td>
                                <td class="text-end font-monospace <?= ($cur > 0) ? 'text-success fw-semibold' : 'text-muted' ?>">
                                    <?= ($cur > 0) ? Helper::formatCurrency($cur) : '-' ?>
                                </td>
                                <td class="text-end font-monospace <?= ($o1 > 0) ? 'text-warning fw-semibold' : 'text-muted' ?>">
                                    <?= ($o1 > 0) ? Helper::formatCurrency($o1) : '-' ?>
                                </td>
                                <td class="text-end font-monospace <?= ($o2 > 0) ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    <?= ($o2 > 0) ? Helper::formatCurrency($o2) : '-' ?>
                                </td>
                                <td class="text-end font-monospace <?= ($o3 > 0) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= ($o3 > 0) ? Helper::formatCurrency($o3) : '-' ?>
                                </td>
                                <td class="text-end font-monospace <?= ($o4 > 0) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= ($o4 > 0) ? Helper::formatCurrency($o4) : '-' ?>
                                </td>
                                <td class="text-end font-monospace <?= ($o5 > 0) ? 'badge-danger fw-bold text-danger' : 'text-muted' ?>">
                                    <?= ($o5 > 0) ? Helper::formatCurrency($o5) : '-' ?>
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-danger bg-light">
                                    <?= Helper::formatCurrency($rowTot) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark font-monospace text-end fw-bold">
                        <tr>
                            <td class="text-start ps-3">JUMLA KUU (TOTALS):</td>
                            <td><?= Helper::formatCurrency($totCurrent) ?></td>
                            <td><?= Helper::formatCurrency($tot1_7) ?></td>
                            <td><?= Helper::formatCurrency($tot8_30) ?></td>
                            <td><?= Helper::formatCurrency($tot31_60) ?></td>
                            <td><?= Helper::formatCurrency($tot61_90) ?></td>
                            <td><?= Helper::formatCurrency($tot90Plus) ?></td>
                            <td class="pe-3 text-warning"><?= Helper::formatCurrency($totGrand) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
