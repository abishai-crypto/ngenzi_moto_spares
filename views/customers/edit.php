<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-pencil text-warning me-2"></i>Hariri Mteja: <?= htmlspecialchars($customer['name']) ?>
                </h4>
                <p class="text-muted small mb-0">Sasisha taarifa za mteja, nambari za simu, au ukomo wa mkopo.</p>
            </div>
            <a href="/index.php?route=customer_show&id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Wasifu
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-gear text-primary me-2"></i>Taarifa za Mteja #<?= htmlspecialchars($customer['customer_code']) ?>
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=customer_update">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">

                    <!-- Row 1: Code & Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Mteja (Customer Code)</label>
                            <input type="text" name="customer_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($customer['customer_code']) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">
                                Jina Kamili la Mteja <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($customer['name']) ?>">
                        </div>
                    </div>

                    <!-- Row 2: Phone Numbers & Email -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu ya Msingi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu ya Pili</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" 
                                       value="<?= htmlspecialchars($customer['alternative_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Barua Pepe (Email)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Customer Type, Status & Credit Limit -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">
                                Aina ya Mteja (Customer Type) <span class="text-danger">*</span>
                            </label>
                            <select name="customer_type" class="form-select" required>
                                <option value="individual" <?= ($customer['customer_type'] === 'individual' || $customer['customer_type'] === 'retail') ? 'selected' : '' ?>>Mteja wa Kawaida (Individual)</option>
                                <option value="mechanic" <?= ($customer['customer_type'] === 'mechanic') ? 'selected' : '' ?>>Fundi wa Pikipiki/Bajaji (Mechanic)</option>
                                <option value="garage" <?= ($customer['customer_type'] === 'garage') ? 'selected' : '' ?>>Gereji / Karakana (Garage)</option>
                                <option value="business" <?= ($customer['customer_type'] === 'business') ? 'selected' : '' ?>>Biashara / Kampuni (Business)</option>
                                <option value="walk_in" <?= ($customer['customer_type'] === 'walk_in') ? 'selected' : '' ?>>Mteja wa Kupita (Walk-In)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Hadhi ya Mteja (Status)</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($customer['status'] === 'active') ? 'selected' : '' ?>>Hai (Active)</option>
                                <option value="inactive" <?= ($customer['status'] === 'inactive') ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                                <option value="blocked" <?= ($customer['status'] === 'blocked') ? 'selected' : '' ?>>Imezuiwa (Blocked)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Ukomo wa Mkopo (Credit Limit TSh)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TSh</span>
                                <input type="number" step="1000" name="credit_limit" class="form-control" 
                                       value="<?= htmlspecialchars($customer['credit_limit'] ?? '0.00') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Area & Address -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Jinsia (Gender)</label>
                            <select name="gender" class="form-select">
                                <option value="" <?= empty($customer['gender']) ? 'selected' : '' ?>>-- Chagua --</option>
                                <option value="Me" <?= ($customer['gender'] === 'Me') ? 'selected' : '' ?>>Mwanaume (Male)</option>
                                <option value="Ke" <?= ($customer['gender'] === 'Ke') ? 'selected' : '' ?>>Mwanamke (Female)</option>
                                <option value="Other" <?= ($customer['gender'] === 'Other') ? 'selected' : '' ?>>Nyingine / Shirika</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Eneo / Mtaa (Area)</label>
                            <input type="text" name="area" class="form-control" 
                                   value="<?= htmlspecialchars($customer['area'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Anuani Kamili</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 5: Notes -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Notes)</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="/index.php?route=customer_show&id=<?= $customer['id'] ?>" class="btn btn-light border px-4">Ghairi</a>
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-save me-1"></i> Hifadhi Mabadiliko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
