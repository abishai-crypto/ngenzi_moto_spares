<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Hariri Taarifa za Mfanyakazi</h4>
                <p class="text-muted small mb-0">Sasisha maelezo ya akaunti ya <strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= htmlspecialchars($user['username']) ?>).</p>
            </div>
            <a href="/index.php?route=users" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
            </a>
        </div>

        <div class="premium-card">
            <div class="premium-card-header">
                <h5><i class="bi bi-person-badge text-teal"></i> Maelezo ya Akaunti</h5>
                <span class="badge bg-secondary-subtle text-secondary">ID: #<?= $user['id'] ?></span>
            </div>
            <div class="premium-card-body p-4">
                <form action="/index.php?route=user_update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jina la Mtumiaji (Username)</label>
                            <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                            <small class="text-muted" style="font-size:11px;">Jina la mtumiaji haliwezi kubadilishwa baada ya usajili.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Majina Kamili <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nambari ya Simu</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Barua Pepe (Email)</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Jukumu Kwenye Duka (Role) <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['display_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Hali ya Akaunti (Status) <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Hai (Active)</option>
                                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Ili kubadilisha nenosiri, tumia kitufe cha 'Weka Nenosiri Upya' kwenye orodha.
                        </span>
                        <div class="d-flex gap-2">
                            <a href="/index.php?route=users" class="btn btn-light border px-4">Ghairi</a>
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="bi bi-check2-circle me-1"></i> Hifadhi Mabadiliko
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
