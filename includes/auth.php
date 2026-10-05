<?php

/**
 * Authentication Functions
 * Bootstraps the hardened session configuration (includes/session.php)
 * before any session starts, and exposes auth, authorization, audit and
 * password-change-flow helpers used across the application.
 */

require_once __DIR__ . '/url_helper.php';

// Hardened session bootstrap — must run before any session_start().
// session.php sets strict mode, HttpOnly + SameSite cookies and timeouts,
// and starts the session itself if none is active.
require_once __DIR__ . '/session.php';

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_auth_user(): ?array
{
    ensure_session_started();
    return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
}

function require_auth(): void
{
    ensure_session_started();
    $user = current_auth_user();
    if (!$user) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_role(string $allowedRole): void
{
    require_auth();
    $user = current_auth_user();
    if (!$user || $user['role'] !== $allowedRole) {
        http_response_code(403);
        exit('Access denied: insufficient permissions');
    }
}

function require_superadmin(): void
{
    require_role('superadmin');
}

/**
 * Destructive operations must arrive via POST (§1.4).
 * Blocks drive-by/state-changing GET URLs from being linked, prefetched or cached.
 */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed: this operation must be submitted via POST.');
    }
}

function require_conference_admin(int $conferenceId): void
{
    require_auth();
    $user = current_auth_user();
    if ($user['role'] === 'superadmin') return;
    if ($user['role'] === 'conference_admin' && (int)$user['conference_id'] === $conferenceId) return;
    http_response_code(403);
    exit('Access denied: you do not manage this conference');
}

/**
 * Append an audit entry to logs/audit.log.
 * Used for sensitive operations (backups, exports, deletions, publishes).
 */
function audit_log(string $action, string $detail = ''): void
{
    $user = current_auth_user();
    $entry = sprintf(
        "[%s] [%s] user=%s role=%s ip=%s %s %s | %s\n",
        date('Y-m-d H:i:s'),
        $action,
        $user['username'] ?? 'anonymous',
        $user['role'] ?? '-',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['REQUEST_METHOD'] ?? '-',
        $_SERVER['REQUEST_URI'] ?? '-',
        $detail
    );
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/audit.log', $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Password-change enforcement (seeded/default accounts).
 */
function must_change_password(): bool
{
    ensure_session_started();
    return !empty($_SESSION['force_password_change']);
}

function force_password_change(): void
{
    ensure_session_started();
    $_SESSION['force_password_change'] = true;
}

function mark_password_changed(): void
{
    ensure_session_started();
    unset($_SESSION['force_password_change']);
}
