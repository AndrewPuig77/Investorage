<?php
// bootstrap.php - loaded first by every page and action:
// error handling, the session cookie, security headers and CSRF protection.

// Errors go to the server log, never to the visitor
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Links in the plain error pages below, from pages or from actions/
$appRoot = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'actions' ? '../' : '';

function plainPage($status, $title, $text) {
    global $appRoot;
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . htmlspecialchars($title) . ' | Investorage</title></head>'
        . '<body style="margin:0;padding:48px 24px;background:#0E1116;color:#E6E9EE;font-family:Inter,system-ui,sans-serif">'
        . '<main style="max-width:520px"><h1 style="font-size:1.5rem;margin:0 0 12px">' . htmlspecialchars($title) . '</h1>'
        . '<p style="color:#9AA4B2;line-height:1.6">' . htmlspecialchars($text) . '</p>'
        . '<p><a style="color:#6EA8DE" href="javascript:history.back()">Go back</a> &middot; '
        . '<a style="color:#6EA8DE" href="' . $appRoot . 'activeHome.php">Dashboard</a></p></main></body></html>';
    exit();
}

// Anything unexpected, including database errors, is logged and shown as a plain message
set_exception_handler(function (Throwable $e) {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    plainPage(500, 'Something went wrong', 'That didn\'t work. Please go back and try again. If it keeps happening, sign out and back in.');
});

// Session cookie: not readable by scripts, not sent on cross-site requests, HTTPS-only when the site is on HTTPS
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '3600');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Security headers: no framing (clickjacking), no MIME sniffing, no full URLs leaked to other sites
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// CSRF: every form post must carry this session's token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function csrfToken() {
    return $_SESSION['csrf_token'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $sentToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sentToken) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
        error_log('CSRF token missing or wrong on ' . ($_SERVER['SCRIPT_NAME'] ?? 'unknown script'));
        plainPage(403, 'This form expired', 'For your security the form could not be sent. Go back, refresh the page, and try again.');
    }
}
