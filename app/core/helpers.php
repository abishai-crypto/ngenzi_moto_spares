<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Global Helper Functions & Localization Shortcuts
 * Dar es Salaam, Tanzania
 */

if (!function_exists('__')) {
    /**
     * Translate a localization key with optional replacements
     */
    function __(string $key, array $replace = []): string {
        return \App\Core\Lang::get($key, $replace);
    }
}
