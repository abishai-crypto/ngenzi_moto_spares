<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-badge">
                <i class="bi bi-gear-wide-connected"></i>
            </div>
            <h4 class="fw-bold mb-1 tracking-wide">NGENZI MOTO SPARE</h4>
            <p class="small text-white-50 mb-0">Dar es Salaam • Business Management & POS</p>
        </div>

        <div class="auth-body">
            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-2"></i><?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="/index.php?route=login_post" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small text-uppercase">Jina la Mtumiaji au Barua Pepe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="username" id="usernameInput" class="form-control border-start-0 ps-0" placeholder="Mf. admin" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold text-muted small text-uppercase mb-0">Nenosiri (Password)</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="passwordInput" class="form-control border-start-0 ps-0" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2 fs-6">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingia Kwenye Mfumo
                </button>
            </form>

            <!-- Quick Demo Accounts for Testing -->
            <div class="mt-4 pt-3 border-top">
                <div class="text-center mb-2">
                    <small class="text-muted fw-semibold">Akaunti za Majaribio (Bofya Kujaza):</small>
                </div>
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setDemo('admin', 'admin123')">
                        👑 Mmiliki
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setDemo('manager', 'manager123')">
                        📋 Meneja
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setDemo('cashier', 'cashier123')">
                        💰 Cashier
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setDemo('storekeeper', 'store123')">
                        📦 Stoo
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setDemo('accountant', 'account123')">
                        📊 Mhasibu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setDemo(u, p) {
    document.getElementById('usernameInput').value = u;
    document.getElementById('passwordInput').value = p;
}
</script>
