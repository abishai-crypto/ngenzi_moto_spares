<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-box-seam text-primary me-2"></i>Maagizo ya Mtandaoni (Online Orders)</h4>
            <span class="text-muted small">Usimamizi na uchakataji wa maagizo yote yaliyowekwa na wateja mtandaoni</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form action="/index.php" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="online_orders">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Namba ya Agizo, Mteja au Simu..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Hali zote za Agizo</option>
                    <option value="PENDING" <?= ($_GET['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING (Limepokelewa)</option>
                    <option value="CONFIRMED" <?= ($_GET['status'] ?? '') === 'CONFIRMED' ? 'selected' : '' ?>>CONFIRMED (Limethibitishwa)</option>
                    <option value="PROCESSING" <?= ($_GET['status'] ?? '') === 'PROCESSING' ? 'selected' : '' ?>>PROCESSING (Linaandaliwa)</option>
                    <option value="READY_FOR_DELIVERY" <?= ($_GET['status'] ?? '') === 'READY_FOR_DELIVERY' ? 'selected' : '' ?>>READY (Liko Tayari Dukani)</option>
                    <option value="DELIVERED" <?= ($_GET['status'] ?? '') === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED (Limechukuliwa Dukani)</option>
                    <option value="CANCELLED" <?= ($_GET['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED (Limeghairiwa)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">Hali zote za Malipo</option>
                    <option value="paid" <?= ($_GET['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>PAID (Yamelipwa)</option>
                    <option value="unpaid" <?= ($_GET['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>UNPAID (Hayajalipwa)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Namba ya Agizo</th>
                        <th>Mteja</th>
                        <th>Simu</th>
                        <th>Jumla</th>
                        <th>Njia ya Malipo</th>
                        <th>Malipo</th>
                        <th>Hali ya Agizo</th>
                        <th>Utaratibu</th>
                        <th>Tarehe</th>
                        <th class="text-end pe-4">Hatua</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">Hakuna maagizo yaliyopatikana kwa vigezo hivi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary">
                                <a href="/index.php?route=online_order_show&id=<?= $ord['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($ord['order_number']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                            <td><?= htmlspecialchars($ord['customer_phone']) ?></td>
                            <td class="fw-bold">TSh <?= number_format($ord['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($ord['payment_method']) ?></td>
                            <td>
                                <span class="badge <?= $ord['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= strtoupper($ord['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $badge = match($ord['status']) {
                                    'DELIVERED', 'COMPLETED' => 'bg-success',
                                    'CONFIRMED', 'PAID' => 'bg-info text-dark',
                                    'PROCESSING' => 'bg-warning text-dark',
                                    'READY_FOR_DELIVERY' => 'bg-primary',
                                    'CANCELLED' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($ord['status']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border"><i class="bi bi-shop me-1"></i> Dukani</span>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="/index.php?route=online_order_show&id=<?= $ord['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    Fungua &rarr;
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
