<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Hariri Gereji: <?= htmlspecialchars($garage['garage_name']) ?>
                </h4>
                <p class="text-muted small mb-0">Sasisha taarifa za mawasiliano, mmiliki, na makubaliano ya gereji.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/index.php?route=garage_show&id=<?= $garage['id'] ?>" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-eye me-1"></i> Angalia Wasifu
                </a>
                <a href="/index.php?route=garages" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Rudi
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-card-text text-primary me-2"></i>Taarifa za Gereji
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=garage_update">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $garage['id'] ?>">

                    <!-- Row 1: Code, Name & Owner -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Gereji (Garage Code)</label>
                            <input type="text" name="garage_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($garage['garage_code'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">
                                Jina la Gereji / Karakana <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="garage_name" class="form-control" required 
                                   value="<?= htmlspecialchars($garage['garage_name']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Jina la Mmiliki / Msimamizi</label>
                            <input type="text" name="owner_name" class="form-control" 
                                   value="<?= htmlspecialchars($garage['owner_name'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 2: Phones & Email -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu Kuu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" required 
                                       value="<?= htmlspecialchars($garage['phone']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Simu ya Ziada</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" 
                                       value="<?= htmlspecialchars($garage['alternative_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Barua Pepe (Email)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($garage['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Location & Area & Address -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Mahali / Mtaa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt text-muted"></i></span>
                                <input type="text" name="location" class="form-control" required 
                                       value="<?= htmlspecialchars($garage['location']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Eneo / Wilaya (Area)</label>
                            <input type="text" name="area" class="form-control" 
                                   value="<?= htmlspecialchars($garage['area'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Anwani Kamili (Physical Address)</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?= htmlspecialchars($garage['address'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 4: Garage Type & Specialization & Credit Limit -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Aina ya Karakana</label>
                            <select name="garage_type" class="form-select">
                                <option value="Motorcycle Garage" <?= ($garage['garage_type'] === 'Motorcycle Garage') ? 'selected' : '' ?>>Gereji ya Pikipiki (Motorcycle)</option>
                                <option value="Bajaj Garage" <?= ($garage['garage_type'] === 'Bajaj Garage') ? 'selected' : '' ?>>Gereji ya Bajaji (Bajaj/Tricycle)</option>
                                <option value="Mixed Workshop" <?= ($garage['garage_type'] === 'Mixed Workshop') ? 'selected' : '' ?>>Karakana ya Pamoja (Mixed)</option>
                                <option value="Electrical Workshop" <?= ($garage['garage_type'] === 'Electrical Workshop') ? 'selected' : '' ?>>Karakana ya Umeme (Auto-Electrical)</option>
                                <option value="Engine Workshop" <?= ($garage['garage_type'] === 'Engine Workshop') ? 'selected' : '' ?>>Karakana ya Injini (Engine Overhaul)</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Utaalamu / Huduma Maalum</label>
                            <input type="text" name="specialization" class="form-control" 
                                   value="<?= htmlspecialchars($garage['specialization'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Kiwango cha Mkopo (TZS)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light font-monospace small">TZS</span>
                                <input type="number" step="1000" min="0" name="credit_limit" class="form-control" 
                                       value="<?= htmlspecialchars($garage['credit_limit'] ?? 0) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Hali ya Gereji (Status)</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($garage['status'] === 'active') ? 'selected' : '' ?>>Inafanya Kazi (Active)</option>
                                <option value="inactive" <?= ($garage['status'] === 'inactive') ? 'selected' : '' ?>>Haifanyi Kazi (Inactive)</option>
                                <option value="blocked" <?= ($garage['status'] === 'blocked') ? 'selected' : '' ?>>Imezuiwa (Blocked)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Notes)</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($garage['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/index.php?route=garages" class="btn btn-outline-secondary px-4">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-check-lg me-1"></i> Hifadhi Mabadiliko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
