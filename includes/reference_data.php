<?php

/**
 * Reference Data Caching Helper
 * Provides cached access to frequently accessed reference data to reduce database queries
 * OPTIMIZED: Adds configurable TTL (time-to-live) — fresh until age exceeds TTL,
 * then refreshed on next call. Reduces database hits for stable data (conferences, areas).
 */

// Cache configuration — adjustable per application
const REFERENCE_CACHE_TTL = 300; // 5 minutes (seconds)

function get_conferences(PDO $pdo): array
{
    static $cache = null;
    static $cacheTime = 0;
    if ($cache !== null && (time() - $cacheTime) < REFERENCE_CACHE_TTL) {
        return $cache;
    }
    $cache = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
    $cacheTime = time();
    return $cache;
}

function get_areas(PDO $pdo): array
{
    static $cache = null;
    static $cacheTime = 0;
    if ($cache !== null && (time() - $cacheTime) < REFERENCE_CACHE_TTL) {
        return $cache;
    }
    $cache = $pdo->query("SELECT area_id, area_name FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
    $cacheTime = time();
    return $cache;
}

function get_components(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = ['MB', 'AS', 'Y', 'YA'];
    }
    return $cache;
}

function get_genders(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = ['M', 'F'];
    }
    return $cache;
}

function get_church_statuses(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = ['active', 'inactive', 'closed'];
    }
    return $cache;
}

function get_media_types(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = ['image', 'video', 'audio'];
    }
    return $cache;
}

function get_media_categories(PDO $pdo): array
{
    static $cache = null;
    static $cacheTime = 0;
    if ($cache !== null && (time() - $cacheTime) < REFERENCE_CACHE_TTL) {
        return $cache;
    }
    $cache = $pdo->query("SELECT DISTINCT category FROM media_items WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $cacheTime = time();
    return $cache;
}
