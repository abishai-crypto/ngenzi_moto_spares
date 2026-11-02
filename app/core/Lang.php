<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Multi-Language Localization System (Phase 18)
 * Bilingual Engine supporting Swahili (Kiswahili) & English.
 * Dar es Salaam, Tanzania
 */

namespace App\Core;

class Lang {

    private static ?string $currentLocale = null;

    /**
     * Dictionary of translations
     */
    private static array $translations = [
        'sw' => [
            // Navigation & Header
            'nav_home'                 => 'Nyumbani',
            'nav_shop'                 => 'Katalogi',
            'nav_my_vehicle'           => 'Chombo Changu',
            'nav_cart'                 => 'Kapu',
            'nav_wishlist'             => 'Matamanio',
            'nav_login'                => 'Ingia',
            'nav_register'             => 'Jisajili',
            'nav_logout'               => 'Toka',
            'nav_dashboard'            => 'Dashibodi Yangu',
            'nav_orders'               => 'Maagizo Yangu',
            'nav_addresses'            => 'Anwani Zangu',
            'nav_notifications'        => 'Taarifa & Meseji',
            'nav_profile'              => 'Mipangilio ya Akaunti',
            'nav_staff_portal'         => 'Mfumo wa Utawala (Admin)',
            'nav_staff'                => 'Wafanyakazi',
            'search_placeholder'       => 'Tafuta kipuri, SKU, au jina la chombo...',
            'search_btn'               => 'Tafuta Sasa',
            'lang_name'                => 'Kiswahili',
            'lang_code'                => 'SW',
            'lang_switch'              => 'Badili Lugha',

            // Hero & Badges
            'hero_badge'               => 'DUKA RASMI LA MTANDAONI — KARIAKOO',
            'hero_title_prefix'        => 'Vipuri Halisi vya',
            'hero_title_highlight'     => 'Pikipiki & Bajaji',
            'hero_title_suffix'        => 'Dar es Salaam',
            'hero_desc'                => 'Tazama na kuagiza vipuri vyote vya Boxer BM150, TVS King, Bajaj RE 4S, Honda, na SanLG vikiwa na bei zake halisi.',
            'hero_btn_shop'            => 'Nunua Vipuri Sasa',
            'hero_btn_vehicle'         => 'Tafuta kwa Chombo',
            'hero_filter_title'        => 'Tafuta Kipuri Haraka',
            'hero_filter_vehicle_type' => 'Aina ya Chombo',
            'hero_filter_all_types'    => 'Aina zote (Pikipiki & Bajaji)',
            'hero_filter_motorcycle'   => 'Pikipiki (Motorcycle)',
            'hero_filter_bajaj'        => 'Bajaji (Three-Wheeler)',

            // Value Propositions
            'val_prop_1_title'         => 'Vipuri Vilivyothibitishwa',
            'val_prop_1_desc'          => 'Vipuri vyote vimekaguliwa ubora na uimara wake 100%.',
            'val_prop_2_title'         => 'Bei Wazi kwa Kila Kipuri',
            'val_prop_2_desc'          => 'Kila kipuri kina bei yake hadharani bila kujali kipo stoo au unaagiza.',
            'val_prop_3_title'         => 'Kuchukua Dukani Kariakoo',
            'val_prop_3_desc'          => 'Huduma ya haraka kaunta ya duka letu Mtaa wa Nyamwezi/Msimbazi.',

            // Products & Catalog
            'catalog_title'            => 'Katalogi Kamili ya Vipuri & Bei Zake',
            'catalog_subtitle'         => 'Tazama vipuri vyote vilivyopo stoo na vinavyoagizwa vikiwa na bei kamili',
            'featured_title'           => 'Vipuri Vilivyoangaziwa',
            'featured_subtitle'        => 'Vipuri vinavyotafutwa na kuaminika zaidi Kariakoo',
            'categories_title'         => 'Kategoria za Vipuri',
            'categories_subtitle'      => 'Chagua kulingana na sehemu ya chombo unachotaka kukitengeneza',
            'vehicles_title'           => 'Nunua kwa Aina ya Chombo',
            'vehicles_subtitle'        => 'Chagua mtindo wa pikipiki au bajaji yako kuona vipuri vinavyoendana',
            'selling_price'            => 'Bei ya Kuuza',
            'in_stock'                 => 'Stoo Ipo',
            'out_of_stock'             => 'Imeisha Stoo',
            'on_order'                 => 'Inapatikana kwa Oda',
            'add_to_cart'              => 'Weka Kikapuni',
            'order_on_request'         => 'Agiza Kipuri',
            'view_details'             => 'Maelezo',
            'compatible_models'        => 'Inafaa vyombo',
            'all_parts'                => 'Vipuri Vyote',
            'no_parts_found'           => 'Hakuna vipuri vilivyopatikana kwa vigezo hivi.',

            // Cart & Checkout
            'cart_title'               => 'Kapu Lako la Ununuzi',
            'cart_empty'               => 'Kapu lako halina bidhaa kwa sasa.',
            'checkout_title'           => 'Kamilisha Agizo Lako (Checkout)',
            'pickup_procedure'         => 'Utaratibu wa Kuchukua',
            'store_pickup'             => 'Kuchukua Dukani (BURE)',
            'pickup_location'          => 'Duka Kuu — Ngenzi Moto Spare Parts, Kariakoo Dar es Salaam',
            'subtotal'                 => 'Jumla ya Vipuri',
            'delivery_fee'             => 'Gharama ya Usafirishaji',
            'free'                     => 'BURE (TSh 0.00)',
            'grand_total'              => 'Jumla Kuu ya Kulipa',
            'confirm_order_btn'        => 'Thibitisha Agizo Lako',
            'payment_method'           => 'Njia ya Malipo',
            'pay_mobile'               => 'Lipa kwa Simu (Lipa Namba ya Mtandao)',
            'pay_bank'                 => 'Uhamisho wa Benki (CRDB / NMB)',
            'pay_store'                => 'Lipa Dukani Utakapofika (Cash / POS Kaunta)',
            'customer_name'            => 'Jina Kamili la Mteja',
            'customer_phone'           => 'Namba ya Simu ya Mawasiliano',
            'customer_notes'           => 'Maelekezo au Ujumbe wa Ziada (Hiari)',
            'order_success'            => 'Agizo Lako Limethibitishwa Kikamilifu!',

            // Order Tracking Statuses
            'status_pending'           => 'Limepokelewa',
            'status_confirmed'         => 'Limethibitishwa',
            'status_processing'        => 'Linaandaliwa Dukani',
            'status_ready'             => 'Liko Tayari Kuchukuliwa',
            'status_delivered'         => 'Limekabidhiwa Dukani',
            'status_cancelled'         => 'Limeghairiwa',
        ],

        'en' => [
            // Navigation & Header
            'nav_home'                 => 'Home',
            'nav_shop'                 => 'Catalog',
            'nav_my_vehicle'           => 'My Vehicle',
            'nav_cart'                 => 'Cart',
            'nav_wishlist'             => 'Wishlist',
            'nav_login'                => 'Login',
            'nav_register'             => 'Register',
            'nav_logout'               => 'Logout',
            'nav_dashboard'            => 'My Dashboard',
            'nav_orders'               => 'My Orders',
            'nav_addresses'            => 'My Addresses',
            'nav_notifications'        => 'Notifications',
            'nav_profile'              => 'Account Settings',
            'nav_staff_portal'         => 'Staff Admin Portal',
            'nav_staff'                => 'Staff Portal',
            'search_placeholder'       => 'Search spare part, SKU, or vehicle name...',
            'search_btn'               => 'Search Now',
            'lang_name'                => 'English',
            'lang_code'                => 'EN',
            'lang_switch'              => 'Switch Language',

            // Hero & Badges
            'hero_badge'               => 'OFFICIAL ONLINE STORE — KARIAKOO',
            'hero_title_prefix'        => 'Genuine Spare Parts for',
            'hero_title_highlight'     => 'Motorcycles & Bajaj',
            'hero_title_suffix'        => 'Dar es Salaam',
            'hero_desc'                => 'Browse and order genuine spare parts for Boxer BM150, TVS King, Bajaj RE 4S, Honda, and SanLG with transparent pricing.',
            'hero_btn_shop'            => 'Shop Spare Parts Now',
            'hero_btn_vehicle'         => 'Search by Vehicle',
            'hero_filter_title'        => 'Quick Part Lookup',
            'hero_filter_vehicle_type' => 'Vehicle Type',
            'hero_filter_all_types'    => 'All types (Motorcycle & Bajaj)',
            'hero_filter_motorcycle'   => 'Motorcycle',
            'hero_filter_bajaj'        => 'Bajaj (Three-Wheeler)',

            // Value Propositions
            'val_prop_1_title'         => 'Certified Genuine Parts',
            'val_prop_1_desc'          => 'All spare parts undergo 100% quality and durability verification.',
            'val_prop_2_title'         => 'Transparent Prices for All Items',
            'val_prop_2_desc'          => 'Every part has its price displayed whether physically in stock or on order.',
            'val_prop_3_title'         => 'In-Store Pickup in Kariakoo',
            'val_prop_3_desc'          => 'Quick counter collection at our store on Nyamwezi/Msimbazi Street.',

            // Products & Catalog
            'catalog_title'            => 'Full Spare Parts Catalog & Prices',
            'catalog_subtitle'         => 'Browse all in-stock and orderable parts with accurate prices displayed',
            'featured_title'           => 'Featured Spare Parts',
            'featured_subtitle'        => 'Most demanded and trusted spare parts in Kariakoo',
            'categories_title'         => 'Part Categories',
            'categories_subtitle'      => 'Select by vehicle system or repair category',
            'vehicles_title'           => 'Shop by Vehicle Type',
            'vehicles_subtitle'        => 'Select your motorcycle or bajaj model to see compatible parts',
            'selling_price'            => 'Selling Price',
            'in_stock'                 => 'In Stock',
            'out_of_stock'             => 'Out of Stock',
            'on_order'                 => 'Available on Order',
            'add_to_cart'              => 'Add to Cart',
            'order_on_request'         => 'Order Part',
            'view_details'             => 'Details',
            'compatible_models'        => 'Compatible with',
            'all_parts'                => 'All Parts',
            'no_parts_found'           => 'No spare parts matched your search criteria.',

            // Cart & Checkout
            'cart_title'               => 'Your Shopping Cart',
            'cart_empty'               => 'Your shopping cart is currently empty.',
            'checkout_title'           => 'Complete Your Order (Checkout)',
            'pickup_procedure'         => 'Collection Method',
            'store_pickup'             => 'Store Pickup (FREE)',
            'pickup_location'          => 'Main Store — Ngenzi Moto Spare Parts, Kariakoo Dar es Salaam',
            'subtotal'                 => 'Parts Subtotal',
            'delivery_fee'             => 'Delivery Fee',
            'free'                     => 'FREE (TSh 0.00)',
            'grand_total'              => 'Total Amount Payable',
            'confirm_order_btn'        => 'Confirm Your Order',
            'payment_method'           => 'Payment Method',
            'pay_mobile'               => 'Mobile Money (Lipa Namba)',
            'pay_bank'                 => 'Bank Transfer (CRDB / NMB)',
            'pay_store'                => 'Pay at Store on Pickup (Cash / POS Counter)',
            'customer_name'            => 'Full Customer Name',
            'customer_phone'           => 'Contact Phone Number',
            'customer_notes'           => 'Special Instructions / Notes (Optional)',
            'order_success'            => 'Your Order Has Been Confirmed Successfully!',

            // Order Tracking Statuses
            'status_pending'           => 'Received',
            'status_confirmed'         => 'Confirmed',
            'status_processing'        => 'Preparing at Store',
            'status_ready'             => 'Ready for Pickup',
            'status_delivered'         => 'Handed Over at Store',
            'status_cancelled'         => 'Cancelled',
        ]
    ];

    /**
     * Get current active language code ('sw' or 'en')
     */
    public static function getLocale(): string {
        if (self::$currentLocale !== null) {
            return self::$currentLocale;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $locale = $_SESSION['lang'] ?? ($_COOKIE['app_lang'] ?? 'sw');
        $locale = strtolower(trim((string)$locale));

        if (!in_array($locale, ['sw', 'en'], true)) {
            $locale = 'sw';
        }

        self::$currentLocale = $locale;
        return self::$currentLocale;
    }

    /**
     * Set active language code and persist in session and cookie
     */
    public static function setLocale(string $locale): void {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, ['sw', 'en'], true)) {
            $locale = 'sw';
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION['lang'] = $locale;
        self::$currentLocale = $locale;

        // Persist cookie for 1 year
        if (!headers_sent()) {
            setcookie('app_lang', $locale, time() + (86400 * 365), '/');
        }
    }

    /**
     * Translate a given key
     */
    public static function get(string $key, array $replace = []): string {
        $locale = self::getLocale();
        $text = self::$translations[$locale][$key] ?? (self::$translations['sw'][$key] ?? $key);

        foreach ($replace as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, (string)$value, $text);
        }

        return $text;
    }

    /**
     * Get all supported locales with labels and flags
     */
    public static function getSupportedLocales(): array {
        return [
            'sw' => [
                'code'  => 'sw',
                'name'  => 'Kiswahili',
                'flag'  => '🇹🇿',
                'label' => '🇹🇿 Kiswahili'
            ],
            'en' => [
                'code'  => 'en',
                'name'  => 'English',
                'flag'  => '🇬🇧',
                'label' => '🇬🇧 English'
            ]
        ];
    }
}

