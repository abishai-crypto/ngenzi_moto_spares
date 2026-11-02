<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item active" aria-current="page">Katalogi ya Vipuri</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top: 80px; z-index: 10;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-funnel text-primary"></i> Chuja Bidhaa</h6>
                    <a href="/index.php?route=shop" class="small text-danger text-decoration-none">Futa vichujio</a>
                </div>

                <form action="/index.php" method="GET">
                    <input type="hidden" name="route" value="shop">

                    <!-- Search -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Neno la Kutafuta</label>
                        <input type="text" name="search" class="form-control form-control-sm rounded-3" 
                               placeholder="Jina, SKU, Boxer, TVS..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>

                    <!-- Vehicle Type -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Aina ya Chombo</label>
                        <select name="vehicle_type" class="form-select form-select-sm rounded-3">
                            <option value="">Aina zote</option>
                            <option value="Motorcycle" <?= ($filters['vehicle_type'] ?? '') === 'Motorcycle' ? 'selected' : '' ?>>Pikipiki tu</option>
                            <option value="Bajaj" <?= ($filters['vehicle_type'] ?? '') === 'Bajaj' ? 'selected' : '' ?>>Bajaji tu</option>
                        </select>
                    </div>

                    <!-- Specific Vehicle Model -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mtindo wa Chombo (Model)</label>
                        <select name="model" class="form-select form-select-sm rounded-3">
                            <option value="">Vyombo vyote</option>
                            <?php foreach ($vehicleModels as $vm): ?>
                                <option value="<?= $vm['id'] ?>" <?= ($filters['vehicle_model_id'] ?? 0) === (int)$vm['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vm['brand_name']) ?> <?= htmlspecialchars($vm['model_name']) ?> (<?= $vm['vehicle_type'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategoria</label>
                        <select name="category" class="form-select form-select-sm rounded-3">
                            <option value="">Kategoria zote</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Brand -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Chapa ya Kipuri (Brand)</label>
                        <select name="brand" class="form-select form-select-sm rounded-3">
                            <option value="">Chapa zote</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($filters['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Availability Status Filter (Phase 17) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Upatikanaji (Availability)</label>
                        <select name="availability" class="form-select form-select-sm rounded-3">
                            <option value="">Upatikanaji wote</option>
                            <option value="in_stock" <?= ($filters['availability'] ?? '') === 'in_stock' ? 'selected' : '' ?>>Vilivyopo Stoo (In Stock)</option>
                            <option value="on_order" <?= ($filters['availability'] ?? '') === 'on_order' ? 'selected' : '' ?>>Kwa Agizo (On Order)</option>
                            <option value="catalog" <?= ($filters['availability'] ?? '') === 'catalog' ? 'selected' : '' ?>>Katalogi ya Marejeleo (Catalog)</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bei (TSh)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Kiwango cha chini" value="<?= htmlspecialchars($filters['min_price'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Kiwango cha juu" value="<?= htmlspecialchars($filters['max_price'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Sorting -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Panga kwa</label>
                        <select name="sort" class="form-select form-select-sm rounded-3">
                            <option value="">Chaguo-msingi</option>
                            <option value="popular" <?= ($filters['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>Maarufu Zaidi</option>
                            <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Vilivyoongezwa Karibuni</option>
                            <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Bei: Ndogo kwenda Kubwa</option>
                            <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Bei: Kubwa kwenda Ndogo</option>
                            <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Jina (A hadi Z)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-store-primary w-100 rounded-3 btn-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Tekeleza Vichujio
                    </button>
                </form>
            </div>
        </div>

        <!-- Product Grid & Results -->
        <div class="col-lg-9">
            <!-- Header bar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white d-flex flex-row justify-content-between align-items-center">
                <span class="text-muted small">
                    Inaonyesha vipuri <strong><?= count($products) ?></strong> kati ya <strong><?= $totalProducts ?></strong> vilivyopatikana
                </span>
                <?php if (!empty($filters['search'])): ?>
                    <span class="badge bg-light text-dark border">
                        Matokeo ya: "<?= htmlspecialchars($filters['search']) ?>"
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <!-- Empty State -->
                <div class="text-center py-5 bg-white rounded-4 border shadow-sm my-4">
                    <div class="p-4 rounded-circle bg-light d-inline-block text-muted mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-search fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hakuna Kipuri Kilichopatikana</h5>
                    <p class="text-muted small max-w-sm mx-auto mb-4" style="max-width: 400px;">
                        Hakuna vipuri vinavyoendana na vigezo vya utafutaji wako. Jaribu kulegeza vichujio au kuandika jina tofauti.
                    </p>
                    <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-4">
                        Onyesha Vipuri Vyote
                    </a>
                </div>
            <?php else: ?>
                <!-- Product Grid (Mobile-First 2 to 3 cols) -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                    <?php foreach ($products as $product): 
                        $avail = $product['availability'] ?? \App\Models\Product::getAvailabilityInfo($product);
                    ?>
                    <div class="col">
                        <div class="product-card-online h-100 d-flex flex-column border rounded-4 bg-white shadow-sm overflow-hidden transition-all">
                            <!-- Image Container -->
                            <div class="position-relative bg-light text-center p-3" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                                <a href="/index.php?route=product_detail&id=<?= $product['id'] ?>" class="d-block w-100 h-100 text-center">
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                         class="img-fluid" 
                                         style="max-height: 170px; object-fit: contain;" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         onerror="this.src='/assets/img/spare_part_placeholder.svg';">
                                </a>

                                <!-- Customer-Friendly Availability Badge (Phase 17) -->
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge <?= $avail['badge_class'] ?> rounded-pill px-2 py-1 small">
                                        <?= htmlspecialchars($avail['display_stock']) ?>
                                    </span>
                                </div>

                                <!-- Wishlist Button -->
                                <form action="/index.php?route=wishlist_add" method="POST" class="position-absolute top-0 end-0 m-2">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Hifadhi">
                                        <i class="bi bi-heart text-danger"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Content -->
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="small text-muted mb-1 d-flex justify-content-between">
                                    <span><?= htmlspecialchars($product['category_name']) ?></span>
                                    <span class="font-monospace text-uppercase"><?= htmlspecialchars($product['sku']) ?></span>
                                </div>

                                <h6 class="fw-bold mb-2">
                                    <a href="/index.php?route=product_detail&id=<?= $product['id'] ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h6>

                                <!-- Compatible Motorcycle/Bajaj Models Badge -->
                                <?php if (!empty($product['compatible_models'])): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-light text-dark border text-truncate d-inline-block small" style="max-width: 100%; font-size: 11px;" title="<?= htmlspecialchars($product['compatible_models']) ?>">
                                            <i class="bi bi-bicycle text-primary me-1"></i> <?= htmlspecialchars($product['compatible_models']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Price & Action Footer -->
                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 11px;"><?= __('selling_price') ?></small>
                                        <span class="fs-5 fw-bold text-primary">TSh <?= number_format($product['selling_price'], 2) ?></span>
                                    </div>

                                    <?php if (!empty($avail['can_order'])): ?>
                                        <form action="/index.php?route=cart_add" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-store-primary btn-sm rounded-pill px-3 shadow-sm">
                                                <i class="bi bi-cart-plus"></i> <?= __('add_to_cart') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/index.php?route=request_out_of_stock" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill px-2.5 small fw-semibold text-dark shadow-sm" title="<?= __('order_on_request') ?>: TSh <?= number_format($product['selling_price'], 2) ?>">
                                                <i class="bi bi-telephone-outbound me-1"></i> <?= __('order_on_request') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="/index.php?route=shop&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&category=<?= urlencode($filters['category_id'] ?? '') ?>&model=<?= urlencode($filters['vehicle_model_id'] ?? '') ?>&availability=<?= urlencode($filters['availability'] ?? '') ?>&sort=<?= urlencode($filters['sort'] ?? '') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
