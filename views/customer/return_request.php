<div class="container my-4" style="max-width: 760px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_orders" class="text-decoration-none">Maagizo Yangu</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_order_show&id=<?= $order['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($order['order_number']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Ombi la Marejesho</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-arrow-counterclockwise text-warning"></i> Omba Kurejesha Kipuri</h4>
        <p class="text-muted small mb-4">Agizo: <strong class="text-primary font-monospace"><?= htmlspecialchars($order['order_number']) ?></strong></p>

        <form action="/index.php?route=customer_return_store" method="POST">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

            <h6 class="fw-bold text-dark mb-2">Chagua vipuri unavyotaka kurudisha:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Kipuri</th>
                            <th>Ulichonunua</th>
                            <th style="width: 130px;">Idadi ya Kurudisha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold d-block"><?= htmlspecialchars($item['product_name']) ?></span>
                                <small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                            </td>
                            <td><?= $item['quantity'] ?></td>
                            <td>
                                <input type="number" name="items[<?= $item['product_id'] ?>][quantity]" 
                                       class="form-control form-control-sm text-center" 
                                       value="0" min="0" max="<?= $item['quantity'] ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Sababu Kuu ya Kurejesha</label>
                <select name="reason" class="form-select form-select-sm" required>
                    <option value="CUSTOMER_RETURN">Sikuhitaji / Nimebadili nia (Customer Return)</option>
                    <option value="WRONG_ITEM">Kipuri Si Sahihi / Hakifai kwenye chombo (Wrong Item)</option>
                    <option value="DEFECTIVE_ITEM">Kina kasoro / Hakifanyi kazi (Defective)</option>
                    <option value="DAMAGED_ITEM">Kiliharibika wakati wa usafirishaji (Damaged)</option>
                    <option value="WARRANTY_RETURN">Madai ya Udhamini (Warranty Claim)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Maelezo ya Ziada (Ufafanuzi)</label>
                <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Eleza kwa undani kasoro au sababu ya kurudisha ili kusaidia idara ya ukaguzi..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/index.php?route=customer_order_show&id=<?= $order['id'] ?>" class="btn btn-outline-secondary rounded-pill px-4">Ghairi</a>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">
                    <i class="bi bi-send me-1"></i> Wasilisha Ombi la Marejesho
                </button>
            </div>
        </form>
    </div>
</div>
