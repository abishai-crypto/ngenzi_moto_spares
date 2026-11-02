<div class="container my-4" style="max-width: 720px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_dashboard" class="text-decoration-none">Dashibodi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Wasifu Wangu</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-gear text-primary"></i> Taarifa za Akaunti Yangu</h4>
        <p class="text-muted small mb-4">Badilisha jina, simu, barua pepe au nenosiri lako la kuingia kwenye duka la mtandaoni.</p>

        <form action="/index.php?route=customer_profile_update" method="POST">
            <div class="p-3 bg-light rounded-4 mb-4 border">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Namba ya Mteja (Customer Code):</small>
                        <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($customer['customer_code']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Hali ya Akaunti:</small>
                        <span class="badge bg-success-subtle text-success"><?= strtoupper($customer['status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Jina Kamili</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Namba ya Simu</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Barua Pepe</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" required>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-key text-warning"></i> Badilisha Nenosiri (Hiari)</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nenosiri la Sasa (La Zamani)</label>
                <input type="password" name="old_password" class="form-control" placeholder="Weka tu ikiwa unataka kubadilisha nenosiri">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nenosiri Jipya</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Angalau tarakimu 6">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Thibitisha Nenosiri Jipya</label>
                    <input type="password" name="confirm_new_password" class="form-control" placeholder="Rudia nenosiri jipya">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/index.php?route=customer_dashboard" class="btn btn-outline-secondary rounded-pill px-4">Ghairi</a>
                <button type="submit" class="btn btn-store-primary rounded-pill px-4 fw-bold">
                    <i class="bi bi-check-lg me-1"></i> Hifadhi Mabadiliko
                </button>
            </div>
        </form>
    </div>
</div>
