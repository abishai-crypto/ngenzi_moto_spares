<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-receipt text-info me-2"></i>Faida kwa Mauzo, Cashier & Wateja
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Ankara: <strong><?= number_format($total) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=financial/profit_report" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Ripoti ya P&L
        </a>
    </div>
</div>

<!-- Tabs: Profit by Sale, Profit by Cashier, Profit by Customer -->
<ul class="nav nav-tabs nav-fill mb-4" id="saleProfitTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="sales-tab" data-bs-toggle="tab" data-bs-target="#salesPane" type="button" role="tab">
            <i class="bi bi-receipt me-1"></i> Faida kwa Kila Mauzo (Invoices)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="cashier-tab" data-bs-toggle="tab" data-bs-target="#cashierPane" type="button" role="tab">
            <i class="bi bi-person-badge me-1"></i> Faida kwa Kila Cashier
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customerPane" type="button" role="tab">
            <i class="bi bi-people me-1"></i> Faida kwa Wateja (Top Customers)
        </button>
    </li>
</ul>

<div class="tab-content" id="saleProfitTabsContent">
    <!-- 1. PROFIT BY INDIVIDUAL SALE -->
    <div class="tab-pane fade show active" id="salesPane" role="tabpanel">
        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/index.php" class="row g-2 align-items-center">
                    <input type="hidden" name="route" value="financial/sale_profit">

                    <div class="col-md-3 col-sm-6">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Ankara, Mteja..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">-- Cashier Wote --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
                    </div>

                    <div class="col-md-2 col-sm-12 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
                        <a href="/index.php?route=financial/sale_profit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sales Profit Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tarehe</th>
                                <th>Ankara (Invoice)</th>
                                <th>Mteja / Cashier</th>
                                <th class="text-end">Mauzo Ghafi</th>
                                <th class="text-end">Punguzo</th>
                                <th class="text-end">Mapato Halisi</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">Hakuna mauzo yaliyopatikana kwa vigezo hivi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $s): ?>
                                    <tr>
                                        <td class="ps-3 text-nowrap"><?= date('d/m/Y', strtotime($s['sale_date'])) ?></td>
                                        <td>
                                            <a href="/index.php?route=sales/show&id=<?= $s['id'] ?>" class="badge bg-light text-primary border font-monospace text-decoration-none">
                                                <?= htmlspecialchars($s['invoice_no']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($s['customer_name'] ?? 'Mteja wa Kawaida (Walk-in)') ?></div>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($s['cashier_name'] ?? 'System') ?></small>
                                        </td>
                                        <td class="text-end"><?= number_format($s['gross_sales'], 2) ?></td>
                                        <td class="text-end text-danger"><?= ($s['discount_amount'] > 0) ? '(' . number_format($s['discount_amount'], 2) . ')' : '—' ?></td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($s['net_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($s['cogs'], 2) ?></td>
                                        <td class="text-end fw-bold <?= ($s['gross_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($s['gross_profit'], 2) ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="badge <?= ($s['margin_percent'] >= 25) ? 'bg-success-subtle text-success' : (($s['margin_percent'] >= 10) ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning') ?> fw-bold">
                                                <?= number_format($s['margin_percent'], 1) ?>%
                                            </span>
                                        </td>
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
                                        <a class="page-link" href="/index.php?route=financial/sale_profit&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&user_id=<?= urlencode($filters['user_id']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. PROFIT BY CASHIER -->
    <div class="tab-pane fade" id="cashierPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Jina la Cashier</th>
                                <th class="text-center">Jumla ya Mauzo</th>
                                <th class="text-end">Mapato (Revenue)</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cashierProfits)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Hakuna mauzo ya Cashier katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cashierProfits as $cp): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cp['cashier_name']) ?></div>
                                            <small class="text-muted font-monospace">@<?= htmlspecialchars($cp['username']) ?></small>
                                        </td>
                                        <td class="text-center fw-bold"><?= number_format($cp['sales_count']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($cp['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($cp['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($cp['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($cp['margin_percent'], 2) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. PROFIT BY CUSTOMER -->
    <div class="tab-pane fade" id="customerPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Mteja (Customer)</th>
                                <th>Aina ya Mteja</th>
                                <th class="text-center">Manunuzi Yaliyofanyika</th>
                                <th class="text-end">Jumla ya Manunuzi (Revenue)</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customerProfits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Hakuna manunuzi ya wateja yaliyopatikana.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customerProfits as $cust): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cust['customer_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($cust['customer_phone'] ?? 'Bila Simu') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($cust['customer_type'] ?? 'Regular')) ?></span>
                                        </td>
                                        <td class="text-center fw-bold"><?= number_format($cust['purchases_count']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($cust['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($cust['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($cust['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($cust['margin_percent'], 2) ?>%</span>
                                        </td>
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
