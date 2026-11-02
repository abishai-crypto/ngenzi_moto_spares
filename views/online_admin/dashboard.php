<div class="content-wrapper p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-shop text-primary me-2"></i>Dashibodi ya Duka la Mtandaoni</h4>
            <span class="text-muted small">Muhtasari wa mauzo, maagizo na usafirishaji wa wateja wa mtandaoni (Online Store KPIs)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="/index.php?route=online_orders" class="btn btn-primary btn-sm rounded-3">
                <i class="bi bi-box-seam me-1"></i> Orodha ya Maagizo
            </a>
            <a href="/index.php?route=online_settings" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-gear me-1"></i> Mipangilio
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Mauzo ya Mtandaoni Leo</span>
                    <i class="bi bi-cash-stack text-success fs-4"></i>
                </div>
                <h4 class="fw-bold text-success mb-0">TSh <?= number_format($salesToday, 2) ?></h4>
                <small class="text-muted"><?= $ordersToday ?> maagizo yaliyopokelewa leo</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Yanasubiri Malipo/Uhuru</span>
                    <i class="bi bi-hourglass-split text-warning fs-4"></i>
                </div>
                <h4 class="fw-bold text-warning mb-0"><?= $pendingOrders ?></h4>
                <small class="text-muted"><?= $paidOrders ?> tayari yamelipiwa</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Yanaandaliwa / Njiani</span>
                    <i class="bi bi-bicycle text-info fs-4"></i>
                </div>
                <h4 class="fw-bold text-info mb-0"><?= $processingOrders + $outForDeliveryOrders ?></h4>
                <small class="text-muted"><?= $outForDeliveryOrders ?> yako njiani sasa</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Wateja Waliojisajili</span>
                    <i class="bi bi-people-fill text-primary fs-4"></i>
                </div>
                <h4 class="fw-bold text-primary mb-0"><?= $onlineCustomers ?></h4>
                <small class="text-muted"><?= $newCustThisMonth ?> wapya mwezi huu</small>
            </div>
        </div>
    </div>

    <!-- Recent Online Orders -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Maagizo Mapya Zaidi ya Mtandaoni</h6>
            <a href="/index.php?route=online_orders" class="small text-decoration-none">Tazama yote (<?= count($recentOrders) ?>) &rarr;</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="text-center py-4 text-muted small">Bado hakuna maagizo yoyote ya mtandaoni yaliyowekwa.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Namba ya Agizo</th>
                            <th>Mteja</th>
                            <th>Simu</th>
                            <th>Jumla</th>
                            <th>Malipo</th>
                            <th>Hali</th>
                            <th>Tarehe</th>
                            <th class="text-end">Hatua</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($ord['order_number']) ?></td>
                            <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                            <td><?= htmlspecialchars($ord['customer_phone']) ?></td>
                            <td class="fw-bold">TSh <?= number_format($ord['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $ord['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= strtoupper($ord['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($ord['status']) ?></span>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="/index.php?route=online_order_show&id=<?= $ord['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    Chakata &rarr;
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
