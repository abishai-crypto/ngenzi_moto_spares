<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Ripoti ya Mauzo ya Kila Siku (Daily Sales Summary)
        </h4>
        <p class="text-muted small mb-0">Tathmini ya mapato ya siku, mgawanyo wa njia za malipo, na utendaji wa makashia.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=pos" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-cart-plus"></i> Mashine ya POS
        </a>
        <a href="/index.php?route=sales" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-receipt"></i> Historia ya Mauzo
        </a>
    </div>
</div>

<!-- Date Picker Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="sales_daily_summary">

            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Chagua Tarehe ya Ripoti</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                    <input type="date" name="date" class="form-control border-start-0" value="<?= htmlspecialchars($date) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja kwa Muuzaji (Cashier)</label>
                <select name="cashier_id" class="form-select form-select-sm">
                    <option value="">-- Makashia Wote --</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($cashierId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm flex-fill">
                    <i class="bi bi-funnel me-1"></i> Onyesha Ripoti
                </button>
                <a href="/index.php?route=sales_daily_summary&date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">
                    Leo
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Daily KPI Overview -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Jumla ya Mapato</span>
                    <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['total_revenue']) ?>
                    </h3>
                    <small class="text-muted"><?= $dailyStats['total_sales_count'] ?> ankara zilizouzwa</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Fedha Zilizopokelewa</span>
                    <h3 class="fw-bold mb-0 text-success font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['total_paid']) ?>
                    </h3>
                    <small class="text-muted">Paid amount</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Mauzo ya Mkopo (Madeni)</span>
                    <h3 class="fw-bold mb-0 text-danger font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['total_balance_due']) ?>
                    </h3>
                    <small class="text-muted"><?= $dailyStats['credit_sales_count'] ?> ankara zenye madeni</small>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Faida Halisi ya Siku</span>
                    <h3 class="fw-bold mb-0 <?= $canViewProfit ? 'text-info' : 'text-muted' ?> font-monospace mt-1">
                        <?= $canViewProfit ? Helper::formatCurrency((float)$dailyStats['total_gross_profit']) : '***' ?>
                    </h3>
                    <small class="text-muted"><?= $canViewProfit ? "Margin: {$dailyStats['margin_percent']}%" : 'Imezuiwa kwa Cashier' ?></small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                    <i class="bi bi-graph-up fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cashier Breakdown Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people text-primary me-2"></i>Mgawanyo wa Mauzo kwa Kila Kashia (Cashier Breakdown)
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cashierStats)): ?>
            <div class="text-center py-5 text-muted">
                Hakuna shughuli za mauzo zilizorekodiwa kwa tarehe hii.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Muuzaji (Cashier)</th>
                            <th class="text-center">Miamala</th>
                            <th class="text-end">Cash (TSh)</th>
                            <th class="text-end">Simu (M-Pesa/Tigo/Airtel)</th>
                            <th class="text-end">Benki / Kadi</th>
                            <th class="text-end">Mkopo (Credit)</th>
                            <th class="text-end">Jumla Kuu (TSh)</th>
                            <th class="text-center pe-3">Zilizoghairiwa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cashierStats as $cs): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <i class="bi bi-person-circle text-secondary me-1"></i>
                                    <?= htmlspecialchars($cs['cashier_name']) ?>
                                </td>
                                <td class="text-center font-monospace">
                                    <?= $cs['total_transactions'] ?>
                                </td>
                                <td class="text-end font-monospace text-success">
                                    <?= Helper::formatCurrency((float)$cs['cash_sales']) ?>
                                </td>
                                <td class="text-end font-monospace text-primary">
                                    <?= Helper::formatCurrency((float)$cs['mobile_money_sales']) ?>
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    <?= Helper::formatCurrency((float)$cs['bank_card_sales']) ?>
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    <?= Helper::formatCurrency((float)$cs['credit_sales']) ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    <?= Helper::formatCurrency((float)$cs['total_sales']) ?>
                                </td>
                                <td class="text-center pe-3">
                                    <?php if ((int)$cs['cancelled_transactions'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= $cs['cancelled_transactions'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">0</span>
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
