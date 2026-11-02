<?php
use App\Core\Auth;
$user = Auth::user();
?>
<div class="main-wrapper">
    <header class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="d-none d-md-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Dar es Salaam, TZ
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                    <i class="bi bi-graph-up-arrow me-1"></i> Lengo la Faida: 30% Gross Margin
                </span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Port indicator -->
            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1.5 d-none d-sm-inline-block">
                <i class="bi bi-database me-1"></i> DB: Port 3307
            </span>

            <!-- System Language Switcher (Phase 18) -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-1 rounded-pill py-1.5 px-3 border shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= __('lang_switch') ?>">
                    <i class="bi bi-translate text-primary"></i>
                    <span class="fw-bold small"><?= \App\Core\Lang::getLocale() === 'en' ? '🇬🇧 English' : '🇹🇿 Kiswahili' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                    <li><h6 class="dropdown-header text-muted py-1 small"><?= __('lang_switch') ?></h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 small <?= \App\Core\Lang::getLocale() === 'sw' ? 'active fw-bold' : '' ?>" href="/index.php?route=switch_language&lang=sw">
                            <span>🇹🇿 Kiswahili</span>
                            <?php if (\App\Core\Lang::getLocale() === 'sw'): ?><i class="bi bi-check-lg text-success"></i><?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 small <?= \App\Core\Lang::getLocale() === 'en' ? 'active fw-bold' : '' ?>" href="/index.php?route=switch_language&lang=en">
                            <span>🇬🇧 English</span>
                            <?php if (\App\Core\Lang::getLocale() === 'en'): ?><i class="bi bi-check-lg text-success"></i><?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 rounded-pill py-1.5 px-3 border" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-badge" style="width:28px; height:28px; font-size:12px;">
                        <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="fw-semibold small d-none d-md-inline"><?= htmlspecialchars($user['full_name'] ?? 'Mtumiaji') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold small"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($user['email'] ?? $user['username']) ?></div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill mt-1" style="font-size:10px;">
                            <?= htmlspecialchars($user['role_display'] ?? '') ?>
                        </span>
                    </li>
                    <li><a class="dropdown-item py-2 small" href="/index.php?route=change_password"><i class="bi bi-key me-2"></i> Badili Nenosiri</a></li>
                    <?php if (Auth::hasRole(['super_admin'])): ?>
                    <li><a class="dropdown-item py-2 small" href="/index.php?route=settings"><i class="bi bi-gear me-2"></i> Mipangilio</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item py-2 small text-danger" href="/index.php?route=logout"><i class="bi bi-box-arrow-right me-2"></i> Toka (Logout)</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="content-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill' ?> fs-5 me-2"></i>
                    <div><?= $flash['message'] ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
