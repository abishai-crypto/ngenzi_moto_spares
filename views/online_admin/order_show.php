<?php
$addressData = json_decode($order['delivery_address_snapshot'], true) ?: [];
?>

<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="/index.php?route=online_orders" class="small text-decoration-none">&larr; Rudi kwenye Maagizo</a>
            <h4 class="fw-bold mb-1 mt-1 font-monospace">Agizo #<?= htmlspecialchars($order['order_number']) ?></h4>
            <span class="text-muted small">Mteja: <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_phone']) ?>)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="/index.php?route=customer_order_invoice&id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Chapisha Dispatch / Ankara
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Order Items & Status History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold mb-3">Vipuri Vilivyoagizwa</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Kipuri</th>
                                <th>SKU</th>
                                <th>Bei ya Kuuza</th>
                                <th>Idadi</th>
                                <th class="text-end">Jumla Ndogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="font-monospace text-muted"><?= htmlspecialchars($item['product_sku']) ?></td>
                                <td>TSh <?= number_format($item['unit_price'], 2) ?></td>
                                <td class="fw-bold"><?= $item['quantity'] ?></td>
                                <td class="text-end fw-bold text-primary">TSh <?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="4" class="text-end text-muted">Jumla ya Vipuri:</td>
                                <td class="text-end fw-semibold">TSh <?= number_format($order['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Usafirishaji:</td>
                                <td class="text-end fw-semibold text-success">
                                    <?= $order['delivery_fee'] == 0 ? 'BURE' : 'TSh ' . number_format($order['delivery_fee'], 2) ?>
                                </td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold fs-6">Jumla Kuu:</td>
                                <td class="text-end fw-bold text-primary fs-6">TSh <?= number_format($order['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Status History -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Kumbukumbu ya Hatua za Agizo</h6>
                <div class="ps-3 border-start">
                    <?php foreach ($order['history'] as $h): ?>
                    <div class="mb-3 ps-3">
                        <div class="fw-bold small"><?= htmlspecialchars($h['new_status']) ?></div>
                        <small class="text-muted d-block"><?= htmlspecialchars($h['comment']) ?></small>
                        <small class="text-muted" style="font-size: 11px;">
                            <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                            <?= !empty($h['changed_by_name']) ? " • Mfanyakazi: " . htmlspecialchars($h['changed_by_name']) : '' ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Management Actions & Details -->
        <div class="col-lg-4">
            <!-- Update Order Status Action Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear-wide text-primary"></i> Badilisha Hali ya Agizo</h6>
                <form action="/index.php?route=online_order_update_status" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Hali Mpya</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="PENDING" <?= $order['status'] === 'PENDING' ? 'selected' : '' ?>>PENDING (Limepokelewa)</option>
                            <option value="CONFIRMED" <?= $order['status'] === 'CONFIRMED' ? 'selected' : '' ?>>CONFIRMED (Limethibitishwa)</option>
                            <option value="PROCESSING" <?= $order['status'] === 'PROCESSING' ? 'selected' : '' ?>>PROCESSING (Linaandaliwa)</option>
                            <option value="READY_FOR_DELIVERY" <?= $order['status'] === 'READY_FOR_DELIVERY' ? 'selected' : '' ?>>READY (Liko Tayari Dukani)</option>
                            <option value="DELIVERED" <?= $order['status'] === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED (Limechukuliwa Dukani)</option>
                            <option value="CANCELLED" <?= $order['status'] === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED (Ghairi Agizo)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maelezo / Sababu</label>
                        <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Weka maelezo ya mabadiliko ya hali..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3">
                        <i class="bi bi-check-lg me-1"></i> Sasisha Hali ya Agizo
                    </button>
                </form>
            </div>

            <!-- Confirm Payment Card (if unpaid) -->
            <?php if ($order['payment_status'] !== 'paid'): ?>
            <div class="card border-warning border-opacity-50 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cash-coin text-warning"></i> Thibitisha Malipo</h6>
                <p class="small text-muted mb-3">Thibitisha kuwa mteja amelipa kiasi hiki kupitia Lipa Namba au Benki.</p>

                <?php if (!empty($order['payments'])): ?>
                <form action="/index.php?route=online_payment_confirm" method="POST">
                    <input type="hidden" name="payment_id" value="<?= $order['payments'][0]['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Kumbukumbu ya Muamala (Provider Ref)</label>
                        <input type="text" name="provider_reference" class="form-control form-control-sm" placeholder="Mf. MPESA-REF-12345" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maelezo</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Imethibitishwa na Mhasibu">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100 rounded-3">
                        <i class="bi bi-check-circle me-1"></i> Thibitisha Malipo (TSh <?= number_format($order['total_amount'], 2) ?>)
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Pickup & Recipient Info -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-shop text-primary me-1"></i> Utaratibu wa Kuchukua & Mteja</h6>
                <div class="small text-muted">
                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">Kuchukua Dukani Kariakoo</span><br>
                    <strong class="text-dark d-block"><?= htmlspecialchars($addressData['recipient_name'] ?? $order['customer_name']) ?></strong>
                    <span>Simu: <?= htmlspecialchars($addressData['phone'] ?? $order['customer_phone']) ?></span>
                </div>
            </div>

            <!-- Internal ERP Integration Info -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-2"><i class="bi bi-link-45deg text-primary"></i> Muunganisho wa Ndani (ERP)</h6>
                <div class="small text-muted">
                    <span>Njia ya Mauzo: <strong class="badge bg-info text-dark">ONLINE</strong></span><br>
                    <?php if (!empty($order['invoice_no'])): ?>
                        <span>Ankara ya Ndani: <strong class="text-primary font-monospace"><?= htmlspecialchars($order['invoice_no']) ?></strong></span><br>
                    <?php endif; ?>
                    <?php if (!empty($order['sale_id'])): ?>
                        <span>Sale Record ID: #<?= (int)$order['sale_id'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
