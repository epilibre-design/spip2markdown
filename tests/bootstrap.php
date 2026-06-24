<?php
declare(strict_types=1);

if (!function_exists('include_spip')) {
    function include_spip(string $path): bool { return true; }
}

if (!function_exists('lire_config')) {
    function lire_config(string $key, mixed $default = null): mixed {
        return $GLOBALS['_test_config'][$key] ?? $default;
    }
}

if (!function_exists('sql_fetsel')) {
    function sql_fetsel(): array|false {
        $queue = &$GLOBALS['_test_sql_fetsel_results'];
        if (!empty($queue)) {
            return array_shift($queue);
        }
        return false;
    }
}
