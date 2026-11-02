<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Backup Management View
 * Dar es Salaam, Tanzania
 */
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title text-white mb-1"><i class="bi bi-database-down me-2 text-primary"></i>Hifadhi & Nakala za Data (Database Backups)</h2>
        <p class="text-white-50 mb-0">Tengeneza, pakua na fanya majaribio salama ya kurejesha nakala za mfumo (Disaster Recovery)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=health" class="btn btn-outline-info">
            <i class="bi bi-activity me-1"></i>Afya ya Mfumo
        </a>
        <?php if (Auth::hasPermission('backups.create')): ?>
        <form action="/index.php?route=backup_create" method="POST" onsubmit="return confirm('Je, una uhakika unataka kutengeneza nakala mpya ya database sasa?');">
            <?= Helper::csrfField() ?>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i>Tengeneza Nakala Sasa
            </button>
        </form>
        <?php endif; ?>
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

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-3 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small">Jumla ya Nakala</div>
                    <div class="fs-4 fw-bold"><?= $totalBackups ?></div>
                </div>
                <div class="bg-primary bg-opacity-25 rounded-circle p-3 text-primary">
                    <i class="bi bi-files fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-3 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small">Nafasi ya Nakala Iliyotumika</div>
                    <div class="fs-4 fw-bold"><?= $totalSizeFormatted ?></div>
                </div>
                <div class="bg-info bg-opacity-25 rounded-circle p-3 text-info">
                    <i class="bi bi-hdd-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-3 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small">Nafasi Iliyobaki Kwenye Diski</div>
                    <div class="fs-4 fw-bold"><?= $freeSpaceFormatted ?></div>
                </div>
                <div class="bg-success bg-opacity-25 rounded-circle p-3 text-success">
                    <i class="bi bi-pie-chart-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-3 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white-50 small">Hali ya Saraka ya Hifadhi</div>
                    <div class="fs-6 fw-bold text-<?= $isWritable ? 'success' : 'danger' ?>">
                        <i class="bi bi-<?= $isWritable ? 'shield-check' : 'x-circle' ?> me-1"></i>
                        <?= $isWritable ? 'Inaandikika (Salama)' : 'Haina Ruhusa ya Kuandika' ?>
                    </div>
                </div>
                <div class="bg-warning bg-opacity-25 rounded-circle p-3 text-warning">
                    <i class="bi bi-folder-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backups Table Card -->
<div class="card border-0 shadow-sm rounded-3 bg-dark text-white mb-4">
    <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-archive me-2 text-primary"></i>Orodha ya Faili za Nakala</h5>
        <span class="badge bg-secondary"><?= count($backups) ?> files</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($backups)): ?>
            <div class="p-5 text-center text-white-50">
                <i class="bi bi-cloud-slash display-4 mb-3 d-block text-secondary"></i>
                <h5>Hakuna faili la nakala lililopatikana</h5>
                <p class="small">Bofya kitufe cha <strong>"Tengeneza Nakala Sasa"</strong> hapo juu ili kuunda nakala ya kwanza ya mfumo mzima.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="table-secondary text-white-50 small">
                        <tr>
                            <th class="ps-4">JINA LA FAILI</th>
                            <th>TAREHE & SAA</th>
                            <th>UKUBWA</th>
                            <th>HALI YA ULINZI</th>
                            <th class="text-end pe-4">VITENDO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-filetype-sql fs-4 text-primary"></i>
                                    <div>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($b['filename']) ?></div>
                                        <div class="text-white-50 small font-monospace">.sql dump</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($b['created_at']) ?></div>
                                <small class="text-white-50"><?= Helper::timeAgo($b['created_at']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark-subtle border border-secondary text-info font-monospace"><?= $b['formatted_size'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-shield-lock-fill me-1"></i>Imehifadhiwa Salama
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <?php if (Auth::hasPermission('backups.download')): ?>
                                    <a href="/index.php?route=backup_download&file=<?= urlencode($b['filename']) ?>" class="btn btn-sm btn-outline-primary" title="Pakua Faili (Download)">
                                        <i class="bi bi-download"></i> Pakua
                                    </a>
                                    <?php endif; ?>

                                    <?php if (Auth::hasPermission('backups.restore_test')): ?>
                                    <form action="/index.php?route=backup_restore_test" method="POST" class="d-inline" onsubmit="return confirm('Mfumo utafanya jaribio salama la kurejesha nakala hii kwenye database ya majaribio (ngenzi_restore_test) na kuthibitisha schema na data. Database ya uzalishaji HAITAGUSWA. Je, unataka kuendelea?');">
                                        <?= Helper::csrfField() ?>
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($b['filename']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Pima Urejesho Kwenye Database ya Majaribio">
                                            <i class="bi bi-check2-circle"></i> Pima Urejesho
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (Auth::hasPermission('backups.delete')): ?>
                                    <form action="/index.php?route=backup_delete" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kufuta faili hili la nakala? Hatua hii haiwezi kubadilishwa.');">
                                        <?= Helper::csrfField() ?>
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($b['filename']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Futa Faili">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Best Practice Disaster Recovery Notice -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-4">
            <h5 class="fw-bold text-info mb-3"><i class="bi bi-lightbulb me-2"></i>Mwongozo wa Kulinda Data za Biashara</h5>
            <ul class="text-white-50 small mb-0 ps-3" style="line-height: 1.8;">
                <li><strong>Nakala za Kila Siku:</strong> Mfumo una uwezo wa kujiendesha kiotomatiki kupitia Windows Task Scheduler au Linux Cron Job.</li>
                <li><strong>Hifadhi Nje ya Kompyuta:</strong> Pakua nakala angalau mara moja kwa wiki na kuihifadhi kwenye Flash Drive au Google Drive/Cloud kwa ajili ya usalama wa dharura.</li>
                <li><strong>Uthibitisho wa Kurejesha (Restore Testing):</strong> Kila nakala inapoundwa, tumia kitufe cha <em>"Pima Urejesho"</em> ili kuhakikisha kuwa data inaweza kurudi iwapo kompyuta itaharibika.</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white p-4">
            <h5 class="fw-bold text-success mb-3"><i class="bi bi-clock-history me-2"></i>Mpangilio wa Ratiba ya Kujiendesha (Cron / Scheduler)</h5>
            <p class="text-white-50 small">Ili mfumo ujitengenezee nakala kila siku saa 5 usiku kiotomatiki, tumia amri ifuatayo:</p>
            <div class="bg-black p-3 rounded font-monospace small text-warning border border-secondary mb-2">
                # Linux Cron:<br>
                0 23 * * * /usr/bin/php /var/www/moto_spare/app/scripts/cron_backup.php<br><br>
                # Windows Task Scheduler:<br>
                C:\xampp\php\php.exe "D:\MOTO_SPARE TRADE\app\scripts\cron_backup.php"
            </div>
            <small class="text-white-50">Amri hii inafuta nakala za zamani zaidi ya 14 kiotomatiki ili kuzuia diski kujaa.</small>
        </div>
    </div>
</div>
