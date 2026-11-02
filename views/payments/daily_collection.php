<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-calendar-check text-primary me-2"></i>Ripoti ya Makusanyo ya Siku (Daily Collections)
        </h4>
        <p class="text-muted small mb-0">Tathmini kamili ya pesa zilizoingia kupitia fedha taslimu, mitandao ya simu, benki na kadi.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=payments" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-wallet2"></i> Orodha ya Malipo
        </a>
        <a href="/index.php?route=debt_collect" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-cash-coin"></i> Pokea Deni
        </a>
    </div>
</div>

<!-- Date Picker Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="payments_daily_collection">

            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Tarehe ya Makusanyo</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                    <input type="date" name="date" class="form-control border-start-0" value="<?= htmlspecialchars($date) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja kwa Mpokeaji (Cashier)</label>
                <select name="cashier_id" class="form-select form-select-sm">
                    <option value="">-- Wafanyakazi Wote --</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($cashierId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm flex-fill">
                    <i class="bi bi-funnel me-1"></i> Onyesha Makusanyo
                </button>
                <a href="/index.php?route=payments_daily_collection&date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">
                    Leo
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Total Collected -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Jumla Iliyokusanywa</span>
                    <h3 class="fw-bold mb-0 text-success font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['total_collected']) ?>
                    </h3>
                    <small class="text-muted"><?= $dailyStats['total_payments_count'] ?> risiti zilizotolewa</small>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Total -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Taslimu (Cash)</span>
                    <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['cash_total']) ?>
                    </h3>
                    <small class="text-muted">Pesa zilizopo sandukuni</small>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-cash fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Money Total -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Mitandao ya Simu</span>
                    <h3 class="fw-bold mb-0 text-info font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['mobile_money_total']) ?>
                    </h3>
                    <small class="text-muted">M-Pesa, Tigo, Airtel, Halo</small>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                    <i class="bi bi-phone fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank & Card Total -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Benki & Kadi</span>
                    <h3 class="fw-bold mb-0 text-dark font-monospace mt-1">
                        <?= Helper::formatCurrency((float)$dailyStats['bank_total'] + (float)$dailyStats['card_total']) ?>
                    </h3>
                    <small class="text-muted">CRDB, NMB na POS Card</small>
                </div>
                <div class="bg-secondary bg-opacity-10 p-3 rounded-circle text-secondary">
                    <i class="bi bi-bank fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Providers Breakdown Pill Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-2 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="small text-muted fw-semibold">Vodacom M-Pesa:</span>
                <span class="font-monospace fw-bold text-danger"><?= Helper::formatCurrency((float)$dailyStats['mpesa_total']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-2 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="small text-muted fw-semibold">Tigo Pesa:</span>
                <span class="font-monospace fw-bold text-primary"><?= Helper::formatCurrency((float)$dailyStats['tigo_total']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-2 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="small text-muted fw-semibold">Airtel Money:</span>
                <span class="font-monospace fw-bold text-danger"><?= Helper::formatCurrency((float)$dailyStats['airtel_total']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-2 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="small text-muted fw-semibold">Halopesa:</span>
                <span class="font-monospace fw-bold text-warning"><?= Helper::formatCurrency((float)$dailyStats['halo_total']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Cashier Collection Performance Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people text-primary me-2"></i>Makusanyo kwa Kila Mpokeaji / Kashia (Cashier Collections Breakdown)
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cashierSummary)): ?>
            <div class="text-center py-5 text-muted">
                Hakuna makusanyo yaliyorekodiwa kwa tarehe hii.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Mpokeaji (Cashier)</th>
                            <th class="text-center">Risiti</th>
                            <th class="text-end">Cash (TSh)</th>
                            <th class="text-end">Simu (M-Pesa/Tigo/Airtel)</th>
                            <th class="text-end">Benki / Kadi</th>
                            <th class="text-end pe-3">Jumla Kuu (TSh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cashierSummary as $cs): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <i class="bi bi-person-circle text-secondary me-1"></i>
                                    <?= htmlspecialchars($cs['cashier_name']) ?>
                                    <small class="text-muted fw-normal font-monospace">(<?= htmlspecialchars($cs['username']) ?>)</small>
                                </td>
                                <td class="text-center font-monospace">
                                    <?= $cs['payment_count'] ?>
                                </td>
                                <td class="text-end font-monospace text-primary fw-semibold">
                                    <?= Helper::formatCurrency((float)$cs['cash_total']) ?>
                                </td>
                                <td class="text-end font-monospace text-info fw-semibold">
                                    <?= Helper::formatCurrency((float)$cs['mobile_money_total']) ?>
                                </td>
                                <td class="text-end font-monospace text-secondary fw-semibold">
                                    <?= Helper::formatCurrency((float)$cs['bank_total'] + (float)$cs['card_total']) ?>
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-success">
                                    <?= Helper::formatCurrency((float)$cs['total_collected']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
