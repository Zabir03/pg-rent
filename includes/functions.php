<?php
/**
 * Shared helpers: session bootstrap, auth/RBAC guards, CSRF, escaping.
 * Include this at the top of every protected page.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

// ---- Session with timeout (30 min idle) -------------------
const SESSION_TIMEOUT = 1800;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: /auth/login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// ---- Output escaping (XSS protection) ---------------------
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---- CSRF -------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$sent)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please reload and try again.');
    }
}

// ---- Auth / RBAC guards -----------------------------------
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        http_response_code(403);
        exit('403 — You do not have access to this resource.');
    }
}

/** Returns the pg_owners.id for the logged-in owner (null for super admin). */
function current_owner_id(): ?int
{
    $u = current_user();
    if (!$u || $u['role'] !== 'pg_owner') {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM pg_owners WHERE user_id = ? LIMIT 1');
    $stmt->execute([$u['id']]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// ---- Activity log -----------------------------------------
function log_activity(string $action): void
{
    $u = current_user();
    $stmt = db()->prepare(
        'INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?,?,?)'
    );
    $stmt->execute([
        $u['id'] ?? null,
        $action,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// ---- Settings (key/value) ---------------------------------
function get_setting(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT setting_val FROM settings WHERE owner_id IS NULL AND setting_key=? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_val'] : $default;
}

// ---- Notifications ----------------------------------------
function notify(int $userId, string $title, string $body = '', string $type = 'info'): void
{
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, title, body, type) VALUES (?,?,?,?)'
    );
    $stmt->execute([$userId, $title, $body, $type]);
}

function unread_count(int $userId): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0'
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['c'];
}

// ---- Flash messages ---------------------------------------
function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="toast toast-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}
