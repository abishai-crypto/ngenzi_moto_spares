<div class="container my-5" style="max-width: 500px;">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
            <div class="brand-icon rounded-3 p-2 bg-gradient d-inline-flex align-items-center justify-content-center text-white mb-2" style="background: linear-gradient(135deg, #0f766e, #f59e0b); width: 48px; height: 48px;">
                <i class="bi bi-person-plus-fill fs-4"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Kujisajili kama Mteja</h4>
            <p class="text-muted small">Fungua akaunti uanze kuagiza vipuri halisi kwa urahisi</p>
        </div>

        <form action="/index.php?route=customer_register_post" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Jina Kamili</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="name" class="form-control border-start-0" placeholder="mf. Ally Juma Rashid" 
                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Namba ya Simu</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                        <input type="tel" name="phone" class="form-control border-start-0" placeholder="07xxxxxxxx" 
                               value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Barua Pepe (Email)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control border-start-0" placeholder="mfano@gmail.com" 
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nenosiri</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="Angalau tarakimu 6" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Thibitisha Nenosiri</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check text-muted"></i></span>
                        <input type="password" name="confirm_password" class="form-control border-start-0" placeholder="Rudia nenosiri" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-store-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Fungua Akaunti Yangu
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="text-muted small mb-0">
                Tayari una akaunti? <a href="/index.php?route=customer_login" class="fw-bold text-primary text-decoration-none">Ingia Hapa</a>
            </p>
        </div>
    </div>
</div>
