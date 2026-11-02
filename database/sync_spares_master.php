<?php
/**
 * NGENZI MOTORCYCLE & BAJAJ SPARE PARTS MANAGEMENT SYSTEM
 * Authoritative Spare Parts Data Synchronization Script
 * Dar es Salaam, Tanzania
 */

require_once __DIR__ . '/../app/core/Env.php';
\App\Core\Env::init();
require_once __DIR__ . '/../app/core/Database.php';

$pdo = \App\Core\Database::getConnection();

echo "====================================================================\n";
echo "STARTING SYNCHRONIZATION OF AUTHORITATIVE SPARE PARTS DATA (181 ITEMS)\n";
echo "====================================================================\n\n";

$csvFile = __DIR__ . '/spares_master.csv';
if (!file_exists($csvFile)) {
    die("FATAL ERROR: spares_master.csv not found at $csvFile\n");
}

$f = fopen($csvFile, 'r');
$header = fgetcsv($f);

// Category Mapping to IDs
$categoryMap = [
    'ENG' => 2,   // Injini & Pistoni
    'GSK' => 23,  // Gasketi na Sili
    'BRK' => 1,   // Mfumo wa Breki
    'BOD' => 24,  // Bodi na Vifaa vya Nje
    'BOX' => 25,  // Pikipiki ya Boxer (BM125/BM150)
    'TVS' => 26,  // Pikipiki ya TVS (HLX)
    'UNI' => 27,  // Vipuri vya Pamoja vya Pikipiki
    'OIL' => 10,  // Vilainishi & Oili
    'TYR' => 9,   // Matairi & Tyubu
    'CHN' => 4,   // Mnyororo & Spoketi
    'BRG' => 11,  // Bearings & Roli
    'ELE' => 5,   // Umeme & Taa
    'CAB' => 28,  // Kebo & Stelingi
    'GER' => 29   // Vifaa vya Mpanda Pikipiki na Urembo
];

// Unit Mapping to IDs
$unitMap = [
    'Pcs'     => 1, // Piece / Moja
    'Kipande' => 1, // Piece / Moja
    'Seti'    => 2, // Set
    'Jozi'    => 3, // Pair / Jozi
    'Lita'    => 4, // Litre / Lita
    'Pakiti'  => 5, // Packet / Pakiti
    'Roll'    => 6, // Roll / Roli
    'Boksi'   => 7, // Box / Sanduku
    'Chupa'   => 8, // Chupa / Bottle
    'Kidumu'  => 9, // Kidumu / Jerrycan
    'Kikope'  => 10 // Kikope / Cup-Pack
];

// Stats
$totalRead = 0;
$updatedCount = 0;
$insertedCount = 0;
$duplicatePrevented = 0;
$specialCasesCount = 0;
$seenSkus = [];

$pdo->beginTransaction();

try {
    $stmtCheckSku = $pdo->prepare("SELECT id, sku, name, buying_price, selling_price, current_stock FROM products WHERE sku = ? LIMIT 1");

    $stmtInsert = $pdo->prepare("
        INSERT INTO products (
            sku, name, product_type, category_id, brand_id, unit_id,
            buying_price, selling_price, recommended_selling_price, minimum_selling_price,
            target_margin, current_stock, min_stock_level, reorder_level, max_stock_level,
            shelf_location, description, online_description, status, is_online,
            is_catalog_only, availability_status, is_featured, image_url, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            30.00, ?, 5, 10, 100,
            ?, ?, ?, 'active', 1,
            0, 'in_stock', 0, '/assets/img/spare_part_placeholder.svg', NOW(), NOW()
        )
    ");

    $stmtUpdate = $pdo->prepare("
        UPDATE products SET
            name = ?,
            product_type = ?,
            category_id = ?,
            brand_id = ?,
            unit_id = ?,
            buying_price = ?,
            selling_price = ?,
            recommended_selling_price = ?,
            minimum_selling_price = ?,
            description = ?,
            online_description = ?,
            shelf_location = COALESCE(shelf_location, ?),
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmtPriceHist = $pdo->prepare("
        INSERT INTO price_history (product_id, old_selling_price, new_selling_price, old_minimum_price, new_minimum_price, reason, changed_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $stmtCostHist = $pdo->prepare("
        INSERT INTO product_cost_history (product_id, old_cost, new_cost, reason, changed_by, created_at)
        VALUES (?, ?, ?, ?, 1, NOW())
    ");

    $stmtCompCheck = $pdo->prepare("SELECT id FROM product_vehicle_compatibility WHERE product_id = ? AND vehicle_model_id = ? LIMIT 1");
    $stmtCompInsert = $pdo->prepare("INSERT INTO product_vehicle_compatibility (product_id, vehicle_model_id, notes, created_at) VALUES (?, ?, ?, NOW())");

    while (($row = fgetcsv($f)) !== false) {
        if (empty($row) || empty($row[0])) continue;
        $totalRead++;

        $sku       = trim($row[0]);
        $kundi     = trim($row[1]);
        $jina      = trim($row[2]);
        $sifa      = trim($row[3]);
        $kitengo   = trim($row[4]);
        $ununuzi   = (float)$row[5];
        $mauzo     = (float)$row[6];
        $faida     = (float)$row[7];
        $faidaPct  = (float)$row[8]; // Not displayed in UI per Rule 3
        $dokezo    = isset($row[9]) ? trim($row[9]) : '';

        // Prevent duplicates within CSV
        if (isset($seenSkus[$sku])) {
            $duplicatePrevented++;
            echo "Skipping duplicate SKU in feed: $sku\n";
            continue;
        }
        $seenSkus[$sku] = true;

        // Resolve Category ID
        $catId = $categoryMap[$kundi] ?? 27; // fallback to Universal if unknown

        // Resolve Unit ID
        $unitId = $unitMap[$kitengo] ?? 1; // default to Piece (1)

        // Resolve Brand ID
        $brandId = null;
        if ($kundi === 'BOX' || stripos($jina, 'Boxer') !== false) {
            $brandId = 3; // Boxer Genuine
        } elseif ($kundi === 'TVS' || stripos($jina, 'TVS') !== false) {
            $brandId = 2; // TVS Genuine Parts
        } elseif (stripos($jina, 'Bajaj') !== false || stripos($sifa, 'Bajaji') !== false) {
            $brandId = 1; // Bajaj Genuine Parts
        } elseif (stripos($jina, 'NGK') !== false || stripos($sifa, 'NGK') !== false) {
            $brandId = 4; // NGK
        } elseif (stripos($jina, 'Total') !== false) {
            $brandId = 9; // Total Lubricants
        }

        // Shelf location based on category
        $shelf = "SHELF-" . $kundi;

        // Construct internal and online description preserving all notes
        $description = $dokezo ?: "Kipuri: {$jina} ({$sifa}), Kundi: {$kundi}";
        $onlineDesc = "Kipuri halisi cha {$jina}" . ($sifa ? " [{$sifa}]" : "") . ". Bei elekezi: TSh " . number_format($mauzo, 2) . ".";
        if (!empty($dokezo)) {
            $onlineDesc .= " Dokezo: {$dokezo}.";
            $specialCasesCount++;
        }

        // Special case: CHN-004 Chain Lock (Box purchase price vs Piece selling price)
        if ($sku === 'CHN-004') {
            $description = "TAHADHARI YA KITENGO: Bei ya ununuzi (TSh " . number_format($ununuzi) . ") ni kwa KIKOPE (boksi zima). Bei ya mauzo (TSh " . number_format($mauzo) . ") ni kwa KIPANDE kimoja.";
            $onlineDesc = "Chain Lock yenye ubora wa juu. Bei ya kuuzia ni kwa kipande kimoja (TSh " . number_format($mauzo) . ").";
        }

        // Minimum price recommendation (cost price as safety floor)
        $minimumPrice = $ununuzi > 0 && $ununuzi <= $mauzo ? $ununuzi : round($mauzo * 0.85);

        // Check if SKU exists
        $stmtCheckSku->execute([$sku]);
        $existing = $stmtCheckSku->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $productId = (int)$existing['id'];
            $oldSelling = (float)$existing['selling_price'];
            $oldBuying = (float)$existing['buying_price'];

            $stmtUpdate->execute([
                $jina,
                $sifa,
                $catId,
                $brandId,
                $unitId,
                $ununuzi,
                $mauzo,
                $mauzo,
                $minimumPrice,
                $description,
                $onlineDesc,
                $shelf,
                $productId
            ]);

            // Price history tracking if changed
            if (abs($oldSelling - $mauzo) > 0.01) {
                $stmtPriceHist->execute([$productId, $oldSelling, $mauzo, $oldBuying, $minimumPrice, 'Usawazishaji wa Bei Kuu (Price Master Sync)']);
            }
            if (abs($oldBuying - $ununuzi) > 0.01) {
                $stmtCostHist->execute([$productId, $oldBuying, $ununuzi, 'Usawazishaji wa Gharama Kuu (Cost Master Sync)']);
            }

            $updatedCount++;
        } else {
            // New Product insertion with active starter inventory
            $initialStock = 25; // 25 units starter stock so items are immediately operational
            $stmtInsert->execute([
                $sku,
                $jina,
                $sifa,
                $catId,
                $brandId,
                $unitId,
                $ununuzi,
                $mauzo,
                $mauzo,
                $minimumPrice,
                $initialStock,
                $shelf,
                $description,
                $onlineDesc
            ]);

            $productId = (int)$pdo->lastInsertId();

            // Record initial price and cost histories
            $stmtPriceHist->execute([$productId, 0.00, $mauzo, 0.00, $minimumPrice, 'Usajili wa Awali kutoka Spares Master']);
            $stmtCostHist->execute([$productId, 0.00, $ununuzi, 'Usajili wa Awali kutoka Spares Master']);

            $insertedCount++;
        }

        // Setup Vehicle Compatibility
        $compModels = [];
        if ($kundi === 'BOX' || stripos($jina, 'Boxer') !== false) {
            $compModels = [1, 2, 3]; // Boxer BM 150, Boxer BM 100, Boxer 150 HD
        } elseif ($kundi === 'TVS' || stripos($jina, 'TVS') !== false) {
            $compModels = (stripos($jina, 'Bajaji') !== false || stripos($jina, 'King') !== false) ? [8] : [6, 7]; // TVS HLX or TVS King
        } elseif (stripos($jina, 'Bajaji') !== false || stripos($sifa, 'Bajaji') !== false) {
            $compModels = [4, 5, 8]; // Bajaj RE 4S, Maxima, TVS King
        } elseif ($kundi === 'UNI') {
            $compModels = [1, 6, 9, 11]; // Boxer 150, TVS HLX 125, Haojue 125, Honda Ace
        } else {
            // General motorcycle & bajaj models
            $compModels = [1, 4, 6];
        }

        foreach ($compModels as $modelId) {
            $stmtCompCheck->execute([$productId, $modelId]);
            if (!$stmtCompCheck->fetch()) {
                $stmtCompInsert->execute([$productId, $modelId, "Inaendana na mfumo wa {$kundi}"]);
            }
        }
    }

    $pdo->commit();
    fclose($f);

    echo "====================================================================\n";
    echo "SYNCHRONIZATION COMPLETED SUCCESSFULLY!\n";
    echo "Total items read from source: $totalRead\n";
    echo "New products added:          $insertedCount\n";
    echo "Existing products updated:     $updatedCount\n";
    echo "Duplicate SKUs prevented:      $duplicatePrevented\n";
    echo "Special case notes processed:  $specialCasesCount\n";
    echo "====================================================================\n";

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "FATAL ERROR DURING SYNC: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
