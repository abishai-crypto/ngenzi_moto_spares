# MWONGOZO WA DUKA LA MTANDAONI NA USHIRIKIANO WA MIFUMO (PHASE 16 E-COMMERCE GUIDE)

## Ngenzi Motorcycle & Bajaj Spare Parts Management System — Kariakoo, Dar es Salaam

---

### 1. UTANGULIZI NA MALENGO YA PHASE 16

Mfumo wa Ngenzi Motorcycle & Bajaj Spare Parts umeboreshwa kutoka kwenye mfumo wa usimamizi wa ndani pekee (ERP/POS) na kuwa jukwaa jumuishi la **Rejareja Dukani + Duka la Mtandaoni (Retail + E-Commerce Platform)**.

Lengo kuu la muundo huu ni kuhakikisha:

1. **Hakuna kurudia data (Single Source of Truth):** Vipuri, stoo, mauzo, malipo na ripoti za faida vinatumia database moja na hesabu zile zile.
2. **Kutenganisha Usalama wa Wateja na Wafanyakazi (Strict RBAC & Customer Session Isolation):** Wateja wa mtandaoni hawawezi kamwe kuona bei za kununulia (buying price), faida ya duka (margin), kumbukumbu za ndani (audit trail), au kufikia kurasa za usimamizi wa ndani.
3. **Kuzuia Kuuza Zaidi ya Stoo (Anti-Overselling Concurrency Protection):** Wakati mteja wa mtandaoni na mweka hazina wa kaunta (POS) wananunua kipuri kile kile kwa wakati mmoja, mfumo unatumia `SELECT ... FOR UPDATE` (row locking) kuzuia stoo kuwa hasi.

---

### 2. ANWANI ZA DUKA LA MTANDAONI (STOREFRONT & PORTAL URLS)

| Ukurasa | Njia ya Mfumo (Route) | Maelezo |
| --- | --- | --- |
| **Ukurasa wa Mwanzo** | `/index.php?route=store` au `/` | Ukurasa mkuu wa umma wenye vipuri vilivyoangaziwa, makundi, na orodha ya vyombo. |
| **Katalogi ya Vipuri (Shop)** | `/index.php?route=shop` | Katalogi kamili yenye uchujaji wa Kategoria, Chapa, Chombo, Bei na Stoo. |
| **Ukurasa wa Kipuri (Detail)** | `/index.php?route=product_detail&id={id}` | Maelezo kamili ya kipuri, utangamano wa vyombo, picha, bei, na maoni ya wanunuzi. |
| **Tafuta kwa Chombo** | `/index.php?route=store_vehicle` | Kivinjari cha utangamano wa pikipiki (Boxer, TVS, SanLG, Honda) na bajaji (TVS King, RE 4S). |
| **Kapu la Ununuzi (Cart)** | `/index.php?route=cart` | Kapu la kidijitali lenye hesabu halisi ya bei, ada ya usafirishaji, na uhakiki wa stoo. |
| **Kamilisha Agizo (Checkout)** | `/index.php?route=checkout` | Uchaguzi wa anwani ya uwasilishaji, njia ya malipo, na kuthibitisha agizo. |
| **Kuingia kwa Mteja (Login)** | `/index.php?route=customer_login` | Kuingia kwa wateja waliothibitishwa kupitia Barua Pepe au Namba ya Simu. |
| **Usajili wa Mteja (Register)** | `/index.php?route=customer_register` | Usajili mpya wa mteja wenye ulinzi wa `password_hash()` na kuzuia namba/barua pepe rudufu. |
| **Dashibodi ya Mteja (Portal)** | `/index.php?route=customer_dashboard` | Muhtasari wa maagizo, anwani, taarifa za akaunti, orodha ya matamanio na arifa. |
| **Maagizo Yangu** | `/index.php?route=customer_orders` | Orodha ya maagizo yote ya mteja, hali ya sasa, na viungo vya ankara. |
| **Ufuatiliaji wa Agizo** | `/index.php?route=customer_order_show&id={id}` | Mchakato wa hatua kwa hatua wa agizo (`PENDING` -> `PROCESSING` -> `OUT_FOR_DELIVERY` -> `DELIVERED`). |
| **Kuchapisha Ankara** | `/index.php?route=customer_order_invoice&id={id}` | Ankara rasmi ya kidijitali inayoweza kupakuliwa na kuchapishwa na mteja. |
| **Kuomba Marejesho** | `/index.php?route=customer_return_create&order_id={id}` | Fomu ya mteja kuomba kurejesha kipuri kwa maagizo yaliyowasilishwa ndani ya siku 3. |
| **Orodha ya Matamanio (Wishlist)** | `/index.php?route=customer_wishlist` | Vipuri vilivyohifadhiwa na mteja kwa ajili ya kununua baadaye. |
| **Kituo cha Arifa** | `/index.php?route=customer_notifications` | Arifa za ndani ya mfumo kuhusu mabadiliko ya maagizo na malipo. |

---

### 3. USALAMA WA KIKAO NA RBAC ISOLATION

#### Tofauti Kati ya Vikao (Sessions)

1. **Kikao cha Mfanyakazi (`Auth::check()`):**
   - Kinatumia `$_SESSION['user_id']` na kinahusika na wafanyakazi wa duka (Super Admin, Manager, Cashier, Storekeeper, Accountant).
   - Wafanyakazi wanaweza kufikia dashibodi ya biashara, POS, manunuzi, ripoti za faida, na ukaguzi wa ndani kulingana na majukumu yao.
2. **Kikao cha Mteja (`Auth::isCustomer()`):**
   - Kinatumia `$_SESSION['customer_id']` na kinatengwa kabisa na `$_SESSION['user_id']`.
   - `Auth::check()` inarudisha `false` kwa kikao cha mteja.
   - Wateja hawawezi kamwe kupata idhini za ndani (`Auth::can()` daima inarudisha `false`).
   - Wateja wamezuiwa kutazama bei ya kununulia (buying price), gharama za uendeshaji (COGS), au maagizo ya mteja mwingine yeyote.

---

### 4. USANIFU WA MALIPO NA HALI YA WATOA HUDUMA (PAYMENT GATEWAYS)

Mfumo umejengwa kwa kutumia muundo sanifu wa kiolesura cha malipo (`PaymentGatewayInterface`) na msimamizi wa malipo (`PaymentManager`) ili kuhakikisha malipo ya mtandaoni yanafanyika kwa usahihi na bila kurudiwa (`idempotency_key`).

#### Hali ya Watoa Huduma wa Malipo (Provider Readiness Statuses)

| Njia ya Malipo | Watoa Huduma | Hali ya Sasa | Maelezo ya Uendeshaji |
| --- | --- | --- | --- |
| **Mobile Money** | M-Pesa, Tigo Pesa, Airtel Money, Halopesa | **`READY` (Mfumo wa Lipa Namba)** — *API Push: `NOT CONFIGURED`* | Wateja wanalipa kwa namba rasmi za Lipa Namba za biashara zilizosanidiwa kwenye mfumo. Mfanyakazi anathibitisha muamala kwenye mfumo kupitia namba ya kumbukumbu. |
| **Uhamisho wa Benki** | CRDB, NMB, NBC | **`READY` (Maelekezo ya Benki)** | Mteja anapewa maelekezo ya akaunti rasmi ya benki na kuweka namba ya kumbukumbu ya benki wakati wa kuagiza. |
| **Kadi za Benki** | Visa, Mastercard | **`NOT CONFIGURED`** | Moduli ya kadi imesanidiwa kiusanifu lakini haijaunganishwa na API ya malipo ya benki moja kwa moja kwa sababu hakuna vitambulisho vya uzalishaji (Live API Keys). |

#### Jinsi ya Kuweka API Keys Rasmi (Pindi Zitakapopatikana)

Fungua faili la `.env` kwenye seva na ujaze vigezo vifuatavyo:

```env
# Malipo ya Mtandao (Live Payment Gateways)
PAYMENT_GATEWAY_ENV=production
PAYMENT_MERCHANT_ID=WEKA_MERCHANT_ID_HAPA
PAYMENT_API_KEY=WEKA_API_KEY_HAPA
PAYMENT_PUBLIC_KEY=WEKA_PUBLIC_KEY_HAPA
PAYMENT_CALLBACK_SECRET=WEKA_WEBHOOK_SECRET_HAPA
```

---

### 5. USAFIRISHAJI NA HATUA ZA AGIZO (DELIVERY WORKFLOW)

Kila agizo la mtandaoni linapopokelewa, mfumo unatengeneza rekodi ya usafirishaji yenye namba maalum (`DEL-XXXXXX`).

#### Hatua za Mzigo (Lifecycle Stages)

1. **PENDING:** Agizo limewekwa na linasubiri uthibitisho wa malipo au maandalizi.
2. **ASSIGNED:** Mzigo umefungashwa na umekabidhiwa kwa dereva bodaboda au gari la duka.
3. **OUT_FOR_DELIVERY:** Dereva yupo njiani kuelekea kwa mteja (mteja anapokea arifa kwenye akaunti yake).
4. **DELIVERED:** Mteja amepokea mzigo na kuthibitisha.

Kila badiliko la hali linahifadhiwa kwenye jedwali la historia (`delivery_status_history`) pamoja na jina la mfanyakazi aliyebadilisha, maelezo, na muda halisi.

---

### 6. UHUSIANO WA MAUZO YA MTANDAONI NA HESABU ZA DUKA (SALES & COGS)

Wakati agizo la mtandaoni linapolipwa na kuthibitishwa:

1. Mfumo unaita `OnlineOrder::integrateWithSales($orderId)`.
2. Mauzo yanarekodiwa kwenye jedwali la `sales` lenye:
   - `sales_channel = 'ONLINE'`
   - `invoice_no = 'NGZ-INV-XXXXXX'`
   - `online_order_id = $orderId`
3. Vipuri vya agizo vinarekodiwa kwenye `sale_items` vikiwa na `unit_cost` (COGS ya ununuzi) na `unit_price` (bei ya kuuzia).
4. Malipo yanarekodiwa kwenye `sale_payments` yakiwa na namba ya kipekee ya malipo (`PAY-ONL-XXXXXX`).
5. **Faida Ghafi (Gross Profit):** Mfumo unatumia fomula ile ile ya dukani: `Gross Profit = Net Revenue - Total COGS`.
6. Taarifa za mauzo ya mtandaoni zinaonekana moja kwa moja kwenye Ripoti za Mauzo (`ReportController`), Faida na Hasara (`FinancialController`), na Dashibodi ya Mtendaji Mkuu (`DashboardController`) bila haja ya kazi ya ziada ya kuhamisha data.

---

### 7. SHERIA ZA BIASHARA NA KANUNI ZA MAREJESHO (RETURN POLICY)

- **Muda wa Kurejesha:** Wateja wa mtandaoni wanaruhusiwa kuomba marejesho ndani ya siku tatu (3) tangu siku mzigo ulipowasilishwa (`DELIVERED`).
- **Uthibitisho wa Ununuzi (Verified Purchases):** Ni wateja waliokamilisha ununuzi wa kipuri ndio wanaoruhusiwa kutoa tathmini ya nyota (Ratings) na maoni (Reviews) ili kulinda sifa na uhalisia wa duka.
- **Kughairi Agizo (Order Cancellation):** Mteja anaweza kughairi agizo moja kwa moja iwapo tu agizo hilo bado lipo kwenye hali ya `PENDING` au `PAYMENT_PENDING`. Ikiwa mzigo umeshaanza kuchakatwa au kuwasilishwa, hauwezi kughairiwa moja kwa moja bila idhini ya meneja.
