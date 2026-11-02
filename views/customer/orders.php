<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_dashboard" class="text-decoration-none">Dashibodi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Maagizo Yangu</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-box-seam text-primary"></i> Historia ya Maagizo Yangu</h4>
            <p class="text-muted small mb-0">Tazama na ufuatilie maagizo yako yote uliyofanya mtandaoni.</p>
        </div>
        <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-3">
            <i class="bi bi-cart-plus me-1"></i> Agiza Vipuri Zaidi
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <h5 class="fw-bold mt-2">Bado haujafanya agizo lolote.</h5>
            <p class="text-muted small">Angalia katalogi yetu uanze kuagiza vipuri vya chombo chako.</p>
            <div>
                <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-4">Anza Kununua</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Namba ya Agizo</th>
                            <th>Tarehe</th>
                            <th>Jumla Kuu</th>
                            <th>Malipo</th>
                            <th>Hali ya Agizo</th>
                            <th>Utaratibu</th>
                            <th class="text-end pe-4">Hatua</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary font-monospace">
                                <a href="/index.php?route=customer_order_show&id=<?= $ord['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($ord['order_number']) ?>
                                </a>
                            </td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                            <td class="fw-bold text-dark">TSh <?= number_format($ord['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $ord['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-2 py-1 small">
                                    <?= strtoupper($ord['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($ord['status']) {
                                    'DELIVERED', 'COMPLETED' => 'bg-success',
                                    'CONFIRMED', 'PAID' => 'bg-info text-dark',
                                    'PROCESSING', 'OUT_FOR_DELIVERY' => 'bg-warning text-dark',
                                    'CANCELLED' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?> rounded-pill px-2 py-1 small">
                                    <?= htmlspecialchars($ord['status']) ?>
                                </span>
                            </td>
                            <td class="small">
                                <span class="badge bg-light text-primary border">
                                    <i class="bi bi-shop me-1"></i> Dukani
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="/index.php?route=customer_order_show&id=<?= $ord['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                        Fuatilia
                                    </a>
                                    <a href="/index.php?route=customer_order_invoice&id=<?= $ord['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Chapisha Ankara">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="/index.php?route=customer_orders&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
