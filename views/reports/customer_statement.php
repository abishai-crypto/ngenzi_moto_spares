<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-file-earmark-person text-primary me-2"></i>Taarifa ya Akaunti ya Mteja (Customer Statement)
        </h4>
        <p class="text-muted small mb-0">Uchambuzi wa kihistoria wa mauzo, malipo na baki ya mteja</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports/debts" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Ripoti ya Madeni
        </a>
        <?php if (!empty($statement)): ?>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Chapisha Taarifa (Print Statement)
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Select Customer Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/customer_statement">

            <div class="col-md-4">
                <select name="customer_id" class="form-select form-select-sm" required>
                    <option value="">-- Chagua Mteja --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($customerId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?? 'Bila Simu') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>" placeholder="Kuanzia">
            </div>

            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>" placeholder="Hadi">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-dark btn-sm w-100"><i class="bi bi-search me-1"></i> Fungua</button>
            </div>
        </form>
    </div>
</div>

<?php if ($statement): ?>
    <!-- Statement Voucher Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white p-4 border-bottom">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h5 class="fw-bold text-dark mb-1">NGENZI MOTO SPARE PARTS</h5>
                    <div class="small text-muted">Kariakoo, Dar es Salaam, Tanzania</div>
                    <div class="small text-muted">Simu: +255 700 000 000 | info@ngenzimotospare.co.tz</div>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <h5 class="fw-bold text-primary mb-1">TAARIFA YA AKAUNTI YA MTEJA</h5>
                    <div class="small text-muted">Kipindi: <?= date('d/m/Y', strtotime($statement['start_date'])) ?> hadi <?= date('d/m/Y', strtotime($statement['end_date'])) ?></div>
                    <div class="small text-muted">Tarehe ya Kuchapishwa: <?= date('d/m/Y H:i') ?></div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Customer Info & Balance Card -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="small text-muted">Taarifa za Mteja:</div>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($statement['customer']['name']) ?></h5>
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($statement['customer']['phone'] ?? '—') ?></div>
                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($statement['customer']['address'] ?? 'Dar es Salaam') ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 text-md-end">
                        <div class="small text-muted">Baki Inayodaiwa Sasa:</div>
                        <h3 class="fw-bold <?= ($statement['closing_balance'] > 0) ? 'text-danger' : 'text-success' ?> mb-1">
                            TSh <?= number_format($statement['closing_balance'], 2) ?>
                        </h3>
                        <div class="small text-muted">Baki ya Mwanzo: TSh <?= number_format($statement['opening_balance'], 2) ?></div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tarehe</th>
                            <th>Kumbukumbu (Ref)</th>
                            <th>Maelezo ya Muamala</th>
                            <th class="text-end">Mauzo / Deni (Debit)</th>
                            <th class="text-end">Malipo (Credit)</th>
                            <th class="text-end pe-3">Baki (Balance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening Balance Row -->
                        <tr class="table-light fw-bold">
                            <td class="ps-3" colspan="3">Baki ya Awali (Opening Balance kabla ya <?= date('d/m/Y', strtotime($statement['start_date'])) ?>)</td>
                            <td class="text-end">—</td>
                            <td class="text-end">—</td>
                            <td class="text-end pe-3 text-dark">TSh <?= number_format($statement['opening_balance'], 2) ?></td>
                        </tr>

                        <?php if (empty($statement['transactions'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Hakuna miamala katika kipindi hiki.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statement['transactions'] as $t): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= date('d/m/Y', strtotime($t['trans_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($t['reference']) ?></span></td>
                                    <td><?= htmlspecialchars($t['description']) ?></td>
                                    <td class="text-end fw-semibold text-danger">
                                        <?= ($t['debit'] > 0) ? number_format($t['debit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        <?= ($t['credit'] > 0) ? number_format($t['credit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end pe-3 fw-bold <?= ($t['balance'] > 0) ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($t['balance'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Closing Balance Row -->
                        <tr class="table-light fw-bold fs-6">
                            <td class="ps-3" colspan="3">JUMLA YA KIPINDI & BAKI YA MWISHO (CLOSING BALANCE)</td>
                            <td class="text-end text-danger">TSh <?= number_format($statement['total_debits'], 2) ?></td>
                            <td class="text-end text-success">TSh <?= number_format($statement['total_credits'], 2) ?></td>
                            <td class="text-end pe-3 <?= ($statement['closing_balance'] > 0) ? 'text-danger' : 'text-success' ?>">
                                TSh <?= number_format($statement['closing_balance'], 2) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row pt-4 text-center">
                <div class="col-6">
                    <div class="text-muted small">Imetayarishwa na:</div>
                    <div class="fw-bold mt-4 border-top pt-1 w-50 mx-auto"><?= htmlspecialchars(Auth::user()['full_name'] ?? 'Accountant') ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Mteja / Saini:</div>
                    <div class="fw-bold mt-4 border-top pt-1 w-50 mx-auto"><?= htmlspecialchars($statement['customer']['name']) ?></div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-3 text-center py-5 text-muted">
        <i class="bi bi-file-earmark-person fs-1 d-block mb-2 text-primary"></i>
        Tafadhali chagua mteja hapo juu ili kuangalia taarifa yake kamili ya akaunti (Customer Statement).
    </div>
<?php endif; ?>
