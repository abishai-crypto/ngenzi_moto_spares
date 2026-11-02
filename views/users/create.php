<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-person-plus-fill text-primary me-2"></i>Sajili Mfanyakazi Mpya</h4>
                <p class="text-muted small mb-0">Unda akaunti mpya ya mfanyakazi na umpatie jukumu linalostahili.</p>
            </div>
            <a href="/index.php?route=users" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
            </a>
        </div>

        <div class="premium-card">
            <div class="premium-card-header">
                <h5><i class="bi bi-shield-lock text-teal"></i> Taarifa za Akaunti</h5>
                <span class="badge bg-primary-subtle text-primary">Usajili Salama</span>
            </div>
            <div class="premium-card-body p-4">
                <form action="/index.php?route=user_store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Majina Kamili <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="Mf. Rashid Juma" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jina la Mtumiaji (Username) <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="Mf. rashid" required>
                            <small class="text-muted" style="font-size:11px;">Hili ndilo jina atakalotumia kuingia kwenye mfumo.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nambari ya Simu</label>
                            <input type="text" name="phone" class="form-control" placeholder="Mf. 0754 112 233">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Barua Pepe (Email)</label>
                            <input type="email" name="email" class="form-control" placeholder="Mf. rashid@motosparertz.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jukumu Kwenye Duka (Role) <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">-- Chagua Jukumu --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>">
                                        <?= htmlspecialchars($r['display_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Hali ya Akaunti (Status)</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Hai (Active — Anaweza kuingia)</option>
                                <option value="inactive">Imezimwa (Inactive — Hataweza kuingia)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nenosiri la Kuanzia <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="passInput" class="form-control" required minlength="6" placeholder="••••••••">
                            <small class="text-muted" style="font-size:11px;">Zisipungue herufi 6.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Thibitisha Nenosiri <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/index.php?route=users" class="btn btn-light border px-4">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-save me-1"></i> Hifadhi Mfanyakazi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
