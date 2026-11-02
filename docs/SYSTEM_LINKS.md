# ORODHA YA VIUNGO NA ANWANI ZA MFUMO (SYSTEM URLS & ROUTES DIRECTORY)

## Ngenzi Moto Spare Parts — Kariakoo, Dar es Salaam

**Seva ya Ndani / Mtandao:** `http://localhost:8000` (au anwani ya seva yako ya duka/domain)  
**Database Name:** `ngenzi_moto_spare`  
**Mfumo Mkuu:** Rejareja Dukani + Duka la Mtandaoni (Retail ERP + E-Commerce Platform)  

---

### 1. DUKA LA MTANDAONI (PUBLIC STOREFRONT — WATEJA NA UMMA)

Viungo hivi vinafikiwa na umma na wateja wote bila kuhitaji akaunti ya mfanyakazi wa ndani.

| Ukurasa | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Ukurasa wa Mwanzo (Home)** | `store` au `home` au `/` | [http://localhost:8000/](http://localhost:8000/) | Ukurasa mkuu wenye matangazo, kategoria, vipuri vilivyochaguliwa na vichungi vya vyombo. |
| **Katalogi ya Vipuri (Shop)** | `shop` | [http://localhost:8000/index.php?route=shop](http://localhost:8000/index.php?route=shop) | Katalogi kamili yenye uchujaji wa Kategoria, Chapa, Pikipiki/Bajaji, Bei na Stoo. |
| **Tafuta kwa Chombo** | `store_vehicle` | [http://localhost:8000/index.php?route=store_vehicle](http://localhost:8000/index.php?route=store_vehicle) | Tafuta vipuri vinavyoendana na Boxer BM150, TVS King, Bajaj RE 4S, Haojue, n.k. |
| **Maelezo ya Kipuri (Detail)** | `product_detail` | [http://localhost:8000/index.php?route=product_detail&id=1](http://localhost:8000/index.php?route=product_detail&id=1) | Picha, bei ya kuuzia, utangamano wa vyombo, maelezo, na kitufe cha Kuongeza Kikapuni. |
| **Kapu la Ununuzi (Cart)** | `cart` | [http://localhost:8000/index.php?route=cart](http://localhost:8000/index.php?route=cart) | Angalia vipuri vilivyomo kwenye kapu, badilisha idadi, au ondoa bidhaa. |
| **Kamilisha Agizo (Checkout)** | `checkout` | [http://localhost:8000/index.php?route=checkout](http://localhost:8000/index.php?route=checkout) | Weka anwani ya kupelekewa, chagua njia ya malipo (Lipa Namba/Benki/Kadi), na thibitisha agizo. |
| **Vigezo na Masharti** | `terms` | [http://localhost:8000/index.php?route=terms](http://localhost:8000/index.php?route=terms) | Mwongozo wa kisheria na vigezo vya matumizi ya huduma ya duka mtandaoni. |
| **Sera ya Faragha** | `privacy` | [http://localhost:8000/index.php?route=privacy](http://localhost:8000/index.php?route=privacy) | Sera ya ulinzi wa taarifa binafsi za wateja na mawasiliano yao. |
| **Sera ya Marejesho** | `return_policy` | [http://localhost:8000/index.php?route=return_policy](http://localhost:8000/index.php?route=return_policy) | Mwongozo rasmi wa kurejesha bidhaa ndani ya siku 3 tangu kuwasilishwa. |

---

### 2. UTAMBUZI NA AKAUNTI YA MTEJA (CUSTOMER AUTH & PORTAL)

Viungo vya wateja waliothibitishwa kwa ajili ya kufuatilia maagizo yao, anwani na orodha ya matamanio.

| Ukurasa | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Kuingia kwa Mteja (Login)** | `customer_login` | [http://localhost:8000/index.php?route=customer_login](http://localhost:8000/index.php?route=customer_login) | Fomu ya mteja kuingia kwa Barua Pepe au Namba ya Simu. |
| **Kujisajili Mteja Mpya** | `customer_register` | [http://localhost:8000/index.php?route=customer_register](http://localhost:8000/index.php?route=customer_register) | Usajili wa mteja mpya mtandaoni. |
| **Kutoka kwa Mteja (Logout)** | `customer_logout` | [http://localhost:8000/index.php?route=customer_logout](http://localhost:8000/index.php?route=customer_logout) | Funga kikao cha mteja kwa usalama. |
| **Dashibodi ya Mteja** | `customer_dashboard` | [http://localhost:8000/index.php?route=customer_dashboard](http://localhost:8000/index.php?route=customer_dashboard) | Muhtasari wa maagizo, arifa, anwani na vipuri vilivyohifadhiwa. |
| **Profaili ya Mteja** | `customer_profile` | [http://localhost:8000/index.php?route=customer_profile](http://localhost:8000/index.php?route=customer_profile) | Sasisha jina, simu, barua pepe, na nenosiri la akaunti. |
| **Daftari la Anwani (Addresses)** | `customer_addresses` | [http://localhost:8000/index.php?route=customer_addresses](http://localhost:8000/index.php?route=customer_addresses) | Ongeza na dhibiti anwani za ofisini, gereji au nyumbani (pamoja na ramani/GPS). |
| **Maagizo Yangu (My Orders)** | `customer_orders` | [http://localhost:8000/index.php?route=customer_orders](http://localhost:8000/index.php?route=customer_orders) | Orodha ya maagizo yote yaliyowekwa na hali zake. |
| **Ufuatiliaji wa Agizo** | `customer_order_show` | [http://localhost:8000/index.php?route=customer_order_show&id=1](http://localhost:8000/index.php?route=customer_order_show&id=1) | Hatua za agizo (`PENDING` -> `PROCESSING` -> `OUT_FOR_DELIVERY` -> `DELIVERED`). |
| **Chapisha Ankara (Invoice)** | `customer_order_invoice` | [http://localhost:8000/index.php?route=customer_order_invoice&id=1](http://localhost:8000/index.php?route=customer_order_invoice&id=1) | Ankara rasmi ya agizo la mtandaoni inayoweza kuchapishwa au kupakuliwa. |
| **Omba Marejesho ya Mtandaoni** | `customer_return_create` | [http://localhost:8000/index.php?route=customer_return_create&order_id=1](http://localhost:8000/index.php?route=customer_return_create&order_id=1) | Fomu ya kuomba kurejesha kipuri kilichowasilishwa ndani ya siku 3. |
| **Orodha ya Matamanio (Wishlist)** | `customer_wishlist` | [http://localhost:8000/index.php?route=customer_wishlist](http://localhost:8000/index.php?route=customer_wishlist) | Vipuri vilivyohifadhiwa na mteja kwa ajili ya kununua baadaye. |
| **Kituo cha Arifa** | `customer_notifications` | [http://localhost:8000/index.php?route=customer_notifications](http://localhost:8000/index.php?route=customer_notifications) | Taarifa za mabadiliko ya maagizo, usafirishaji na malipo. |

---

### 3. USIMAMIZI WA DUKA LA MTANDAONI (STAFF E-COMMERCE MANAGEMENT)

Viungo vya wasimamizi na mameneja wa duka kwa ajili ya kuchakata maagizo ya mtandaoni na usafirishaji.

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Dashibodi ya Mtandaoni** | `online_dashboard` | [http://localhost:8000/index.php?route=online_dashboard](http://localhost:8000/index.php?route=online_dashboard) | Muhtasari wa maagizo mapya, yanayosubiri malipo, na mapato ya mtandaoni. |
| **Maagizo ya Mtandaoni** | `online_orders` | [http://localhost:8000/index.php?route=online_orders](http://localhost:8000/index.php?route=online_orders) | Orodha kamili ya maagizo ya wateja wa mtandaoni. |
| **Tazama na Chakata Agizo** | `online_order_show` | [http://localhost:8000/index.php?route=online_order_show&id=1](http://localhost:8000/index.php?route=online_order_show&id=1) | Thibitisha malipo, badilisha hadhi ya agizo, na chapisha stakabadhi ya kufungashia. |
| **Mizigo & Usafirishaji** | `online_deliveries` | [http://localhost:8000/index.php?route=online_deliveries](http://localhost:8000/index.php?route=online_deliveries) | Panga madereva (Bolt/Ngenzi/Msafirishaji wa Mteja/Mabasi) na fuatilia hatua za uwasilishaji. |
| **Uvumbuzi wa Mahitaji (Demand)** | `online_demand` | [http://localhost:8000/index.php?route=online_demand](http://localhost:8000/index.php?route=online_demand) | Dashibodi ya mmiliki ya kutambua mahitaji ya wateja (Searches/Inquiries) na kubadilisha katalogi kuwa stoo halisi. |
| **Wateja wa Mtandaoni** | `online_customers` | [http://localhost:8000/index.php?route=online_customers](http://localhost:8000/index.php?route=online_customers) | Orodha ya akaunti za wateja wa mtandaoni na idadi ya maagizo yao. |
| **Mipangilio ya Mtandaoni** | `online_settings` | [http://localhost:8000/index.php?route=online_settings](http://localhost:8000/index.php?route=online_settings) | Sanidi uchaguzi wa Bolt, usafirishaji wa duka, viwango vya bure, maelekezo ya kuchukua dukani, na siri ya namba za stoo. |

---

### 4. USIMAMIZI WA NDANI, KAUNTA NA POS (INTERNAL STAFF ERP & POS)

Viungo vya shughuli za kila siku za duka la Kariakoo (Wamiliki, Mameneja, Makasisi, Watunza Stoo na Wahasibu).

#### A. Kuingia na Dashibodi Kuu (Auth & Main Dashboard)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Kuingia kwa Mfanyakazi (Staff Login)** | `login` | [http://localhost:8000/index.php?route=login](http://localhost:8000/index.php?route=login) | Kuingia kwa Super Admin, Meneja, Cashier, Storekeeper au Accountant. |
| **Kutoka kwa Mfanyakazi (Logout)** | `logout` | [http://localhost:8000/index.php?route=logout](http://localhost:8000/index.php?route=logout) | Toka kwenye mfumo wa wafanyakazi. |
| **Badilisha Nenosiri** | `change_password` | [http://localhost:8000/index.php?route=change_password](http://localhost:8000/index.php?route=change_password) | Sasisha nenosiri la akaunti ya sasa. |
| **Dashibodi Kuu ya Mfumo** | `dashboard` | [http://localhost:8000/index.php?route=dashboard](http://localhost:8000/index.php?route=dashboard) | Takwimu za mauzo, stoo ya chini, faida, na muhtasari wa biashara. |

#### B. Mauzo na Mashine ya Kaunta (POS & Sales)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Mashine ya Mauzo (POS)** | `pos` | [http://localhost:8000/index.php?route=pos](http://localhost:8000/index.php?route=pos) | Kiolesura chenye kasi cha mauzo ya kaunta, skana ya barcode na risiti za 80mm. |
| **Historia ya Mauzo (Sales)** | `sales` | [http://localhost:8000/index.php?route=sales](http://localhost:8000/index.php?route=sales) | Orodha ya ankara zote za mauzo (Dukani na Mtandaoni). |
| **Tazama Mauzo (Sale Detail)** | `sale_show` | [http://localhost:8000/index.php?route=sale_show&id=1](http://localhost:8000/index.php?route=sale_show&id=1) | Maelezo kamili ya muamala wa mauzo na bidhaa zilizouzwa. |
| **Mauzo ya Siku (Daily Summary)** | `sales_daily_summary` | [http://localhost:8000/index.php?route=sales_daily_summary](http://localhost:8000/index.php?route=sales_daily_summary) | Muhtasari wa mauzo, faida na idadi ya wateja wa siku ya leo. |

#### C. Malipo na Usimamizi wa Madeni (Payments & Debts)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Orodha ya Malipo** | `payments` | [http://localhost:8000/index.php?route=payments](http://localhost:8000/index.php?route=payments) | Malipo yote yaliyopokelewa kwa Pesa Taslimu, M-Pesa, Benki, n.k. |
| **Makusanyo ya Siku** | `payments_daily_collection` | [http://localhost:8000/index.php?route=payments_daily_collection](http://localhost:8000/index.php?route=payments_daily_collection) | Ripoti ya fedha zilizoingia kaunta leo kulingana na njia za malipo. |
| **Madeni ya Wateja** | `debts` | [http://localhost:8000/index.php?route=debts](http://localhost:8000/index.php?route=debts) | Orodha ya wateja wenye madeni na salio wanalodaiwa. |
| **Uzeekaji wa Madeni (Debt Aging)** | `debt_aging` | [http://localhost:8000/index.php?route=debt_aging](http://localhost:8000/index.php?route=debt_aging) | Uchambuzi wa madeni kulingana na siku (Siku 1-30, 31-60, 61-90, 90+). |
| **Kusanya Deni la Mteja** | `debt_collect` | [http://localhost:8000/index.php?route=debt_collect](http://localhost:8000/index.php?route=debt_collect) | Fomu ya kupokea malipo ya deni na kupunguza salio la mteja. |

#### D. Katalogi ya Vipuri & Vyombo (Products & Catalog)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Katalogi ya Vipuri** | `products` | [http://localhost:8000/index.php?route=products](http://localhost:8000/index.php?route=products) | Orodha kamili ya vipuri vyote, bei za kununua, kuuza na stoo. |
| **Sajili Kipuri Kipya** | `product_create` | [http://localhost:8000/index.php?route=product_create](http://localhost:8000/index.php?route=product_create) | Fomu ya kuingiza kipuri kipya na utangamano wake na vyombo. |
| **Tafuta Kipuri kwa Chombo** | `product_vehicle_search` | [http://localhost:8000/index.php?route=product_vehicle_search](http://localhost:8000/index.php?route=product_vehicle_search) | Chagua mfano wa pikipiki/bajaji kuona vipuri vyote vinavyoendana nacho. |
| **Kategoria za Vipuri** | `categories` | [http://localhost:8000/index.php?route=categories](http://localhost:8000/index.php?route=categories) | Usimamizi wa kategoria (Breki, Injini, Klachi, n.k.). |
| **Chapa za Vipuri (Brands)** | `brands` | [http://localhost:8000/index.php?route=brands](http://localhost:8000/index.php?route=brands) | Watengenezaji wa vipuri (Bajaj Genuine, TVS, Boxer, NGK, Bosch, n.k.). |
| **Vipimo (Units)** | `units` | [http://localhost:8000/index.php?route=units](http://localhost:8000/index.php?route=units) | Vipimo vya hesabu (Pcs, Set, Ltr, Box, Roll, n.k.). |
| **Vyombo (Pikipiki & Bajaji)** | `vehicles` | [http://localhost:8000/index.php?route=vehicles](http://localhost:8000/index.php?route=vehicles) | Orodha ya chapa na miundo ya vyombo vya usafiri. |

#### E. Usimamizi wa Stoo na Mali (Inventory & Stock)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Dashibodi ya Stoo** | `inventory_dashboard` | [http://localhost:8000/index.php?route=inventory_dashboard](http://localhost:8000/index.php?route=inventory_dashboard) | Muhtasari wa thamani ya stoo, bidhaa zilizopungua na mzunguko wa vipuri. |
| **Vipuri Vilivyopo Stoo** | `inventory` | [http://localhost:8000/index.php?route=inventory](http://localhost:8000/index.php?route=inventory) | Hali halisi ya idadi ya vipuri vilivyopo stoo sasa hivi. |
| **Mienendo ya Stoo (History)** | `inventory_history` | [http://localhost:8000/index.php?route=inventory_history](http://localhost:8000/index.php?route=inventory_history) | Kumbukumbu ya kila kipuri kilipoingia au kutoka stoo. |
| **Rekebisha Idadi ya Stoo** | `inventory_adjust` | [http://localhost:8000/index.php?route=inventory_adjust](http://localhost:8000/index.php?route=inventory_adjust) | Kurekebisha tofauti ya hesabu ya stoo au uharibifu. |
| **Hesabu ya Stoo (Stock Counts)** | `inventory_counts` | [http://localhost:8000/index.php?route=inventory_counts](http://localhost:8000/index.php?route=inventory_counts) | Orodha ya kaguzi za kimwili za mali ya stoo (Stock taking). |
| **Anzisha Ukaguzi Mpya** | `inventory_count_create` | [http://localhost:8000/index.php?route=inventory_count_create](http://localhost:8000/index.php?route=inventory_count_create) | Fomu ya kufanya ukaguzi wa stoo wa mara kwa mara. |
| **Thamani ya Mali (Valuation)** | `inventory_valuation` | [http://localhost:8000/index.php?route=inventory_valuation](http://localhost:8000/index.php?route=inventory_valuation) | Thamani ya jumla ya stoo kulingana na bei ya kununua na bei ya kuuza. |
| **Uchambuzi wa Mzunguko** | `inventory_analytics` | [http://localhost:8000/index.php?route=inventory_analytics](http://localhost:8000/index.php?route=inventory_analytics) | Uchambuzi wa vipuri vinavyoenda kwa kasi (Fast Moving) na vinavyolala. |
| **Mapendekezo ya Kuagiza** | `inventory_reorder` | [http://localhost:8000/index.php?route=inventory_reorder](http://localhost:8000/index.php?route=inventory_reorder) | Orodha ya vipuri vilivyo chini ya kiwango salama vinavyopaswa kuagizwa. |

#### F. Wateja, Mafundi & Gereji (Customers & Garages)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Dashibodi ya Wateja** | `customer_dashboard` | [http://localhost:8000/index.php?route=customer_dashboard](http://localhost:8000/index.php?route=customer_dashboard) | Takwimu za wateja wa duka na madeni yao (kwa wafanyakazi). |
| **Orodha ya Wateja** | `customers` | [http://localhost:8000/index.php?route=customers](http://localhost:8000/index.php?route=customers) | Wateja wote, viwango vya mikopo na anwani zao. |
| **Sajili Mteja Mpya** | `customer_create` | [http://localhost:8000/index.php?route=customer_create](http://localhost:8000/index.php?route=customer_create) | Fomu ya kuingiza mteja mpya wa kaunta. |
| **Mafundi (Mechanics)** | `mechanics` | [http://localhost:8000/index.php?route=mechanics](http://localhost:8000/index.php?route=mechanics) | Orodha ya mafundi wa pikipiki/bajaji wanaounganishwa na wateja. |
| **Gereji & Karakana** | `garages` | [http://localhost:8000/index.php?route=garages](http://localhost:8000/index.php?route=garages) | Gereji rasmi zinazonunua vipuri kwa jumla au mikopo. |

#### G. Matumizi na Ripoti za Fedha (Expenses & Financial Reports)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Dashibodi ya Fedha** | `financial/dashboard` | [http://localhost:8000/index.php?route=financial/dashboard](http://localhost:8000/index.php?route=financial/dashboard) | Muhtasari wa Mapato, Gharama za Bidhaa (COGS), Matumizi na Faida Safi. |
| **Orodha ya Matumizi** | `expenses` | [http://localhost:8000/index.php?route=expenses](http://localhost:8000/index.php?route=expenses) | Orodha ya gharama za uendeshaji wa duka (Kodi, Umeme, Usafiri, n.k.). |
| **Rekodi Matumizi Mapya** | `expenses/create` | [http://localhost:8000/index.php?route=expenses/create](http://localhost:8000/index.php?route=expenses/create) | Fomu ya kuingiza vocha ya matumizi. |
| **Kategoria za Matumizi** | `expenses/categories` | [http://localhost:8000/index.php?route=expenses/categories](http://localhost:8000/index.php?route=expenses/categories) | Makundi ya gharama za biashara. |
| **Ripoti ya Mapato (P&L)** | `financial/profit_report` | [http://localhost:8000/index.php?route=financial/profit_report](http://localhost:8000/index.php?route=financial/profit_report) | Ripoti ya Faida Ghafi na Faida Halisi (Profit and Loss Statement). |
| **Faida kwa Kila Bidhaa** | `financial/product_profit` | [http://localhost:8000/index.php?route=financial/product_profit](http://localhost:8000/index.php?route=financial/product_profit) | Mchanganuo wa faida inayotokana na kila kipuri. |
| **Faida kwa Kategoria & Chapa** | `financial/category_profit` | [http://localhost:8000/index.php?route=financial/category_profit](http://localhost:8000/index.php?route=financial/category_profit) | Angalia kategoria na chapa zenye kutoa faida kubwa zaidi. |
| **Faida kwa Cashier & Mauzo** | `financial/sale_profit` | [http://localhost:8000/index.php?route=financial/sale_profit](http://localhost:8000/index.php?route=financial/sale_profit) | Faida kwa kila risiti na utendaji wa wauzaji. |
| **Uchambuzi wa Matumizi** | `financial/expense_analysis` | [http://localhost:8000/index.php?route=financial/expense_analysis](http://localhost:8000/index.php?route=financial/expense_analysis) | Chati na uchambuzi wa makundi ya matumizi yanayotumia fedha nyingi. |
| **Ulinganifu wa Vipindi** | `financial/period_comparison` | [http://localhost:8000/index.php?route=financial/period_comparison](http://localhost:8000/index.php?route=financial/period_comparison) | Linganisha mwenendo wa biashara mwezi huu dhidi ya mwezi uliopita. |

#### H. Marejesho ya Bidhaa (Returns & Refunds)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Dashibodi ya Marejesho** | `returns_dashboard` | [http://localhost:8000/index.php?route=returns_dashboard](http://localhost:8000/index.php?route=returns_dashboard) | Muhtasari wa vipuri vilivyorejeshwa na sababu zake. |
| **Orodha ya Marejesho** | `returns` | [http://localhost:8000/index.php?route=returns](http://localhost:8000/index.php?route=returns) | Kumbukumbu zote za marejesho ya kaunta na mtandaoni. |
| **Rekodi Marejesho Mapya** | `return_create` | [http://localhost:8000/index.php?route=return_create](http://localhost:8000/index.php?route=return_create) | Fomu ya kuanzisha marejesho ya kipuri kilichouzwa. |
| **Ripoti za Marejesho** | `return_reports` | [http://localhost:8000/index.php?route=return_reports](http://localhost:8000/index.php?route=return_reports) | Uchambuzi wa kiwango cha marejesho na hasara za bidhaa mbovu. |

#### I. Kituo cha Ripoti na Takwimu (Reports & Analytics)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Kituo Kikuu cha Ripoti** | `reports` | [http://localhost:8000/index.php?route=reports](http://localhost:8000/index.php?route=reports) | Dashibodi kuu ya ripoti zote za mfumo. |
| **Ripoti ya Mauzo** | `reports/sales` | [http://localhost:8000/index.php?route=reports/sales](http://localhost:8000/index.php?route=reports/sales) | Ripoti ya kina ya miamala ya mauzo yote. |
| **Mauzo kwa Bidhaa** | `reports/sales_by_product` | [http://localhost:8000/index.php?route=reports/sales_by_product](http://localhost:8000/index.php?route=reports/sales_by_product) | Mauzo na idadi ya vipuri vilivyouzwa. |
| **Mauzo kwa Kategoria** | `reports/sales_by_category` | [http://localhost:8000/index.php?route=reports/sales_by_category](http://localhost:8000/index.php?route=reports/sales_by_category) | Mchanganuo wa mauzo kwa makundi ya vipuri. |
| **Mauzo kwa Cashier** | `reports/sales_by_cashier` | [http://localhost:8000/index.php?route=reports/sales_by_cashier](http://localhost:8000/index.php?route=reports/sales_by_cashier) | Mchanganuo wa mauzo yaliyofanywa na kila mweka hazina. |
| **Mauzo kwa Mteja** | `reports/sales_by_customer` | [http://localhost:8000/index.php?route=reports/sales_by_customer](http://localhost:8000/index.php?route=reports/sales_by_customer) | Wateja wakuu wanaonunua vipuri kwa wingi. |
| **Ripoti ya Mikusanyo ya Pesa** | `reports/payments` | [http://localhost:8000/index.php?route=reports/payments](http://localhost:8000/index.php?route=reports/payments) | Mchanganuo wa fedha zilizoingia kupitia mbinu mbalimbali za malipo. |
| **Ripoti ya Madeni ya Wateja** | `reports/debts` | [http://localhost:8000/index.php?route=reports/debts](http://localhost:8000/index.php?route=reports/debts) | Ripoti ya madeni yanayodaiwa na muda wake (Aging). |
| **Ripoti ya Stoo na Thamani** | `reports/inventory` | [http://localhost:8000/index.php?route=reports/inventory](http://localhost:8000/index.php?route=reports/inventory) | Ripoti ya idadi na thamani ya mali iliyopo stoo. |
| **Ripoti ya Manunuzi** | `reports/purchases` | [http://localhost:8000/index.php?route=reports/purchases](http://localhost:8000/index.php?route=reports/purchases) | Manunuzi toka viwandani na kwa wauzaji wa jumla. |
| **Utangamano wa Vyombo** | `reports/vehicle_compatibility` | [http://localhost:8000/index.php?route=reports/vehicle_compatibility](http://localhost:8000/index.php?route=reports/vehicle_compatibility) | Uchambuzi wa vipuri na vyombo vinavyoongoza kwa ununuzi. |

#### J. Utawala, Usalama na Afya ya Mfumo (Admin, RBAC, Backups & Health)

| Moduli | Njia (Route) | Anwani Kamili ya Kujaribu (URL) | Maelezo |
| --- | --- | --- | --- |
| **Wafanyakazi & Akaunti** | `users` | [http://localhost:8000/index.php?route=users](http://localhost:8000/index.php?route=users) | Orodha ya watumiaji na wafanyakazi wa mfumo. |
| **Sajili Mfanyakazi Mpya** | `user_create` | [http://localhost:8000/index.php?route=user_create](http://localhost:8000/index.php?route=user_create) | Fomu ya kuanzisha mtumiaji mpya mwenye jukumu maalum. |
| **Haki & Majukumu (RBAC)** | `roles_permissions` | [http://localhost:8000/index.php?route=roles_permissions](http://localhost:8000/index.php?route=roles_permissions) | Mpangilio wa haki 18+ kwa kila jukumu la mfanyakazi. |
| **Kumbukumbu za Mfumo (Audit)** | `audit` | [http://localhost:8000/index.php?route=audit](http://localhost:8000/index.php?route=audit) | Kumbukumbu isiyoweza kufutwa ya kila tukio linalofanyika mfumoni. |
| **Usalama wa Mfumo (Security)** | `security` | [http://localhost:8000/index.php?route=security](http://localhost:8000/index.php?route=security) | Tathmini ya usalama, majaribio ya kuingia na kuzuia brute-force. |
| **Hifadhi ya Data (Backups)** | `backups` | [http://localhost:8000/index.php?route=backups](http://localhost:8000/index.php?route=backups) | Tengeneza, pakua au fanya majaribio ya kurejesha nakala za SQL. |
| **Afya ya Mfumo (Health Check)** | `health` | [http://localhost:8000/index.php?route=health](http://localhost:8000/index.php?route=health) | Hali ya database, uwezo wa kuandika mafaili, viongezo vya PHP na debug mode. |
| **Mipangilio ya Mfumo** | `settings` | [http://localhost:8000/index.php?route=settings](http://localhost:8000/index.php?route=settings) | Jina la duka, asilimia ya faida lengwa (30% margin), sarafu na mawasiliano. |
