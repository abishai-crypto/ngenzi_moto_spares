<?php
/**
 * NGENZI MOTO SPARE PARTS
 * System Settings & Business Profile View
 * Dar es Salaam, Tanzania
 */
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title text-white mb-1"><i class="bi bi-sliders me-2 text-primary"></i>Mipangilio ya Mfumo & Wasifu wa Biashara</h2>
        <p class="text-white-50 mb-0">Sanidi taarifa za duka, risiti, kodi, sarafu na mipaka ya faida ya biashara</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=health" class="btn btn-outline-info">
            <i class="bi bi-activity me-1"></i>Afya ya Mfumo
        </a>
        <a href="/index.php?route=backups" class="btn btn-outline-primary">
            <i class="bi bi-database-down me-1"></i>Hifadhi Data
        </a>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form action="/index.php?route=settings_update" method="POST">
    <?= Helper::csrfField() ?>

    <div class="row g-4">
        <!-- Col 1: Business Profile -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 bg-dark text-white mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-shop me-2"></i>Taarifa za Duka & Biashara (Shop Profile)
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Jina Rasmi la Biashara</label>
                        <input type="text" name="business_name" class="form-control bg-dark text-white border-secondary" 
                               value="<?= htmlspecialchars($settings['business_name'] ?? 'NGENZI MOTO SPARE PARTS') ?>" 
                               <?= !$canEdit ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Namba ya Simu ya Duka</label>
                            <input type="text" name="business_phone" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['business_phone'] ?? '+255 700 000 000') ?>" 
                                   <?= !$canEdit ? 'readonly' : 'required' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Barua Pepe (Email)</label>
                            <input type="email" name="business_email" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['business_email'] ?? 'info@ngenzispareparts.co.tz') ?>" 
                                   <?= !$canEdit ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Anuani ya Mahali Duka Lilipo</label>
                        <input type="text" name="business_address" class="form-control bg-dark text-white border-secondary" 
                               value="<?= htmlspecialchars($settings['business_address'] ?? 'Mtaa wa Livingstone & Aggrey, Kariakoo, Dar es Salaam') ?>" 
                               <?= !$canEdit ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Namba ya Utambulisho wa Mlipa Kodi (TIN)</label>
                            <input type="text" name="tax_number" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['tax_number'] ?? '123-456-789') ?>" 
                                   <?= !$canEdit ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Kiambishi Awali cha Ankara (Invoice Prefix)</label>
                            <input type="text" name="invoice_prefix" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'NGZ-') ?>" 
                                   <?= !$canEdit ? 'readonly' : 'required' ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Ujumbe wa Chini kwenye Risiti ya Mauzo (Receipt Footer)</label>
                        <textarea name="receipt_footer" class="form-control bg-dark text-white border-secondary" rows="2" <?= !$canEdit ? 'readonly' : '' ?>><?= htmlspecialchars($settings['receipt_footer'] ?? 'Asante kwa kufanya biashara na Ngenzi Moto Spare Parts! Karibu tena.') ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-white-50 small fw-bold">Ujumbe wa Chini kwenye Ankara / Madeni (Invoice Footer)</label>
                        <textarea name="invoice_footer" class="form-control bg-dark text-white border-secondary" rows="2" <?= !$canEdit ? 'readonly' : '' ?>><?= htmlspecialchars($settings['invoice_footer'] ?? 'Bidhaa zikishanunuliwa haziwezi kurudishwa isipokuwa kwa masharti maalum ndani ya siku 3.') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Col 2: Financial & Operational Policies -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 bg-dark text-white mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h5 class="card-title mb-0 fw-bold text-success">
                        <i class="bi bi-currency-exchange me-2"></i>Sarafu & Vigezo vya Fedha
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Sarafu Rasmi ya Nchi</label>
                        <input type="text" name="default_currency" class="form-control bg-dark text-white border-secondary" 
                               value="<?= htmlspecialchars($settings['default_currency'] ?? 'TZS') ?>" readonly>
                        <div class="form-text text-white-50 small">Biashara inafanyika nchini Tanzania (Shilingi ya Tanzania).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Alama ya Kuonyesha Kwenye Ripoti & Risiti</label>
                        <input type="text" name="currency_symbol" class="form-control bg-dark text-white border-secondary" 
                               value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'TSh') ?>" 
                               <?= !$canEdit ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Kiwango Lengwa cha Faida Ghafi (Target Margin %)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="target_margin" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['target_margin'] ?? '30.00') ?>" 
                                   <?= !$canEdit ? 'readonly' : 'required' ?>>
                            <span class="input-group-text bg-secondary text-white">%</span>
                        </div>
                        <div class="form-text text-white-50 small">Kiwango cha kawaida cha faida ya vipuri (Chaguo-msingi: 30%).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Tahadhari ya Kiwango cha Chini cha Stoo</label>
                        <div class="input-group">
                            <input type="number" min="1" name="low_stock_threshold" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['low_stock_threshold'] ?? '5') ?>" 
                                   <?= !$canEdit ? 'readonly' : 'required' ?>>
                            <span class="input-group-text bg-secondary text-white">Vipande (Pcs)</span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-white-50 small fw-bold">Kipindi cha Bidhaa Isiyotembea (Dead Stock)</label>
                        <div class="input-group">
                            <input type="number" min="15" name="dead_stock_days" class="form-control bg-dark text-white border-secondary" 
                                   value="<?= htmlspecialchars($settings['dead_stock_days'] ?? '60') ?>" 
                                   <?= !$canEdit ? 'readonly' : 'required' ?>>
                            <span class="input-group-text bg-secondary text-white">Siku</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information Summary Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-4">
                <h6 class="fw-bold text-info mb-3"><i class="bi bi-info-circle me-2"></i>Taarifa za Usanidi wa Mfumo</h6>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary small">
                    <span class="text-white-50">Toleo la Mfumo:</span>
                    <span class="badge bg-primary font-monospace"><?= htmlspecialchars($settings['app_version'] ?? 'v1.0.0') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary small">
                    <span class="text-white-50">Mazingira (Environment):</span>
                    <span class="badge bg-success font-monospace"><?= htmlspecialchars($settings['system_environment'] ?? 'production') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 small">
                    <span class="text-white-50">Ukanda wa Saa (Timezone):</span>
                    <span class="text-white font-monospace"><?= htmlspecialchars($settings['app_timezone'] ?? 'Africa/Dar_es_Salaam') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canEdit): ?>
    <div class="d-flex justify-content-end gap-2 mt-3 mb-5">
        <a href="/index.php?route=dashboard" class="btn btn-outline-secondary px-4">Ghairi</a>
        <button type="submit" class="btn btn-primary px-5 fw-bold">
            <i class="bi bi-save me-1"></i> Hifadhi Mabadiliko
        </button>
    </div>
    <?php endif; ?>
</form>
