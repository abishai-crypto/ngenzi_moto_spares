<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Ngenzi Moto Spare Parts — Duka la Mtandaoni') ?></title>
    <meta name="description" content="Duka rasmi la mtandaoni la Ngenzi Moto Spare Parts — Kariakoo, Dar es Salaam. Nunua vipuri halisi vya Pikipiki na Bajaji kwa bei nafuu.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Style -->
    <link href="/assets/css/style.css?v=2.2" rel="stylesheet">
    
    <style>
        :root {
            --store-primary: #0f766e;
            --store-primary-hover: #115e59;
            --store-accent: #f59e0b;
            --store-dark: #0f172a;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .store-navbar {
            background: #0f172a;
            border-bottom: 2px solid var(--store-accent);
        }
        .store-brand-badge {
            background: linear-gradient(135deg, #0f766e, #f59e0b);
            color: white;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #134e4a 100%);
            color: white;
            border-radius: 20px;
            padding: 48px 36px;
            position: relative;
            overflow: hidden;
        }
        .product-card-online {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.25s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-card-online:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -6px rgba(0,0,0,0.08);
            border-color: #0f766e;
        }
        .product-card-online .card-img-top {
            height: 190px;
            object-fit: contain;
            padding: 16px;
            background: #f8fafc;
        }
        .badge-vehicle {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            padding: 3px 8px;
        }
        .btn-store-primary {
            background-color: var(--store-primary);
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .btn-store-primary:hover {
            background-color: var(--store-primary-hover);
            color: #fff;
        }
        .btn-store-accent {
            background-color: var(--store-accent);
            color: #0f172a;
            font-weight: 700;
            border: none;
        }
        .btn-store-accent:hover {
            background-color: #d97706;
            color: #0f172a;
        }
        .footer-store {
            background: #0f172a;
            color: #94a3b8;
            border-top: 1px solid #1e293b;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
