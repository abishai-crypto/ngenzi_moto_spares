<?php
use App\Models\Setting;
$businessName = Setting::get('business_name', 'NGENZI MOTO SPARE PARTS');
$businessPhone = Setting::get('business_phone', '+255 754 123 456 / +255 715 000 111');
$businessEmail = Setting::get('business_email', 'info@motosparertz.com');
$businessAddress = Setting::get('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam, Tanzania');
?>

<footer class="footer-store mt-auto py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Col 1: Brand & Bio -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-3 p-2 bg-gradient d-flex align-items-center justify-content-center text-white" style="background: linear-gradient(135deg, #0f766e, #f59e0b); width: 36px; height: 36px;">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <h5 class="text-white mb-0 fw-bold"><?= htmlspecialchars($businessName) ?></h5>
                </div>
                <p class="text-white-50 small mb-3">
                    Wauzaji wakuu wa vipuri halisi na vya ubora wa juu vya pikipiki na bajaji jijini Dar es Salaam. Tunakuhakikishia uimara wa chombo chako na safari salama.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary p-2"><i class="bi bi-shield-check text-warning"></i> Vipuri Halisi</span>
                    <span class="badge bg-secondary p-2"><i class="bi bi-truck text-info"></i> Usafirishaji DSM</span>
                    <span class="badge bg-secondary p-2"><i class="bi bi-wallet2 text-success"></i> Malipo Salama</span>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white fw-bold mb-3">Viungo vya Haraka</h6>
                <ul class="list-unstyled text-white-50 small d-flex flex-column gap-2">
                    <li><a href="/index.php?route=store" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Nyumbani</a></li>
                    <li><a href="/index.php?route=shop" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Duka / Katalogi</a></li>
                    <li><a href="/index.php?route=store_vehicle" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Chombo Changu</a></li>
                    <li><a href="/index.php?route=cart" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Kapu la Ununuzi</a></li>
                    <li><a href="/index.php?route=customer_dashboard" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Akaunti Yangu</a></li>
                </ul>
            </div>

            <!-- Col 3: Policies -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Sera na Ulinzi</h6>
                <ul class="list-unstyled text-white-50 small d-flex flex-column gap-2">
                    <li><a href="/index.php?route=terms" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Vigezo na Masharti</a></li>
                    <li><a href="/index.php?route=privacy" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Sera ya Faragha</a></li>
                    <li><a href="/index.php?route=return_policy" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Sera ya Marejesho & Fidia</a></li>
                    <li><a href="/index.php?route=customer_return_create" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right"></i> Omba Marejesho</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Wasiliana Nasi</h6>
                <ul class="list-unstyled text-white-50 small d-flex flex-column gap-2">
                    <li><i class="bi bi-geo-alt-fill text-warning me-2"></i> <?= htmlspecialchars($businessAddress) ?></li>
                    <li><i class="bi bi-telephone-fill text-warning me-2"></i> <?= htmlspecialchars($businessPhone) ?></li>
                    <li><i class="bi bi-envelope-fill text-warning me-2"></i> <?= htmlspecialchars($businessEmail) ?></li>
                    <li><i class="bi bi-clock-fill text-warning me-2"></i> Jtatu - Jmosi: 08:00 Asubuhi - 06:00 Jioni</li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-white-50 small">
            <div>
                &copy; <?= date('Y') ?> <?= htmlspecialchars($businessName) ?>. Haki zote zimehifadhiwa. Kariakoo, Dar es Salaam.
            </div>
            <div class="d-flex gap-3 mt-2 mt-md-0">
                <span class="text-white-50">Njia za Malipo:</span>
                <span class="text-white fw-semibold">M-Pesa</span>
                <span class="text-white fw-semibold">Tigo Pesa</span>
                <span class="text-white fw-semibold">Airtel Money</span>
                <span class="text-white fw-semibold">Halopesa</span>
                <span class="text-white fw-semibold">Benki</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
