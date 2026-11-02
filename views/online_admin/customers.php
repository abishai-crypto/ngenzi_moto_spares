<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Wateja wa Mtandaoni (Online Customers)</h4>
            <span class="text-muted small">Orodha ya wateja wote waliojisajili kupitia duka la mtandaoni</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Namba ya Mteja</th>
                        <th>Jina Kamili</th>
                        <th>Simu</th>
                        <th>Barua Pepe</th>
                        <th>Idadi ya Maagizo</th>
                        <th>Jumla ya Manunuzi</th>
                        <th>Hali</th>
                        <th>Tarehe ya Usajili</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Bado hakuna wateja waliojisajili mtandaoni.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary"><?= htmlspecialchars($c['customer_code']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['phone']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td class="fw-bold"><?= $c['order_count'] ?></td>
                            <td class="fw-bold text-success">TSh <?= number_format($c['total_spent'], 2) ?></td>
                            <td>
                                <span class="badge <?= $c['status'] === 'active' ? 'bg-success' : ($c['status'] === 'blocked' ? 'bg-danger' : 'bg-secondary') ?>">
                                    <?= strtoupper($c['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
