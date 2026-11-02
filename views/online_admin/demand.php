<div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Ugunduzi wa Mahitaji ya Wateja & Katalogi Tarajiwa (Demand Discovery)</h4>
            <span class="text-muted small">Inamsaidia mmiliki kugundua vipuri vinavyotafutwa zaidi na wateja mtandaoni ili kufanya maamuzi ya kuviagiza stoo.</span>
        </div>
        <div>
            <a href="/index.php?route=products" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-box-seam me-1"></i> Katalogi Kuu ya Stoo
            </a>
        </div>
    </div>

    <!-- Top Inquired Search Terms (Customer Inquiries) -->
    <?php if (!empty($topSearches)): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-search text-warning me-2"></i>Maneno Yanayotafutwa Zaidi na Wateja (Search Trends)</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($topSearches as $ts): ?>
                <span class="badge bg-light text-dark border p-2 rounded-3 small">
                    <strong><?= htmlspecialchars($ts['search_term']) ?></strong> 
                    <span class="badge bg-primary rounded-pill ms-1"><?= $ts['search_count'] ?> utafutaji</span>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Demand Ranking Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">Mchanganuo wa Mahitaji kwa Kila Kipuri (Katalogi & Stoo)</h6>
            <span class="badge bg-light text-dark border">Imepangwa kwa Mahitaji ya Juu</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kipuri na SKU</th>
                        <th>Kategoria & Chapa</th>
                        <th>Hali ya Sasa</th>
                        <th class="text-center">Kutazamwa (Views)</th>
                        <th class="text-center">Kutafutwa (Searches)</th>
                        <th class="text-center">Maombi ya OOS</th>
                        <th class="text-center">Wishlists</th>
                        <th class="text-center">Maagizo (Orders)</th>
                        <th class="text-end pe-4">Kitendo cha Mmiliki</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Hakuna kumbukumbu za mahitaji ya vipuri zilizorekodiwa bado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                <code class="small text-muted"><?= htmlspecialchars($p['sku']) ?></code>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($p['category_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($p['brand_name'] ?: 'Generic') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($p['is_catalog_only'])): ?>
                                    <span class="badge bg-secondary rounded-pill px-2 py-1">Katalogi Tu (Reference)</span>
                                <?php elseif ((int)$p['current_stock'] <= 0): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1">Imeisha Stoo (0)</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1">Stoo Ipo (<?= $p['current_stock'] ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-semibold"><?= (int)$p['views_count'] ?></td>
                            <td class="text-center fw-semibold"><?= (int)$p['search_count'] + (int)$p['compatibility_count'] ?></td>
                            <td class="text-center">
                                <?php if ((int)$p['oos_requests_count'] > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-2"><?= (int)$p['oos_requests_count'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-semibold"><?= (int)$p['wishlist_count'] ?></td>
                            <td class="text-center fw-semibold"><?= (int)$p['order_count'] ?></td>
                            <td class="text-end pe-4">
                                <?php if (!empty($p['is_catalog_only'])): ?>
                                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold shadow-sm"
                                            onclick='openConvertModal(<?= json_encode([
                                                "id" => $p["id"],
                                                "name" => $p["name"],
                                                "sku" => $p["sku"],
                                                "selling_price" => (float)$p["selling_price"]
                                            ]) ?>)'>
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Weka Stoo
                                    </button>
                                <?php else: ?>
                                    <a href="/index.php?route=product_edit&id=<?= $p['id'] ?>" class="btn btn-light btn-sm border rounded-pill px-3">
                                        Hariri Bei / Stoo
                                    </a>
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

<!-- Modal: Convert Catalog Reference Item to Sellable Inventory -->
<div class="modal fade" id="convertCatalogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="/index.php?route=online_convert_catalog" method="POST">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-box-seam text-success me-2"></i> Badilisha Kuwa Bidhaa ya Stoo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="convertProductId">
                    <p class="text-muted small mb-3">
                        Kipuri hiki kwa sasa ni cha katalogi pekee. Weka bei ya kununua, kuuza na idadi ya stoo unayotaka kuanza nayo kuweka bidhaa hii kwenye mauzo halisi ya duka na mtandaoni:
                    </p>
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <strong class="d-block text-dark" id="convertProdName"></strong>
                        <span class="small text-muted font-monospace" id="convertProdSku"></span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bei ya Kununua (Cost TSh) <span class="text-danger">*</span></label>
                            <input type="number" step="100" name="buying_price" id="convertBuyingPrice" class="form-control form-control-sm" required min="0" placeholder="Mf. 15000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bei ya Kuuza (Selling TSh) <span class="text-danger">*</span></label>
                            <input type="number" step="100" name="selling_price" id="convertSellingPrice" class="form-control form-control-sm" required min="100" placeholder="Mf. 20000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Idadi ya Kuanzia Stoo (Opening Stock)</label>
                        <input type="number" name="initial_stock" class="form-control form-control-sm" min="0" value="10" placeholder="0">
                        <small class="text-muted" style="font-size: 11px;">Marekebisho ya stoo yatahifadhiwa kiotomatiki kwenye kumbukumbu za hesabu za bidhaa.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Thibitisha Kuweka Stoo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openConvertModal(item) {
    document.getElementById('convertProductId').value = item.id;
    document.getElementById('convertProdName').textContent = item.name;
    document.getElementById('convertProdSku').textContent = 'SKU: ' + item.sku;
    document.getElementById('convertBuyingPrice').value = '';
    document.getElementById('convertSellingPrice').value = item.selling_price > 0 ? item.selling_price : '';

    var modal = new bootstrap.Modal(document.getElementById('convertCatalogModal'));
    modal.show();
}
</script>
