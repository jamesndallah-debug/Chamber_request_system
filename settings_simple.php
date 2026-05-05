<?php
// Ultra-minimal settings page to find exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading function.php...<br>";

try {
    require_once __DIR__ . '/function.php';
    echo "Step 2: function.php loaded successfully<br>";
} catch (Exception $e) {
    die("Error loading function.php: " . $e->getMessage());
}

echo "Step 3: Checking user session...<br>";

try {
    $user = current_user();
    if (!$user) {
        die("No user in session");
    }
    echo "Step 4: User found - " . htmlspecialchars($user['fullname']) . "<br>";
} catch (Exception $e) {
    die("Error checking user: " . $e->getMessage());
}

echo "Step 5: Checking user role...<br>";

if ($user['role_id'] != 7) {
    die("Access denied. User role: " . $user['role_id']);
}

echo "Step 6: User is admin, continuing...<br>";

// Simple HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Settings</title>
</head>
<body>
    <h1>Settings Page</h1>
    <p>Welcome: <?= htmlspecialchars($user['fullname']) ?></p>
    <p>Role: <?= htmlspecialchars($user['role_id']) ?></p>
    <p>If you can see this, basic loading works.</p>
    
    <?php
    echo "Step 7: Testing database connection...<br>";
    try {
        global $pdo;
        if ($pdo) {
            echo "Step 8: Database connection available<br>";
            
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Step 9: User count = $count<br>";
        } else {
            echo "Step 8: No database connection<br>";
        }
    } catch (Exception $e) {
        echo "Step 8: Database error: " . $e->getMessage() . "<br>";
    }
    ?>
</body>
</html>
