<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 56px; height: 56px;">
            <i class="bi bi-wrench"></i>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($mechanic['name']) ?></h4>
                <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($mechanic['mechanic_code']) ?></span>
                <?php if ($mechanic['status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Hai (Active)</span>
                <?php else: ?>
                    <span class="badge bg-secondary text-white rounded-pill">Amesitishwa</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                <i class="bi bi-geo-alt me-1"></i>Eneo: <?= htmlspecialchars($mechanic['area'] ?: 'Dar es Salaam') ?>
                &bull; <i class="bi bi-gear ms-2 me-1"></i>Utaalamu: <?= htmlspecialchars($mechanic['specialization'] ?: 'Ufundi wa Jumla') ?>
            </p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canEdit): ?>
            <a href="/index.php?route=mechanic_edit&id=<?= $mechanic['id'] ?>" class="btn btn-warning btn-sm text-dark fw-semibold">
                <i class="bi bi-pencil me-1"></i> Hariri
            </a>
        <?php endif; ?>
        <?php if ($canDeactivate): ?>
            <form method="POST" action="/index.php?route=mechanic_toggle_status" onsubmit="return confirm('Badilisha hadhi ya fundi huyu?');" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="id" value="<?= $mechanic['id'] ?>">
                <input type="hidden" name="status" value="<?= $mechanic['status'] === 'active' ? 'inactive' : 'active' ?>">
                <button type="submit" class="btn btn-sm <?= $mechanic['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                    <i class="bi <?= $mechanic['status'] === 'active' ? 'bi-pause-circle' : 'bi-play-circle' ?> me-1"></i>
                    <?= $mechanic['status'] === 'active' ? 'Sitisha Fundi' : 'Wezesha Fundi' ?>
                </button>
            </form>
        <?php endif; ?>
        <a href="/index.php?route=mechanics" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Mafundi
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>Taarifa za Mawasiliano
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Simu ya Msingi:</span>
                        <a href="tel:<?= htmlspecialchars($mechanic['phone']) ?>" class="fw-semibold text-dark text-decoration-none">
                            <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($mechanic['phone']) ?>
                        </a>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Simu ya Pili:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($mechanic['alternative_phone'] ?: '--') ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Eneo Anapopatikana:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($mechanic['area'] ?: '--') ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Utaalamu:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($mechanic['specialization'] ?: 'Ufundi wa Jumla') ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Uzoefu wa Kazi:</span>
                        <span><?= $mechanic['years_of_experience'] ? $mechanic['years_of_experience'] . ' Miaka' : '--' ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Tarehe ya Usajili:</span>
                        <span><?= date('d M Y', strtotime($mechanic['created_at'])) ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <?php if (!empty($mechanic['notes'])): ?>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-chat-left-text text-primary me-2"></i>Maelezo ya Ziada
                    </h6>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded text-dark small border">
                        <?= nl2br(htmlspecialchars($mechanic['notes'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Associated Garage & Customer Profile -->
    <div class="col-lg-7">
        <!-- Associated Garage Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-building text-primary me-2"></i>Gereji / Karakana Anayofanyia Kazi
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($mechanic['garage_id'])): ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($mechanic['garage_name']) ?></h5>
                            <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($mechanic['garage_code']) ?></span>
                            <div class="text-muted small mt-2">
                                <i class="bi bi-geo-alt me-1"></i>Mahali: <?= htmlspecialchars($mechanic['garage_location']) ?>
                                <?php if (!empty($mechanic['garage_phone'])): ?>
                                    &bull; <i class="bi bi-telephone ms-2 me-1"></i><?= htmlspecialchars($mechanic['garage_phone']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <a href="/index.php?route=garage_show&id=<?= $mechanic['garage_id'] ?>" class="btn btn-outline-primary btn-sm">
                                Tazama Gereji &rarr;
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-building-slash fs-2 d-block mb-2 text-secondary"></i>
                        Fundi huyu ni fundi huru (hajahusishwa na gereji maalum).
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Linked Customer Account Card (Prompt Item 22) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-check text-success me-2"></i>Akaunti ya Mteja Iliyounganishwa (Customer Profile)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($mechanic['customer_id'])): ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mb-2">Imeunganishwa</span>
                            <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($mechanic['customer_name']) ?></h5>
                            <div class="font-monospace small text-muted">Kodi: <?= htmlspecialchars($mechanic['customer_code']) ?></div>
                            <?php if (isset($mechanic['customer_balance'])): ?>
                                <div class="small mt-1 <?= (float)$mechanic['customer_balance'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    Salio la Deni: <?= Helper::formatMoney((float)$mechanic['customer_balance']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="/index.php?route=customer_show&id=<?= $mechanic['customer_id'] ?>" class="btn btn-primary-custom btn-sm">
                                Tazama Wasifu wa Mteja &rarr;
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-3 text-muted small">
                        Fundi huyu hana akaunti ya mteja ya moja kwa moja. Unaweza kumtengenezea akaunti kutoka kwenye ukurasa wa wateja.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
