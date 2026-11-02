# MPANGO WA KUKABILIANA NA MAJANGA NA KUREJESHA DATA (DISASTER RECOVERY PLAN)

## Ngenzi Moto Spare Parts — Dar es Salaam, Tanzania

**Malengo ya Urejeshaji:**

- **RPO (Recovery Point Objective):** Upeo wa saa 24 za data (kwa kutumia nakala za kila siku) au saa 1 ya data (ikiwa ratiba ya saa inatumika).
- **RTO (Recovery Time Objective):** Chini ya dakika 15 kurejesha mfumo kamili wa uzalishaji iwapo kompyuta itaharibika au kuibiwa.

---

### 1. CHANZO NA ENEO LA NAKALA ZA DATA (BACKUP LOCATIONS)

1. **Seva Kuu (Local Storage):**
   - Eneo: `storage/backups/`
   - Ulinzi: Saraka imelindwa na `.htaccess` kuzuia upakuaji wowote wa moja kwa moja kupitia mtandao.
   - Muundo wa Jina: `spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql`
2. **Hifadhi ya Nje (Off-site Secondary Storage):**
   - Nakala zote zilizopakuliwa zinatakiwa kuwekwa kwenye Flash Drive au seva ya wingu (Cloud VPS / Google Drive) iliyosimbwa kwa nenosiri.

---

### 2. UTARATIBU WA HATUA KWA HATUA WA KUREJESHA DATA (RESTORE PROCEDURE)

Iwapo seva au kompyuta ya duka imeharibika, fuata utaratibu ufuatao wa kiusalama:

#### Hatua ya 1: Sitisha Uandishi wa Mfumo (Stop Application Traffic)

Zuia watumiaji kuingiza miamala mipya wakati wa zoezi la urejeshaji ili kuzuia mgongano wa data:

- Weka faili la muda la matengenezo au zima Apache/Nginx kwa muda mfupi:

  ```bash
  sudo systemctl stop nginx
  ```

  au kwenye Windows zima huduma ya Apache kwenye XAMPP Control Panel.

#### Hatua ya 2: Tengeneza Nakala ya Dharura ya Database Iliyopo (Pre-restore Safety Backup)

Hata kama database ina matatizo, tengeneza nakala ya hali iliyopo sasa kama tahadhari ya ziada kabla ya kufanya jambo lolote:

```cmd
C:\xampp\mysql\bin\mysqldump.exe --host=127.0.0.1 --port=3307 --user=root --single-transaction --routines ngenzi_moto_spare > emergency_before_restore.sql
```

#### Hatua ya 3: Chagua Nakala Salama Inayotakiwa Kurejeshwa

Chagua faili la hivi punde zaidi lililothibitishwa (`spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql`).

#### Hatua ya 4: Rejesha Database (Execute Restoration)

Kupitia MySQL CLI (njia salama na yenye kasi zaidi):

```cmd
C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3307 --user=root --default-character-set=utf8mb4 ngenzi_moto_spare < "storage\backups\spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql"
```

*Kwenye Linux:*

```bash
mysql -u [username] -p ngenzi_moto_spare < /var/www/moto_spare/storage/backups/spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql
```

#### Hatua ya 5: Hakiki Majedwali na Rekodi Muhimu (Verification Checks)

Fungua MySQL na uhakikishe kuwa majedwali yote 34 yapo:

```sql
USE ngenzi_moto_spare;
SHOW FULL TABLES WHERE Table_type = 'BASE TABLE';
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM sales;
SELECT COUNT(*) FROM customers;
SELECT COUNT(*) FROM payments;
SELECT COUNT(*) FROM inventory_transactions;
```

#### Hatua ya 6: Fanya Majaribio ya Miamala (Operational Acceptance Test)

1. Washa upya seva ya mtandao.
2. Ingia kwenye mfumo kama Super Admin (`admin`).
3. Fungua POS, hakikisha orodha ya vipuri na stoo inasomeka vizuri.
4. Fungua Ripoti ya Fedha, hakikisha hesabu za mauzo na madeni zinawiana.
5. Ruhusu watumiaji wengine kuendelea na kazi.

---

### 3. RATIBA YA NAKALA ZA KIOTOMATIKI (AUTOMATED SCHEDULER SETUP)

#### A. Kwenye Windows Server / Windows 10/11 (Task Scheduler)

1. Fungua **Task Scheduler** kwenye Windows.
2. Chagua **Create Basic Task** -> Weka jina: `Ngenzi Spare Parts Daily Backup`.
3. Chagua **Daily** -> Saa: `23:00` (Saa 5 Usiku).
4. Action: **Start a program**:
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `"D:\MOTO_SPARE TRADE\app\scripts\cron_backup.php"`
   - Start in: `D:\MOTO_SPARE TRADE`
5. Hifadhi. Mfumo utajiendesha kila siku na kufuta nakala za zamani zaidi ya 14 kiotomatiki.

#### B. Kwenye Linux (Crontab)

```bash
# Fungua crontab
crontab -e

# Ongeza mstari ufuatao (inapiga kila siku saa 5 usiku):
0 23 * * * /usr/bin/php /var/www/moto_spare/app/scripts/cron_backup.php >> /var/www/moto_spare/storage/logs/cron.log 2>&1
```

---

### 4. MATUKIO YA KUKATIKA KWA UMEME AU MTANDAO (POWER / NETWORK FAILURE READINESS)

- **Database Transactions (ACID):** Mfumo unatumia `InnoDB` na `beginTransaction()`, `commit()`, na `rollBack()` kwenye kila muamala wa mauzo, stoo, malipo na marejesho.
- Iwapo umeme au mtandao utakatika katikati ya muamala:
  - Muamala hauwezi kubaki nusu (Hakuna risiti itakayoundwa bila stoo kupungua au bila malipo kurekodiwa).
  - MySQL inatupilia mbali (roll back) shughuli yoyote isiyokamilika mara umeme unaporudi.
- **Pendekezo la Vifaa:** Kila kompyuta ya kaunta iunganishwe na kifaa cha kuzuia kukatika umeme (**UPS**) chenye uwezo wa kutoa umeme kwa angalau dakika 15 ili kumpa cashier muda wa kuhifadhi kazi.
