<?php
// FILE: config.php
// Configuration file for database connection and other settings.

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chamber_request_system');

// Base URL for the application
define('BASE_URL', 'http://localhost/chamber_request_system');  // Base URL without trailing slash

// Path for uploaded attachments
define('UPLOAD_PATH', __DIR__ . '/uploads/');

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
