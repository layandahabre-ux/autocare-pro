<?php
// config/auth.php — אימות, הרשאות (RBAC), ופונקציות עזר משותפות

require_once __DIR__ . '/database.php';

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => false, // להפוך ל-true בסביבת ייצור עם HTTPS
            'use_strict_mode' => true,
        ]);
    }
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            jsonErr('נדרשת התחברות', 401);
        }
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function currentUser(): array
{
    startSession();
    return $_SESSION['user'] ?? [];
}

// תפקידים: admin (מנהל), mechanic (מכונאי), receptionist (פקיד קבלה)
function hasRole(string ...$roles): bool
{
    startSession();
    return in_array($_SESSION['user']['role'] ?? '', $roles, true);
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        if (isApiRequest()) {
            jsonErr('אין לך הרשאה לבצע פעולה זו', 403);
        }
        http_response_code(403);
        die('<div style="font-family:sans-serif;direction:rtl;text-align:center;margin-top:80px">
                <h1>403</h1><p>אין לך הרשאה לצפות בעמוד זה.</p></div>');
    }
}

function isApiRequest(): bool
{
    return str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function logDeniedAccess(string $user): void
{
    Database::query(
        "INSERT INTO tbl_login_attempts (ip_address, machine_name, attempt_user)
         VALUES (?, ?, ?)",
        [$_SERVER['REMOTE_ADDR'] ?? '', gethostname() ?: '', $user]
    );
}

// ── תגובות JSON אחידות ל-API ─────────────────────────
function jsonOk(mixed $data = [], string $message = ''): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'data' => $data, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonErr(string $error, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── עזרי קלט ──────────────────────────────────────────
function sanitize(mixed $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function paginate(int $page, int $limit = 20): array
{
    $page   = max(1, $page);
    $limit  = max(1, min($limit, 100));
    $offset = ($page - 1) * $limit;
    return ['limit' => $limit, 'offset' => $offset, 'page' => $page];
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
