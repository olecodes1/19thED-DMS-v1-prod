<?php

/**
 * Centralized configuration file
 * This file contains all configuration values
 * Update these values for different environments
 */

return [
    // Database configuration - Update for different hosting environments
    'db' => [
        'host' => '127.0.0.1', // Use IP instead of localhost to avoid socket issues
        'port' => '3307', // XAMPP MySQL default port
        'fallback_port' => '3306', // Standard MySQL port fallback
        'name' => '19edypd_db',
        'user' => 'root',
        'pass' => '', // XAMPP default password
        'charset' => 'utf8mb4',
    ],
    
    // Application settings
    'app' => [
        'debug' => true, // Enable debug mode to see actual error messages
        'name' => '19th Episcopal District AdminDash',
        'timezone' => 'UTC',
        // Set to true when serving over HTTPS (production). Enables Secure session cookies.
        'secure_cookies' => false,
    ],
    
    // Security settings
    'security' => [
        'session_timeout' => 3600, // 1 hour in seconds
        'csrf_token_length' => 32,
        'password_min_length' => 8,
        'login_rate_limit' => [
            'attempts' => 5,
            'window' => 900, // 15 minutes in seconds
        ],
    ],
    
    // File upload settings
    'uploads' => [
        'max_file_size' => 52428800, // 50MB in bytes
        'max_doc_size' => 26214400, // 25MB for PDF documents (e.g. event booklets)
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_videos' => ['mp4', 'webm', 'ogg', 'mov'],
        'allowed_audio' => ['mp3', 'wav', 'ogg', 'm4a'],
        'allowed_docs' => ['pdf'],
        'upload_dir' => __DIR__ . '/assets/uploads/media',
        'event_booklet_dir' => __DIR__ . '/assets/uploads/event_booklets',
    ],
    
    // URL paths
    'urls' => [
        'base' => '/AdminDash',
        'login' => '/AdminDash/login.php',
        'home' => '/AdminDash/index.php',
    ],
    
    // District information
    'district' => [
        'id' => 19,
        'name' => '19th Episcopal District',
    ],

    // Feature flags - cache column existence to avoid expensive information_schema queries
    'features' => [
        'joined_ypd' => true,
        'full_member_of_church' => true,
        'occupational_status' => true,
    ],
];