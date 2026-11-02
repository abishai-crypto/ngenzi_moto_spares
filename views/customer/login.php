<div class="container my-5" style="max-width: 440px;">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
            <div class="brand-icon rounded-3 p-2 bg-gradient d-inline-flex align-items-center justify-content-center text-white mb-2" style="background: linear-gradient(135deg, #0f766e, #f59e0b); width: 48px; height: 48px;">
                <i class="bi bi-person-fill fs-4"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Kuingia kwenye Akaunti</h4>
            <p class="text-muted small">Weka taarifa zako kuingia kwenye duka la mtandaoni</p>
        </div>

        <form action="/index.php?route=customer_login_post" method="POST">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? 'customer_dashboard') ?>">

            <div class="mb-3">
                <label class="form-label small fw-semibold">Barua Pepe au Namba ya Simu</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="identifier" class="form-control border-start-0" placeholder="mf. 0712345678 au barua pepe" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-semibold mb-0">Nenosiri</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-store-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Ingia Sasa
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="text-muted small mb-2">Huna akaunti bado?</p>
            <a href="/index.php?route=customer_register" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                Fungua Akaunti ya Mteja Bure
            </a>
        </div>

        <div class="text-center mt-3">
            <a href="/index.php?route=login" class="text-muted small text-decoration-none">
                <i class="bi bi-shield-lock"></i> Wafanyakazi: Bonyeza hapa kuingia
            </a>
        </div>
    </div>
</div>
