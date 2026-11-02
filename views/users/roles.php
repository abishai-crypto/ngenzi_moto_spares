<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Haki na Majukumu (Permission Matrix)</h4>
        <p class="text-muted small mb-0">Usimamizi wa haki za kiutendaji (RBAC) kwa kila jukumu la wafanyakazi wa NGENZI MOTO SPARE PARTS.</p>
    </div>
    <a href="/index.php?route=users" class="btn btn-light border">
        <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Wafanyakazi
    </a>
</div>

<!-- Matrix Tabs by Role -->
<div class="row g-4">
    <?php foreach ($roles as $r): ?>
        <?php 
        $isOwner = ($r['name'] === 'super_admin');
        $assignedPerms = $rolePermissionsMap[$r['id']] ?? [];
        ?>
        <div class="col-xl-6">
            <div class="premium-card h-100">
                <div class="premium-card-header">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi <?= $isOwner ? 'bi-star-fill text-warning' : 'bi-shield-check text-primary' ?> me-2"></i>
                            <?= htmlspecialchars($r['display_name']) ?>
                        </h5>
                        <small class="text-muted"><?= htmlspecialchars($r['description']) ?></small>
                    </div>
                    <div>
                        <span class="badge <?= $isOwner ? 'bg-warning-subtle text-dark border border-warning' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-3 py-1.5">
                            <?= count($assignedPerms) ?> Haki
                        </span>
                    </div>
                </div>

                <div class="premium-card-body p-3">
                    <form action="/index.php?route=role_permissions_update" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="role_id" value="<?= $r['id'] ?>">

                        <div class="permission-groups" style="max-height: 480px; overflow-y: auto;">
                            <?php foreach ($permissionsByModule as $module => $perms): ?>
                                <div class="mb-3 border rounded p-2.5 bg-light">
                                    <div class="fw-bold text-uppercase small text-muted mb-2 border-bottom pb-1">
                                        Moduli: <?= htmlspecialchars($module) ?>
                                    </div>
                                    <div class="row g-2">
                                        <?php foreach ($perms as $p): ?>
                                            <?php $checked = in_array((int)$p['id'], $assignedPerms) || $isOwner; ?>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" 
                                                           value="<?= $p['id'] ?>" id="perm_<?= $r['id'] ?>_<?= $p['id'] ?>"
                                                           <?= $checked ? 'checked' : '' ?>
                                                           <?= $isOwner ? 'disabled' : '' ?>>
                                                    <label class="form-check-label small fw-semibold" for="perm_<?= $r['id'] ?>_<?= $p['id'] ?>">
                                                        <code><?= htmlspecialchars($p['name']) ?></code>
                                                        <span class="text-muted d-block" style="font-size:11px; font-weight:normal;">
                                                            <?= htmlspecialchars($p['description']) ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!$isOwner): ?>
                            <div class="mt-3 pt-2 border-top text-end">
                                <button type="submit" class="btn btn-sm btn-primary-custom">
                                    <i class="bi bi-check-lg me-1"></i> Hifadhi Haki za <?= htmlspecialchars($r['display_name']) ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="mt-3 pt-2 border-top text-center text-muted small">
                                <i class="bi bi-lock-fill me-1"></i>Haki za Mmiliki zipo wazi moja kwa moja kwa usalama.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
