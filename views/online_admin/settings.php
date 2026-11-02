<div class="content-wrapper p-4" style="max-width: 960px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-sliders text-primary me-2"></i>Mipangilio ya Duka la Mtandaoni & Usafirishaji</h4>
            <span class="text-muted small">Sanidi sera za usafirishaji (Bolt, Store, Pickup), malipo ya simu, na mwonekano wa katalogi</span>
        </div>
    </div>

    <form action="/index.php?route=online_settings" method="POST">
        <!-- Section 1: Store Basics & Stock Masking -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shop text-primary"></i> 1. Upatikanaji wa Duka & Faragha ya Stoo</h6>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Upatikanaji wa Duka la Mtandaoni</label>
                    <select name="online_store_enabled" class="form-select form-select-sm">
                        <option value="1" <?= ($settings['online_store_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Limefunguliwa (Active Online)</option>
                        <option value="0" <?= ($settings['online_store_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Limefungwa kwa Muda (Maintenance Mode)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kuonyesha Idadi Halisi ya Vipuri Stoo kwa Wateja</label>
                    <select name="catalog_display_exact_stock" class="form-select form-select-sm">
                        <option value="0" <?= ($settings['catalog_display_exact_stock'] ?? '0') === '0' ? 'selected' : '' ?>>Ficha Idadi Kamili (Onyesha 'Vipo Stoo' / 'Vichache')</option>
                        <option value="1" <?= ($settings['catalog_display_exact_stock'] ?? '0') === '1' ? 'selected' : '' ?>>Onyesha Idadi Kamili ya Stoo kwa Umma</option>
                    </select>
                    <small class="text-muted" style="font-size: 11px;">Kulinda usiri wa idadi ya ndani ya maghala ya Ngenzi.</small>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Kichwa cha Duka (SEO Title)</label>
                    <input type="text" name="online_store_title" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['online_store_title'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Maelezo Mafupi (Meta Description)</label>
                    <textarea name="online_store_description" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($settings['online_store_description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Simu Rasmi ya Huduma kwa Wateja</label>
                    <input type="text" name="customer_support_phone" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['customer_support_phone'] ?? '+255 754 123 456') ?>">
                </div>
            </div>
        </div>

        <!-- Section 2: Flexible Delivery Providers (Phase 17) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-truck text-warning"></i> 2. Njia za Usafirishaji (Delivery Providers)</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-light h-100">
                        <label class="form-label small fw-bold text-dark">Store Delivery (Ngenzi Fleet)</label>
                        <select name="delivery_store_enabled" class="form-select form-select-sm">
                            <option value="1" <?= ($settings['delivery_store_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Imewashwa (Active)</option>
                            <option value="0" <?= ($settings['delivery_store_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Imezimwa (Disabled)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Usafirishaji unaopangwa na duka.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-light h-100">
                        <label class="form-label small fw-bold text-dark">Bolt Express Delivery</label>
                        <select name="delivery_bolt_enabled" class="form-select form-select-sm">
                            <option value="1" <?= ($settings['delivery_bolt_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Imewashwa (Active)</option>
                            <option value="0" <?= ($settings['delivery_bolt_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Imezimwa (Disabled)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Usafirishaji wa haraka kupitia Bolt.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-light h-100">
                        <label class="form-label small fw-bold text-dark">Customer Pickup (Kuchukua Dukani)</label>
                        <select name="delivery_pickup_enabled" class="form-select form-select-sm">
                            <option value="1" <?= ($settings['delivery_pickup_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Imewashwa (Active)</option>
                            <option value="0" <?= ($settings['delivery_pickup_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Imezimwa (Disabled)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Mteja kujichukulia dukani Kariakoo (BURE).</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light h-100">
                        <label class="form-label small fw-bold text-dark">My Own Courier (Msafirishaji wa Mteja)</label>
                        <select name="delivery_own_courier_enabled" class="form-select form-select-sm">
                            <option value="1" <?= ($settings['delivery_own_courier_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Imewashwa (Active)</option>
                            <option value="0" <?= ($settings['delivery_own_courier_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Imezimwa (Disabled)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Mteja kutuma msafirishaji/bodaboda wake.</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light h-100">
                        <label class="form-label small fw-bold text-dark">Other Courier (Wasafirishaji Wengine / Mabasi)</label>
                        <select name="delivery_other_courier_enabled" class="form-select form-select-sm">
                            <option value="1" <?= ($settings['delivery_other_courier_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Imewashwa (Active)</option>
                            <option value="0" <?= ($settings['delivery_other_courier_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Imezimwa (Disabled)</option>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Kutuma kwa mabasi ya mikoani au huduma za vifurushi.</small>
                    </div>
                </div>
            </div>

            <!-- Delivery Pricing -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Gharama ya Kawaida ya Usafirishaji (TSh)</label>
                    <input type="number" step="100" name="delivery_flat_rate" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['delivery_flat_rate'] ?? '5000.00') ?>">
                    <small class="text-muted" style="font-size: 11px;">Gharama ya kupeleka mzigo ndani ya Dar es Salaam.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kiwango cha Usafirishaji wa Bure (TSh)</label>
                    <input type="number" step="1000" name="free_delivery_threshold" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['free_delivery_threshold'] ?? '100000.00') ?>">
                    <small class="text-muted" style="font-size: 11px;">Mteja akinunua kuanzia kiwango hiki, halipii usafirishaji.</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Maelezo ya Kujichukulia Mzigo Dukani (Pickup Instructions)</label>
                <textarea name="store_pickup_instructions" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($settings['store_pickup_instructions'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Maelezo ya Usafirishaji kwa Wateja (Delivery Notes)</label>
                <textarea name="delivery_notes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($settings['delivery_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Section 3: Bolt API Integration Settings -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> 3. Sanidi API ya Bolt (Hiari)</h6>
            <p class="text-muted small mb-3">
                Ikiwa API ya Bolt haijasanidiwa, mfumo utatumia mfumo salama wa kawaida (Manual Dispatch Fallback) ambapo wateja wanaweza kuchagua Bolt na wafanyakazi wa Ngenzi wanapanga dereva kupitia programu ya Bolt.
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Hali ya Muunganisho wa Bolt API</label>
                    <select name="bolt_api_configured" class="form-select form-select-sm">
                        <option value="0" <?= ($settings['bolt_api_configured'] ?? '0') === '0' ? 'selected' : '' ?>>Haijaunganishwa (Manual Fallback)</option>
                        <option value="1" <?= ($settings['bolt_api_configured'] ?? '0') === '1' ? 'selected' : '' ?>>Imeunganishwa Moja kwa Moja (Live API)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bolt API Key</label>
                    <input type="password" name="bolt_api_key" class="form-control form-control-sm font-monospace" placeholder="Ufunguo wa siri wa API" value="<?= htmlspecialchars($settings['bolt_api_key'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bolt API URL</label>
                    <input type="text" name="bolt_api_url" class="form-control form-control-sm font-monospace" placeholder="https://..." value="<?= htmlspecialchars($settings['bolt_api_url'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Section 4: Mobile Money & Payment Methods -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 text-success"></i> 4. Namba za Malipo ya Simu & Benki</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Lipa Namba ya M-Pesa</label>
                    <input type="text" name="lipa_namba_mpesa" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['lipa_namba_mpesa'] ?? '5522114') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Lipa Namba ya Tigo Pesa</label>
                    <input type="text" name="lipa_namba_tigo" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['lipa_namba_tigo'] ?? '7744112') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Lipa Namba ya Airtel Money</label>
                    <input type="text" name="lipa_namba_airtel" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['lipa_namba_airtel'] ?? '8899221') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Maelekezo ya Benki</label>
                <textarea name="bank_transfer_instructions" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($settings['bank_transfer_instructions'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Muda wa Mwisho wa Kuomba Marejesho (Siku)</label>
                <input type="number" name="online_return_policy_days" class="form-control form-control-sm" style="max-width: 150px;" value="<?= htmlspecialchars($settings['online_return_policy_days'] ?? '3') ?>">
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                <i class="bi bi-save me-1"></i> Hifadhi Mipangilio Yote
            </button>
        </div>
    </form>
</div>
