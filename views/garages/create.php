<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-buildings text-primary me-2"></i>Sajili Gereji / Karakana Mpya
                </h4>
                <p class="text-muted small mb-0">Weka taarifa za gereji ya pikipiki au bajaji, mmiliki, na makubaliano ya biashara.</p>
            </div>
            <a href="/index.php?route=garages" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-card-text text-primary me-2"></i>Fomu ya Usajili wa Gereji
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=garage_store">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">

                    <!-- Row 1: Code & Name & Owner -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Gereji (Code)</label>
                            <input type="text" name="garage_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($suggestedCode) ?>" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">
                                Jina la Gereji / Karakana <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="garage_name" class="form-control" required placeholder="Mf. Golani Auto Garage">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Jina la Mmiliki / Msimamizi</label>
                            <input type="text" name="owner_name" class="form-control" placeholder="Mf. Rashid Ally">
                        </div>
                    </div>

                    <!-- Row 2: Phones & Email -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu Kuu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" required placeholder="07XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Simu ya Ziada</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" placeholder="06XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Barua Pepe (Email)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="garage@example.com">
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Location & Area & Address -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Mahali / Mtaa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt text-muted"></i></span>
                                <input type="text" name="location" class="form-control" required placeholder="Mf. Kariakoo, Msimbazi">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Eneo / Wilaya (Area)</label>
                            <input type="text" name="area" class="form-control" placeholder="Mf. Ilala, Kinondoni, Temeke...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Anwani Kamili (Physical Address)</label>
                            <input type="text" name="address" class="form-control" placeholder="Mf. Plot 45, Karume Rd">
                        </div>
                    </div>

                    <!-- Row 4: Garage Type & Specialization & Credit Limit -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Aina ya Karakana (Garage Type)</label>
                            <select name="garage_type" class="form-select">
                                <option value="Motorcycle Garage">Gereji ya Pikipiki (Motorcycle)</option>
                                <option value="Bajaj Garage">Gereji ya Bajaji (Bajaj/Tricycle)</option>
                                <option value="Mixed Workshop" selected>Karakana ya Pamoja (Mixed)</option>
                                <option value="Electrical Workshop">Karakana ya Umeme (Auto-Electrical)</option>
                                <option value="Engine Workshop">Karakana ya Injini (Engine Overhaul)</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Utaalamu / Huduma Maalum (Specialization)</label>
                            <input type="text" name="specialization" class="form-control" placeholder="Mf. Bajaj RE 4S, TVS King, Boxer 150...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Kiwango cha Mkopo (Credit Limit - TZS)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light font-monospace small">TZS</span>
                                <input type="number" step="1000" min="0" name="credit_limit" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Notes)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Taarifa zozote za ziada kuhusu gereji hii au utaratibu wa malipo..."></textarea>
                    </div>

                    <!-- Linked Customer Option -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_customer_profile" value="1" id="createCustomerCheck" checked>
                            <label class="form-check-label fw-semibold text-dark small" for="createCustomerCheck">
                                <i class="bi bi-person-check text-primary me-1"></i> Fungua akaunti ya mteja (Customer Account) kwa ajili ya gereji hii kiotomatiki
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1 ps-4" style="font-size: 11px;">
                            Hii inawezesha gereji kuandikishwa ankara za mauzo, ankara za madeni (credit sales), na kuunganisha mafundi wake kwenye manunuzi ya vipuri.
                        </small>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/index.php?route=garages" class="btn btn-outline-secondary px-4">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-check-lg me-1"></i> Sajili Gereji
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
