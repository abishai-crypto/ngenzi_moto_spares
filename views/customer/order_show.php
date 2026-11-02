<?php
$addressData = json_decode($order['delivery_address_snapshot'], true) ?: [];
$statusSteps = [
    'PENDING'            => 1,
    'PAYMENT_PENDING'    => 1,
    'PAID'               => 2,
    'CONFIRMED'          => 2,
    'PROCESSING'         => 3,
    'READY_FOR_DELIVERY' => 4,
    'OUT_FOR_DELIVERY'   => 5,
    'DELIVERED'          => 6,
    'COMPLETED'          => 6,
];
$currentStep = $statusSteps[$order['status']] ?? 1;
?>

<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_orders" class="text-decoration-none">Maagizo Yangu</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($order['order_number']) ?></li>
        </ol>
    </nav>

    <!-- Header bar -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-light text-primary border mb-1">Agizo la Mtandaoni</span>
                <h4 class="fw-bold text-dark mb-1 font-monospace"><?= htmlspecialchars($order['order_number']) ?></h4>
                <small class="text-muted">
                    Liliwekwa tarehe: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="/index.php?route=customer_order_invoice&id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
                    <i class="bi bi-printer me-1"></i> Chapisha Ankara / Risiti
                </a>
                <?php if (in_array($order['status'], ['DELIVERED', 'COMPLETED', 'PAID', 'CONFIRMED'])): ?>
                    <a href="/index.php?route=customer_return_create&order_id=<?= $order['id'] ?>" class="btn btn-outline-warning rounded-pill px-3 btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Omba Marejesho
                    </a>
                <?php endif; ?>
                <?php if (in_array($order['status'], ['PENDING', 'PAYMENT_PENDING'])): ?>
                    <button class="btn btn-outline-danger rounded-pill px-3 btn-sm" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="bi bi-x-circle me-1"></i> Ghairi Agizo
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Tracking Timeline (Manual Store Pickup) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shop text-primary me-1"></i> Maendeleo ya Agizo Lako (Kuchukua Dukani)</h6>

        <?php if ($order['status'] === 'CANCELLED'): ?>
            <div class="alert alert-danger rounded-3 mb-0">
                <i class="bi bi-x-octagon-fill me-1"></i> Agizo hili limeghairiwa. Sababu: <?= htmlspecialchars($order['cancelled_reason'] ?: 'Haijatajwa') ?>
            </div>
        <?php else: ?>
            <div class="row g-2 text-center small mt-2">
                <div class="col">
                    <div class="p-2 rounded-3 <?= $currentStep >= 1 ? 'bg-success text-white' : 'bg-light text-muted' ?> fw-semibold">
                        <i class="bi bi-check-circle"></i> 1. Limepokelewa
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded-3 <?= $currentStep >= 2 ? 'bg-success text-white' : 'bg-light text-muted' ?> fw-semibold">
                        <i class="bi bi-wallet2"></i> 2. Limethibitishwa
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded-3 <?= $currentStep >= 3 ? 'bg-success text-white' : 'bg-light text-muted' ?> fw-semibold">
                        <i class="bi bi-box-seam"></i> 3. Linaandaliwa Dukani
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded-3 <?= $currentStep >= 4 ? 'bg-success text-white' : 'bg-light text-muted' ?> fw-semibold">
                        <i class="bi bi-bell-fill"></i> 4. Liko Tayari Kuchukuliwa
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded-3 <?= $currentStep >= 6 ? 'bg-success text-white' : 'bg-light text-muted' ?> fw-semibold">
                        <i class="bi bi-shop"></i> 5. Limekabidhiwa Dukani
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">Vipuri Vilivyoagizwa</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Kipuri</th>
                                <th>Bei</th>
                                <th>Idadi</th>
                                <th class="text-end">Jumla Ndogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block small"><?= htmlspecialchars($item['product_name']) ?></span>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                </td>
                                <td class="small">TSh <?= number_format($item['unit_price'], 2) ?></td>
                                <td class="small fw-bold"><?= $item['quantity'] ?></td>
                                <td class="text-end fw-bold text-primary small">TSh <?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end small text-muted">Jumla ya Vipuri:</td>
                                <td class="text-end small fw-semibold">TSh <?= number_format($order['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end small text-muted">Gharama ya Usafirishaji:</td>
                                <td class="text-end small fw-semibold text-success">
                                    <?= $order['delivery_fee'] == 0 ? 'BURE' : 'TSh ' . number_format($order['delivery_fee'], 2) ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Jumla Kuu:</td>
                                <td class="text-end fw-bold text-primary fs-5">TSh <?= number_format($order['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Status History Log -->
            <?php if (!empty($order['history'])): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-journal-text text-primary"></i> Kumbukumbu ya Hatua za Agizo</h6>
                <div class="timeline ps-3 border-start">
                    <?php foreach ($order['history'] as $h): ?>
                    <div class="position-relative mb-3 ps-3">
                        <div class="position-absolute start-0 top-0 translate-middle-x rounded-circle bg-primary" style="width: 10px; height: 10px; margin-left: -1px; margin-top: 5px;"></div>
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($h['new_status']) ?></div>
                        <small class="text-muted d-block"><?= htmlspecialchars($h['comment']) ?></small>
                        <small class="text-muted" style="font-size: 10px;"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Address & Payment Info -->
        <div class="col-lg-4">
            <!-- Pickup Location & Recipient Info -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shop text-primary me-1"></i> Eneo la Kuchukulia Mzigo</h6>
                <div class="small text-muted mb-3">
                    <strong class="text-dark d-block">Duka la Ngenzi Moto Spare Parts</strong>
                    <span>Mtaa wa Nyamwezi na Msimbazi, Kariakoo, Dar es Salaam</span><br>
                    <span>Muda: Jumatatu – Jumamosi (Saa 2:00 Asubuhi – 12:00 Jioni)</span>
                </div>
                <h6 class="fw-bold text-dark small mb-1">Mpokeaji:</h6>
                <div class="small text-muted">
                    <strong class="text-dark d-block"><?= htmlspecialchars($addressData['recipient_name'] ?? $order['customer_name']) ?></strong>
                    <span>Simu: <?= htmlspecialchars($addressData['phone'] ?? $order['customer_phone']) ?></span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 text-success"></i> Taarifa za Malipo</h6>
                <div class="small mb-2">
                    <span class="text-muted d-block">Njia:</span>
                    <strong class="text-dark"><?= htmlspecialchars($order['payment_method']) ?></strong>
                </div>
                <div class="small mb-3">
                    <span class="text-muted d-block">Hali ya Malipo:</span>
                    <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= strtoupper($order['payment_status']) ?>
                    </span>
                </div>

                <?php if (!empty($order['payments'])): ?>
                    <h6 class="fw-bold text-dark small mb-2 border-top pt-2">Miamala ya Malipo:</h6>
                    <?php foreach ($order['payments'] as $p): ?>
                        <div class="p-2 bg-light rounded-3 small mb-2 border">
                            <span class="font-monospace text-primary fw-bold d-block"><?= htmlspecialchars($p['payment_reference']) ?></span>
                            <span class="text-muted"><?= htmlspecialchars($p['provider']) ?> • TSh <?= number_format($p['amount'], 2) ?></span>
                            <span class="badge bg-success-subtle text-success float-end"><?= $p['status'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cancel Order -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ghairi Agizo #<?= htmlspecialchars($order['order_number']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/index.php?route=customer_order_cancel" method="POST">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <div class="modal-body">
                    <p class="small text-muted mb-3">Una uhakika unataka kughairi agizo hili? Bidhaa zitarejeshwa stoo moja kwa moja.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sababu ya Kughairi</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Mf. Nimekosea mtindo wa chombo, nitaweka jipya..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Hapana</button>
                    <button type="submit" class="btn btn-danger rounded-pill">Ndio, Ghairi Agizo</button>
                </div>
            </form>
        </div>
    </div>
</div>
