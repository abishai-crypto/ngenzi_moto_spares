<?php
use App\Core\Helper;
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-person-plus text-primary me-2"></i>Sajili Mteja Mpya
                </h4>
                <p class="text-muted small mb-0">Ingiza taarifa kamili za mteja, mawasiliano, aina yake, na maelezo mengine.</p>
            </div>
            <a href="/index.php?route=customers" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-card-text text-primary me-2"></i>Fomu ya Usajili wa Mteja
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?route=customer_store">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">

                    <!-- Row 1: Code & Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Kodi ya Mteja (Customer Code)</label>
                            <input type="text" name="customer_code" class="form-control font-monospace bg-light" 
                                   value="<?= htmlspecialchars($suggestedCode) ?>" readonly>
                            <small class="text-muted" style="font-size: 11px;">Inazalishwa kiotomatiki na mfumo.</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">
                                Jina Kamili la Mteja / Kampuni <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Mf. John Peter, Golani Moto Workshop, n.k.">
                        </div>
                    </div>

                    <!-- Row 2: Phone Numbers & Email -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu ya Msingi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX au 06XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Nambari ya Simu ya Pili (Alternative)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone-plus text-muted"></i></span>
                                <input type="text" name="alternative_phone" class="form-control" placeholder="Simu mbadala">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Barua Pepe (Email)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="jina@mfano.com">
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Customer Type, Gender & Credit Limit -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">
                                Aina ya Mteja (Customer Type) <span class="text-danger">*</span>
                            </label>
                            <select name="customer_type" class="form-select" required>
                                <option value="individual" selected>Mteja wa Kawaida (Individual)</option>
                                <option value="mechanic">Fundi wa Pikipiki / Bajaji (Mechanic)</option>
                                <option value="garage">Gereji / Karakana (Garage)</option>
                                <option value="business">Biashara / Kampuni (Business)</option>
                                <option value="walk_in">Mteja wa Kupita (Walk-In)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Jinsia (Gender)</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Chagua (Si Lazima) --</option>
                                <option value="Me">Mwanaume (Male)</option>
                                <option value="Ke">Mwanamke (Female)</option>
                                <option value="Other">Nyingine / Shirika</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Ukomo wa Mkopo (Credit Limit TSh)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TSh</span>
                                <input type="number" step="1000" name="credit_limit" class="form-control" value="0.00">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Kiwango cha juu cha kukopesha mteja.</small>
                        </div>
                    </div>

                    <!-- Row 4: Area & Address -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Eneo / Mtaa (Area)</label>
                            <input type="text" name="area" class="form-control" placeholder="Mf. Kariakoo, Kimara Korogwe, Mwenge, n.k.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Anuani Kamili (Full Physical Address)</label>
                            <input type="text" name="address" class="form-control" placeholder="Mf. Mtaa wa Msimbazi, Plot 14, Dar es Salaam">
                        </div>
                    </div>

                    <!-- Row 5: Notes -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ziada (Customer Notes)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Mf. 'Huwa ananunua vipuri vya Boxer BM 150', 'Anapendelea bidhaa orijino pekee', n.k."></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="/index.php?route=customers" class="btn btn-light border px-4">Ghairi</a>
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-check-lg me-1"></i> Hifadhi Mteja Mpya
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
