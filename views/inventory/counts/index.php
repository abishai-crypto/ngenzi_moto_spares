<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-clipboard-check text-primary me-2"></i>Ukaguzi wa Kimwili wa Stoo (Stock Counts)
        </h4>
        <p class="text-muted small mb-0">Hesabu halisi ya stoo (Physical Audit) na upatanisho wa tofauti dhidi ya mfumo.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=inventory_count_create" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-circle"></i> Anzisha Ukaguzi Mpya
        </a>
        <a href="/index.php?route=inventory" class="btn btn-light border btn-sm">
            <i class="bi bi-boxes me-1"></i> Orodha ya Stoo
        </a>
    </div>
</div>

<!-- Sessions Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (empty($counts)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clipboard-x fs-1 d-block mb-3 text-secondary"></i>
                <h5>Bado hakuna ukaguzi wa stoo ulioanzishwa</h5>
                <p class="small mb-3">Anzisha ukaguzi wa kimwili wa bidhaa stoo ili kuhakikisha idadi ya mfumo inalingana na hali halisi ya stoo.</p>
                <a href="/index.php?route=inventory_count_create" class="btn btn-primary-custom btn-sm">Anzisha Ukaguzi wa Kwanza</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Namba ya Ukaguzi</th>
                            <th>Tarehe ya Kuhesabu</th>
                            <th class="text-center">Vipuri Vilivyohesabiwa</th>
                            <th class="text-center">Tofauti (Discrepancies)</th>
                            <th class="text-end">Thamani ya Tofauti</th>
                            <th>Aliyehesabu</th>
                            <th>Aliyeidhinisha</th>
                            <th class="text-center">Hali (Status)</th>
                            <th class="text-center">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($counts as $sc): ?>
                            <?php
                            $statusBadges = [
                                'draft'       => 'bg-secondary-subtle text-secondary border',
                                'in_progress' => 'bg-info-subtle text-info border border-info-subtle',
                                'submitted'   => 'bg-primary-subtle text-primary border border-primary-subtle',
                                'pending'     => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'approved'    => 'bg-success-subtle text-success border border-success-subtle',
                                'rejected'    => 'bg-danger-subtle text-danger border border-danger-subtle',
                                'cancelled'   => 'bg-dark-subtle text-dark border'
                            ];
                            $badgeClass = $statusBadges[$sc['status']] ?? 'bg-secondary';
                            ?>
                            <tr>
                                <td>
                                    <a href="/index.php?route=inventory_count_show&id=<?= $sc['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                        #<?= htmlspecialchars($sc['count_no'] ?: sprintf('SC-%05d', $sc['id'])) ?>
                                    </a>
                                </td>
                                <td><?= date('d M Y', strtotime($sc['count_date'])) ?></td>
                                <td class="text-center fw-semibold"><?= (int)$sc['items_count'] ?></td>
                                <td class="text-center">
                                    <?php if ((int)$sc['discrepancies_count'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <?= $sc['discrepancies_count'] ?> zenye tofauti
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Sawa (Zote 0)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold <?= (float)$sc['total_variance_cost'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                    <?= Helper::formatMoney((float)$sc['total_variance_cost']) ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($sc['conducted_by_name'] ?? 'Storekeeper') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($sc['approved_by_name'] ?? '--') ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> text-uppercase"><?= $sc['status'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="/index.php?route=inventory_count_show&id=<?= $sc['id'] ?>" class="btn btn-sm btn-light border">
                                        <i class="bi bi-eye me-1"></i> Kagua
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
