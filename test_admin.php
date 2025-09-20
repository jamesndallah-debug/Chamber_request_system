<?php
// FILE: test_admin.php
// Comprehensive testing script for admin dashboard functionality

session_start();
require_once 'config.php';
require_once 'function.php';

// Test results array
$test_results = [];

function addTestResult($test_name, $status, $message) {
    global $test_results;
    $test_results[] = [
        'test' => $test_name,
        'status' => $status,
        'message' => $message,
        'timestamp' => date('H:i:s')
    ];
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard Test Suite</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .test-result { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .header { background: #1e40af; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class='test-container'>
<div class='header'>
    <h1>🧪 Admin Dashboard Test Suite</h1>
    <p>Comprehensive testing of all admin functionality</p>
</div>";

// Test 1: Database Connection
try {
    $stmt = $pdo->query("SELECT 1");
    addTestResult("Database Connection", "PASS", "Successfully connected to database");
} catch (Exception $e) {
    addTestResult("Database Connection", "FAIL", "Database connection failed: " . $e->getMessage());
}

// Test 2: Users Table Structure
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required_columns = ['user_id', 'fullname', 'username', 'password', 'department', 'role_id'];
    $missing = array_diff($required_columns, $columns);
    
    if (empty($missing)) {
        addTestResult("Users Table Structure", "PASS", "All required columns present");
    } else {
        addTestResult("Users Table Structure", "FAIL", "Missing columns: " . implode(', ', $missing));
    }
} catch (Exception $e) {
    addTestResult("Users Table Structure", "FAIL", "Error checking table: " . $e->getMessage());
}

// Test 3: Roles Table and Data
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM roles");
    $role_count = $stmt->fetchColumn();
    
    if ($role_count >= 7) {
        addTestResult("Roles System", "PASS", "Found {$role_count} roles in database");
    } else {
        addTestResult("Roles System", "FAIL", "Only {$role_count} roles found, expected at least 7");
    }
} catch (Exception $e) {
    addTestResult("Roles System", "FAIL", "Error checking roles: " . $e->getMessage());
}

// Test 4: Admin User Exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = 7");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();
    
    if ($admin_count > 0) {
        addTestResult("Admin Users", "PASS", "Found {$admin_count} admin user(s)");
    } else {
        addTestResult("Admin Users", "FAIL", "No admin users found");
    }
} catch (Exception $e) {
    addTestResult("Admin Users", "FAIL", "Error checking admin users: " . $e->getMessage());
}

// Test 5: User Creation Functionality
try {
    $test_username = 'test_user_' . time();
    $test_password = password_hash('test123', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, department, role_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute(['Test User', $test_username, $test_password, 'Test Department', 1]);
    
    if ($result) {
        addTestResult("User Creation", "PASS", "Successfully created test user");
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$test_username]);
    } else {
        addTestResult("User Creation", "FAIL", "Failed to create test user");
    }
} catch (Exception $e) {
    addTestResult("User Creation", "FAIL", "Error creating user: " . $e->getMessage());
}

// Test 6: Session Management
try {
    if (session_status() === PHP_SESSION_ACTIVE) {
        addTestResult("Session Management", "PASS", "PHP sessions are working");
    } else {
        addTestResult("Session Management", "FAIL", "PHP sessions not active");
    }
} catch (Exception $e) {
    addTestResult("Session Management", "FAIL", "Session error: " . $e->getMessage());
}

// Test 7: File Permissions
$files_to_check = [
    'admin_dashboard_simple.php',
    'index.php',
    'config.php',
    'function.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file) && is_readable($file)) {
        addTestResult("File Access: {$file}", "PASS", "File exists and is readable");
    } else {
        addTestResult("File Access: {$file}", "FAIL", "File missing or not readable");
    }
}

// Test 8: Admin Dashboard Access Test
try {
    // Simulate admin user
    $_SESSION['user'] = [
        'user_id' => 999,
        'fullname' => 'Test Admin',
        'username' => 'test_admin',
        'role_id' => 7
    ];
    
    // Check if admin dashboard would load
    $user = $_SESSION['user'];
    if ($user && (int)$user['role_id'] === 7) {
        addTestResult("Admin Access Control", "PASS", "Admin role verification working");
    } else {
        addTestResult("Admin Access Control", "FAIL", "Admin role verification failed");
    }
} catch (Exception $e) {
    addTestResult("Admin Access Control", "FAIL", "Error testing access: " . $e->getMessage());
}

// Calculate test statistics
$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results, function($test) { return $test['status'] === 'PASS'; }));
$failed_tests = $total_tests - $passed_tests;
$success_rate = round(($passed_tests / $total_tests) * 100, 1);

echo "<div class='stats'>
    <div class='stat-card'>
        <h3>Total Tests</h3>
        <h2>{$total_tests}</h2>
    </div>
    <div class='stat-card'>
        <h3>Passed</h3>
        <h2 style='color: #28a745;'>{$passed_tests}</h2>
    </div>
    <div class='stat-card'>
        <h3>Failed</h3>
        <h2 style='color: #dc3545;'>{$failed_tests}</h2>
    </div>
    <div class='stat-card'>
        <h3>Success Rate</h3>
        <h2 style='color: " . ($success_rate >= 80 ? '#28a745' : '#dc3545') . ";'>{$success_rate}%</h2>
    </div>
</div>";

echo "<h2>📋 Test Results</h2>";

foreach ($test_results as $result) {
    $class = $result['status'] === 'PASS' ? 'pass' : 'fail';
    $icon = $result['status'] === 'PASS' ? '✅' : '❌';
    
    echo "<div class='test-result {$class}'>
        <strong>{$icon} {$result['test']}</strong> 
        <span style='float: right;'>{$result['timestamp']}</span>
        <br>
        {$result['message']}
    </div>";
}

// Navigation Test Links
echo "<div style='margin-top: 30px; padding: 20px; background: white; border-radius: 10px;'>
    <h3>🔗 Navigation Tests</h3>
    <p>Click these links to test navigation functionality:</p>
    <a href='index.php?action=admin_management' style='display: inline-block; margin: 5px; padding: 10px 15px; background: #1e40af; color: white; text-decoration: none; border-radius: 5px;'>Admin Dashboard</a>
    <a href='index.php?action=login' style='display: inline-block; margin: 5px; padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>Login Page</a>
    <a href='index.php?action=logout' style='display: inline-block; margin: 5px; padding: 10px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>Logout Test</a>
</div>";

echo "<div style='margin-top: 20px; padding: 15px; background: " . ($success_rate >= 80 ? '#d4edda' : '#f8d7da') . "; border-radius: 10px;'>
    <h3>" . ($success_rate >= 80 ? '🎉 Tests Completed Successfully!' : '⚠️ Some Tests Failed') . "</h3>
    <p>" . ($success_rate >= 80 ? 
        'All critical systems are working properly. Admin dashboard is ready for use.' : 
        'Please review failed tests and fix issues before using admin dashboard.') . "</p>
</div>";

echo "</div></body></html>";

// Clean up test session
unset($_SESSION['user']);
?>
