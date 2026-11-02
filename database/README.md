# NGENZI MOTO SPARE PARTS — Database Documentation

**Mfumo wa Usimamizi wa Vipuri vya Pikipiki na Bajaji**  
**Dar es Salaam, Tanzania**  
**Database Engine**: MySQL / MariaDB (InnoDB)  
**Port**: `3307`  
**Database Name**: `ngenzi_moto_spare`  
**Collation**: `utf8mb4_unicode_ci`  

---

## 1. Madhumuni na Muundo Mkuu (Overview & Principles)

Database hii imeundwa kwa viwango vya juu vya **3NF (Third Normal Form)** ili kuhakikisha:

1. **Hakuna Takwimu Zinazojirudia (Zero Data Redundancy)**.
2. **Ulinzi wa Faida ya 30% (Margin Engine Protection)**:
   Kila bidhaa inahifadhi `buying_price`, `recommended_selling_price`, `minimum_selling_price`, na `target_margin`.
3. **Uadilifu wa Miamala ya Nyuma (Historical Financial Integrity)**:
   Kila mstari wa mauzo (`sale_items`) unahifadhi `unit_cost` (cost_price_at_sale) ya kipindi kilipouzwa. Bei ya kununua ikibadilika kesho, faida ya mauzo ya nyuma haipotei wala kubadilika.
4. **Usalama wa Fedha**:
   Thamani zote za pesa zinatumia **`DECIMAL(15,2)`** badala ya `FLOAT` ili kuzuia upotevu wa senti.
5. **Ulinzi dhidi ya Kufuta Kimakosa (Foreign Key Safety)**:
   Hakuna `CASCADE DELETE` kwenye miamala ya kifedha (mauzo, manunuzi, stoo). Kufuta bidhaa kumewekewa `ON RESTRICT` ili kulinda historia ya mauzo.

---

## 2. Majedwali Yote na Madhumuni Yake (Table Dictionary)

| # | Jina la Jedwali | Madhumuni / Maelezo |
| --- | --- | --- |
| 1 | `roles` | Majukumu 5 ya watumiaji (Owner/Admin, Manager, Cashier, Storekeeper, Accountant). |
| 2 | `permissions` | Orodha kamili ya haki 18 za kiutawala na kiutendaji. |
| 3 | `role_permissions` | Uhusiano wa many-to-many kati ya majukumu na haki zao. |
| 4 | `users` | Watumiaji wa mfumo wenye nenosiri salama la Bcrypt (`password_hash`). |
| 5 | `categories` | Kategoria za vipuri (Breki, Injini, Klachi, Umeme, Matairi, Oili, n.k.). |
| 6 | `brands` | Watengenezaji wa vipuri (Bajaj Genuine, TVS, Boxer, NGK, Bosch, Rolon, n.k.). |
| 7 | `units` | Vipimo vya idadi (Pcs, Set, Pr, Ltr, Pkt, Roll, Box). |
| 8 | `vehicle_brands` | Chapa za vyombo vya usafiri (Boxer, TVS, Haojue, Honda, Yamaha, Piaggio). |
| 9 | `vehicle_models` | Miundo maalum ya pikipiki na bajaji (Boxer BM150, TVS HLX 125, Bajaj RE 4S, TVS King). |
| 10 | `products` | Katalogi ya vipuri vyote, bei za kununua, 30% margin, na viwango vya stoo. |
| 11 | `product_vehicle_compatibility` | Uhusiano wa many-to-many: kipuri kimoja kuendana na pikipiki/bajaji zaidi ya moja. |
| 12 | `suppliers` | Wauzaji wa jumla na ufuatiliaji wa madeni (Payables). |
| 13 | `customers` | Wateja wa kawaida, gereji, mafundi na madeni yao (Receivables). |
| 14 | `garages` | Gereji rasmi zinazofanya matengenezo na kununua vipuri. |
| 15 | `mechanics` | Mafundi maalum waliounganishwa na wateja na gereji zao. |
| 16 | `purchases` | Miamala ya manunuzi ya mzigo toka kwa suppliers. |
| 17 | `purchase_items` | Vipuri vilivyomo kwenye kila agizo la manunuzi. |
| 18 | `sales` | Ankara na stakabadhi za mauzo (POS) zikiwa na jumla ya faida. |
| 19 | `sale_items` | Vipuri vilivyouzwa vikiwa na `unit_cost` ya kihistoria na faida ya kila mstari. |
| 20 | `sale_payments` | Malipo ya mauzo (Pesa Taslimu, M-Pesa, Tigo Pesa, Benki, n.k.). |
| 21 | `supplier_payments` | Malipo yaliyofanywa kwenda kwa wauzaji wa jumla kupunguza madeni. |
| 22 | `customer_payments` | Malipo ya marejesho ya mikopo kutoka kwa wateja/gereji. |
| 23 | `inventory_transactions` | Rekodi ya kila mzunguko wa stoo (Mauzo, Manunuzi, Uharibifu, Marejesho). |
| 24 | `product_cost_history` | Kumbukumbu ya mabadiliko ya bei za kununulia na nani aliyebadili. |
| 25 | `price_history` | Kumbukumbu ya mabadiliko ya bei za kuuzia dukan. |
| 26 | `expense_categories` | Makundi ya matumizi ya duka (Kodi ya Fremu, LUKU, Mishahara, Usafiri, n.k.). |
| 27 | `expenses` | Gharama za kila siku za uendeshaji ili kuhesabu Net Profit. |
| 28 | `returns` | Marejesho ya vipuri toka kwa wateja na sababu zake. |
| 29 | `return_items` | Vipuri vilivyorejeshwa na ukaguzi wa kama vinafaa kuuzwa tena au vimeharibika. |
| 30 | `stock_counts` | Ukaguzi wa kimwili wa stoo (Physical Stock Count reconciliation). |
| 31 | `stock_count_items` | Tofauti (Variance) kati ya hesabu ya mfumo na hesabu halisi ya stoo. |
| 32 | `audit_logs` | Kumbukumbu ya kiusalama isiyoweza kufutwa inayorekodi kila hatua ya mfumo. |
| 33 | `settings` | Mipangilio rasmi ya biashara (Jina, Anwani, Target Margin 30%, n.k.). |

---

## 3. Uhusiano Mkuu (Entity Relationships)

```text
[roles] 1 ──── ∞ [users]
[roles] 1 ──── ∞ [role_permissions] ∞ ──── 1 [permissions]

[categories] 1 ──── ∞ [products]
[brands]     1 ──── ∞ [products]
[units]      1 ──── ∞ [products]

[vehicle_brands] 1 ──── ∞ [vehicle_models]
[products]       ∞ ──── ∞ [product_vehicle_compatibility] ∞ ──── ∞ [vehicle_models]

[suppliers] 1 ──── ∞ [purchases] 1 ──── ∞ [purchase_items] ∞ ──── 1 [products]
[suppliers] 1 ──── ∞ [supplier_payments]

[customers] 1 ──── ∞ [sales] 1 ──── ∞ [sale_items] ∞ ──── 1 [products]
[customers] 1 ──── ∞ [customer_payments]
[customers] 1 ──── 0..1 [mechanics] ∞ ──── 0..1 [garages]

[sales] 1 ──── ∞ [sale_payments]
[sales] 1 ──── ∞ [returns] 1 ──── ∞ [return_items]

[products] 1 ──── ∞ [inventory_transactions]
[products] 1 ──── ∞ [product_cost_history]
[products] 1 ──── ∞ [price_history]

[expense_categories] 1 ──── ∞ [expenses]
```

---

## 4. Jinsi ya Kuagiza na Kuanzisha Database (Import & Setup)

### Kutumia PHP Migration Script (Inayopendekezwa)

Kutoka kwenye root ya mradi, endesha:

```bash
php database/migrate.php
```

Script hii ita:

1. Unganisha kwenye MySQL Port 3307.
2. Unda database ya `ngenzi_moto_spare`.
3. Tekeleza `database/schema.sql` (majedwali yote 33 na fahirisi zake).
4. Tekeleza `database/seed.sql` (watumiaji, majukumu, haki, vyombo na vipuri).

### Kutumia MySQL Command-Line

```bash
mysql -h 127.0.0.1 -P 3307 -u root < database/schema.sql
mysql -h 127.0.0.1 -P 3307 -u root < database/seed.sql
```

---

## 5. Mkakati wa Transaction Integrity (ACID & Rollback)

Kwenye shughuli za kifedha (mauzo, manunuzi na marejesho), mfumo unatumia mtindo wa **Database Transactions**:

```php
$pdo->beginTransaction();
try {
    // 1. Hakiki stoo ya bidhaa
    // 2. Ingiza rekodi ya mauzo (sales)
    // 3. Ingiza bidhaa za mauzo zikiwa na unit_cost ya kihistoria (sale_items)
    // 4. Punguza idadi ya bidhaa stoo (products.current_stock)
    // 5. Ingiza kumbukumbu ya mzunguko wa stoo (inventory_transactions)
    // 6. Ingiza rekodi ya malipo (sale_payments)
    // 7. Weka rekodi ya audit log (audit_logs)
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack(); // Rudisha nyuma mabadiliko yote ili kuzuia nusu-muamala
    throw $e;
}
```

Mkakati huu unahakikisha hakuna stoo inayopungua bila risiti, na hakuna risiti inayotoka bila kurekodi malipo au deni.
