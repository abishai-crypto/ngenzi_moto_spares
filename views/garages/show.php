<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-buildings text-primary me-2"></i><?= htmlspecialchars($garage['garage_name']) ?>
            </h4>
            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($garage['garage_code']) ?></span>
            
            <?php if ($garage['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Inafanya Kazi</span>
            <?php elseif ($garage['status'] === 'inactive'): ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-pause-circle me-1"></i>Haifanyi Kazi</span>
            <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-slash-circle me-1"></i>Imezuiwa</span>
            <?php endif; ?>

            <span class="badge bg-info-subtle text-info border border-info-subtle">
                <?= htmlspecialchars($garage['garage_type'] ?? 'Motorcycle Garage') ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            Mmiliki: <strong><?= htmlspecialchars($garage['owner_name'] ?: 'Hajajazwa') ?></strong> | 
            Mahali: <strong><?= htmlspecialchars($garage['location']) ?></strong>
            <?php if (!empty($garage['area'])): ?> (<?= htmlspecialchars($garage['area']) ?>)<?php endif; ?>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=garages" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Gereji
        </a>

        <?php if ($canEdit): ?>
            <a href="/index.php?route=garage_edit&id=<?= $garage['id'] ?>" class="btn btn-primary-custom btn-sm">
                <i class="bi bi-pencil me-1"></i> Hariri Gereji
            </a>
        <?php endif; ?>

        <?php if ($canDeactivate): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear me-1"></i> Badili Hali
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <?php if ($garage['status'] !== 'active'): ?>
                        <li>
                            <form method="POST" action="/index.php?route=garage_toggle_status">
                                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                <input type="hidden" name="id" value="<?= $garage['id'] ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="dropdown-item text-success small">
                                    <i class="bi bi-check-circle me-2"></i> Washa (Activate)
                                </button>
                            </form>
                        </li>
                    <?php endif; ?>
                    <?php if ($garage['status'] !== 'inactive'): ?>
                        <li>
                            <form method="POST" action="/index.php?route=garage_toggle_status">
                                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                <input type="hidden" name="id" value="<?= $garage['id'] ?>">
                                <input type="hidden" name="status" value="inactive">
                                <button type="submit" class="dropdown-item text-secondary small">
                                    <i class="bi bi-pause-circle me-2"></i> Simamisha (Inactive)
                                </button>
                            </form>
                        </li>
                    <?php endif; ?>
                    <?php if ($garage['status'] !== 'blocked'): ?>
                        <li>
                            <form method="POST" action="/index.php?route=garage_toggle_status">
                                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                                <input type="hidden" name="id" value="<?= $garage['id'] ?>">
                                <input type="hidden" name="status" value="blocked">
                                <button type="submit" class="dropdown-item text-danger small">
                                    <i class="bi bi-slash-circle me-2"></i> Zuia (Block)
                                </button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Top Stats / Overview Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Mafundi Walioajiriwa</span>
                    <h3 class="fw-bold mb-0 text-primary mt-1"><?= count($mechanics) ?></h3>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-wrench fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Ukomo wa Mkopo</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1">TZS <?= number_format((float)($garage['credit_limit'] ?? 0), 2) ?></h3>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Akaunti ya Mteja</span>
                    <div class="mt-1">
                        <?php if (!empty($garage['customer_id'])): ?>
                            <a href="/index.php?route=customer_show&id=<?= $garage['customer_id'] ?>" class="fw-bold text-success text-decoration-none">
                                <i class="bi bi-person-check me-1"></i> Imeunganishwa (<?= htmlspecialchars($garage['customer_code'] ?? 'CUS') ?>)
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary">Haijaunganishwa</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-person-lines-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Taarifa za Mawasiliano & Mahali
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small py-2" style="width: 40%;"><i class="bi bi-person me-1"></i> Mmiliki / Msimamizi:</td>
                            <td class="fw-semibold py-2"><?= htmlspecialchars($garage['owner_name'] ?: 'Hajajazwa') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-telephone me-1"></i> Simu Kuu:</td>
                            <td class="fw-semibold py-2">
                                <a href="tel:<?= htmlspecialchars($garage['phone']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($garage['phone']) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-telephone-plus me-1"></i> Simu Mbadala:</td>
                            <td class="py-2"><?= htmlspecialchars($garage['alternative_phone'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-envelope me-1"></i> Barua Pepe:</td>
                            <td class="py-2"><?= htmlspecialchars($garage['email'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-geo-alt me-1"></i> Mahali (Mtaa):</td>
                            <td class="fw-semibold py-2"><?= htmlspecialchars($garage['location']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-map me-1"></i> Eneo (Area):</td>
                            <td class="py-2"><?= htmlspecialchars($garage['area'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-pin-map me-1"></i> Anwani:</td>
                            <td class="py-2"><?= htmlspecialchars($garage['address'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-tools me-1"></i> Utaalamu:</td>
                            <td class="py-2"><?= htmlspecialchars($garage['specialization'] ?: 'General Repair') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2"><i class="bi bi-calendar-event me-1"></i> Tarehe ya Usajili:</td>
                            <td class="text-muted small py-2"><?= date('d/m/Y H:i', strtotime($garage['created_at'])) ?></td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($garage['notes'])): ?>
                    <hr class="my-2">
                    <div class="p-2 bg-light rounded">
                        <small class="text-muted fw-semibold d-block mb-1">Maelezo ya Ziada (Notes):</small>
                        <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($garage['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Mechanics in this garage -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-wrench text-primary me-2"></i>Mafundi wa Gereji Hii (<?= count($mechanics) ?>)
                </h6>
                <a href="/index.php?route=mechanic_create" class="btn btn-outline-primary btn-sm py-1 px-2 font-monospace" style="font-size: 12px;">
                    <i class="bi bi-plus-lg me-1"></i> Fundi Mpya
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mechanics)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-wrench-adjustable display-6 text-secondary d-block mb-2"></i>
                        <p class="mb-1">Hakuna fundi aliyesajiliwa chini ya gereji hii bado.</p>
                        <a href="/index.php?route=mechanic_create" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="bi bi-plus-lg me-1"></i> Sajili Fundi
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-muted small">
                                    <th class="ps-3">Kodi</th>
                                    <th>Jina la Fundi</th>
                                    <th>Simu</th>
                                    <th>Utaalamu</th>
                                    <th>Uzoefu</th>
                                    <th>Hali</th>
                                    <th class="text-end pe-3">Kitendo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mechanics as $m): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace small fw-semibold text-primary">
                                            <?= htmlspecialchars($m['mechanic_code'] ?? 'MECH') ?>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <a href="/index.php?route=mechanic_show&id=<?= $m['id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($m['name']) ?>
                                            </a>
                                        </td>
                                        <td class="small">
                                            <?= htmlspecialchars($m['phone']) ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($m['specialization'] ?: 'General') ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= !empty($m['years_of_experience']) ? $m['years_of_experience'] . ' Miaka' : '—' ?>
                                        </td>
                                        <td>
                                            <?php if ($m['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success" style="font-size: 11px;">Active</span>
                                            <?php elseif ($m['status'] === 'inactive'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Inactive</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger" style="font-size: 11px;">Blocked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="/index.php?route=mechanic_show&id=<?= $m['id'] ?>" class="btn btn-light btn-sm py-0 px-2" title="Angalia Wasifu">
                                                <i class="bi bi-eye text-primary"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
