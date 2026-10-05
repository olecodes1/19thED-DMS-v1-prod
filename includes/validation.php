<?php

/**
 * Comprehensive input validation functions
 */

/**
 * Validate and sanitize a string input
 */
function validate_string(string $input, int $minLength = 0, int $maxLength = 255): ?string
{
    $trimmed = trim($input);
    if (strlen($trimmed) < $minLength || strlen($trimmed) > $maxLength) {
        return null;
    }
    return $trimmed;
}

/**
 * Validate an integer input
 */
function validate_int($input, int $min = 0, ?int $max = null): ?int
{
    $value = filter_var($input, FILTER_VALIDATE_INT);
    if ($value === false) {
        return null;
    }
    if ($value < $min || ($max !== null && $value > $max)) {
        return null;
    }
    return $value;
}

/**
 * Validate a date string
 */
function validate_date(string $date): ?string
{
    $format = 'Y-m-d';
    $d = DateTime::createFromFormat($format, $date);
    if ($d && $d->format($format) === $date) {
        return $date;
    }
    return null;
}

/**
 * Validate email address
 */
function validate_email(string $email): ?string
{
    $value = filter_var($email, FILTER_VALIDATE_EMAIL);
    return $value ? $value : null;
}

/**
 * Validate phone number (basic validation)
 */
function validate_phone(string $phone): ?string
{
    $trimmed = trim($phone);
    if (preg_match('/^[0-9+\s()-]{7,20}$/', $trimmed)) {
        return $trimmed;
    }
    return null;
}

/**
 * Validate enum value against allowed options
 */
function validate_enum($value, array $allowed): ?string
{
    if (in_array($value, $allowed, true)) {
        return $value;
    }
    return null;
}

/**
 * Sanitize text output (XSS prevention)
 */
function sanitize_output(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file upload
 */
function validate_file_upload(array $file, array $allowedTypes, int $maxSize = 52428800): ?array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if (!isset($file['size']) || $file['size'] <= 0 || $file['size'] > $maxSize) {
        return null;
    }
    
    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) {
        return null;
    }
    
    // Validate MIME type
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detectedMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            // Basic MIME family validation
            $mimeFamilies = [
                'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
                'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'],
            ];
            
            $isValidMime = false;
            foreach ($mimeFamilies as $family => $mimes) {
                if (in_array($detectedMime, $mimes, true)) {
                    $isValidMime = true;
                    break;
                }
            }
            
            if (!$isValidMime) {
                return null;
            }
        }
    }
    
    return $file;
}

/**
 * Check for double extensions in filename
 */
function has_double_extension(string $filename): bool
{
    $parts = explode('.', $filename);
    return count($parts) > 2;
}

/**
 * Generate safe filename
 */
function generate_safe_filename(string $original): string
{
    $basename = pathinfo($original, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    
    // Remove any double extensions first
    $safeBasename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $basename);
    $randomSuffix = bin2hex(random_bytes(4));
    
    return date('YmdHis') . '_' . $safeBasename . '_' . $randomSuffix . '.' . $extension;
}