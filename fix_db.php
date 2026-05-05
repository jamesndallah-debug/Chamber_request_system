<?php
require_once __DIR__ . '/config.php';

try {
    // Check if 'active' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER role_id");
        echo "Added 'active' column to users table.\n";
    }

    // Check if 'deleted_at' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER active");
        echo "Added 'deleted_at' column to users table.\n";
    }
    
    echo "Database schema updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
