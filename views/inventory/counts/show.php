<?php
use App\Core\Helper;
use App\Core\Auth;

$statusBadges = [
    'draft'       => 'bg-secondary-subtle text-secondary border',
    'in_progress' => 'bg-info-subtle text-info border border-info-subtle',
    'submitted'   => 'bg-primary-subtle text-primary border border-primary-subtle',
    'pending'     => 'bg-warning-subtle text-warning border border-warning-subtle',
    'approved'    => 'bg-success-subtle text-success border border-success-subtle',
    'rejected'    => 'bg-danger-subtle text-danger border border-danger-subtle',
    'cancelled'   => 'bg-dark-subtle text-dark border'
];
$badgeClass = $statusBadges[$count['status']] ?? 'bg-secondary';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">Ukaguzi wa Stoo #<?= htmlspecialchars($count['count_no'] ?: sprintf('SC-%05d', $count['id'])) ?></h4>
            <span class="badge <?= $badgeClass ?> text-uppercase px-3 py-1"><?= $count['status'] ?></span>
        </div>
        <p class="text-muted small mb-0">
            Tarehe: <strong><?= date('d M Y', strtotime($count['count_date'])) ?></strong> &bull; 
            Aliyehesabu: <strong><?= htmlspecialchars($count['conducted_by_name'] ?? 'Storekeeper') ?></strong>
            <?php if (!empty($count['approved_by_name'])): ?>
                &bull; Aliyeidhinisha: <strong><?= htmlspecialchars($count['approved_by_name']) ?></strong>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=inventory_counts" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Ukaguzi
        </a>
        <?php if ($canApprove && $count['status'] !== 'approved'): ?>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle me-1"></i> Kataa
            </button>
            <form action="/index.php?route=inventory_count_approve" method="POST" onsubmit="return confirm('Je, una uhakika unataka kuidhinisha ukaguzi huu? Hii itarekebisha idadi ya stoo kiotomatiki kulingana na tofauti zilizojitokeza.');" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= Helper::csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $count['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Idhinisha & Rekebisha Stoo
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Session Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <span class="text-muted small fw-medium text-uppercase">Vipuri Vilivyokaguliwa</span>
            <h3 class="fw-bold mb-0 mt-1"><?= $count['total_items'] ?></h3>
            <span class="text-muted small">Jumla ya bidhaa zilizopo</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <span class="text-muted small fw-medium text-uppercase">Zilizo Sawa (Matches)</span>
            <h3 class="fw-bold mb-0 mt-1 text-success"><?= ($count['total_items'] - $count['total_discrepancies']) ?></h3>
            <span class="text-muted small">Mfumo na Ukaguzi zinalingana</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <span class="text-muted small fw-medium text-uppercase">Zenye Tofauti (Discrepancies)</span>
            <h3 class="fw-bold mb-0 mt-1 <?= $count['total_discrepancies'] > 0 ? 'text-danger' : 'text-success' ?>">
                <?= $count['total_discrepancies'] ?>
            </h3>
            <span class="text-muted small">Zinahitaji kusahihishwa</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <span class="text-muted small fw-medium text-uppercase">Jumla ya Tofauti ya Vipande</span>
            <h3 class="fw-bold mb-0 mt-1 <?= $count['net_variance_qty'] < 0 ? 'text-danger' : ($count['net_variance_qty'] > 0 ? 'text-primary' : 'text-success') ?>">
                <?= ($count['net_variance_qty'] > 0 ? '+' : '') . $count['net_variance_qty'] ?>
            </h3>
            <span class="text-muted small">Net Quantity Variance</span>
        </div>
    </div>
</div>

<?php if ($canSeeCosts): ?>
<div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-4 <?= $count['net_variance_cost'] < 0 ? 'border-danger' : 'border-success' ?> mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <span class="text-muted small fw-semibold text-uppercase">Thamani ya Kifedha ya Tofauti (Financial Variance Impact)</span>
            <h4 class="fw-bold mt-1 mb-0 <?= $count['net_variance_cost'] < 0 ? 'text-danger' : 'text-dark' ?>">
                <?= Helper::formatMoney($count['net_variance_cost']) ?>
            </h4>
        </div>
        <div class="small text-muted text-end">
            Hesabu inazingatia: <code>Variance × Buying Cost</code>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($count['notes'])): ?>
    <div class="alert alert-light border rounded-3 mb-4">
        <h6 class="fw-bold mb-1 small text-uppercase text-muted">Maelezo ya Ukaguzi:</h6>
        <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($count['notes'])) ?></p>
    </div>
<?php endif; ?>

<!-- Items Breakdown Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark">Matokeo ya Ukaguzi wa Kila Kipuri (Audit Reconciliation Sheet)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Kipuri</th>
                        <th>SKU</th>
                        <th class="text-center">Mfumo (System)</th>
                        <th class="text-center">Halisi (Physical)</th>
                        <th class="text-center">Tofauti (Variance)</th>
                        <?php if ($canSeeCosts): ?>
                            <th class="text-end">Gharama ya Tofauti</th>
                        <?php endif; ?>
                        <th>Sababu / Maelezo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($count['items'] as $it): ?>
                        <?php
                        $var = (int)$it['variance'];
                        if ($var === 0) {
                            $varBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">0 (Sawa)</span>';
                        } elseif ($var > 0) {
                            $varBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">+' . $var . ' (Ziada)</span>';
                        } else {
                            $varBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">' . $var . ' (Upungufu)</span>';
                        }
                        ?>
                        <tr class="<?= $var !== 0 ? 'table-warning-subtle' : '' ?>">
                            <td>
                                <a href="/index.php?route=product_show&id=<?= $it['product_id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                    <?= htmlspecialchars($it['product_name']) ?>
                                </a>
                                <div class="small text-muted"><?= htmlspecialchars($it['category_name']) ?></div>
                            </td>
                            <td><code><?= htmlspecialchars($it['product_sku']) ?></code></td>
                            <td class="text-center fw-semibold text-secondary">
                                <?= $it['system_quantity'] ?> <?= $it['unit_short'] ?>
                            </td>
                            <td class="text-center fw-bold fs-6 text-dark">
                                <?= $it['physical_quantity'] ?> <?= $it['unit_short'] ?>
                            </td>
                            <td class="text-center">
                                <?= $varBadge ?>
                            </td>
                            <?php if ($canSeeCosts): ?>
                                <td class="text-end fw-semibold <?= $it['variance_cost'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                    <?= Helper::formatMoney((float)$it['variance_cost']) ?>
                                </td>
                            <?php endif; ?>
                            <td class="small text-muted">
                                <?= htmlspecialchars($it['reason'] ?: '--') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/index.php?route=inventory_count_reject" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= Helper::csrfToken() ?>">
            <input type="hidden" name="id" value="<?= $count['id'] ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Kataa Ukaguzi wa Stoo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Ukikataa ukaguzi huu, idadi ya stoo haitabadilishwa kwenye mfumo.</p>
                <div class="mb-3">
                    <label for="rejection_reason" class="form-label fw-semibold">Sababu ya Kukataa <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" placeholder="Eleza sababu ya kukataa ukaguzi huu..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                <button type="submit" class="btn btn-danger">Thibitisha Kukataa</button>
            </div>
        </form>
    </div>
</div>
