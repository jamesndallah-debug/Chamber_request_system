<?php
// Minimal test version to debug 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/function.php';

// Check if user is authenticated
$user = current_user();
if (!$user || $user['role_id'] != 7) {
    die("Access denied. Admin only.");
}

echo "Debug: User authenticated - " . htmlspecialchars($user['fullname']);

// Test database connection
try {
    global $pdo;
    echo "<br>Debug: Database connection successful";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test simple query
try {
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<br>Debug: User count = " . $count;
} catch (Exception $e) {
    die("Query failed: " . $e->getMessage());
}

// Test directorates query
try {
    $directorates = $pdo->query("SELECT * FROM directorates ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    echo "<br>Debug: Directorates count = " . count($directorates);
} catch (Exception $e) {
    die("Directorates query failed: " . $e->getMessage());
}

// Test roles query
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
    echo "<br>Debug: Roles count = " . count($roles);
} catch (Exception $e) {
    die("Roles query failed: " . $e->getMessage());
}

echo "<br>Debug: All tests passed!";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings Test</title>
</head>
<body>
    <h1>Settings Test Page</h1>
    <p>If you can see this, basic functionality works.</p>
    <p>User: <?= htmlspecialchars($user['fullname']) ?></p>
    <p>Users: <?= $count ?></p>
    <p>Directorates: <?= count($directorates) ?></p>
    <p>Roles: <?= count($roles) ?></p>
</body>
</html>
