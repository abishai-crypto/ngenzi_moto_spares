<?php
use App\Core\Helper;
?>
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/index.php?route=dashboard" class="text-decoration-none">Dashibodi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Usalama wa Mfumo</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-shield-check text-primary me-2"></i>Hadhi ya Usalama & Uthabiti (Security Status)
            </h4>
            <small class="text-muted">Tathmini kamili ya kiufundi ya usalama wa taarifa, miamala na akaunti za mfumo.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="/index.php?route=audit" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-journal-text me-1"></i>Tazama Audit Trail
            </a>
            <a href="/index.php?route=roles_permissions" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-person-lock me-1"></i>Majukumu & Haki (RBAC)
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Audit Logs Zilizopo</span>
                        <h3 class="fw-bold mb-0 text-primary font-monospace mt-1"><?= number_format($auditCount) ?></h3>
                        <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Hazifutiki (Immutable)</small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle p-3 text-primary fs-4">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Wamiliki Hai (Owners)</span>
                        <h3 class="fw-bold mb-0 text-dark font-monospace mt-1"><?= $userStats['active_owners'] ?? 1 ?></h3>
                        <small class="text-muted">Kinga ya kufutwa mmiliki wa mwisho</small>
                    </div>
                    <div class="rounded-circle bg-dark-subtle p-3 text-dark fs-4">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Session Timeout</span>
                        <h3 class="fw-bold mb-0 text-warning font-monospace mt-1"><?= $timeoutMinutes ?> min</h3>
                        <small class="text-muted">Inactivity Auto-Logout</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle p-3 text-warning fs-4">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">PHP Environment</span>
                        <h3 class="fw-bold mb-0 text-info font-monospace mt-1">PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></h3>
                        <small class="text-muted">PDO MySQL Driver</small>
                    </div>
                    <div class="rounded-circle bg-info-subtle p-3 text-info fs-4">
                        <i class="bi bi-cpu"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Components Grid -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-shield-lock-fill text-success me-2"></i>Tathmini ya Safu za Ulinzi (Security Matrix)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 25%;">Eneo la Usalama</th>
                            <th style="width: 15%;">Hadhi (Status)</th>
                            <th style="width: 60%;">Maelezo ya Kiufundi (Technical Details)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statusSummary as $k => $item): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <?= htmlspecialchars($item['title']) ?>
                                </td>
                                <td>
                                    <?php if ($item['status'] === 'success'): ?>
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill font-monospace fw-bold">
                                            <i class="bi bi-check2-circle me-1"></i>SALAMA (Configured)
                                        </span>
                                    <?php elseif ($item['status'] === 'warning'): ?>
                                        <span class="badge bg-warning-subtle text-dark px-3 py-2 rounded-pill font-monospace fw-bold">
                                            <i class="bi bi-exclamation-triangle me-1"></i>TAHADHARI (Action)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill font-monospace fw-bold">
                                            <i class="bi bi-x-circle me-1"></i>HATARI
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars($item['detail']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hardening Policies Detail Cards -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-key-fill text-primary me-2"></i>Sera ya Nywila na Kuingia (Auth Policy)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Urefu wa Chini wa Nenosiri (Min Password Length):</span>
                            <span class="fw-bold font-monospace text-dark"><?= $minPassLength ?> Characters</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Kiwango cha Majaribio Yasiyo Sahihi (Max Attempts):</span>
                            <span class="fw-bold font-monospace text-dark"><?= $maxAttempts ?> Attempts / 3 min</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Muda wa Kufungiwa kwa Muda (Lockout Duration):</span>
                            <span class="fw-bold font-monospace text-dark"><?= $lockoutSeconds ?> Seconds (3 min)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Algorithm ya Hashing:</span>
                            <span class="badge bg-success-subtle text-success font-monospace">PASSWORD_BCRYPT</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Uthibitisho wa Mtumiaji Aliyesimamishwa:</span>
                            <span class="badge bg-primary-subtle text-primary">Kufukuzwa papo hapo (Instant Session Invalidation)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-cash-coin text-success me-2"></i>Uadilifu wa Fedha na Stoo (Financial Integrity)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Ulinzi wa Bei za Chini (MSP Enforcement):</span>
                            <span class="badge bg-success-subtle text-success">Server-side strict check + Audit</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Hesabu ya Jumla & Punguzo (Totals & Discounts):</span>
                            <span class="badge bg-success-subtle text-success">Inafanywa na server pekee (Never trusted from client)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Uzuiaji wa Malipo Mara Mbili (Duplicate Payment):</span>
                            <span class="badge bg-primary-subtle text-primary">Idempotency check + Rapid submit block</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Uzuiaji wa Marejesho Mara Mbili (Duplicate Refund):</span>
                            <span class="badge bg-primary-subtle text-primary">Strict state locking + Sale row lock</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Uzuiaji wa Kufuta Kumbukumbu (Immutability):</span>
                            <span class="badge bg-secondary-subtle text-secondary">Mauzo, malipo na marejesho hayafutwi kimwili (Soft cancel)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
