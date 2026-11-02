# MWONGOZO WA KUSIMIKA NA KUPELEKA MFUMO HEWANI (DEPLOYMENT GUIDE)

## Ngenzi Moto Spare Parts — Dar es Salaam, Tanzania

**Toleo:** `v1.0.0` | **Hali:** `Production Ready`

---

### 1. MAHITAJI YA MFUMO (SYSTEM REQUIREMENTS)

#### 1.1 Seva & Mazingira ya Programu (Server Stack)

- **Mfumo wa Uendeshaji (OS):** Windows 10/11 / Windows Server (kwa Kompyuta ya Duka/LAN) AU Ubuntu 20.04 / 22.04 LTS (kwa Seva Binafsi/VPS ya Mtandaoni).
- **Lugha ya PHP:** PHP **7.4.3** au zaidi (Inaoana kikamilifu na PHP 8.0, 8.1, 8.2).
- **Seva ya Database:** MySQL **5.7+** au MariaDB **10.4+**.
- **Seva ya Mtandao (Web Server):** Apache 2.4+ (yenye `mod_rewrite`, `mod_headers`) au Nginx 1.18+.

#### 1.2 Viongezo vya Lazima vya PHP (Required PHP Extensions)

Hakikisha viongezo vifuatavyo vimewashwa kwenye `php.ini`:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=json
extension=ctype
extension=fileinfo
extension=curl
```

---

### 2. KUSIMIKA KWENYE XAMPP (WINDOWS SHOP COMPUTER / LOCAL LAN)

#### Hatua ya 1: Nakili Faili za Mradi

1. Weka folda la mradi ndani ya `C:\xampp\htdocs\moto_spare` au eneo lako maalum (mf. `D:\MOTO_SPARE TRADE`).
2. Ikiwa mradi umewekwa nje ya `htdocs`, tengeneza Virtual Host kwenye Apache au fungua kupitia built-in PHP server au weka symlink.

#### Hatua ya 2: Sanidi Mazingira (.env)

1. Nakili `.env.example` na uipe jina `.env`:

   ```cmd
   copy .env.example .env
   ```

2. Fungua `.env` na urekebishe taarifa za MySQL kulingana na usanidi wako wa XAMPP:

   ```dotenv
   APP_NAME="NGENZI MOTO SPARE PARTS"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL="http://localhost:8000"
   APP_TIMEZONE="Africa/Dar_es_Salaam"

   # MySQL Port (Angalia port ya XAMPP MySQL, chaguo-msingi 3306 au 3307)
   DB_HOST="127.0.0.1"
   DB_PORT=3307
   DB_DATABASE="ngenzi_moto_spare"
   DB_USERNAME="root"
   DB_PASSWORD=""
   ```

#### Hatua ya 3: Unda Database na Pakia Schema

Kupitia MySQL CLI au phpMyAdmin:

```sql
CREATE DATABASE IF NOT EXISTS `ngenzi_moto_spare` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Pakia faili la msingi la database na uhamisho wa data:

```cmd
C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3307 --user=root ngenzi_moto_spare < database/schema.sql
C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3307 --user=root ngenzi_moto_spare < database/seed.sql
C:\xampp\php\php.exe database/migrate_phase15.php
```

#### Hatua ya 4: Hakikisha Ruhusa za Saraka (Directory Permissions)

Hakikisha saraka zifuatazo zina ruhusa kamili ya kusoma na kuandika:

- `storage/`
- `storage/backups/`
- `storage/logs/`

---

### 3. KUSIMIKA KWENYE SEVA YA LINUX (UBUNTU / NGINX / APACHE)

#### 3.1 Usanidi wa Nginx Virtual Host (`/etc/nginx/sites-available/moto_spare`)

```nginx
server {
    listen 80;
    server_name spare.ngenzimotoparts.co.tz;
    root /var/www/moto_spare/public;

    index index.php index.html;

    # Ulinzi dhidi ya kufungua mafaili ya siri
    location ~ /\.(env|git|htaccess) {
        deny all;
        return 404;
    }

    # Zuia upakuaji wa moja kwa moja wa logs na backups
    location ^~ /storage/ {
        deny all;
        return 403;
    }

    location ^~ /database/ {
        deny all;
        return 403;
    }

    location ^~ /app/ {
        deny all;
        return 403;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # au php8.1-fpm.sock
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### 3.2 Ruhusa za Faili Linux (File Permissions)

```bash
sudo chown -R www-data:www-data /var/www/moto_spare
sudo find /var/www/moto_spare -type d -exec chmod 755 {} \;
sudo find /var/www/moto_spare -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/moto_spare/storage
```

---

### 4. USALAMA WA PRODUCTION & VYETI VYA SSL (HTTPS)

Kwenye uzalishaji (Production), matumizi ya HTTPS ni ya **LAZIMA** ili kulinda manenosiri, vikao vya watumiaji na miamala ya fedha dhidi ya udukuzi wa mtandao (Man-in-the-Middle attacks).

#### Kufunga Cheti cha Bure cha Let's Encrypt (Certbot)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d spare.ngenzimotoparts.co.tz
```

Baada ya kuwasha HTTPS, sasisha `.env`:

```dotenv
APP_URL="https://spare.ngenzimotoparts.co.tz"
APP_DEBUG=false
```

---

### 5. UKAGUZI WA MWISHO WA KABLA YA KUWASHA MFUMO (PRE-FLIGHT CHECK)

1. Fungua ukurasa wa Afya ya Mfumo: `https://[domain]/index.php?route=health`.
2. Hakikisha vitu vyote viko kwenye hadhi ya kijani:
   - Database: **Connected**
   - Debug Mode: **False**
   - Storage Writable: **Yes**
   - Extensions: **All Checked**
3. Ingia kama Super Admin (`admin`), badilisha nenosiri la msingi mara moja.
4. Nenda kwenye **Mipangilio ya Mfumo** (`/index.php?route=settings`) na ujaze taarifa halisi za biashara na asilimia ya faida lengwa.
5. Nenda kwenye **Hifadhi Data** (`/index.php?route=backups`), tengeneza nakala ya kwanza ya msingi na ubonyeze **"Pima Urejesho"** ili kuhakikisha usalama wa 100%.
