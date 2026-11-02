# ngenzi_moto_spares

This system is online ordering and POS system this will ensure the business activities going good and with proper maintaining of transactions and customers.

## Overview
- **System:** Retail ERP, POS, and E-Commerce Platform
- **Location:** Dar es Salaam, Tanzania
- **Stack:** PHP 8.1 / Apache, MySQL / MariaDB

## Key Features
- **Online Storefront (E-Commerce):** Public product catalog, shopping cart, vehicle compatibility filtering (Boxer, TVS, Bajaj, etc.), checkout and online order tracking.
- **Retail POS (Point of Sale):** Fast cashier interface with barcode/SKU search, thermal receipt generation, and multi-channel payment support (Cash, M-Pesa, Tigo Pesa, Bank).
- **Inventory & Margins:** Real-time stock tracking, historical cost integrity, and automatic 30% gross profit margin calculations.
- **Role-Based Access Control (RBAC):** Granular permissions for Admin, Manager, Cashier, Storekeeper, and Accountant.
- **Security:** CSRF protection, secure password hashing (Bcrypt), session management, and HTTP security headers.

## Deployment on Render

This repository includes a `Dockerfile` and `render.yaml` configured for Docker deployment on Render:

1. Create a new **Web Service** on [Render](https://render.com) and connect this repository: `https://github.com/abishai-crypto/ngenzi_moto_spares`.
2. Select **Docker** as the runtime.
3. Configure your Environment Variables in Render:
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_URL`: `https://<your-render-subdomain>.onrender.com`
   - `APP_TIMEZONE`: `Africa/Dar_es_Salaam`
   - `DB_HOST`: Your remote MySQL database host (e.g., Aiven, Clever Cloud, PlanetScale, or external MySQL)
   - `DB_PORT`: `3306`
   - `DB_DATABASE`: `ngenzi_moto_spare`
   - `DB_USERNAME`: Database username
   - `DB_PASSWORD`: Database password

4. Import the initial database schema and seed data found in `database/schema.sql` and `database/seed.sql` into your remote MySQL database.

## Local Setup (XAMPP / PHP)
1. Ensure Apache and MySQL are running in XAMPP.
2. Copy `.env.example` to `.env` and verify database credentials.
3. Run migrations:
   ```bash
   php database/migrate.php
   ```
4. Start local development server:
   ```bash
   php -S 0.0.0.0:8000
   ```
5. Open `http://localhost:8000` in your browser.
