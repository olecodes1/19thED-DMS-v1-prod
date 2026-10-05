<?php
/**
 * auth.php - Authentication helper functions (INTEGRATED VERSION)
 * This file now serves as a compatibility layer for YPD API to use AdminDash authentication
 */

// Load AdminDash authentication
require_once __DIR__ . '/../includes/auth.php';

// Compatibility functions to bridge YPD API with AdminDash auth
function isLoggedIn(): bool {
    ensure_session_started();
    return current_auth_user() !== null;
}

function getCurrentUser(): ?array {
    ensure_session_started();
    return current_auth_user();
}

function hasRole(string $role): bool {
    ensure_session_started();
    $user = current_auth_user();
    return $user && $user['role'] === $role;
}

function hasAnyRole(array $roles): bool {
    ensure_session_started();
    $user = current_auth_user();
    return $user && in_array($user['role'], $roles, true);
}