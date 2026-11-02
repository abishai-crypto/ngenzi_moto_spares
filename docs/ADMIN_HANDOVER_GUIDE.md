# MWONGOZO WA MAKABIDHIANO YA USIMAMIZI WA MFUMO (ADMIN HANDOVER GUIDE)

## Ngenzi Moto Spare Parts — Kariakoo, Dar es Salaam

**Kwa Ajili ya:** Mmiliki wa Biashara, Mameneja na Wasimamizi Wakuu wa Mfumo

---

### 1. KUINGIA KWA MARA YA KWANZA NA USALAMA WA AKAUNTI

1. **Anwani ya Kuingilia:** `http://localhost:8000/index.php?route=login` (au anwani ya seva ya duka).
2. **Kubadilisha Nenosiri:**
   - Mara tu unapoingia mara ya kwanza na akaunti ya `admin`, nenda kwenye **Wafanyakazi & Akaunti** (`/index.php?route=users`).
   - Badilisha nenosiri na uweke nenosiri gumu lenye herufi kubwa, ndogo, namba na alama maalum (angalau herufi 10).
3. **Muda wa Kikao (Session Timeout):** Mfumo unamtoa mtumiaji nje kiotomatiki baada ya dakika 30 bila shughuli (inactivity) ili kulinda fedha na data.

---

### 2. USIMAMIZI WA MAJUKUMU NA WATUMIAJI (RBAC)

Mfumo una majukumu matano (5) makuu yenye mipaka madhubuti:

| Jukumu (Role) | Kazi Zilizoidhinishwa | Mipaka ya Usalama (Vizuizi) |
| --- | --- | --- |
| **Super Admin (Mmiliki)** | Udhibiti kamili wa mfumo, watumiaji, mipangilio, faida, ripoti, nakala za database na ukaguzi. | Hakuna. |
| **Manager (Meneja)** | Usimamizi wa shughuli za kila siku za duka, idhini ya marejesho, kufuta ankara zenye dosari, kuona ripoti za hesabu. | Hawezi kufuta watumiaji, kubadilisha mfumo mzima au kupakua nakala za database. |
| **Cashier (Mweka Hazina)** | Kuuza kwenye POS, kutoa risiti, kupokea malipo (Pesa taslimu, M-Pesa, Benki), kuomba marejesho ya bidhaa. | **Haoni bei ya ununuzi**, **haoni faida ghafi**, hawezi kubadilisha bei chini ya kiwango, hawezi kuidhinisha marejesho bila idhini ya meneja. |
| **Storekeeper (Mtunza Stoo)** | Kupokea manunuzi ya vipuri, kuhesabu mali (Stock Taking), kurekodi marejesho ya bidhaa salama stoo. | Hawezi kufanya mauzo, hawezi kuona taarifa za mapato ya fedha au faida. |
| **Accountant (Mhasibu)** | Kurekodi matumizi (Expenses), kufuatilia madeni ya wateja (Debts/Ledger), malipo ya wasambazaji, kuangalia ripoti ya Faida na Hasara (P&L). | Hawezi kubadilisha stoo moja kwa moja bila kumbukumbu rasmi. |

---

### 3. MTIRIRIKO WA SHUGHULI ZA KILA SIKU (DAILY OPERATIONS)

#### A. Kusajili na Kupokea Vipuri (Purchases & Inventory)

1. Nenda **Manunuzi** -> **Agizo Jipya (New Purchase)**.
2. Chagua Msambazaji, jaza vipuri, idadi na bei ya ununuzi kutoka kiwandani.
3. Bidhaa zikifika dukani, mtunza stoo anathibitisha idadi kwenye **Receive Stock**. Stoo inaongezeka kiotomatiki na bei ya wastani (COGS) inahuishwa.

#### B. Mauzo ya Kaunta (POS / Cashier)

1. Fungua **POS (Point of Sale)**.
2. Tafuta kipuri kwa Jina, SKU, au mtindo wa chombo (mfano: *Boxer BM150, TVS King, Bajaj RE 4S*).
3. Ongeza kwenye kikapu -> Chagua mteja (au Mteja wa Kawaida wa Matembezi).
4. Chagua njia ya malipo: **Pesa Taslimu (Cash)**, **M-Pesa / TigoPesa / Airtel Money**, au **Benki (NMB/CRDB)**.
5. Bonyeza **Kamilisha Mauzo** -> Chapisha Risiti ya POS (Thermal 80mm).

#### C. Mauzo ya Mkopo na Malipo ya Awamu (Customer Credit & Debts)

1. Kwenye POS, weka mauzo kama mkopo kwa kuchagua mteja mwenye kikomo cha mkopo.
2. Mteja akija kulipa deni, nenda **Madeni ya Wateja** -> Tafuta mteja -> Bonyeza **Rekodi Malipo (Record Payment)**.
3. Ankara inafungwa na salio la mteja kwenye Ledger linapungua mara moja.

#### D. Marejesho ya Bidhaa (Returns & Refunds)

1. Mteja akirudisha kipuri chenye hitilafu au kibovu: Nenda **Marejesho (Returns)** -> **Rejesha Bidhaa**.
2. Weka namba ya ankara/risiti ya awali.
3. Chagua kipuri, idadi, sababu ya kurudisha na hali ya kipuri:
   - **Kinafaa Kuuzwa (Resellable):** Kitarudishwa kwenye stoo kiotomatiki.
   - **Kimeharibika (Damaged/Defective):** Hakitarudishwa kwenye stoo inayouzwa.
4. Meneja anathibitisha na kuidhinisha marejesho.

---

### 4. USIMAMIZI WA NAKALA ZA DATA (BACKUP & RESTORE)

1. **Kuunda Nakala:**
   - Kila jioni kabla ya kufunga duka, Super Admin aingie **Hifadhi Data (Backups)** -> Bonyeza **Tengeneza Nakala Sasa**.
   - Faili linatengenezwa chini ya sekunde 2 (`spare_parts_backup_YYYY-MM-DD_HH-MM-SS.sql`).
2. **Kupakua na Kuhifadhi Nje:**
   - Bonyeza **Pakua (Download)** kwenye nakala mpya zaidi.
   - Iweke kwenye Flash Drive au seva ya nje ya Google Drive.
3. **Kupima Urejesho (Verify Restore):**
   - Bofya **Pima Urejesho** kwenye faili la nakala.
   - Mfumo utaunda database ya majaribio ya muda (`ngenzi_restore_test`), kupakia faili hilo, kuthibitisha kuwa majedwali yote 34 na rekodi zote ziko sawa, kisha kuifuta database hiyo ya majaribio kwa usalama.

---

### 5. UKAGUZI WA USALAMA NA KUMBUKUMBU (AUDIT TRAIL)

- Kila tendo linalofanyika kwenye mfumo (Kuingia, Kutoka, Kufuta, Kurekebisha bei, Kutoa punguzo, Kurekebisha stoo) linarekodiwa kwenye **Audit Trail (Kumbukumbu)**.
- Kumbukumbu hizo haziwezi kufutwa wala kurekebishwa na mfanyakazi yeyote.

---

### 6. USIMAMIZI WA DUKA LA MTANDAONI (E-COMMERCE & ONLINE STORE MANAGEMENT — PHASE 16)

Mfumo sasa umeunganishwa moja kwa moja na Duka la Mtandaoni (Retail + E-Commerce Platform) linaloshiriki bidhaa, stoo na ripoti zile zile bila mgongano.

#### A. Kuingia na Dashibodi ya E-Commerce

- **Kwenye Menyu ya Kushoto:** Bofya **Duka la Mtandaoni** -> **Dashibodi ya Mtandaoni** (`/index.php?route=online_dashboard`).
- Dashibodi hii inaonyesha: Idadi ya maagizo mapya yanayosubiri kuchakatwa, maagizo yanayosubiri uthibitisho wa malipo, mizigo iliyo safarini na mapato ya mtandaoni.

#### B. Kuchakata Maagizo ya Mtandaoni (Online Order Workflow)

1. **Agizo Jipya Linapoingia:**
   - Nenda **Maagizo ya Mtandaoni** (`/index.php?route=online_orders`).
   - Bofya agizo linaloanza na `ORD-XXXXXX` ili kufungua maelezo yake kamili.
2. **Kuhakiki Malipo ya Lipa Namba au Benki:**
   - Ikiwa mteja alilipa kwa Lipa Namba (M-Pesa, Tigo Pesa, n.k.) au Benki, hakiki namba ya kumbukumbu (Transaction Reference) kwenye simu ya duka au akaunti ya benki.
   - Bofya **Thibitisha Malipo (Confirm Payment)** na uweke namba ya kumbukumbu ya muamala.
   - Mara tu malipo yanapothibitishwa:
     - Hali ya agizo inabadilika kuwa `PAID`.
     - Mauzo yanarekodiwa kiotomatiki kwenye jedwali kuu la mauzo (`sales`) kama `ONLINE` na ankara rasmi (`NGZ-INV-XXXXXX`) inatengenezwa bila kulazimika kuingiza upya.
     - Stoo inakatwa kiotomatiki na gharama ya ununuzi (COGS) inahuishwa kwa usahihi.
3. **Kufungasha na Kutuma Mzigo (Delivery Dispatch):**
   - Agizo likiwa tayari, badilisha hali kuwa `PROCESSING` kisha `OUT_FOR_DELIVERY`.
   - Nenda **Usafirishaji & Mizigo** (`/index.php?route=online_deliveries`), mpe dereva bodaboda au gari namba ya ufuatiliaji (`DEL-XXXXXX`).
   - Mteja akipokea mzigo, badilisha hali kuwa `DELIVERED`.

#### C. Kushughulikia Maombi ya Marejesho ya Mtandaoni (Returns)

- Mteja akiomba kurejesha kipuri kupitia akaunti yake ya mtandaoni, ombi linaingia kiotomatiki kwenye mfumo wa kawaida wa **Marejesho** (`/index.php?route=returns`) likiwa na hali `requested`.
- Meneja au Msimamizi anakagua sababu, anathibitisha na kutoa idhini ya kurejeshewa fedha au kipuri mbadala kulingana na Sera ya Marejesho ya Siku 3.

#### D. Mipangilio ya Duka la Mtandaoni

- Nenda **Duka la Mtandaoni** -> **Mipangilio ya Duka** (`/index.php?route=online_settings`).
- Hapa unaweza:
  - Kuwasha au Kuzima duka kwa muda mfano wakati wa hesabu ya mwisho wa mwaka (`online_store_enabled`).
  - Kubadilisha gharama ya usafirishaji wa kawaida Kariakoo/Dar es Salaam (`delivery_flat_rate`).
  - Kuweka kiwango cha ununuzi wa kupata usafirishaji wa bure (`free_delivery_threshold`).
  - Kusasisha namba rasmi za Lipa Namba za M-Pesa, Tigo Pesa, Airtel Money na akaunti za Benki.
