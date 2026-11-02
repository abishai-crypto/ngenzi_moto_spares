<?php
use App\Core\Auth;
use App\Core\Helper;
?>
<div class="container-fluid px-4 py-3">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/index.php?route=dashboard" class="text-decoration-none">Dashibodi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Audit Trail</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text text-primary me-2"></i>Kumbukumbu ya Matukio ya Mfumo (Audit Trail)
            </h4>
            <small class="text-muted">Kumbukumbu rasmi isiyofutika ya matukio yote ya kiusalama, kifedha, stoo na uendeshaji.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                <i class="bi bi-shield-lock me-1"></i>Rekodi Hazifutiki (Append-Only)
            </span>
            <?php if (Auth::hasRole(['super_admin']) || Auth::hasPermission('audit.export')): ?>
                <a href="/index.php?route=audit_export&<?= http_build_query($filters) ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Pakua CSV
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/index.php" class="row g-2 align-items-end">
                <input type="hidden" name="route" value="audit">

                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Tafuta</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Action, IP, thamani..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Mtumiaji</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Watumiaji Wote</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Moduli</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">Moduli Zote</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= ($filters['module'] === $m) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(strtoupper($m)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Kitendo (Action)</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">Vitendo Vyote</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= ($filters['action'] === $act) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 col-sm-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Kipindi cha Tarehe</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from']) ?>" title="Kuanzia">
                        <span class="input-group-text">-</span>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to']) ?>" title="Hadi">
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="/index.php?route=audit" class="btn btn-light btn-sm px-3 rounded-pill border">
                        <i class="bi bi-x-circle me-1"></i>Weka Upya
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill fw-semibold">
                        <i class="bi bi-funnel me-1"></i>Chuja
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                Orodha ya Matukio ya Mfumo
                <span class="badge bg-primary-subtle text-primary ms-2 rounded-pill"><?= number_format($totalLogs) ?> Rekodi</span>
            </h6>
            <small class="text-muted">Ukurasa <?= $currentPage ?> kati ya <?= $totalPages ?></small>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase">
                            <th class="ps-3" style="width: 15%;">Tarehe & Saa</th>
                            <th style="width: 18%;">Mtumiaji</th>
                            <th style="width: 18%;">Kitendo (Action)</th>
                            <th style="width: 12%;">Moduli & ID</th>
                            <th style="width: 12%;">IP Address</th>
                            <th style="width: 15%;">Muhtasari</th>
                            <th class="text-end pe-3" style="width: 10%;">Ukaguzi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                    Hakuna matukio yaliyopatikana kulingana na vigezo vilivyochaguliwa.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                $act = strtoupper($log['action']);
                                if (strpos($act, 'LOGIN') !== false || strpos($act, 'AUTH') !== false) {
                                    $badgeClass = (strpos($act, 'FAIL') !== false || strpos($act, 'LOCK') !== false) ? 'bg-danger text-white' : 'bg-success-subtle text-success';
                                } elseif (strpos($act, 'CANCEL') !== false || strpos($act, 'REJECT') !== false || strpos($act, 'DELETE') !== false) {
                                    $badgeClass = 'bg-danger-subtle text-danger';
                                } elseif (strpos($act, 'OVERRIDE') !== false || strpos($act, 'ADJUST') !== false) {
                                    $badgeClass = 'bg-warning-subtle text-dark';
                                } elseif (strpos($act, 'CREATE') !== false || strpos($act, 'STORE') !== false) {
                                    $badgeClass = 'bg-primary-subtle text-primary';
                                } elseif (strpos($act, 'UPDATE') !== false || strpos($act, 'RECEIVE') !== false) {
                                    $badgeClass = 'bg-info-subtle text-dark';
                                }
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark" style="font-size: 13px;">
                                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 11px;">
                                            #<?= $log['id'] ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['full_name'])): ?>
                                            <div class="fw-semibold text-dark" style="font-size: 13px;">
                                                <?= htmlspecialchars($log['full_name']) ?>
                                            </div>
                                            <small class="text-muted">
                                                @<?= htmlspecialchars($log['username']) ?> &bull; <span class="badge bg-light text-secondary border"><?= htmlspecialchars($log['role_display'] ?? '') ?></span>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">System / CLI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> font-monospace fw-bold" style="font-size: 11px;">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace text-uppercase" style="font-size: 11px;">
                                            <?= htmlspecialchars($log['module']) ?>
                                        </span>
                                        <?php if (!empty($log['record_id'])): ?>
                                            <small class="text-muted font-monospace d-block">ID: #<?= $log['record_id'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="font-monospace text-muted"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></small>
                                    </td>
                                    <td>
                                        <div class="text-truncate text-muted small" style="max-width: 180px;" title="<?= htmlspecialchars($log['new_values'] ?? '') ?>">
                                            <?= htmlspecialchars(substr($log['new_values'] ?? $log['old_values'] ?? '—', 0, 50)) ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 view-audit-btn" data-id="<?= $log['id'] ?>">
                                            <i class="bi bi-eye me-1"></i>Tazama
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Inaonyesha rekodi <?= min(($currentPage - 1) * 30 + 1, $totalLogs) ?> hadi <?= min($currentPage * 30, $totalLogs) ?> ya <?= number_format($totalLogs) ?>
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="/index.php?route=audit&<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">Iliyopita</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        for ($p = $startPage; $p <= $endPage; $p++):
                        ?>
                            <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="/index.php?route=audit&<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="/index.php?route=audit&<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">Inayofuata</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Audit Details Modal -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-labelledby="auditDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-3">
                <h5 class="modal-title fw-bold" id="auditDetailModalLabel">
                    <i class="bi bi-shield-check text-primary me-2"></i>Maelezo ya Tukio la Ukaguzi #<span id="modalAuditId"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Mtumiaji:</small>
                        <span class="fw-semibold text-dark" id="modalUserName"></span>
                        <div class="small text-muted" id="modalRole"></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tarehe na Saa:</small>
                        <span class="fw-semibold text-dark font-monospace" id="modalDate"></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Kitendo (Action):</small>
                        <span class="badge bg-primary font-monospace" id="modalAction"></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Moduli & ID ya Rekodi:</small>
                        <span class="fw-bold font-monospace" id="modalModule"></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">IP Address & Kifaa:</small>
                        <span class="font-monospace small text-dark" id="modalIp"></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Thamani za Zamani (Old Values)</label>
                    <pre class="bg-light p-3 rounded-3 border text-secondary font-monospace small mb-0" id="modalOldValues" style="max-height: 160px; overflow-y: auto;"></pre>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Thamani Mpya (New Values / Payload)</label>
                    <pre class="bg-light p-3 rounded-3 border text-dark font-monospace small mb-0" id="modalNewValues" style="max-height: 200px; overflow-y: auto;"></pre>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Funga</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('auditDetailModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.view-audit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('/index.php?route=audit_details&id=' + id)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const d = res.data;
                        document.getElementById('modalAuditId').textContent = d.id;
                        document.getElementById('modalUserName').textContent = d.user_name;
                        document.getElementById('modalRole').textContent = 'Jukumu: ' + d.role_display;
                        document.getElementById('modalDate').textContent = d.created_at;
                        document.getElementById('modalAction').textContent = d.action;
                        document.getElementById('modalModule').textContent = d.module + (d.record_id ? ' (#' + d.record_id + ')' : '');
                        document.getElementById('modalIp').textContent = d.ip_address;
                        
                        let oldText = d.old_values || '— (Hakuna data ya awali)';
                        try {
                            const parsed = JSON.parse(oldText);
                            oldText = JSON.stringify(parsed, null, 2);
                        } catch(e) {}
                        document.getElementById('modalOldValues').textContent = oldText;

                        let newText = d.new_values || '—';
                        try {
                            const parsed = JSON.parse(newText);
                            newText = JSON.stringify(parsed, null, 2);
                        } catch(e) {}
                        document.getElementById('modalNewValues').textContent = newText;

                        modal.show();
                    } else {
                        alert(res.message || 'Hitilafu imetokea.');
                    }
                })
                .catch(err => alert('Hitilafu ya mtandao: ' + err.message));
        });
    });
});
</script>
