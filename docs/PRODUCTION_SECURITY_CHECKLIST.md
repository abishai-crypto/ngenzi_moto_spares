# NGENZI MOTO SPARE PARTS

## Orodha ya Ukaguzi wa Usalama Kabla ya Uzalishaji (Production Security Checklist)

**Eneo la Biashara:** Dar es Salaam, Tanzania  
**Mfumo:** Mfumo wa Usimamizi wa Mauzo, Stoo & Fedha za Vipuri vya Pikipiki na Bajaji  
**Awamu:** Phase 14 — Security Hardening & Reliability  

---

### UTANGULIZI

Hati hii inaeleza vigezo muhimu vya kiufundi na usalama vinavyopaswa kutekelezwa na kuthibitishwa kabla ya kuhamisha mfumo kutoka mazingira ya majaribio (Development/XAMPP) kwenda kwenye seva halisi ya biashara (Production Server / Cloud VPS / Dedicated Server).

> [!IMPORTANT]
> Kuwepo kwa orodha hii hakuifanyi seva kuwa salama kiotomatiki. Kila hatua lazima ifanyiwe ukaguzi wa kimatendo (Manual Verification & Configuration) na Mhandisi wa Mifumo (SysAdmin).

---

### 1. NYWILA NA HAKI ZA DATABASE (DATABASE CREDENTIALS & HARDENING)

- [ ] **Nenosiri Imara la MySQL:**
  - Badilisha mtumiaji `root` asiyekuwa na nenosiri (development setup) na tengeneza mtumiaji maalum mwenye jina lisilotabirika (mfano: `ngz_parts_user`).
  - Nenosiri la database liwe na urefu wa herufi angalau 24 zikiwemo herufi kubwa, ndogo, namba na alama maalum.
- [ ] **Kanuni ya Haki za Chini (Least Privilege Principle):**
  - Mtumiaji wa database asipewe haki za `GRANT OPTION`, `SUPER`, `PROCESS`, au `FILE` iwapo hazihitajiki.
  - Apewe haki za `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `INDEX`, na `ALTER` kwenye database ya `ngenzi_moto_spare` pekee.
- [ ] **Ufungaji wa Port 3306 / 3307:**
  - Zuia mawasiliano ya nje (Remote Connection) kwenye port ya MySQL kupitia Firewall (`ufw` au `iptables`). MySQL isikilize `127.0.0.1` (localhost) pekee.

---

### 2. HUDUMA YA SSL / HTTPS NA USALAMA WA MTANDAO

- [ ] **Cheti cha SSL/TLS:**
  - Sakinisha cheti cha TLS 1.3 (kupitia Let's Encrypt Certbot au Commercial SSL).
  - Washa uelekezaji wa lazima (Force HTTPS Redirect) kutoka HTTP kwenda HTTPS kwenye Web Server (Apache/Nginx).
- [ ] **HSTS (HTTP Strict Transport Security):**
  - Hakikisha `Strict-Transport-Security` header inawashwa kwenye uzalishaji ikiwa na `max-age=31536000; includeSubDomains`.
- [ ] **Cookies Salama:**
  - Hakikisha `session.cookie_secure = 1` inatumika kupitia muunganisho wa HTTPS, pamoja na `HttpOnly` na `SameSite=Lax`.

---

### 3. MPANGILIO WA SEVA NA MAFAILI (FILE PERMISSIONS & PROTECTION)

- [ ] **Ulinzi wa Saraka za Ndani (`.htaccess` / Nginx Deny):**
  - Thibitisha kuwa saraka za `app/`, `database/`, na faili zenye `.sql`, `.env`, `.log`, `.git` haziwezi kufikiwa au kupakuliwa kupitia kivinjari cha mtandao.
- [ ] **Ruhusa za Mafaili (File Permissions):**
  - Folda: `755` (au `750`)
  - Mafaili ya PHP: `644` (au `640`)
  - Faili la database config (`app/config/database.php`): `600` likimilikiwa na mtumiaji wa web server (`www-data` au `nobody`).
- [ ] **Kuzuia Kuonyesha Orodha ya Mafaili (Directory Browsing):**
  - Washa `Options -Indexes` kwenye Apache ili kuzuia wateja kuona faili zilizomo ndani ya folda.

---

### 4. MPANGILIO WA PHP KWA MAZINGIRA HALISI (PHP RUNTIME HARDENING)

- [ ] **Kuzima Uonyeshaji wa Makosa (Disable Display Errors):**
  - Kwenye `php.ini`:

    ```ini
    display_errors = Off
    display_startup_errors = Off
    log_errors = On
    error_log = /var/log/php/ngenzi_errors.log
    ```

  - Kwenye uzalishaji, wateja wasione taarifa za kitaalamu za makosa, stack traces, wala majina ya database/table zikipatikana hitilafu.
- [ ] **Kuzima Kazi Hatari (Disable Dangerous Functions):**
  - Zuia kazi za PHP zisizohitajika kwenye mfumo huu:

    ```ini
    disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source
    ```

- [ ] **Kuzima Kujitambulisha kwa PHP (Expose PHP):**
  - Weka `expose_php = Off` ili kuzuia HTTP Header ya `X-Powered-By: PHP/...`.

---

### 5. AKAUNTI ZA WATUMIAJI NA UTAMBUZI (USERS & RBAC)

- [ ] **Akaunti ya Awali ya Mmiliki:**
  - Badilisha nenosiri chaguomsingi la `admin` / Mmiliki mara moja kabla ya kuanza kazi.
  - Thibitisha kuwa kila mfanyakazi (Cashier, Storekeeper, Accountant) ana akaunti yake binafsi yenye jina na nenosiri lake, na hakuna akaunti inayoshirikishwa kwa watu wengi.
- [ ] **Udhibiti wa Majukumu:**
  - Thibitisha kuwa Cashier hana ufikiaji wa taarifa za bei za ununuzi (Buying Cost), Faida Ghafi (Gross Profit), Matumizi (Expenses), au Uidhinishaji wa Marejesho/Bei.
- [ ] **Kinga ya Mmiliki Pekee:**
  - Thibitisha kuwa mfumo unazuia kumzima (deactivate) au kumfutia haki mmiliki pekee aliyebaki (Safeguard active).

---

### 6. NAKALA YA DATA NA MAJARIBIO YA KUREJESHA (BACKUP & DISASTER RECOVERY)

- [ ] **Ratiba ya Nakala za Kila Siku (Daily Automated Backups):**
  - Washa mfumo wa nakala ya database (mysqldump) unaojiendesha kila siku usiku (Cron Job) na kuhifadhi kwenye hifadhi salama tofauti na seva kuu.
- [ ] **Uthibitisho wa Kurejesha Data (Restore Test):**
  - Fanya jaribio la kimatendo la kurejesha nakala hiyo (mock restore) kwenye mazingira ya majaribio ili kuthibitisha kuwa data inarudi bila kuharibika (integrity verified).
- [ ] **Ulinzi wa Nakala:**
  - Mafaili ya nakala (dump files) yasiwekwe kamwe ndani ya saraka ya wazi ya mtandao (`public_html` au web root).

---

### 7. KUMBUKUMBU ZA UKAGUZI NA KUKABILIANA NA BRUTE FORCE

- [ ] **Ukaguzi wa Audit Trail:**
  - Mfumo unarekodi vitendo vyote muhimu (Login, Logout, Mauzo, Malipo, Punguzo, Bei za chini, Kughairi, na Marejesho) pamoja na mtumiaji, saa, na IP Address.
  - Kumbukumbu za ukaguzi hazina uwezo wa kufutwa au kubadilishwa (Immutable Append-Only).
- [ ] **Kuzuia Majaribio Mengi ya Kuingia (Brute-force Rate Limiting):**
  - Mfumo unafungia kwa muda wa dakika 3 mtumiaji anayeingiza nywila zisizo sahihi zaidi ya mara 5 mfululizo.

---

### 8. MAWASILIANO NA MAJUKUMU YA USALAMA

- **Msimamizi Mkuu:** Mmiliki wa Biashara / Meneja Mkuu
- **Mhandisi wa IT / Seva:** SysAdmin Mteule
- **Mkoa/Nchi:** Dar es Salaam, Tanzania
- **Tarehe ya Ukaguzi wa Mwisho:** Septemba 2026
