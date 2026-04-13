<?php
// FILE: config.php
// Configuration file for database connection and other settings.

// --- Minimal .env loader (no external dependency) ---
if (!function_exists('load_env')) {
    function load_env(string $envPath): void {
        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            // Support KEY="VALUE" and KEY='VALUE' and KEY=VALUE
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            // Do not override if already set in real env
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env from project root (same folder as this file) if present
load_env(__DIR__ . '/.env');

// Database credentials (env overrides; defaults preserved)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'chamber_request_system');

// Base URL for the application (env override)
define('BASE_URL', rtrim(getenv('BASE_URL') ?: 'http://localhost/chamber_request_system', '/'));

// Path for uploaded attachments
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Google OAuth credentials (env overrides)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Function to handle errors
function handle_exception($e) {
    // Log the error
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    // Display a user-friendly message using a standalone error page
    header('Location: simple_error.php');
    exit;
}

set_exception_handler('handle_exception');

// Start the session with inactivity timeout (20 minutes)
// Set session lifetime before starting the session
@ini_set('session.gc_maxlifetime', 1200); // 20 minutes
@ini_set('session.cookie_lifetime', 0);   // until browser closes
@ini_set('session.use_strict_mode', 1);
@ini_set('session.use_only_cookies', 1);
@ini_set('session.cookie_httponly', 1);

// Cookie security (secure only when HTTPS)
$__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

// Set cookie params before session_start (PHP 7.3+ supports array form; fall back for older versions)
try {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $__isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        // Legacy fallback: cannot reliably set SameSite, but keep httponly/secure best-effort
        @ini_set('session.cookie_secure', $__isHttps ? '1' : '0');
    }
} catch (Throwable $e) { /* ignore */ }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce inactivity timeout server-side
try {
    $now = time();
    $timeout = 1200; // 20 minutes in seconds
    $isBackground = (isset($_SERVER['HTTP_X_BACKGROUND']) && $_SERVER['HTTP_X_BACKGROUND'] === '1')
        || (isset($_GET['background']) && $_GET['background'] === '1');
    if (!empty($_SESSION['LAST_ACTIVITY']) && ($now - (int)$_SESSION['LAST_ACTIVITY']) > $timeout) {
        // Too long since last activity: destroy session and force login
        session_unset();
        session_destroy();
        header('Location: index.php?action=login&timeout=1');
        exit;
    }
    // Only update LAST_ACTIVITY for non-background (user-driven) requests
    if (!$isBackground) {
        $_SESSION['LAST_ACTIVITY'] = $now;
    }
} catch (Throwable $e) { /* ignore */ }
