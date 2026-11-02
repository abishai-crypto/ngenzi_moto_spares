<div class="container my-4" style="max-width: 780px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_dashboard" class="text-decoration-none">Dashibodi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Taarifa & Meseji</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-bell-fill text-warning"></i> Taarifa & Meseji Zangu</h4>
            <p class="text-muted small mb-0">Taarifa zote za maagizo, malipo na usafirishaji wa mizigo yako.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form action="/index.php?route=customer_notification_read" method="POST">
                <input type="hidden" name="id" value="0">
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-check2-all me-1"></i> Weka Zote Zimesomwa
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <h5 class="fw-bold mt-2">Huna taarifa mpya kwa sasa.</h5>
            <p class="text-muted small">Taarifa za maagizo yako zitaonekana hapa mara tu unapofanya ununuzi.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($notifications as $n): ?>
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white <?= !$n['is_read'] ? 'border-start border-4 border-warning' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex gap-3">
                        <div class="p-2 rounded-circle <?= !$n['is_read'] ? 'bg-warning-subtle text-warning' : 'bg-light text-muted' ?> fs-5" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi <?= $n['type'] === 'order' ? 'bi-box-seam' : ($n['type'] === 'payment' ? 'bi-wallet2' : 'bi-info-circle') ?>"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($n['title']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($n['message']) ?></p>
                            <div class="d-flex align-items-center gap-3">
                                <small class="text-muted" style="font-size: 11px;"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                                <?php if (!empty($n['action_url'])): ?>
                                    <a href="/<?= ltrim($n['action_url'], '/') ?>" class="small text-primary text-decoration-none fw-semibold">
                                        Fungua kuona zaidi &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <form action="/index.php?route=customer_notification_read" method="POST">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn btn-link p-0 text-muted small text-decoration-none" title="Weka imesomwa">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
