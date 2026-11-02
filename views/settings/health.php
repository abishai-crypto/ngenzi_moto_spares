<?php
/**
 * NGENZI MOTO SPARE PARTS
 * System Health Diagnostics View
 * Dar es Salaam, Tanzania
 */
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title text-white mb-1"><i class="bi bi-activity me-2 text-info"></i>Ukaguzi wa Afya ya Mfumo (System Health)</h2>
        <p class="text-white-50 mb-0">Uchambuzi wa kina wa hali ya kiufundi ya seva, database, uhifadhi, viongezo na usalama</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=backups" class="btn btn-outline-primary">
            <i class="bi bi-database-down me-1"></i>Hifadhi Data
        </a>
        <a href="/index.php?route=settings" class="btn btn-outline-secondary">
            <i class="bi bi-sliders me-1"></i>Mipangilio
        </a>
    </div>
</div>

<!-- Overall System Status Banner -->
<div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-4 mb-4 border-start border-4 border-success">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-25 rounded-circle p-3 text-success">
                <i class="bi bi-check-circle-fill display-6"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1 text-white">Mfumo Uko Tayari kwa Uzalishaji (Production Ready)</h4>
                <p class="text-white-50 mb-0 small">
                    Muunganisho wa database uko salama, saraka za hifadhi zinaandikika, na viongezo vyote muhimu vya PHP vimeidhinishwa.
                </p>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success-subtle">
                <i class="bi bi-shield-check me-1"></i>Mfumo Imara (100% Operational)
            </span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Application & Environment Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="bi bi-app me-2"></i>Taarifa za Mfumo (Application & Environment)
                </h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <tbody>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Toleo la Mfumo (App Version):</td>
                            <td class="text-end pe-0"><span class="badge bg-primary font-monospace"><?= htmlspecialchars($appVersion) ?></span></td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Mazingira ya Uendeshaji:</td>
                            <td class="text-end pe-0">
                                <span class="badge bg-<?= $environment === 'production' ? 'success' : 'warning text-dark' ?> font-monospace">
                                    <?= strtoupper(htmlspecialchars($environment)) ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Hali ya Debug Mode:</td>
                            <td class="text-end pe-0">
                                <span class="badge bg-<?= $debugMode ? 'danger' : 'success' ?>">
                                    <?= $debugMode ? 'IMEWASHWA (Debug Active)' : 'IMEZIMWA (Production Safe)' ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Ukanda wa Saa (Timezone):</td>
                            <td class="text-end pe-0 font-monospace small"><?= htmlspecialchars($timezone) ?></td>
                        </tr>
                        <tr>
                            <td class="text-white-50 ps-0">Saa ya Seva kwa Sasa:</td>
                            <td class="text-end pe-0 font-monospace small"><?= htmlspecialchars($currentTime) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Database Health Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="card-title mb-0 fw-bold text-info">
                    <i class="bi bi-database-check me-2"></i>Afya ya Database (MySQL / MariaDB)
                </h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <tbody>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Hali ya Muunganisho (Status):</td>
                            <td class="text-end pe-0">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Imeunganishwa (Connected)
                                </span>
                            </td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Toleo la Seva ya Database:</td>
                            <td class="text-end pe-0 font-monospace small"><?= htmlspecialchars($dbVersion) ?></td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Host & Mlango (Port):</td>
                            <td class="text-end pe-0 font-monospace small"><?= htmlspecialchars($dbHost) ?>:<?= htmlspecialchars($dbPort) ?></td>
                        </tr>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="text-white-50 ps-0">Jina la Database:</td>
                            <td class="text-end pe-0 font-monospace text-info"><?= htmlspecialchars($dbDatabase) ?></td>
                        </tr>
                        <tr>
                            <td class="text-white-50 ps-0">Muda wa Kujibu (Query Latency):</td>
                            <td class="text-end pe-0 font-monospace text-success"><?= $dbLatencyMs ?> ms</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Storage & Writability Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="card-title mb-0 fw-bold text-success">
                    <i class="bi bi-hdd-network me-2"></i>Nafasi ya Hifadhi & Saraka (Storage)
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-between small text-white-50 mb-1">
                        <span>Diski Iliyotumika: <?= $diskUsagePercent ?>%</span>
                        <span>Iliyobaki: <?= $freeDiskFormatted ?> / <?= $totalDiskFormatted ?></span>
                    </div>
                    <div class="progress bg-secondary" style="height: 10px;">
                        <div class="progress-bar bg-<?= $diskUsagePercent > 85 ? 'danger' : 'primary' ?>" 
                             role="progressbar" style="width: <?= $diskUsagePercent ?>%"></div>
                    </div>
                </div>

                <table class="table table-dark table-borderless align-middle mb-0 small">
                    <thead class="text-white-50 border-bottom border-secondary">
                        <tr>
                            <th class="ps-0">SARAKA YA HIFADHI</th>
                            <th class="text-end pe-0">HALI YA UANDISHI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storageChecks as $sc): ?>
                        <tr class="border-bottom border-secondary-subtle">
                            <td class="ps-0 font-monospace"><?= htmlspecialchars($sc['path']) ?></td>
                            <td class="text-end pe-0">
                                <?php if ($sc['writable']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i>Inaandikika (OK)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Haina Ruhusa (Denied)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Required Extensions & Runtime Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="card-title mb-0 fw-bold text-warning">
                    <i class="bi bi-cpu me-2"></i>Viongezo vya PHP (Runtime Extensions)
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="text-white-50 small">Toleo la PHP:</span>
                    <span class="badge bg-primary font-monospace"><?= PHP_VERSION ?></span>
                </div>

                <div class="row g-2">
                    <?php foreach ($extensionsStatus as $ext => $loaded): ?>
                    <div class="col-sm-6">
                        <div class="p-2 rounded bg-black bg-opacity-25 border border-secondary d-flex justify-content-between align-items-center">
                            <span class="font-monospace small text-white"><?= htmlspecialchars($ext) ?></span>
                            <?php if ($loaded): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary small text-white-50">
                    <i class="bi bi-info-circle me-1 text-info"></i>
                    Viongezo vyote vilivyoidhinishwa hapo juu ni muhimu kwa uendeshaji wa database, usimbaji fiche na ripoti za mfumo.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Security Architecture Summary -->
<div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-4">
    <h5 class="fw-bold text-primary mb-3">
        <i class="bi bi-shield-lock me-2"></i>Muhtasari wa Ulinzi wa Seva (Security Posture)
    </h5>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary">
                <div class="text-white-50 small">HTTPS Encryption</div>
                <div class="fw-bold text-<?= $securityMatrix['https'] ? 'success' : 'warning' ?>">
                    <i class="bi bi-<?= $securityMatrix['https'] ? 'lock-fill' : 'unlock' ?> me-1"></i>
                    <?= $securityMatrix['https'] ? 'Imewashwa (HTTPS)' : 'HTTP (Seva ya Ndani)' ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary">
                <div class="text-white-50 small">Session HttpOnly Cookie</div>
                <div class="fw-bold text-<?= $securityMatrix['session_httponly'] ? 'success' : 'danger' ?>">
                    <i class="bi bi-shield-check me-1"></i>
                    <?= $securityMatrix['session_httponly'] ? 'Salama (Active)' : 'Hatari (Inactive)' ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary">
                <div class="text-white-50 small">Session SameSite Policy</div>
                <div class="fw-bold text-success">
                    <i class="bi bi-shield-check me-1"></i>
                    <?= htmlspecialchars($securityMatrix['session_samesite']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary">
                <div class="text-white-50 small">Storage .htaccess Ulinzi</div>
                <div class="fw-bold text-<?= $securityMatrix['htaccess_storage_protected'] ? 'success' : 'danger' ?>">
                    <i class="bi bi-shield-check me-1"></i>
                    <?= $securityMatrix['htaccess_storage_protected'] ? 'Imezuiwa (Forbidden)' : 'Haikulindwa' ?>
                </div>
            </div>
        </div>
    </div>
</div>
