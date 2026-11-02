<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-wallet2 text-danger me-2"></i>Orodha ya Matumizi (Expense History)
        </h4>
        <p class="text-muted small mb-0">
            Jumla ya matumizi yaliyorekodiwa: <strong><?= number_format($total) ?></strong> &bull; Jumla ya Fedha: <strong>TSh <?= number_format($totalAmount, 2) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=expenses/categories" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tags me-1"></i> Kategoria za Matumizi
        </a>
        <a href="/index.php?route=financial/expense_analysis" class="btn btn-outline-info btn-sm">
            <i class="bi bi-pie-chart me-1"></i> Uchambuzi wa Matumizi
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant', 'cashier']) || Auth::hasPermission('expenses.create')): ?>
            <a href="/index.php?route=expenses/create" class="btn btn-danger btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Rekodi Matumizi Mapya
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="expenses">

            <div class="col-md-3 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tafuta Namba, Maelezo, Mlipwaji..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">-- Njia Zote za Malipo --</option>
                    <option value="Cash" <?= ($filters['payment_method'] === 'Cash') ? 'selected' : '' ?>>Taslimu (Cash)</option>
                    <option value="Mobile Money" <?= ($filters['payment_method'] === 'Mobile Money') ? 'selected' : '' ?>>Mitandao (M-Pesa / Tigo)</option>
                    <option value="Bank" <?= ($filters['payment_method'] === 'Bank') ? 'selected' : '' ?>>Benki (Bank Transfer)</option>
                    <option value="Card" <?= ($filters['payment_method'] === 'Card') ? 'selected' : '' ?>>Kadi (Card/POS)</option>
                </select>
            </div>

            <div class="col-md-1 col-sm-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Hadhi --</option>
                    <option value="recorded" <?= ($filters['status'] === 'recorded') ? 'selected' : '' ?>>Imerekodiwa</option>
                    <option value="approved" <?= ($filters['status'] === 'approved') ? 'selected' : '' ?>>Imeidhinishwa</option>
                    <option value="rejected" <?= ($filters['status'] === 'rejected') ? 'selected' : '' ?>>Imekataliwa</option>
                    <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Imeghairiwa</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <input type="date" name="date_from" class="form-control form-control-sm" title="Kuanzia Tarehe" 
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" placeholder="Kuanzia">
            </div>

            <div class="col-md-2 col-sm-6 d-flex gap-1">
                <input type="date" name="date_to" class="form-control form-control-sm" title="Hadi Tarehe" 
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" placeholder="Hadi">
                <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-funnel"></i></button>
                <a href="/index.php?route=expenses" class="btn btn-outline-secondary btn-sm" title="Safisha"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tarehe</th>
                        <th>Namba ya Matumizi</th>
                        <th>Kategoria</th>
                        <th>Maelezo & Mlipwaji</th>
                        <th class="text-end">Kiasi (TSh)</th>
                        <th>Njia ya Malipo</th>
                        <th class="text-center">Hadhi</th>
                        <th>Mrekodi / Idhini</th>
                        <th class="text-end pe-3">Kitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna rekodi za matumizi zilizopatikana.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td class="ps-3 text-nowrap">
                                    <strong><?= date('d/m/Y', strtotime($e['expense_date'])) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= htmlspecialchars($e['expense_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <?= htmlspecialchars($e['category_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($e['description']) ?></div>
                                    <?php if (!empty($e['payee'])): ?>
                                        <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($e['payee']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    <?= number_format($e['amount'], 2) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($e['payment_method']) ?>
                                    </span>
                                    <?php if (!empty($e['reference_no'])): ?>
                                        <div class="small text-muted font-monospace"><?= htmlspecialchars($e['reference_no']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusBadgeMap = [
                                        'approved'  => 'bg-success text-white',
                                        'recorded'  => 'bg-info text-dark',
                                        'rejected'  => 'bg-danger text-white',
                                        'cancelled' => 'bg-dark text-white',
                                    ];
                                    $statusLabelMap = [
                                        'approved'  => 'Imeidhinishwa',
                                        'recorded'  => 'Imerekodiwa',
                                        'rejected'  => 'Imekataliwa',
                                        'cancelled' => 'Imeghairiwa',
                                    ];
                                    $badgeClass = $statusBadgeMap[$e['status']] ?? 'bg-secondary text-white';
                                    $statusLabel = $statusLabelMap[$e['status']] ?? ucfirst($e['status']);
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-2 py-1 rounded-pill">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-dark fw-semibold"><?= htmlspecialchars($e['recorder_name'] ?? 'System') ?></div>
                                    <?php if (!empty($e['approver_name'])): ?>
                                        <div class="small text-success"><i class="bi bi-check2 me-1"></i><?= htmlspecialchars($e['approver_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/index.php?route=expenses/show&id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary" title="Tazama Vocha">
                                        <i class="bi bi-eye"></i>
                                    </a>
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
                <div class="small text-muted">
                    Ukurasa <?= $page ?> kati ya <?= $totalPages ?> (Jumla ya matumizi <?= number_format($total) ?>)
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="/index.php?route=expenses&page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&category_id=<?= urlencode($filters['category_id'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
