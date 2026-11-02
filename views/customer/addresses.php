<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_dashboard" class="text-decoration-none">Dashibodi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Anwani Zangu</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-geo-alt-fill text-danger"></i> Anwani Zangu za Usafirishaji</h4>
            <p class="text-muted small mb-0">Hifadhi anwani za nyumbani, karakana au duka lako kwa ajili ya usafirishaji wa haraka.</p>
        </div>
        <button class="btn btn-store-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#newAddressModal">
            <i class="bi bi-plus-lg me-1"></i> Ongeza Anwani
        </button>
    </div>

    <?php if (empty($addresses)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-geo fs-1 text-muted"></i>
            <h5 class="fw-bold mt-2">Bado hauna anwani yoyote iliyohifadhiwa.</h5>
            <p class="text-muted small">Ongeza anwani unayotaka kuletewa vipuri vyako jijini Dar es Salaam.</p>
            <div>
                <button class="btn btn-store-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newAddressModal">
                    Weka Anwani ya Kwanza
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach ($addresses as $addr): ?>
            <div class="col">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 <?= $addr['is_default'] ? 'border border-primary border-2' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-primary border px-3 py-1 fw-bold">
                            <?= htmlspecialchars($addr['label']) ?>
                        </span>
                        <?php if ($addr['is_default']): ?>
                            <span class="badge bg-primary rounded-pill px-2 py-1 small">Chaguomsingi</span>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($addr['recipient_name']) ?></h6>
                    <small class="text-muted d-block mb-2"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($addr['phone']) ?></small>

                    <p class="small text-muted mb-2">
                        <strong><?= htmlspecialchars($addr['region']) ?>, Wilaya ya <?= htmlspecialchars($addr['district']) ?></strong><br>
                        <?= htmlspecialchars($addr['address']) ?>
                        <?php if (!empty($addr['landmark'])): ?>
                            <br><span class="text-secondary"><i class="bi bi-flag me-1"></i> Karibu na: <?= htmlspecialchars($addr['landmark']) ?></span>
                        <?php endif; ?>
                    </p>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <?php if (!$addr['is_default']): ?>
                            <a href="/index.php?route=customer_address_default&id=<?= $addr['id'] ?>" class="btn btn-link text-decoration-none small p-0 text-primary">
                                Weka kama Chaguomsingi
                            </a>
                        <?php else: ?>
                            <span class="small text-muted"><i class="bi bi-check2 text-success"></i> Inatumika sasa</span>
                        <?php endif; ?>

                        <form action="/index.php?route=customer_address_delete" method="POST" onsubmit="return confirm('Una uhakika unataka kufuta anwani hii?');">
                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" title="Futa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Address -->
<div class="modal fade" id="newAddressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Ongeza Anwani ya Usafirishaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/index.php?route=customer_address_store" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="latitude" id="modal_lat">
                    <input type="hidden" name="longitude" id="modal_lng">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted">Jaza maelezo au tumia GPS</span>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="getModalLocation()">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Mahali Nilipo Sasa
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lebo ya Anwani</label>
                        <select name="label" class="form-select form-select-sm">
                            <option value="Nyumbani">Nyumbani (Home)</option>
                            <option value="Karakana / Garage">Karakana / Garage</option>
                            <option value="Ofisini / Dukani">Ofisini / Dukani</option>
                            <option value="Nyingine">Nyingine</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jina la Mpokeaji</label>
                            <input type="text" name="recipient_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Simu ya Mpokeaji</label>
                            <input type="tel" name="phone" class="form-control form-control-sm" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Mkoa</label>
                            <input type="text" name="region" class="form-control form-control-sm" value="Dar es Salaam" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Wilaya</label>
                            <select name="district" class="form-select form-select-sm">
                                <option value="Ilala">Ilala</option>
                                <option value="Kinondoni">Kinondoni</option>
                                <option value="Temeke">Temeke</option>
                                <option value="Ubungo">Ubungo</option>
                                <option value="Kigamboni">Kigamboni</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mtaa na Maelezo ya Mahali</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Mf. Kariakoo mtaa wa Nyamwezi/Swahili karibu na msikiti" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alama Maarufu (Landmark)</label>
                        <input type="text" name="landmark" class="form-control form-control-sm" placeholder="Mf. Karibu na Benki ya CRDB au Shule ya Msingi">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defCheck" checked>
                        <label class="form-check-label small" for="defCheck">Weka kama anwani yangu chaguomsingi</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-store-primary rounded-pill px-4">Hifadhi Anwani</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function getModalLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('modal_lat').value = pos.coords.latitude;
            document.getElementById('modal_lng').value = pos.coords.longitude;
            alert('Mahali ulipo pamerekodiwa kikamilifu kusaidia msafirishaji!');
        }, function(err) {
            alert('Haijawezekana kupata mahali: ' + err.message);
        });
    }
}
</script>
