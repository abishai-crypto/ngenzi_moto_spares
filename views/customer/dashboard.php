<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashibodi ya Mteja</li>
        </ol>
    </nav>

    <!-- Welcome Header -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle p-3 bg-primary-subtle text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">Karibu, <?= htmlspecialchars($customer['name']) ?>!</h4>
                    <span class="badge bg-light text-muted border">Namba ya Mteja: <?= htmlspecialchars($customer['code']) ?></span>
                    <span class="text-muted small ms-2"><i class="bi bi-telephone"></i> <?= htmlspecialchars($customer['phone']) ?></span>
                </div>
            </div>
            <div>
                <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-4">
                    <i class="bi bi-cart-plus me-1"></i> Agiza Vipuri
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Maagizo Yote</span>
                    <i class="bi bi-box-seam text-primary fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= $totalOrders ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Yanayochakatwa</span>
                    <i class="bi bi-hourglass-split text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-warning"><?= $pendingOrders ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Viliyowasilishwa</span>
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-success"><?= $completedOrders ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Wishlist (Vipendwa)</span>
                    <i class="bi bi-heart-fill text-danger fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-danger"><?= $wishlistCount ?></h3>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Navigation -->
    <div class="row g-4">
        <!-- Recent Orders Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary"></i> Maagizo Yako ya Hivi Karibuni</h5>
                    <a href="/index.php?route=customer_orders" class="small text-decoration-none">Tazama yote &rarr;</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-5 bg-light rounded-4 text-muted small">
                        <i class="bi bi-bag-x fs-1 d-block mb-2"></i>
                        Bado haujafanya agizo lolote la mtandaoni.
                        <div class="mt-3">
                            <a href="/index.php?route=shop" class="btn btn-store-primary btn-sm rounded-pill px-3">Anza Kununua</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Namba ya Agizo</th>
                                    <th>Tarehe</th>
                                    <th>Kiasi</th>
                                    <th>Hali</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $ord): ?>
                                <tr>
                                    <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($ord['order_number']) ?></td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($ord['created_at'])) ?></td>
                                    <td class="fw-bold small">TSh <?= number_format($ord['total_amount'], 2) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($ord['status']) {
                                            'DELIVERED', 'COMPLETED' => 'bg-success',
                                            'CONFIRMED', 'PAID' => 'bg-info text-dark',
                                            'PROCESSING', 'OUT_FOR_DELIVERY' => 'bg-warning text-dark',
                                            'CANCELLED' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-2 py-1 small">
                                            <?= htmlspecialchars($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/index.php?route=customer_order_show&id=<?= $ord['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            Tazama
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

        <!-- Quick Links Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-stars text-primary"></i> Njia za Mkato</h6>
                <div class="list-group list-group-flush small">
                    <a href="/index.php?route=customer_orders" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-box-seam me-2 text-primary"></i> Maagizo Yangu</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="/index.php?route=customer_addresses" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-geo-alt me-2 text-success"></i> Anwani Zangu (<?= $addressesCount ?>)</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="/index.php?route=customer_wishlist" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-heart me-2 text-danger"></i> Wishlist Yangu (<?= $wishlistCount ?>)</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="/index.php?route=customer_notifications" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-bell me-2 text-warning"></i> Taarifa & Meseji (<?= $unreadNotifications ?>)</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="/index.php?route=customer_profile" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-gear me-2 text-secondary"></i> Badili Taarifa za Akaunti</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
