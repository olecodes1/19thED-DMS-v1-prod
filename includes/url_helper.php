<?php

/**
 * URL Helper — generates absolute URLs from the centralized config
 * so that moving the project to a different path only requires
 * updating config.php's urls.base value.
 */

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        static $base = null;
        if ($base === null) {
            $config = require __DIR__ . '/../config.php';
            $base = rtrim($config['urls']['base'] ?? '', '/');
        }
        return $base . '/' . ltrim($path, '/');
    }

    function asset_url(string $path): string {
        $absolute = __DIR__ . '/../' . ltrim($path, '/');
        $version = is_file($absolute) ? (string)filemtime($absolute) : '1';
        return base_url($path) . '?v=' . rawurlencode($version);
    }
}
