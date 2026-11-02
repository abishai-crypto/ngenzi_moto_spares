<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-key-fill text-primary me-2"></i>Badili Nenosiri Lako</h4>
                <p class="text-muted small mb-0">Sasisha nenosiri lako la kuingia kwenye mfumo.</p>
            </div>
            <a href="/index.php?route=dashboard" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Dashibodi
            </a>
        </div>

        <div class="premium-card">
            <div class="premium-card-header">
                <h5><i class="bi bi-shield-lock text-teal"></i> Usalama wa Akaunti</h5>
                <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($user['username'] ?? '') ?></span>
            </div>
            <div class="premium-card-body p-4">
                <form action="/index.php?route=change_password_post" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nenosiri la Sasa (Current Password) <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nenosiri Jipya (Herufi 6+) <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••••">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Thibitisha Nenosiri Jipya <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="••••••••">
                    </div>

                    <div class="pt-2 border-top d-flex justify-content-end gap-2">
                        <a href="/index.php?route=dashboard" class="btn btn-light border px-3">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-check2 me-1"></i> Hifadhi Nenosiri Jipya
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
