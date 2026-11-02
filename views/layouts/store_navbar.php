<?php
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Models\CustomerNotification;

$cartSummary = Cart::getSummary();
$isCustomer = Auth::isCustomer();
$customer = Auth::customer();
$wishlistCount = $isCustomer ? Wishlist::count(Auth::customerId()) : 0;
$unreadNotifCount = $isCustomer ? CustomerNotification::countUnread(Auth::customerId()) : 0;
?>

<!-- Top Notification Bar -->
<div class="bg-dark text-white py-1 px-3 border-bottom border-secondary small d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-geo-alt-fill text-warning"></i> Kariakoo, Dar es Salaam</span>
            <span><i class="bi bi-telephone-fill text-warning"></i> +255 754 123 456</span>
            <span><i class="bi bi-clock text-warning"></i> Jtatu - Jmosi: 08:00 - 18:00</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if (Auth::check()): ?>
                <a href="/index.php?route=dashboard" class="text-warning text-decoration-none fw-semibold">
                    <i class="bi bi-speedometer2"></i> Mfumo wa Utawala (Admin)
                </a>
            <?php else: ?>
                <a href="/index.php?route=login" class="text-white-50 text-decoration-none">Wafanyakazi</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Store Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark store-navbar sticky-top py-2">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php?route=store">
            <div class="brand-icon rounded-3 p-2 bg-gradient d-flex align-items-center justify-content-center text-white" style="background: linear-gradient(135deg, #0f766e, #f59e0b); width: 40px; height: 40px;">
                <i class="bi bi-gear-fill fs-5"></i>
            </div>
            <div>
                <span class="fw-bold tracking-wide text-white d-block lh-1 fs-5">NGENZI MOTO</span>
                <span class="store-brand-badge">SPARE PARTS • KARIAKOO</span>
            </div>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#storeNavbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="storeNavbarContent">
            <!-- Search Bar in Navbar -->
            <form action="/index.php" method="GET" class="d-flex my-2 my-lg-0 mx-lg-4 flex-grow-1" style="max-width: 480px;">
                <input type="hidden" name="route" value="shop">
                <div class="input-group">
                    <input type="search" name="search" class="form-control border-0 rounded-start-pill ps-3" 
                           placeholder="Tafuta kipuri, SKU, au jina la chombo..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn btn-store-accent rounded-end-pill px-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-white <?= ($_GET['route'] ?? 'store') === 'store' ? 'fw-bold active' : '' ?>" href="/index.php?route=store">
                        <i class="bi bi-house-door"></i> <?= __('nav_home') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?= ($_GET['route'] ?? '') === 'shop' ? 'fw-bold active' : '' ?>" href="/index.php?route=shop">
                        <i class="bi bi-grid"></i> <?= __('nav_shop') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?= ($_GET['route'] ?? '') === 'store_vehicle' ? 'fw-bold active' : '' ?>" href="/index.php?route=store_vehicle">
                        <i class="bi bi-bicycle"></i> <?= __('nav_my_vehicle') ?>
                    </a>
                </li>
            </ul>

            <!-- Right Actions (Language Switcher, Wishlist, Cart, User Menu) -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <!-- System Language Switcher (Phase 18) -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-2.5 py-1 d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= __('lang_switch') ?>">
                        <i class="bi bi-translate text-warning"></i>
                        <span class="fw-bold small"><?= \App\Core\Lang::getLocale() === 'en' ? '🇬🇧 EN' : '🇹🇿 SW' ?></span>
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

                <!-- Wishlist -->
                <a href="/index.php?route=customer_wishlist" class="btn btn-outline-light btn-sm position-relative rounded-pill px-2.5 py-1" title="<?= __('nav_wishlist') ?>">
                    <i class="bi bi-heart"></i>
                    <?php if ($wishlistCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                            <?= $wishlistCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Cart -->
                <a href="/index.php?route=cart" class="btn btn-store-accent btn-sm position-relative rounded-pill px-3 py-1 d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-cart3 fs-6"></i>
                    <span class="d-none d-sm-inline fw-semibold"><?= __('nav_cart') ?></span>
                    <span class="badge rounded-pill bg-dark text-white ms-1" id="navCartCount">
                        <?= $cartSummary['total_quantity'] ?>
                    </span>
                </a>

                <!-- Customer Account Dropdown or Auth Buttons -->
                <?php if ($isCustomer): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3 py-1 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-6 text-warning"></i>
                            <span class="text-truncate" style="max-width: 120px;"><?= htmlspecialchars($customer['name']) ?></span>
                            <?php if ($unreadNotifCount > 0): ?>
                                <span class="badge rounded-pill bg-warning text-dark"><?= $unreadNotifCount ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><h6 class="dropdown-header text-muted">Akaunti ya Mteja</h6></li>
                            <li><a class="dropdown-item" href="/index.php?route=customer_dashboard"><i class="bi bi-speedometer2 me-2"></i><?= __('nav_dashboard') ?></a></li>
                            <li><a class="dropdown-item" href="/index.php?route=customer_orders"><i class="bi bi-box-seam me-2"></i><?= __('nav_orders') ?></a></li>
                            <li><a class="dropdown-item" href="/index.php?route=customer_addresses"><i class="bi bi-geo-alt me-2"></i><?= __('nav_addresses') ?></a></li>
                            <li><a class="dropdown-item" href="/index.php?route=customer_notifications">
                                <i class="bi bi-bell me-2"></i><?= __('nav_notifications') ?>
                                <?php if ($unreadNotifCount > 0): ?>
                                    <span class="badge bg-warning text-dark float-end"><?= $unreadNotifCount ?></span>
                                <?php endif; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="/index.php?route=customer_profile"><i class="bi bi-gear me-2"></i><?= __('nav_profile') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/index.php?route=customer_logout"><i class="bi bi-box-arrow-right me-2"></i><?= __('nav_logout') ?></a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/index.php?route=customer_login" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1">
                            <i class="bi bi-box-arrow-in-right"></i> <?= __('nav_login') ?>
                        </a>
                        <a href="/index.php?route=customer_register" class="btn btn-store-primary btn-sm rounded-pill px-3 py-1">
                            <?= __('nav_register') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Alerts Container -->
<div class="container mt-3">
    <?php if ($flash = Helper::getFlash()): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> fs-5"></i>
            <div><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>
