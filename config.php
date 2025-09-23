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

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
