<?php
use App\Core\Auth;
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-rulers text-primary me-2"></i>Vipimo vya Vipuri (Units of Measure)</h4>
        <p class="text-muted small mb-0">Usimamizi wa vipimo vya kuhesabia na kuuzia vipuri (Piece, Pair, Set, Litre, Box, Roll, n.k.).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=products" class="btn btn-light border">
            <i class="bi bi-box-seam me-1"></i> Vipuri Vyote
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Sajili Kipimo Kipya</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="premium-card">
            <div class="premium-card-header">
                <h5><i class="bi bi-list-check text-teal"></i> Orodha ya Vipimo Vilivyopo (<?= count($units) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="custom-table table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jina la Kipimo</th>
                            <th>Kifupisho (Short Code)</th>
                            <th class="text-end">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($units)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Hakuna vipimo vilivyopatikana.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($units as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border px-2.5 py-1 fw-bold fs-6">
                                            <?= htmlspecialchars($u['short_name']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($canManage): ?>
                                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editUnitModal<?= $u['id'] ?>">
                                                <i class="bi bi-pencil text-primary me-1"></i> Hariri
                                            </button>

                                            <!-- Edit Unit Modal -->
                                            <div class="modal fade" id="editUnitModal<?= $u['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start">
                                                        <form action="/index.php?route=unit_update" method="POST">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Hariri Kipimo: <?= htmlspecialchars($u['name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">Jina Kamili la Kipimo <span class="text-danger">*</span></label>
                                                                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($u['name']) ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">Kifupisho (Short Code) <span class="text-danger">*</span></label>
                                                                    <input type="text" name="short_name" class="form-control" required value="<?= htmlspecialchars($u['short_name']) ?>" placeholder="mf. Pcs, Set, Pr, Ltr">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                                                                <button type="submit" class="btn btn-primary-custom">Hifadhi Mabadiliko</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Tazama tu</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/index.php?route=unit_store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Sajili Kipimo Kipya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jina Kamili la Kipimo <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="mf. Piece, Pair, Set, Packet, Litre">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kifupisho (Short Code) <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" class="form-control" required placeholder="mf. Pcs, Pr, Set, Pkt, Ltr">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary-custom">Sajili Kipimo</button>
                </div>
            </form>
        </div>
    </div>
</div>
