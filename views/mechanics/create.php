<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-person-badge text-primary me-2"></i>Sajili Fundi Mpya
                </h4>
                <p class="text-muted small mb-0">Ingiza taarifa za fundi wa pikipiki au bajaji na gereji anayofanyia kazi.</p>
            </div>
            <a href="/index.php?route=mechanics" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-card-text text-primary me-2"></i>Fomu ya Usajili wa Fundi
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=mechanic_store">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">

                    <!-- Row 1: Code & Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Fundi (Mechanic Code)</label>
                            <input type="text" name="mechanic_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($suggestedCode) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">
                                Jina Kamili la Fundi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" required placeholder="Mf. Peter Joseph">
                        </div>
                    </div>

                    <!-- Row 2: Phone Numbers & Area -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" required placeholder="07XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Simu Mbadala</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" placeholder="Simu ya ziada">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Eneo Anapopatikana (Area)</label>
                            <input type="text" name="area" class="form-control" placeholder="Mf. Kimara, Ilala, Kariakoo...">
                        </div>
                    </div>

                    <!-- Row 3: Garage & Specialization -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Gereji / Karakana Anayofanyia Kazi</label>
                            <select name="garage_id" class="form-select">
                                <option value="">-- Fundi Huru (Bila Gereji Maalum) --</option>
                                <?php foreach ($garages as $g): ?>
                                    <option value="<?= $g['id'] ?>">
                                        <?= htmlspecialchars($g['garage_name']) ?> (<?= htmlspecialchars($g['location']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Utaalamu Mkuu (Specialization)</label>
                            <input type="text" name="specialization" class="form-control" placeholder="Mf. Boxer Engine, Bajaj Wiring, Suspension...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-muted">Uzoefu (Miaka)</label>
                            <input type="number" min="0" name="years_of_experience" class="form-control" placeholder="Mf. 5">
                        </div>
                    </div>

                    <!-- Linked Customer Option (Prompt Item 22) -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_customer_profile" value="1" id="createCustomerCheck" checked>
                            <label class="form-check-label fw-semibold text-dark small" for="createCustomerCheck">
                                <i class="bi bi-person-check text-primary me-1"></i> Fungua akaunti ya mteja (Customer Account) kwa ajili ya fundi huyu kiotomatiki
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1 ps-4" style="font-size: 11px;">
                            Hii inamwezesha fundi huyu kuwa na historia ya manunuzi ya vipuri, madeni, na kuunganishwa kwenye mauzo bila kurudia data zake mara mbili.
                        </small>
                    </div>

                    <!-- Row 4: Notes -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Notes)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Maelezo ya fundi huyu..."></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="/index.php?route=mechanics" class="btn btn-light border px-4">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-check-lg me-1"></i> Hifadhi Fundi Mpya
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
