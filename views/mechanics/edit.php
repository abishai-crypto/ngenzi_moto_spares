<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-pencil text-warning me-2"></i>Hariri Fundi: <?= htmlspecialchars($mechanic['name']) ?>
                </h4>
                <p class="text-muted small mb-0">Sasisha taarifa za mawasiliano, gereji, au utaalamu wa fundi.</p>
            </div>
            <a href="/index.php?route=mechanic_show&id=<?= $mechanic['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Wasifu
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-badge text-primary me-2"></i>Taarifa za Fundi #<?= htmlspecialchars($mechanic['mechanic_code']) ?>
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=mechanic_update">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $mechanic['id'] ?>">

                    <!-- Row 1: Code & Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Fundi (Mechanic Code)</label>
                            <input type="text" name="mechanic_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($mechanic['mechanic_code']) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">
                                Jina Kamili la Fundi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($mechanic['name']) ?>">
                        </div>
                    </div>

                    <!-- Row 2: Phone Numbers & Area -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" required 
                                       value="<?= htmlspecialchars($mechanic['phone']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Simu Mbadala</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" 
                                       value="<?= htmlspecialchars($mechanic['alternative_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Eneo Anapopatikana (Area)</label>
                            <input type="text" name="area" class="form-control" 
                                   value="<?= htmlspecialchars($mechanic['area'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 3: Garage, Specialization & Experience -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Gereji Anayofanyia Kazi</label>
                            <select name="garage_id" class="form-select">
                                <option value="">-- Fundi Huru (Bila Gereji) --</option>
                                <?php foreach ($garages as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($mechanic['garage_id'] == $g['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['garage_name']) ?> (<?= htmlspecialchars($g['location']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Utaalamu Mkuu</label>
                            <input type="text" name="specialization" class="form-control" 
                                   value="<?= htmlspecialchars($mechanic['specialization'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Uzoefu (Miaka)</label>
                            <input type="number" min="0" name="years_of_experience" class="form-control" 
                                   value="<?= htmlspecialchars($mechanic['years_of_experience'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 4: Status & Notes -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Hadhi ya Fundi (Status)</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($mechanic['status'] === 'active') ? 'selected' : '' ?>>Hai (Active)</option>
                                <option value="inactive" <?= ($mechanic['status'] === 'inactive') ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                                <option value="blocked" <?= ($mechanic['status'] === 'blocked') ? 'selected' : '' ?>>Imezuiwa (Blocked)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Notes)</label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($mechanic['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="/index.php?route=mechanic_show&id=<?= $mechanic['id'] ?>" class="btn btn-light border px-4">Ghairi</a>
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-save me-1"></i> Hifadhi Mabadiliko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
