<?php
require_once __DIR__ . '/function.php';

echo "Database Schema Check:\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error checking users table: " . $e->getMessage() . "\n";
}

echo "\nUser Status Check (First 10 users):\n";
try {
    $stmt = $pdo->query("SELECT user_id, fullname, username, active, deleted_at FROM users LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - ID: " . $row['user_id'] . " | Name: " . $row['fullname'] . " | Active: " . $row['active'] . " | Deleted: " . ($row['deleted_at'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error checking user status: " . $e->getMessage() . "\n";
}
?>
