<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-truck text-primary me-2"></i>Usimamizi wa Usafirishaji (Deliveries)</h4>
            <span class="text-muted small">Kugawa na kusasisha hali ya usafirishaji wa mizigo ya wateja wa mtandaoni (Bolt, Store Delivery, Courier & Pickup)</span>
        </div>
    </div>

    <!-- Filter Bar (Phase 17) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-4">
        <form action="/index.php" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="online_deliveries">

            <!-- Provider Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Msafirishaji (Provider)</label>
                <select name="provider" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Wasafirishaji Wote</option>
                    <option value="BOLT" <?= ($filters['provider'] ?? '') === 'BOLT' ? 'selected' : '' ?>>Bolt Express</option>
                    <option value="STORE_DELIVERY" <?= ($filters['provider'] ?? '') === 'STORE_DELIVERY' ? 'selected' : '' ?>>Store Delivery (Ngenzi)</option>
                    <option value="CUSTOMER_COURIER" <?= ($filters['provider'] ?? '') === 'CUSTOMER_COURIER' ? 'selected' : '' ?>>Msafirishaji wa Mteja</option>
                    <option value="OTHER_COURIER" <?= ($filters['provider'] ?? '') === 'OTHER_COURIER' ? 'selected' : '' ?>>Wasafirishaji Wengine</option>
                    <option value="CUSTOMER_PICKUP" <?= ($filters['provider'] ?? '') === 'CUSTOMER_PICKUP' ? 'selected' : '' ?>>Kuchukua Dukani (Pickup)</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Hali (Status)</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Hali Zote</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                    <option value="PROVIDER_SELECTED" <?= ($filters['status'] ?? '') === 'PROVIDER_SELECTED' ? 'selected' : '' ?>>PROVIDER_SELECTED</option>
                    <option value="ASSIGNED" <?= ($filters['status'] ?? '') === 'ASSIGNED' ? 'selected' : '' ?>>ASSIGNED (Amepangiwa)</option>
                    <option value="OUT_FOR_DELIVERY" <?= ($filters['status'] ?? '') === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>OUT_FOR_DELIVERY (Njia)</option>
                    <option value="DELIVERED" <?= ($filters['status'] ?? '') === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED (Amepokea)</option>
                    <option value="FAILED" <?= ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' ?>>FAILED (Imefeli)</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED (Imeghairiwa)</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Mzigo / Mteja</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Namba ya mzigo, agizo, mpokeaji..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>

            <div class="col-md-2 pt-md-4 text-end">
                <a href="/index.php?route=online_deliveries" class="btn btn-light btn-sm border w-100">Futa Vichujio</a>
            </div>
        </form>
    </div>

    <!-- Deliveries Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Namba ya Mzigo</th>
                        <th>Namba ya Agizo</th>
                        <th>Njia ya Usafirishaji</th>
                        <th>Mpokeaji na Simu</th>
                        <th>Dereva / Msafirishaji</th>
                        <th>Ref / Bolt ID</th>
                        <th>Gharama</th>
                        <th>Hali</th>
                        <th class="text-end pe-4">Vitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Hakuna mizigo inayolingana na vigezo vilivyochaguliwa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($deliveries as $del): ?>
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary">
                                <?= htmlspecialchars($del['delivery_number']) ?>
                            </td>
                            <td class="font-monospace fw-semibold">
                                <a href="/index.php?route=online_order_show&id=<?= $del['order_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($del['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $provCode = strtoupper($del['delivery_provider'] ?? 'STORE_DELIVERY');
                                $provBadge = match($provCode) {
                                    'BOLT' => 'bg-warning text-dark',
                                    'CUSTOMER_PICKUP' => 'bg-success',
                                    'CUSTOMER_COURIER' => 'bg-info text-dark',
                                    'OTHER_COURIER' => 'bg-secondary',
                                    default => 'bg-primary'
                                };
                                ?>
                                <span class="badge <?= $provBadge ?> rounded-pill px-2 py-1">
                                    <?php if ($provCode === 'BOLT'): ?>
                                        <i class="bi bi-lightning-charge-fill me-1"></i> Bolt
                                    <?php elseif ($provCode === 'CUSTOMER_PICKUP'): ?>
                                        <i class="bi bi-shop me-1"></i> Pickup
                                    <?php elseif ($provCode === 'STORE_DELIVERY'): ?>
                                        <i class="bi bi-truck me-1"></i> Store Del.
                                    <?php else: ?>
                                        <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars($provCode) ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($del['recipient_name']) ?></strong></div>
                                <div class="text-muted small"><?= htmlspecialchars($del['recipient_phone']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($del['driver_name'])): ?>
                                    <div><i class="bi bi-person-badge text-primary"></i> <?= htmlspecialchars($del['driver_name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($del['driver_phone'] ?: '') ?> <?= !empty($del['vehicle_info']) ? ' (' . htmlspecialchars($del['vehicle_info']) . ')' : '' ?></div>
                                <?php elseif (!empty($del['courier_name'])): ?>
                                    <div><i class="bi bi-person text-secondary"></i> <?= htmlspecialchars($del['courier_name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($del['courier_phone'] ?: '') ?></div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Haijapangwa</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace small">
                                <?= !empty($del['external_reference']) ? htmlspecialchars($del['external_reference']) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="fw-bold">
                                <?= (float)$del['delivery_fee'] == 0 ? '<span class="text-success">BURE</span>' : 'TSh ' . number_format($del['delivery_fee'], 2) ?>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($del['delivery_status']) {
                                    'DELIVERED' => 'bg-success',
                                    'OUT_FOR_DELIVERY' => 'bg-warning text-dark',
                                    'ASSIGNED', 'ACCEPTED' => 'bg-info text-dark',
                                    'CANCELLED', 'FAILED', 'RETURNED' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($del['delivery_status']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <!-- Assign Driver / Courier Button -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" 
                                            title="Panga Dereva / Weka Ref ya Bolt"
                                            onclick='openAssignModal(<?= json_encode([
                                                "id" => $del["id"],
                                                "delivery_number" => $del["delivery_number"],
                                                "provider" => $del["delivery_provider"],
                                                "driver_name" => $del["driver_name"],
                                                "driver_phone" => $del["driver_phone"],
                                                "vehicle_info" => $del["vehicle_info"],
                                                "courier_name" => $del["courier_name"],
                                                "courier_phone" => $del["courier_phone"],
                                                "external_reference" => $del["external_reference"],
                                                "tracking_notes" => $del["tracking_notes"]
                                            ]) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Quick Status Update Form -->
                                    <form action="/index.php?route=online_delivery_update_status" method="POST" class="d-inline-flex gap-1 align-items-center">
                                        <input type="hidden" name="delivery_id" value="<?= $del['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: 140px;">
                                            <option value="PROVIDER_SELECTED" <?= $del['delivery_status'] === 'PROVIDER_SELECTED' ? 'selected' : '' ?>>PROVIDER_SELECTED</option>
                                            <option value="ASSIGNED" <?= $del['delivery_status'] === 'ASSIGNED' ? 'selected' : '' ?>>ASSIGNED</option>
                                            <option value="OUT_FOR_DELIVERY" <?= $del['delivery_status'] === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>OUT_FOR_DELIVERY</option>
                                            <option value="DELIVERED" <?= $del['delivery_status'] === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED</option>
                                            <option value="FAILED" <?= $del['delivery_status'] === 'FAILED' ? 'selected' : '' ?>>FAILED</option>
                                            <option value="CANCELLED" <?= $del['delivery_status'] === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-3" title="Hifadhi Hali">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Assign Driver / Courier / Bolt Reference -->
<div class="modal fade" id="assignDeliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="/index.php?route=online_delivery_assign" method="POST">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-truck text-primary me-2"></i> Panga Usafirishaji: <span id="modalDelNum"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delivery_id" id="assignDeliveryId">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Njia ya Usafirishaji (Provider)</label>
                        <select name="delivery_provider" id="assignProvider" class="form-select form-select-sm">
                            <option value="STORE_DELIVERY">Store Delivery (Ngenzi Fleet)</option>
                            <option value="BOLT">Bolt Express Delivery</option>
                            <option value="CUSTOMER_COURIER">Customer Courier</option>
                            <option value="OTHER_COURIER">Other Courier</option>
                            <option value="CUSTOMER_PICKUP">Customer Pickup</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jina la Dereva (Store/Bolt)</label>
                            <input type="text" name="driver_name" id="assignDriverName" class="form-control form-control-sm" placeholder="Mf. Ally Bakari">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Simu ya Dereva</label>
                            <input type="tel" name="driver_phone" id="assignDriverPhone" class="form-control form-control-sm" placeholder="07xxxxxxxx">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Chombo / Namba ya Bamba</label>
                            <input type="text" name="vehicle_info" id="assignVehicleInfo" class="form-control form-control-sm" placeholder="Mf. Boxer MC 123 ABC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Namba ya Bolt / Kifurushi (Ref ID)</label>
                            <input type="text" name="external_reference" id="assignExtRef" class="form-control form-control-sm font-monospace" placeholder="Mf. BOLT-TZ-123456">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jina la Courier (Hiari)</label>
                            <input type="text" name="courier_name" id="assignCourierName" class="form-control form-control-sm" placeholder="Jina la Kampuni">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Simu ya Courier</label>
                            <input type="tel" name="courier_phone" id="assignCourierPhone" class="form-control form-control-sm" placeholder="07xxxxxxxx">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maelezo ya Ufuatiliaji (Tracking Notes)</label>
                        <textarea name="tracking_notes" id="assignNotes" class="form-control form-control-sm" rows="2" placeholder="Mf. Dereva amechukua mzigo saa 4:15 asubuhi kuelekea Ubungo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Hifadhi Mabadiliko
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(del) {
    document.getElementById('assignDeliveryId').value = del.id;
    document.getElementById('modalDelNum').textContent = del.delivery_number;
    document.getElementById('assignProvider').value = del.provider || 'STORE_DELIVERY';
    document.getElementById('assignDriverName').value = del.driver_name || '';
    document.getElementById('assignDriverPhone').value = del.driver_phone || '';
    document.getElementById('assignVehicleInfo').value = del.vehicle_info || '';
    document.getElementById('assignExtRef').value = del.external_reference || '';
    document.getElementById('assignCourierName').value = del.courier_name || '';
    document.getElementById('assignCourierPhone').value = del.courier_phone || '';
    document.getElementById('assignNotes').value = del.tracking_notes || '';

    var modal = new bootstrap.Modal(document.getElementById('assignDeliveryModal'));
    modal.show();
}
</script>
