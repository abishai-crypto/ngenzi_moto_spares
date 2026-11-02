<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Usimamizi wa Wafanyakazi (Users & Roles)</h4>
        <p class="text-muted small mb-0">Dhibiti akaunti za wafanyakazi, majukumu (Roles), na haki za kufikia mifumo (RBAC).</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::hasRole('super_admin')): ?>
            <a href="/index.php?route=roles_permissions" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Haki za Majukumu (Matrix)</span>
            </a>
        <?php endif; ?>
        <a href="/index.php?route=user_create" class="btn btn-primary-custom d-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill"></i>
            <span>Sajili Mfanyakazi Mpya</span>
        </a>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="premium-card mb-4">
    <div class="premium-card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="users">

            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tafuta kwa jina, username, au simu..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">-- Majukumu Yote (Roles) --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['name'] ?>" <?= $role === $r['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['display_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Hali Zote --</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hai (Active)</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom flex-grow-1">
                    Chuja
                </button>
                <a href="/index.php?route=users" class="btn btn-light border" title="Futa Vichungi">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="premium-card">
    <div class="premium-card-header">
        <h5><i class="bi bi-person-lines-fill text-teal"></i> Orodha ya Wafanyakazi (<?= count($users) ?>)</h5>
        <span class="badge bg-light text-dark border">Port 3307 &bull; Bcrypt Passwords</span>
    </div>
    <div class="table-responsive">
        <table class="custom-table table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Mfanyakazi / Jina</th>
                    <th>Jina la Mtumiaji (Username)</th>
                    <th>Jukumu (Role)</th>
                    <th>Simu & Barua Pepe</th>
                    <th>Hali (Status)</th>
                    <th>Kuingia Mwisho</th>
                    <th class="text-end">Vitendo (Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                            Hakuna mfanyakazi aliyepatikana kwa vigezo hivi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar-badge" style="width:36px; height:36px; font-size:13px;">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <small class="text-muted">Amesajiliwa: <?= date('d/m/Y', strtotime($u['created_at'])) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($u['username']) ?></code>
                            </td>
                            <td>
                                <?php
                                $roleBadge = 'bg-secondary';
                                if ($u['role_name'] === 'super_admin') $roleBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                elseif ($u['role_name'] === 'manager') $roleBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                elseif ($u['role_name'] === 'cashier') $roleBadge = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($u['role_name'] === 'storekeeper') $roleBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                elseif ($u['role_name'] === 'accountant') $roleBadge = 'bg-info-subtle text-info border border-info-subtle';
                                ?>
                                <span class="badge <?= $roleBadge ?> px-2.5 py-1.5 rounded-pill fw-semibold">
                                    <?= htmlspecialchars($u['role_display']) ?>
                                </span>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone text-muted me-1 small"></i><?= htmlspecialchars($u['phone'] ?? '-') ?></div>
                                <small class="text-muted"><i class="bi bi-envelope text-muted me-1 small"></i><?= htmlspecialchars($u['email'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i>Hai
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-slash-circle me-1"></i>Imezimwa
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Hajaingia bado' ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Chaguo
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item py-2 small" href="/index.php?route=user_edit&id=<?= $u['id'] ?>">
                                                <i class="bi bi-pencil me-2 text-primary"></i> Hariri Taarifa
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item py-2 small" data-bs-toggle="modal" data-bs-target="#resetModal<?= $u['id'] ?>">
                                                <i class="bi bi-key me-2 text-warning"></i> Weka Nenosiri Upya
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form action="/index.php?route=user_toggle" method="POST" onsubmit="return confirm('Je, una uhakika wa <?= $u['status'] === 'active' ? 'KUZIMA' : 'KUWASHA' ?> akaunti ya <?= htmlspecialchars($u['full_name']) ?>?')">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="dropdown-item py-2 small <?= $u['status'] === 'active' ? 'text-danger' : 'text-success' ?>">
                                                    <i class="bi <?= $u['status'] === 'active' ? 'bi-person-x' : 'bi-person-check' ?> me-2"></i>
                                                    <?= $u['status'] === 'active' ? 'Zima Akaunti' : 'Washa Akaunti' ?>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Reset Password Modal -->
                                <div class="modal fade" id="resetModal<?= $u['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="/index.php?route=user_reset_password" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Weka Nenosiri Upya: <?= htmlspecialchars($u['username']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted">
                                                        Kama msimamizi, weka nenosiri jipya kwa ajili ya mfanyakazi huyu. Mfumo utahifadhi nenosiri hili kwa usalama wa Bcrypt.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Nenosiri Jipya (Herufi 6+)</label>
                                                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••••">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Thibitisha Nenosiri Jipya</label>
                                                        <input type="password" name="confirm_new_password" class="form-control" required minlength="6" placeholder="••••••••">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                                                    <button type="submit" class="btn btn-primary-custom">Hifadhi Nenosiri Jipya</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
