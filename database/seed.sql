-- ==========================================================
-- NGENZI MOTO SPARE PARTS SEED DATA (PHASE 2)
-- Dar es Salaam, Tanzania
-- ==========================================================

USE `ngenzi_moto_spare`;

-- 1. ROLES
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'super_admin', 'Super Admin / Mmiliki', 'Uwezo kamili wa kusimamia mfumo mzima, fedha, watumiaji na ripoti'),
(2, 'manager', 'Manager', 'Usimamizi wa shughuli za kila siku, stoo, mauzo na kupitisha punguzo'),
(3, 'cashier', 'Cashier', 'Kufanya mauzo (POS), kupokea malipo na kutoa risiti'),
(4, 'storekeeper', 'Storekeeper', 'Kupokea mizigo, kuhesabu stoo, kuripoti uharibifu na upotevu'),
(5, 'accountant', 'Accountant', 'Kusimamia hesabu, kurekodi gharama za duka, madeni na ripoti za faida')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 2. PERMISSIONS
INSERT INTO `permissions` (`id`, `name`, `module`, `description`) VALUES
(1, 'view_dashboard', 'dashboard', 'Kutazama dashibodi na takwimu'),
(2, 'manage_users', 'users', 'Kusimamia akaunti za wafanyakazi'),
(3, 'manage_products', 'products', 'Kusajili na kubadili vipuri'),
(4, 'manage_prices', 'products', 'Kubadili bei na kusanidi margin ya 30%'),
(5, 'manage_categories', 'categories', 'Kusimamia kategoria za vipuri'),
(6, 'manage_vehicles', 'vehicles', 'Kusimamia miundo ya pikipiki na bajaji'),
(7, 'manage_suppliers', 'suppliers', 'Kusimamia wauzaji wa jumla'),
(8, 'manage_customers', 'customers', 'Kusimamia wateja, mafundi na gereji'),
(9, 'manage_purchases', 'purchases', 'Kufanya na kuthibitisha manunuzi ya stoo'),
(10, 'manage_sales', 'sales', 'Kufanya mauzo na kutoa risiti (POS)'),
(11, 'approve_discounts', 'sales', 'Kupitisha punguzo la bei'),
(12, 'manage_inventory', 'inventory', 'Kusimamia idadi ya vipuri stoo'),
(13, 'approve_stock_adjustments', 'inventory', 'Kupitisha marekebisho ya stoo na stock count'),
(14, 'manage_expenses', 'expenses', 'Kurekodi na kusimamia matumizi ya duka'),
(15, 'manage_payments', 'finance', 'Kupokea na kulipa fedha za madeni'),
(16, 'view_reports', 'reports', 'Kutazama ripoti za mauzo, stoo na faida halisi'),
(17, 'manage_settings', 'settings', 'Kubadili mipangilio ya mfumo'),
(18, 'view_audit_logs', 'audit', 'Kukagua kumbukumbu za miamala na usalama')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- 3. ROLE_PERMISSIONS (Permission Matrix)
-- Super Admin: Has all permissions (1-18)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
(1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18),
-- Manager: 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16
(2, 1), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8),
(2, 9), (2, 10), (2, 11), (2, 12), (2, 13), (2, 15), (2, 16),
-- Cashier: 1, 8, 10, 15
(3, 1), (3, 8), (3, 10), (3, 15),
-- Storekeeper: 1, 3, 6, 9, 12, 13
(4, 1), (4, 3), (4, 6), (4, 9), (4, 12), (4, 13),
-- Accountant: 1, 7, 8, 14, 15, 16
(5, 1), (5, 7), (5, 8), (5, 14), (5, 15), (5, 16)
ON DUPLICATE KEY UPDATE `permission_id` = VALUES(`permission_id`);

-- 4. USERS (Pass: username + 123 e.g. admin123)
INSERT INTO `users` (`id`, `role_id`, `username`, `full_name`, `email`, `phone`, `password`, `status`) VALUES
(1, 1, 'admin', 'Mhandisi Ngenzi (Mmiliki)', 'ngenzi@motosparertz.com', '0754123456', '$2y$10$vG5clTy0qRkY1VDFQKBD4u40UIdbXjporZASUTcosAQQvDo9rlSXm', 'active'),
(2, 2, 'manager', 'Salum Bakari (Meneja)', 'manager@motosparertz.com', '0767112233', '$2y$10$slbX0euiFXig0XK17V8vB.nTm.LR3Z4ZNhOiDsn8H1LeSVb/NiXhe', 'active'),
(3, 3, 'cashier', 'Amina Hassan (Mweka Hazina/POS)', 'amina@motosparertz.com', '0714998877', '$2y$10$xlPG8Z2J6qQE3p8aiLP6uumhLSsK2L.HVC1p6I1WCx30XVt9jwRb2', 'active'),
(4, 4, 'storekeeper', 'Kassim Juma (Mlinzi wa Stoo)', 'kassim@motosparertz.com', '0788334455', '$2y$10$XWfOKcYbEf6Vnw8nNOa7kuRMOofnDdcUWfm2jI5/2N67KiqPoldMO', 'active'),
(5, 5, 'accountant', 'Neema Mwita (Mhasibu)', 'neema@motosparertz.com', '0755667788', '$2y$10$eRkrjzW1N8ZWQDfDpbGIr.XTtVGLIHEFcGrqYYLbBAAF9zwqZRZQq', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 5. UNITS
INSERT INTO `units` (`id`, `name`, `short_name`) VALUES
(1, 'Piece / Moja', 'Pcs'),
(2, 'Set', 'Set'),
(3, 'Pair / Jozi', 'Pr'),
(4, 'Litre / Lita', 'Ltr'),
(5, 'Packet / Pakiti', 'Pkt'),
(6, 'Roll / Roli', 'Roll'),
(7, 'Box / Sanduku', 'Box')
ON DUPLICATE KEY UPDATE `short_name` = VALUES(`short_name`);

-- 6. CATEGORIES
INSERT INTO `categories` (`id`, `name`, `code`, `description`) VALUES
(1, 'Mfumo wa Breki (Brake System)', 'BRK', 'Viatu vya breki, diski, pads, nyaya za breki na mafuta ya breki'),
(2, 'Injini & Pistoni (Engine & Piston)', 'ENG', 'Pistoni, rings, vali, gaskets, silinda, spark plugs'),
(3, 'Klachi & Gia (Clutch & Transmission)', 'CLT', 'Bati za klachi, clutch plate, nyaya za klachi, sprockets'),
(4, 'Mnyororo & Spoketi (Chain & Sprocket)', 'CHN', 'Minjororo ya kuendeshea pikipiki na bajaji, tensioners'),
(5, 'Umeme & Taa (Electrical & Lights)', 'ELE', 'Betri, taa za mbele/nyuma, flashing bulbs, coils, CDI, regulator'),
(6, 'Shockup & Kusimamisha (Suspension)', 'SUS', 'Shock absorbers za mbele na nyuma, bushings, fork oil'),
(7, 'Bodi & Vioo (Body Parts & Mirrors)', 'BDY', 'Mabango, side mirrors, fender, mudguards, handles, stendi'),
(8, 'Vichungi (Filters)', 'FLT', 'Oil filters, fuel filters, air filters'),
(9, 'Matairi & Tyubu (Tyres & Tubes)', 'TYR', 'Matairi ya pikipiki na bajaji, tyubu za ukubwa tofauti'),
(10, 'Vilainishi & Oili (Lubricants & Oils)', 'OIL', 'Oili za 4T, 2T, brake fluid, grease za bearing'),
(11, 'Bearings & Roli', 'BRG', 'Bearings za magurudumu, injini na steering'),
(12, 'Kebo & Nyaya (Cables)', 'CBL', 'Throttle cable, clutch cable, brake cable, speedometer cable')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 7. BRANDS
INSERT INTO `brands` (`id`, `name`, `country`, `description`) VALUES
(1, 'Bajaj Genuine Parts', 'India', 'Vipuri halisi vya Bajaj Auto India kwa ajili ya Boxer na Bajaj RE'),
(2, 'TVS Genuine Parts', 'India', 'Vipuri halisi vya TVS Motor kwa HLX na TVS King'),
(3, 'Boxer Genuine', 'India', 'Vipuri maalum vya Boxer BM 150 na BM 100'),
(4, 'NGK Spark Plugs', 'Japan', 'Plagi bora zaidi duniani kwa ufanisi wa cheche'),
(5, 'Bosch', 'Germany', 'Vifaa vya umeme, honi, taa na betri'),
(6, 'Endurance', 'India', 'Shock absorbers na breki za kiwango cha kimataifa'),
(7, 'Rolon Chains', 'India', 'Minjororo na sproketi imara sana'),
(8, 'Kingstone Tyres', 'China', 'Matairi na tyubu imara kwa barabara za Bongo'),
(9, 'Total Lubricants', 'France', 'Oili ya Total Hi-Perf 4T kwa pikipiki'),
(10, 'Castrol', 'UK', 'Castrol Activ 4T yenye Actibond molecules'),
(11, 'Makida OEM', 'China', 'Vipuri vya mbadala vya kuaminika vya Kichina'),
(12, 'Yash Spare', 'India', 'Vipuri vya bei nafuu na ubora mzuri')
ON DUPLICATE KEY UPDATE `country` = VALUES(`country`);

-- 8. VEHICLE BRANDS (Motorcycles & Bajaj common in Tanzania)
INSERT INTO `vehicle_brands` (`id`, `name`, `vehicle_type`, `description`) VALUES
(1, 'Boxer / Bajaj Auto', 'Both', 'Mtengenezaji mkuu wa Boxer na Bajaji RE'),
(2, 'TVS Motor', 'Both', 'Mtengenezaji wa TVS HLX na TVS King Deluxe'),
(3, 'Haojue', 'Motorcycle', 'Pikipiki maarufu za Kichina za ubora wa juu'),
(4, 'Honda', 'Motorcycle', 'Pikipiki imara za Kijapani ikiwemo Honda Ace'),
(5, 'Yamaha', 'Motorcycle', 'Pikipiki za Yamaha Crux na DT'),
(6, 'Sanlg', 'Motorcycle', 'Pikipiki za bei nafuu za Kichina'),
(7, 'Piaggio Ape', 'Bajaj', 'Bajaji za kubebea mizigo na abiria za Piaggio')
ON DUPLICATE KEY UPDATE `vehicle_type` = VALUES(`vehicle_type`);

-- 9. VEHICLE MODELS
INSERT INTO `vehicle_models` (`id`, `brand_id`, `model_name`, `year_from`, `year_to`, `engine_capacity`, `vehicle_type`, `notes`) VALUES
(1, 1, 'Boxer BM 150', 2012, NULL, '150cc', 'Motorcycle', 'Pikipiki maarufu sana ya biashara ya bodaboda Tanzania'),
(2, 1, 'Boxer BM 100', 2005, NULL, '100cc', 'Motorcycle', 'Boxer ya zamani maarufu sana kwa matumizi ya vijijini'),
(3, 1, 'Boxer 150 HD (Heavy Duty)', 2018, NULL, '150cc', 'Motorcycle', 'Toleo jipya la magurudumu ya spokes na diski'),
(4, 1, 'Bajaj RE 4S (Compact)', 2014, NULL, '200cc', 'Bajaj', 'Bajaji ya kawaida ya abiria 3 nchini Tanzania'),
(5, 1, 'Bajaj Maxima Cargo', 2016, NULL, '236cc', 'Bajaj', 'Bajaji ya kubebea mizigo'),
(6, 2, 'TVS HLX 125', 2015, NULL, '125cc', 'Motorcycle', 'Pikipiki yenye gia 4 maarufu kwa bongo'),
(7, 2, 'TVS HLX 150', 2017, NULL, '150cc', 'Motorcycle', 'TVS ya nguvu yenye gia 5'),
(8, 2, 'TVS King Deluxe (Bajaj)', 2015, NULL, '200cc', 'Bajaj', 'Bajaji ya TVS ya abiria maarufu sana Dar es Salaam'),
(9, 3, 'Haojue Lucky 125', 2013, NULL, '125cc', 'Motorcycle', 'Pikipiki ya Kichina imara sana'),
(10, 3, 'Haojue HJ 150', 2018, NULL, '150cc', 'Motorcycle', 'Haojue ya kisasa'),
(11, 4, 'Honda Ace CB125', 2016, NULL, '125cc', 'Motorcycle', 'Pikipiki ya Honda ya bodaboda'),
(12, 5, 'Yamaha Crux 110', 2010, NULL, '110cc', 'Motorcycle', 'Pikipiki ya Yamaha ya kawaida')
ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`);

-- 10. SUPPLIERS
INSERT INTO `suppliers` (`id`, `supplier_code`, `name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `credit_limit`, `balance`) VALUES
(1, 'SUP-001', 'Dar Moto Spares Importers', 'Bwana Harun Mussa', '0715223344', 'harun@darmoto.co.tz', 'Mtaa wa Msimbazi, Kariakoo, Dar es Salaam', 'Siku 30 za mkopo', 5000000.00, 450000.00),
(2, 'SUP-002', 'Bongo Bajaj Distributors Ltd', 'Alhaj Rashid', '0784998811', 'sales@bongobajaj.com', 'Kurasini Road, Dar es Salaam', 'Malipo ya papo hapo / Pesa taslimu', 2000000.00, 0.00),
(3, 'SUP-003', 'Tanzania Automotive Parts Ltd', 'Deepak Patel', '0754001122', 'deepak@tzautoparts.com', 'Nyerere Road, Dar es Salaam', 'Siku 14 za mkopo', 10000000.00, 1200000.00)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 11. CUSTOMERS
INSERT INTO `customers` (`id`, `customer_code`, `name`, `phone`, `email`, `address`, `customer_type`, `credit_limit`, `current_balance`, `notes`) VALUES
(1, 'CUST-001', 'Wateja wa Rejareja (Walk-in)', '0000000000', NULL, 'Dar es Salaam', 'retail', 0.00, 0.00, 'Akaunti ya wateja wa kawaida wasiokuwa na akaunti maalumu'),
(2, 'CUST-002', 'Gereji ya Mwenge Auto', '0765112233', 'mwengeauto@gmail.com', 'Mwenge ITV, Dar es Salaam', 'garage', 500000.00, 120000.00, 'Gereji kubwa ya kurekebisha Boxer na TVS'),
(3, 'CUST-003', 'Fundi Juma Bajaj Specialist', '0713445566', 'jumabajaj@gmail.com', 'Mabibo Hosteli, Dar es Salaam', 'mechanic', 300000.00, 45000.00, 'Fundi maarufu sana wa bajaji za TVS King na Bajaj RE'),
(4, 'CUST-004', 'Bodaboda Chama Kurasini', '0789778899', 'bodabodakurasini@gmail.com', 'Kurasini Bandarini', 'reseller', 1000000.00, 350000.00, 'Chama cha waendesha bodaboda wananunua kwa bei ya jumla')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 12. GARAGES
INSERT INTO `garages` (`id`, `garage_name`, `owner_name`, `phone`, `location`, `address`, `specialization`, `credit_limit`, `status`, `notes`) VALUES
(1, 'Gereji ya Mwenge Auto', 'Omary Mwenge', '0765112233', 'Mwenge', 'Kando ya ITV, Dar es Salaam', 'Boxer BM150 & TVS HLX', 500000.00, 'active', 'Hufanya matengenezo makubwa ya bodaboda'),
(2, 'Mabibo Bajaj Clinic', 'Fundi Juma', '0713445566', 'Mabibo', 'Mabibo Mwisho, Dar es Salaam', 'Bajaj RE & TVS King Specialist', 300000.00, 'active', 'Kituo kikuu cha matengenezo ya bajaji'),
(3, 'Kariakoo Moto Works', 'Rashid Fundi', '0712334455', 'Kariakoo', 'Mtaa wa Swahili, Dar es Salaam', 'General Motorcycle Repairs', 400000.00, 'active', 'Matengenezo ya haraka')
ON DUPLICATE KEY UPDATE `garage_name` = VALUES(`garage_name`);

-- 13. MECHANICS
INSERT INTO `mechanics` (`id`, `customer_id`, `garage_id`, `name`, `phone`, `specialization`, `notes`) VALUES
(1, 3, 2, 'Fundi Juma', '0713445566', 'Bajaj RE 4S & TVS King Expert', 'Mteja mwaminifu sana wa vipuri vya bajaji'),
(2, 2, 1, 'Fundi Ally Kidevu', '0765112233', 'Boxer BM150 & Haojue Injini Specialist', 'Mtaalamu wa injini, gia na wiring'),
(3, NULL, 3, 'Fundi Jackson', '0787654321', 'Matairi, Tyubu na Shockup', 'Fundi wa kurekebisha miguu na shockup')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 14. PRODUCTS (Calculated with 30% Gross Profit Margin: Recommended = Cost / (1 - 0.30))
INSERT INTO `products` (`id`, `sku`, `barcode`, `name`, `category_id`, `brand_id`, `unit_id`, `buying_price`, `selling_price`, `recommended_selling_price`, `minimum_selling_price`, `target_margin`, `current_stock`, `min_stock_level`, `reorder_level`, `shelf_location`) VALUES
-- 1. Brake Shoe Boxer BM150: Cost 10,000 => Recommended 14,300 => Selling 14,300 (Margin 30.1%)
(1, 'PRD-BRK-001', '8901234001', 'Brake Shoe Rear Boxer BM150 (Viatu vya Breki)', 1, 3, 1, 10000.00, 14300.00, 14300.00, 12500.00, 30.00, 45, 5, 15, 'A-01'),
-- 2. Clutch Plate Set Bajaj RE: Cost 28,000 => Recommended 40,000 => Selling 40,000 (Margin 30.0%)
(2, 'PRD-CLT-002', '8901234002', 'Clutch Plate Set Bajaj RE 4S (Seti ya Klachi)', 3, 1, 2, 28000.00, 40000.00, 40000.00, 35000.00, 30.00, 18, 4, 8, 'B-04'),
-- 3. Spark Plug NGK CPR8EA-9: Cost 4,500 => Recommended 6,500 => Selling 6,500 (Margin 30.8%)
(3, 'PRD-ENG-003', '8901234003', 'Spark Plug NGK CPR8EA-9 (Plagi)', 2, 4, 1, 4500.00, 6500.00, 6500.00, 5500.00, 30.00, 120, 20, 40, 'C-02'),
-- 4. Chain Set Boxer BM150 (Rolon): Cost 25,000 => Recommended 35,800 => Selling 35,800 (Margin 30.2%)
(4, 'PRD-CHN-004', '8901234004', 'Chain & Sprocket Set Boxer BM150 (Mnyororo)', 4, 7, 2, 25000.00, 35800.00, 35800.00, 31500.00, 30.00, 22, 5, 10, 'D-01'),
-- 5. Engine Oil Total 4T 20W-50 1L: Cost 12,000 => Recommended 17,200 => Selling 17,200 (Margin 30.2%)
(5, 'PRD-OIL-005', '8901234005', 'Engine Oil Total 4T 20W-50 1 Litre (Oili)', 10, 9, 4, 12000.00, 17200.00, 17200.00, 15000.00, 30.00, 60, 12, 24, 'E-03'),
-- 6. Piston Kit TVS HLX 125 STD: Cost 22,000 => Recommended 31,500 => Selling 31,500 (Margin 30.2%)
(6, 'PRD-ENG-006', '8901234006', 'Piston Kit Standard TVS HLX 125 (Pistoni)', 2, 2, 2, 22000.00, 31500.00, 31500.00, 27500.00, 30.00, 12, 3, 6, 'B-02'),
-- 7. Tyube Kingstone 3.00-17: Cost 7,000 => Recommended 10,000 => Selling 10,000 (Margin 30.0%)
(7, 'PRD-TYR-007', '8901234007', 'Tube Kingstone Heavy Duty 3.00-17 (Tyubu)', 9, 8, 1, 7000.00, 10000.00, 10000.00, 8500.00, 30.00, 80, 15, 30, 'F-01'),
-- 8. Taa ya Mbele Bajaj RE Halogen 12V 35W: Cost 6,000 => Recommended 8,600 => Selling 8,600 (Margin 30.2%)
(8, 'PRD-ELE-008', '8901234008', 'Headlight Bulb 12V 35/35W Bajaj RE / TVS King (Taa)', 5, 5, 1, 6000.00, 8600.00, 8600.00, 7500.00, 30.00, 40, 10, 20, 'C-05'),
-- 9. Shock Absorber Rear Boxer BM150 (Jozi): Cost 42,000 => Recommended 60,000 => Selling 60,000 (Margin 30.0%)
(9, 'PRD-SUS-009', '8901234009', 'Rear Shock Absorber Pair Boxer BM150 (Shockup Jozi)', 6, 6, 3, 42000.00, 60000.00, 60000.00, 52500.00, 30.00, 8, 2, 5, 'D-04'),
-- 10. Throttle Cable TVS HLX 125: Cost 3,500 => Recommended 5,000 => Selling 5,000 (Margin 30.0%)
(10, 'PRD-CBL-010', '8901234010', 'Throttle Cable TVS HLX 125 (Waya wa Mafuta)', 12, 2, 1, 3500.00, 5000.00, 5000.00, 4200.00, 30.00, 35, 5, 10, 'A-05')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `recommended_selling_price` = VALUES(`recommended_selling_price`);

-- 15. PRODUCT VEHICLE COMPATIBILITY (Many-to-Many)
INSERT INTO `product_vehicle_compatibility` (`product_id`, `vehicle_model_id`, `notes`) VALUES
(1, 1, 'Inafaa magurudumu ya nyuma ya Boxer BM 150'),
(1, 2, 'Inafaa pia Boxer BM 100'),
(1, 3, 'Inafaa Boxer 150 HD'),
(2, 4, 'Maalum kwa Bajaj RE 4S Compact'),
(2, 5, 'Inafaa pia Bajaj Maxima Cargo'),
(3, 1, 'Inafaa Boxer zote'),
(3, 6, 'Inafaa TVS HLX 125'),
(3, 7, 'Inafaa TVS HLX 150'),
(3, 4, 'Inafaa Bajaj RE'),
(4, 1, 'Mnyororo wa Boxer BM150 428-120L'),
(5, 1, 'Oili ya Boxer'),
(5, 4, 'Oili ya Bajaj RE'),
(5, 6, 'Oili ya TVS HLX'),
(5, 8, 'Oili ya TVS King'),
(6, 6, 'Pistoni ya TVS HLX 125'),
(7, 1, 'Tyubu ya Boxer 3.00-17'),
(7, 6, 'Tyubu ya TVS 3.00-17'),
(8, 4, 'Taa ya Bajaj RE'),
(8, 8, 'Taa ya TVS King'),
(9, 1, 'Shockup ya nyuma ya Boxer'),
(10, 6, 'Waya wa TVS HLX 125')
ON DUPLICATE KEY UPDATE `notes` = VALUES(`notes`);

-- 16. EXPENSE CATEGORIES
INSERT INTO `expense_categories` (`id`, `name`, `description`) VALUES
(1, 'Kodi ya Fremu (Shop Rent)', 'Kodi ya pango la duka Dar es Salaam'),
(2, 'Umeme (TANESCO - LUKU)', 'Gharama za umeme wa duka'),
(3, 'Mishahara & Posho (Salaries)', 'Mishahara ya wafanyakazi na posho za chakula'),
(4, 'Usafiri & Mizigo (Transport)', 'Gharama za kubeba vipuri toka bandarini/Kariakoo'),
(5, 'Mawasiliano & Internet', 'Vifurushi vya data na maongezi'),
(6, 'Ukarabati wa Duka (Maintenance)', 'Vifaa vya duka, taa, rafu na ukarabati'),
(7, 'Gharama za Benki & Miamala', 'Makato ya Lipa Namba, M-Pesa na benki'),
(8, 'Vinginevyo (Miscellaneous)', 'Matumizi mengine madogo madogo')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 17. SETTINGS
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`) VALUES
('business_name', 'NGENZI MOTO SPARE PARTS', 'Jina rasmi la biashara', 1),
('business_tagline', 'Dar es Salaam Motorcycle & Bajaj Spare Parts Specialist', 'Kaulimbiu ya duka', 1),
('business_phone', '+255 754 123 456 / +255 715 000 111', 'Namba za simu za duka', 1),
('business_email', 'info@motosparertz.com', 'Barua pepe ya biashara', 1),
('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam, Tanzania', 'Anwani ya duka', 1),
('currency', 'TZS', 'Sarafu rasmi (Shilingi ya Tanzania)', 1),
('default_target_margin', '30.00', 'Lengo la asilimia ya faida (Gross Profit Margin %)', 1),
('minimum_margin', '20.00', 'Kiwango cha chini kabisa cha faida bila idhini ya meneja', 1),
('discount_approval_threshold', '5000.00', 'Kiwango cha punguzo kinachohitaji idhini ya meneja (TSh)', 1),
('low_stock_default_level', '5', 'Kiwango cha chini cha tahadhari ya stoo', 1),
('dead_stock_days', '60', 'Siku za kuhesabu bidhaa ambayo haijauzwa kama dead stock', 1),
('invoice_prefix', 'NGZ-INV-', 'Mwanzo wa nambari ya ankara/risiti', 1),
('receipt_footer', 'Asante sana kwa kufanya biashara na NGENZI MOTO SPARE PARTS! Vipuri Halisi, Bei Salama.', 'Ujumbe wa chini wa risiti', 1)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
