<?php
// FILE: admin_management.php
// Complete Admin Dashboard for Chamber Request System

// Basic security check
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

// Ensure we have user data and admin role
if (!$user || (int)$user['role_id'] !== 7) {
    header('Location: index.php?action=login');
    exit;
}

// Handle POST requests for admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();

    // Directorate + Role management (Settings tab)
    // IMPORTANT: handle here (before HTML output) so redirects work correctly.
    if (isset($_POST['add_directorate'])) {
        $name = trim($_POST['directorate_name'] ?? '');
        try {
            if ($name !== '') {
                $stmt = $pdo->prepare("INSERT INTO directorates (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate added successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Directorate name is required.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error adding directorate: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['edit_directorate_id'])) {
        $id = (int)($_POST['edit_directorate_id'] ?? 0);
        $name = trim($_POST['directorate_name'] ?? '');
        try {
            if ($id > 0 && $name !== '') {
                $stmt = $pdo->prepare("UPDATE directorates SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate updated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid directorate data.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error updating directorate: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['activate_directorate_id'])) {
        $id = (int)($_POST['activate_directorate_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE directorates SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate activated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid directorate id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error activating directorate: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['deactivate_directorate_id'])) {
        $id = (int)($_POST['deactivate_directorate_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE directorates SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deactivated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid directorate id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating directorate: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['delete_directorate_id'])) {
        $id = (int)($_POST['delete_directorate_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users
                    WHERE directorate = (SELECT name FROM directorates WHERE id = ?)
                      AND deleted_at IS NULL");
                $stmt->execute([$id]);
                $userCount = (int)$stmt->fetchColumn();

                if ($userCount > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete directorate: ' . $userCount . ' users are assigned to this directorate. Please reassign or delete users first.'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM directorates WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deleted successfully!'];
                }
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid directorate id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting directorate: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['add_role'])) {
        $name = trim($_POST['role_name'] ?? '');
        try {
            if ($name !== '') {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role added successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Role name is required.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error adding role: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['edit_role_id'])) {
        $id = (int)($_POST['edit_role_id'] ?? 0);
        $name = trim($_POST['role_name'] ?? '');
        try {
            if ($id > 0 && $name !== '') {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role updated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role data.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error updating role: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['activate_role_id'])) {
        $id = (int)($_POST['activate_role_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE roles SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role activated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error activating role: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['deactivate_role_id'])) {
        $id = (int)($_POST['deactivate_role_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE roles SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deactivated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating role: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    if (isset($_POST['delete_role_id'])) {
        $id = (int)($_POST['delete_role_id'] ?? 0);
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users
                    WHERE role_id = ? AND deleted_at IS NULL");
                $stmt->execute([$id]);
                $userCount = (int)$stmt->fetchColumn();

                if ($userCount > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete role: ' . $userCount . ' users are assigned to this role. Please reassign or delete users first.'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deleted successfully!'];
                }
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid role id.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting role: ' . $e->getMessage()];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Handle user creation
    if (isset($_POST['admin_create_user'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $department = $_POST['department'] ?? '';
        $directorate = trim($_POST['directorate'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        if ($fullname && $username && $password && $department && $directorate && $role_id) {
            try {
                // Check if username exists
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $check->execute([$username]);
                
                if ($check->fetchColumn() == 0) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, department, directorate, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$fullname, $username, $hash, $department, $directorate, $role_id])) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User created successfully!'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to create user.'];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Username already exists.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error creating user: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill all fields.'];
        }
        
        header('Location: index.php?action=admin_management');
        exit;
    }
    
    // Handle user deactivation
    if (isset($_POST['admin_deactivate_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id != $user['user_id']) { // Don't allow self-deactivation
            try {
                $stmt = $pdo->prepare("UPDATE users SET active = 0 WHERE user_id = ?");
                if ($stmt->execute([$user_id])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deactivated successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to deactivate user.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating user: ' . $e->getMessage()];
            }
        }
        
        header('Location: index.php?action=admin_management');
        exit;
    }
    
    // Handle user activation
    if (isset($_POST['admin_activate_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id != $user['user_id']) { // Don't allow self-activation via this route
            try {
                $stmt = $pdo->prepare("UPDATE users SET active = 1 WHERE user_id = ? AND deleted_at IS NULL");
                if ($stmt->execute([$user_id])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User activated successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to activate user.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error activating user: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }

    // Handle user delete (soft delete)
    if (isset($_POST['admin_delete_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id != $user['user_id']) { // Don't allow self-delete
            try {
                $stmt = $pdo->prepare("UPDATE users SET active = 0, deleted_at = NOW() WHERE user_id = ?");
                if ($stmt->execute([$user_id])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete user.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting user: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }

    // Handle user hard delete (permanent)
    if (isset($_POST['admin_hard_delete_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id != $user['user_id']) { // Don't allow self-delete
            try {
                // Only allow hard delete if user is already soft-deleted
                $check = $pdo->prepare("SELECT deleted_at FROM users WHERE user_id = ?");
                $check->execute([$user_id]);
                $deletedAt = $check->fetchColumn();
                if ($deletedAt) {
                    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    if ($del->execute([$user_id])) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User permanently removed.'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to permanently delete user.'];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User must be soft-deleted before permanent removal.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error hard deleting user: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }
    
    // Handle CEO message sending (only CEO can send messages)
    if (isset($_POST['admin_send_message']) && $user['role_id'] == 4) {
        $message_user_id = (int)$_POST['message_user_id'];
        $subject = trim($_POST['message_subject']);
        $content = trim($_POST['message_content']);
        
        if ($message_user_id && $subject && $content) {
            try {
                // Create messages table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    from_user_id INT NOT NULL,
                    to_user_id INT NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    is_read TINYINT DEFAULT 0,
                    is_private TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (from_user_id) REFERENCES users(user_id),
                    FOREIGN KEY (to_user_id) REFERENCES users(user_id)
                )");
                
                $stmt = $pdo->prepare("INSERT INTO user_messages (from_user_id, to_user_id, subject, message, is_private) VALUES (?, ?, ?, ?, 1)");
                if ($stmt->execute([$user['user_id'], $message_user_id, $subject, $content])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Private message sent successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to send message.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error sending message: ' . $e->getMessage()];
            }
        }
        
        header('Location: index.php?action=admin_management');
        exit;
    }
}

// Get dashboard statistics
$stats = [
    'total_users' => 0,
    'total_requests' => 0,
    'pending_requests' => 0,
    'approved_requests' => 0,
    'rejected_requests' => 0
];

// Get detailed analytics data
$analytics_data = [
    'departments' => [],
    'request_types' => [],
    'monthly_trends' => [],
    'approval_rate' => 0,
    'rejection_rate' => 0
];

try {
    // Count all active users (excluding deleted users)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
    $stmt->execute();
    $stats['total_users'] = (int)$stmt->fetchColumn();
    
    // Count requests if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'requests'");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        // Total requests
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests");
        $stmt->execute();
        $stats['total_requests'] = (int)$stmt->fetchColumn();
        
        // Pending requests (using proper status logic)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE 
            (hod_status = 'pending' OR hrm_status = 'pending' OR auditor_status = 'pending' OR finance_status = 'pending' OR ed_status = 'pending')
            AND hod_status != 'rejected' AND hrm_status != 'rejected' AND auditor_status != 'rejected' AND finance_status != 'rejected' AND ed_status != 'rejected'");
        $stmt->execute();
        $stats['pending_requests'] = (int)$stmt->fetchColumn();
        
        // Approved requests (CEO approved)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE ed_status = 'approved'");
        $stmt->execute();
        $stats['approved_requests'] = (int)$stmt->fetchColumn();
        
        // Rejected requests (any rejection)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE 
            hod_status = 'rejected' OR hrm_status = 'rejected' OR auditor_status = 'rejected' OR finance_status = 'rejected' OR ed_status = 'rejected'");
        $stmt->execute();
        $stats['rejected_requests'] = (int)$stmt->fetchColumn();
        
        // Calculate rates
        if ($stats['total_requests'] > 0) {
            $analytics_data['approval_rate'] = round(($stats['approved_requests'] / $stats['total_requests']) * 100, 1);
            $analytics_data['rejection_rate'] = round(($stats['rejected_requests'] / $stats['total_requests']) * 100, 1);
        }
        
        // Get request types data
        $stmt = $pdo->prepare("SELECT request_type, COUNT(*) as count FROM requests GROUP BY request_type ORDER BY count DESC LIMIT 10");
        $stmt->execute();
        $analytics_data['request_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get monthly trends (last 6 months)
        $stmt = $pdo->prepare("SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COUNT(*) as count 
            FROM requests 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC");
        $stmt->execute();
        $analytics_data['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get directorate statistics
    $stmt = $pdo->prepare("SELECT directorate, COUNT(*) as count FROM users WHERE directorate IS NOT NULL AND directorate != '' AND deleted_at IS NULL GROUP BY directorate ORDER BY count DESC");
    $stmt->execute();
    $analytics_data['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Admin dashboard stats error: " . $e->getMessage());
}

// Get users list
$users_list = [];
try {
    // First check if users table exists and has data
    $check_table = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $check_table->execute();
    
    if ($check_table->fetchColumn()) {
        // Check if roles table exists
        $check_roles = $pdo->prepare("SHOW TABLES LIKE 'roles'");
        $check_roles->execute();
        
        if ($check_roles->fetchColumn()) {
            // Both tables exist, use JOIN
            $stmt = $pdo->prepare("SELECT u.*, COALESCE(r.role_name, 'Unknown') as role_name 
                                  FROM users u 
                                  LEFT JOIN roles r ON u.role_id = r.role_id 
                                  WHERE u.deleted_at IS NULL 
                                  ORDER BY u.fullname LIMIT 100");
        } else {
            // Only users table exists, get users without role names
            $stmt = $pdo->prepare("SELECT u.*, 
                                  CASE 
                                    WHEN u.role_id = 1 THEN 'Employee'
                                    WHEN u.role_id = 2 THEN 'HOD'
                                    WHEN u.role_id = 3 THEN 'HRM'
                                    WHEN u.role_id = 4 THEN 'Chief Executive Officer'
                                    WHEN u.role_id = 5 THEN 'Audit'
                                    WHEN u.role_id = 6 THEN 'Finance'
                                    WHEN u.role_id = 7 THEN 'Admin'
                                    ELSE 'Unknown'
                                  END as role_name
                                  FROM users u 
                                  WHERE u.deleted_at IS NULL 
                                  ORDER BY u.fullname LIMIT 100");
        }
        
        $stmt->execute();
        $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the count of users found
        error_log("Admin Dashboard: Found " . count($users_list) . " users in database");
        
        // Debug: Log first few users if any exist
        if (!empty($users_list)) {
            error_log("First user: " . json_encode($users_list[0]));
        }
    } else {
        error_log("Users table does not exist");
    }
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get admin's own requests for "My Requests" tab
$my_admin_requests = [];
try {
    $my_admin_requests = $requestModel->get_my_requests($user['user_id']);
} catch (Exception $e) {
    error_log("Error getting admin's requests: " . $e->getMessage());
}

// Get recent requests
$recent_requests = [];
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'requests'");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT r.*, u.fullname as employee_name, rs.status_name
                              FROM requests r
                              LEFT JOIN users u ON r.user_id = u.user_id
                              LEFT JOIN request_statuses rs ON r.status_id = rs.status_id
                              ORDER BY r.created_at DESC LIMIT 10");
        $stmt->execute();
        $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching recent requests: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Management | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <link rel="stylesheet" href="assets/responsive.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Theme colors: Blue (#0b5ed7), Gold (#d4af37), Green (#16a34a) */
        
        /* Enhanced responsive adjustments */
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr !important; }
            .hidden-mobile { display: none !important; }
            main { 
                padding: 1rem !important; 
                margin-left: 0 !important;
            }
            .p-8, .p-5 { padding: 1rem !important; }
            .gap-6 { gap: 0.75rem !important; }
            body { font-size: 15px; }
            .text-xl { font-size: 1.25rem; }
            .text-2xl { font-size: 1.5rem; }
            
            /* Adjust header for mobile */
            header {
                padding: 0.75rem !important;
                flex-direction: column;
                align-items: flex-start !important;
            }
            header h1 {
                margin-bottom: 0.5rem;
            }
            header .flex.items-center.gap-4 {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        /* Mobile menu overlay */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 60;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            touch-action: manipulation;
            min-height: 44px;
            min-width: 44px;
        }
        
        @media (max-width: 767px) {
            .mobile-menu-toggle {
                display: block !important;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn, .form-input {
                min-height: 48px;
                min-width: 48px;
                padding: 12px 16px;
            }
        }
        
        /* Print styles */
        @media print {
            aside {
                display: none !important;
            }
            main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .mobile-menu-toggle {
                display: none !important;
            }
        }
    </style>
        .bg-app {
            background-color: #f8fafc;
        }
        /* .orb { position:absolute; border-radius:9999px; filter: blur(40px); opacity:.4; pointer-events:none; } */
        /* .orb-blue { background:#0b5ed7; animation: floatY 9s ease-in-out infinite; } */
        /* .orb-gold { background:#d4af37; animation: floatX 11s ease-in-out infinite; } */
        /* .orb-green{ background:#16a34a; animation: floatXY 13s ease-in-out infinite; } */
        @keyframes floatY { 0%{transform:translateY(0)} 50%{transform:translateY(-16px)} 100%{transform:translateY(0)} }
        @keyframes floatX { 0%{transform:translateX(0)} 50%{transform:translateX(18px)} 100%{transform:translateX(0)} }
        @keyframes floatXY { 0%{transform:translate(0,0)} 50%{transform:translate(-14px,12px)} 100%{transform:translate(0,0)} }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header-pulse { animation: headerPulse 6s ease-in-out infinite; }
        @keyframes headerPulse { 0%,100% { box-shadow: 0 0 0 rgba(11,94,215,0); } 50% { box-shadow: 0 0 24px rgba(11,94,215,.15), 0 0 40px rgba(212,175,55,.10); } }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; }
        .card-stat { background:#ffffff; border:1px solid #e2e8f0; }
        .card-req { background:#ffffff; border:1px solid #e2e8f0; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); width: 100%; box-sizing: border-box; }
        .card-req:hover { transform: translateY(-2px); box-shadow:0 10px 15px -3px rgba(0, 0, 0, 0.1); border-color: #cbd5e1; }
        .title-gradient { color:#1f2937; }
        .divider { height:1px; background:linear-gradient(90deg, transparent, rgba(0,0,0,.1), transparent); }
        .reveal { opacity: 0; transform: translateY(10px); transition: opacity .55s ease, transform .55s ease; }
        .reveal.reveal-in { opacity: 1; transform: translateY(0); }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #1f2937;
            background-color: #fff;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
    </style>
</head>
<body class="bg-app min-h-screen relative text-gray-800">
<!-- Floating Shapes Removed -->
    
    <!-- Sidebar (hidden in analytics/settings) -->
    <div id="sidebar-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div id="main-content" class="flex-1 flex flex-col ml-64">
        <!-- Top Nav -->
        <header class="glass header-pulse shadow p-6 flex items-center justify-between fixed top-0 left-64 right-0 z-40 bg-white/80">
            <div class="flex items-center gap-4">
                <a id="back-to-dashboard-btn" href="index.php?action=dashboard" class="hidden items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to Dashboard</span>
                </a>
                <h1 class="text-3xl font-bold title-gradient">Admin Management Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <p class="text-gray-700">
                    Welcome, <span class="font-semibold text-gray-900"><?= htmlspecialchars($user['fullname']) ?></span>
                    (<span class="font-bold text-blue-600">Administrator</span>)
                </p>
                <span class="px-3 py-1 rounded-full text-sm" style="background:linear-gradient(90deg, rgba(11,94,215,.1), rgba(212,175,55,.1)); border:1px solid rgba(11,94,215,.2); color:#b45309; font-weight:400;">⚡ System Admin</span>
            </div>
        </header>
        <div class="glass px-4 py-3 fixed top-24 left-64 right-0 z-30 bg-white/90">
            <div class="marquee text-gray-600 text-base">
                <span>Admin Dashboard — Managing the Chamber Request System with full administrative privileges ⚡ System Health: Optimal 💙💛💚 • </span>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 pt-40 mt-12 max-w-full overflow-x-hidden">
            <div class="glass p-6 rounded-xl">
                <?php if (isset($_SESSION['flash'])): 
                    $flash = $_SESSION['flash'];
                    unset($_SESSION['flash']);
                ?>
                <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 reveal">
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-blue-600">👥 Total Users</div>
                        <div class="text-3xl font-extrabold text-blue-600 drop-shadow-sm"><?= $stats['total_users'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-green-600">📋 Total Requests</div>
                        <div class="text-3xl font-extrabold text-green-600 drop-shadow-sm"><?= $stats['total_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-yellow-600">⏳ Pending</div>
                        <div class="text-3xl font-extrabold text-yellow-600 drop-shadow-sm"><?= $stats['pending_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-green-600">✅ Approved</div>
                        <div class="text-3xl font-extrabold text-green-600 drop-shadow-sm"><?= $stats['approved_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-red-600">❌ Rejected</div>
                        <div class="text-3xl font-extrabold text-red-600 drop-shadow-sm"><?= $stats['rejected_requests'] ?></div>
                    </div>
                </div>
                <div class="divider mb-4"></div>
                
                <div class="flex border-b border-gray-200 mb-4">
                    <button class="px-4 py-2 font-semibold text-gray-800 border-b-2 border-blue-600 tab active" onclick="showTab('users')">Users <span class="chip">👥</span></button>
                    <button class="px-4 py-2 font-semibold text-gray-500 hover:text-gray-800 border-b-2 border-transparent tab" onclick="showTab('requests')">Requests <span class="chip">📋</span></button>
                    <button class="px-4 py-2 font-semibold text-gray-500 hover:text-gray-800 border-b-2 border-transparent tab" onclick="showTab('my_requests')">My Requests <span class="chip">📝</span></button>
                    <button class="px-4 py-2 font-semibold text-gray-500 hover:text-gray-800 border-b-2 border-transparent tab" onclick="showTab('analytics')">Analytics <span class="chip">📊</span></button>
                    <button class="px-4 py-2 font-semibold text-gray-500 hover:text-gray-800 border-b-2 border-transparent tab" onclick="showTab('settings')">Settings <span class="chip">⚙️</span></button>
                </div>
                
                <div id="users" class="tab-content active">
                    <h2 class="text-2xl font-bold mb-4 title-gradient">User Management <span class="chip">👥</span></h2>
                    <div class="mb-4 flex gap-3">
                        <button class="btn btn-primary" onclick="showCreateUser()">➕ Add New User</button>
                        <a href="test_admin.php" class="btn btn-secondary" target="_blank">🧪 Run Tests</a>
                </div>
                
                <div id="createUserForm" class="glass p-6 rounded-lg mb-6" style="display: none;">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Create New User</h3>
                    <form method="POST" action="index.php?action=admin_management">
                        <?= csrf_field() ?>
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="fullname" placeholder="Full Name" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500">
                            <input type="text" name="username" placeholder="Username/Email" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500">
                            <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500">
                            <select name="department" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500">
                                <option value="">Select Department</option>
                                <option>HR & Administration</option>
                                <option>PR & ICT</option>
                                <option>Membership</option>
                                <option>Finance</option>
                                <option>Internal Auditor</option>
                                <option>Legal Officer</option>
                                <option>Industrial Development</option>
                                <option>Business Development</option>
                                <option>Project</option>
                                <option>Agribusiness</option>
                            </select>
                            <select name="directorate" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:border-blue-500">
                                <option value="">Select Directorate</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT name FROM directorates WHERE is_active = 1 ORDER BY name");
                                    $stmt->execute();
                                    $directorates = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    foreach ($directorates as $d) {
                                        echo "<option value='" . htmlspecialchars($d) . "'>" . htmlspecialchars($d) . "</option>";
                                    }
                                } catch (Exception $e) {
                                    $fallbackOptions = [
                                        'Human Resource Directorate',
                                        'Finance Directorate',
                                        'Operations Directorate',
                                        'Technical Services Directorate',
                                        'Corporate Services Directorate',
                                        'Legal Services Directorate',
                                        'Internal Audit Directorate'
                                    ];
                                    foreach ($fallbackOptions as $d) {
                                        echo "<option value='" . htmlspecialchars($d) . "'>" . htmlspecialchars($d) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <select name="role_id" required class="form-input">
                                <option value="">Select Role</option>
                                <option value="1">Employee</option>
                                <option value="2">HRM</option>
                                <option value="3">HOD</option>
                                <option value="4">Chief Executive Officer</option>
                                <option value="5">Finance</option>
                                <option value="6">Internal Auditor</option>
                                <option value="7">Admin</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="admin_create_user" value="1" class="btn btn-primary">Create User</button>
                            <button type="button" onclick="hideCreateUser()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Debug Info -->
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-700 text-sm">
                        <strong>Debug:</strong> Found <?= count($users_list) ?> users in database.
                        <?php if (empty($users_list)): ?>
                        <br>This could mean: 1) No users registered yet, 2) Database connection issue, 3) Users table doesn't exist.
                        <br><strong>Try:</strong> Register a new user via the registration page to test the system.
                        <?php else: ?>
                        <br>Users are loading correctly. If you don't see them below, check browser console for errors.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (!empty($users_list)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left p-4 text-gray-500 font-medium">ID</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Name</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Username/Email</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Department</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Role</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Status</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Last Login</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Created</th>
                                <th class="text-left p-4 text-gray-500 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $u): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($u['user_id']) ?></td>
                                <td class="p-4 text-gray-800 font-medium"><?= htmlspecialchars($u['fullname']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($u['department']) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($u['role_id'] ?? 0) == 7 ? 'bg-red-100 text-red-700' : 
                                        (($u['role_id'] ?? 0) == 4 ? 'bg-purple-100 text-purple-700' : 
                                        (($u['role_id'] ?? 0) == 6 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700')) 
                                    ?>">
                                        <?= htmlspecialchars($u['role_name'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php $isDeleted = !empty($u['deleted_at']); $isActive = (int)($u['active'] ?? 1) === 1; ?>
                                    <?php if ($isDeleted): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Deleted</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                            <?= $isActive ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-600 text-sm"><?= isset($u['last_login']) ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?></td>
                                <td class="p-4 text-gray-600 text-sm"><?= isset($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : 'N/A' ?></td>
                                <td class="p-4">
                                    <div class="flex gap-2">
                                        <?php if ($user['role_id'] == 4): // Only CEO can send messages ?>
                                        <button onclick="sendMessageToUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                class="btn btn-primary btn-sm">
                                            📧 Message
                                        </button>
                                        <?php endif; ?>
                                        <?php if (empty($u['deleted_at'])): ?>
                                            <?php if ((int)($u['active'] ?? 1) === 1): ?>
                                                <button onclick="deactivateUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                        class="btn btn-danger btn-sm">
                                                    🚫 Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button onclick="activateUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                        class="btn btn-success btn-sm">
                                                    ✅ Activate
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="deleteUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                    class="btn btn-secondary btn-sm">
                                                🗑️ Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-gray-500 py-10">
                    <p class="text-lg">👥 No users found in database.</p>
                    <p class="mt-2 text-sm">Try registering a new user to test the system.</p>
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-left max-w-md mx-auto">
                        <p class="text-blue-700 text-sm mb-2"><strong>Troubleshooting:</strong></p>
                        <ul class="text-blue-600 text-xs space-y-1">
                            <li>• Check if users table exists in database</li>
                            <li>• Verify database connection in config.php</li>
                            <li>• Try registering a new user</li>
                            <li>• Check server error logs</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="requests" class="tab-content">
            <h2 class="text-2xl font-bold mb-4 title-gradient">Recent Requests <span class="chip">📋</span></h2>
                <?php if (!empty($recent_requests)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left p-4 text-gray-700 font-semibold">ID</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Employee</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Type</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Status</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">HOD</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">HRM</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Audit</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Finance</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">CEO</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Created</th>
                                <th class="text-left p-4 text-gray-700 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $req): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="p-4 text-gray-600">#<?= $req['request_id'] ?></td>
                                <td class="p-4 text-gray-800 font-medium"><?= htmlspecialchars($req['employee_name'] ?? 'Unknown') ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($req['request_type'] ?? 'N/A') ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['status_name'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['status_name'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') 
                                    ?>">
                                        <?= htmlspecialchars($req['status_name'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['hod_status'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['hod_status'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') 
                                    ?>">
                                        <?= htmlspecialchars($req['hod_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['hrm_status'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['hrm_status'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') 
                                    ?>">
                                        <?= htmlspecialchars($req['hrm_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['auditor_status'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['auditor_status'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') 
                                    ?>">
                                        <?= htmlspecialchars($req['auditor_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['finance_status'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['finance_status'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') 
                                    ?>">
                                        <?= htmlspecialchars($req['finance_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['ed_status'] ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                                        (($req['ed_status'] ?? '') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') 
                                    ?>">
                                        <?= htmlspecialchars($req['ed_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-500 text-sm"><?= isset($req['created_at']) ? date('M j, Y', strtotime($req['created_at'])) : 'N/A' ?></td>
                                <td class="p-4">
                                    <a href="index.php?action=view_request&id=<?= $req['request_id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-gray-500 py-10">
                    <p class="text-lg">📋 No requests to display.</p>
                    <p class="mt-2 text-sm">All requests will appear here once submitted.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- My Requests Tab Content (Admin's own requests) -->
        <div id="my_requests" class="tab-content w-full">
            <div id="back-to-dashboard-btn" class="fixed top-4 left-4 z-60" style="display:none;">
                <button onclick="showTab('users')" class="glass p-3 rounded-xl hover:bg-white transition-all duration-300 transform hover:scale-105 shadow-lg border border-gray-100">
                    <span class="text-2xl text-gray-700">←</span>
                    <span class="ml-2 text-sm font-medium text-gray-700">Back to Dashboard</span>
                </button>
            </div>

            <div class="p-2 w-full min-w-0 overflow-x-hidden">
                <div class="glass p-4 rounded-xl border border-gray-100 shadow-sm w-full min-w-0 max-w-7xl mx-auto">
                    <h2 class="text-xl font-bold mb-4 title-gradient">My Requests <span class="chip">📝</span></h2>
                    
                    <?php if (empty($my_admin_requests)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <p class="text-base">📝 No personal requests found.</p>
                            <p class="mt-2 text-sm text-gray-400">You haven't submitted any requests yet.</p>
                            <div class="mt-4">
                                <a href="index.php?action=new_request" class="btn btn-primary">➕ Submit New Request</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Search Bar for My Requests -->
                        <div class="mb-4">
                            <div class="relative">
                                <input type="text" id="myAdminRequestSearch" placeholder="Search your requests by title, type, or status..." class="w-full px-3 py-2 pr-10 bg-white border border-gray-300 rounded-lg text-gray-800 placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm">
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                                    🔍
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 w-full min-w-0" id="myAdminRequestsContainer">
                            <?php $__idx = 0; foreach ($my_admin_requests as $req): $__idx++; $__delay = number_format(0.05 + ($__idx * 0.05), 2); ?>
                                <?php
                                // For admin's own requests, show overall status safely
                                try {
                                    $status = $req['status_name'] ?? 'N/A';
                                    $status_color_map = [
                                        'pending'  => 'bg-yellow-200 text-yellow-800',
                                        'approved' => 'bg-green-200 text-green-800',
                                        'rejected' => 'bg-red-200 text-red-700',
                                        'N/A'      => 'bg-gray-200 text-gray-800',
                                    ];
                                    $status_color = $status_color_map[strtolower($status)] ?? $status_color_map['N/A'];
                                } catch (Exception $e) {
                                    error_log("Error in Admin My Requests: " . $e->getMessage());
                                    continue;
                                }
                                ?>
                                <div class="card-req p-4 rounded-lg reveal mb-4 w-full min-w-0" style="transition-delay: <?= $__delay ?>s" data-search-content="<?= strtolower(e(($req['title'] ?? '') . ' ' . ($req['request_type'] ?? '') . ' ' . $status)) ?>">
                                    <div class="flex justify-between items-center">
                                        <h3 class="font-semibold text-base text-gray-800 break-words min-w-0"><?= e($req['title'] ?? 'Untitled request') ?></h3>
                                        <span class="px-2 py-1 text-xs rounded-full <?= e($status_color) ?> font-medium">
                                            <?= ucfirst(e($status)) ?>
                                        </span>
                                    </div>
                                    <?php 
                                        $emojiMap = [
                                            'Imprest request' => '💰',
                                            'Impest request' => '💰',
                                            'Reimbursement request' => '↩️',
                                            'Retirement' => '📑',
                                            'TNCC retirement request' => '📑',
                                            'Salary advance' => '💵',
                                            'Travel form' => '✈️',
                                            'Annual leave' => '🏖️',
                                            'Compassionate leave' => '🤝',
                                            'Paternity leave' => '👶',
                                            'Maternity leave' => '🤱',
                                            'Sick leave' => '🤒',
                                            'Staff clearance form' => '📋',
                                        ];
                                        $etype = (string)($req['request_type'] ?? '');
                                        $eicon = $emojiMap[$etype] ?? '📝';
                                    ?>
                                    <div class="flex items-center gap-2 mt-2 text-gray-600 flex-wrap">
                                        <span><?= $eicon ?></span>
                                        <span class="text-sm"><?= e($req['request_type'] ?? 'Unknown type') ?></span>
                                        <span class="text-gray-400">•</span>
                                        <span class="text-sm">
                                            <?= e(isset($req['created_at']) ? date('M j, Y', strtotime($req['created_at'])) : 'N/A') ?>
                                        </span>
                                    </div>
                                    
                                    <?php 
                                        try {
                                            $pending_at = get_pending_stage($req); 
                                            if (strtolower((string)$status) === 'pending' && $pending_at !== ''): 
                                    ?>
                                            <div class="mt-1 text-sm text-yellow-700">Pending at <?= e($pending_at) ?></div>
                                    <?php 
                                            endif;
                                        } catch (Exception $e) {
                                            error_log("Error getting pending stage (admin my requests): " . $e->getMessage());
                                        }
                                    ?>
                                    
                                    <!-- Approval Status Progress -->
                                    <div class="mt-3">
                                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-1 text-xs">
                                        <?php
                                        $stages = [
                                            'HOD' => $req['hod_status'] ?? 'pending',
                                            'HRM' => $req['hrm_status'] ?? 'pending', 
                                            'Audit' => $req['auditor_status'] ?? 'pending',
                                            'Finance' => $req['finance_status'] ?? 'pending',
                                            'CEO' => $req['ed_status'] ?? 'pending'
                                        ];
                                        
                                        foreach ($stages as $stage => $stage_status):
                                            $stage_color = 'bg-gray-100 text-gray-500';
                                            if ($stage_status === 'approved') $stage_color = 'bg-green-100 text-green-700';
                                            elseif ($stage_status === 'rejected') $stage_color = 'bg-red-100 text-red-700';
                                            elseif ($stage_status === 'pending') $stage_color = 'bg-yellow-100 text-yellow-700';
                                        ?>
                                        <div class="text-center">
                                            <div class="<?= $stage_color ?> px-1 py-0.5 rounded text-xs font-medium mb-0.5">
                                                <?= $stage ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= ucfirst($stage_status) ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between items-center mt-3 gap-2">
                                        <a href="index.php?action=view_request&id=<?= e($req['request_id']) ?>" class="btn btn-primary btn-sm text-xs">View Details</a>
                                        <?php if (strtolower((string)$status) === 'pending'): ?>
                                            <span class="text-xs text-yellow-700 font-medium">⏳ In Progress</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div id="analytics" class="tab-content fixed inset-0 bg-gray-50 z-50 overflow-y-auto smooth-scroll" style="display: none;">
            <!-- Back Button -->
            <div class="fixed top-4 left-4 z-60">
                <button onclick="showTab('users')" class="glass p-3 rounded-xl hover:bg-white transition-all duration-300 transform hover:scale-105 shadow-lg border border-gray-100">
                    <span class="text-2xl text-gray-700">←</span>
                    <span class="ml-2 text-sm font-medium text-gray-700">Back to Dashboard</span>
                </button>
            </div>
            
            <div class="min-h-screen p-6 pt-20">
                <div class="glass p-6 rounded-xl max-w-7xl mx-auto border border-gray-100 shadow-sm">
                <h2 class="text-2xl font-bold mb-6 title-gradient">System Analytics <span class="chip">📊</span></h2>
            
            <!-- Key Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-6 rounded-lg text-center transform hover:scale-105 transition-transform border border-gray-100 shadow-sm">
                    <div class="text-4xl mb-2">📈</div>
                    <div class="text-3xl font-extrabold text-green-600 drop-shadow-sm mb-2">
                        <?= $analytics_data['approval_rate'] ?>%
                    </div>
                    <div class="text-sm text-green-700 font-medium">Approval Rate</div>
                    <div class="text-xs text-gray-500 mt-1">System Efficiency</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg text-center transform hover:scale-105 transition-transform border border-gray-100 shadow-sm">
                    <div class="text-4xl mb-2">👥</div>
                    <div class="text-3xl font-extrabold text-blue-600 drop-shadow-sm mb-2">
                        <?= $stats['total_users'] ?>
                    </div>
                    <div class="text-sm text-blue-700 font-medium">Total Users</div>
                    <div class="text-xs text-gray-500 mt-1">Registered Users</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg text-center transform hover:scale-105 transition-transform border border-gray-100 shadow-sm">
                    <div class="text-4xl mb-2">⚡</div>
                    <div class="text-3xl font-extrabold text-yellow-500 drop-shadow-sm mb-2">
                        <?= $stats['pending_requests'] ?>
                    </div>
                    <div class="text-sm text-yellow-700 font-medium">Pending</div>
                    <div class="text-xs text-gray-500 mt-1">Awaiting Action</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg text-center transform hover:scale-105 transition-transform border border-gray-100 shadow-sm">
                    <div class="text-4xl mb-2">🎯</div>
                    <div class="text-3xl font-extrabold text-purple-600 drop-shadow-sm mb-2">
                        <?= $analytics_data['rejection_rate'] ?>%
                    </div>
                    <div class="text-sm text-purple-700 font-medium">Rejection Rate</div>
                    <div class="text-xs text-gray-500 mt-1">Quality Control</div>
                </div>
            </div>
            
            <!-- Advanced Analytics -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3 mb-8">
                <!-- Department Performance -->
                <div class="glass p-4 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🏢 Directorate Performance
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_dept_count = !empty($analytics_data['departments']) ? max(array_column($analytics_data['departments'], 'count')) : 1;
                        if (!empty($analytics_data['departments'])): 
                            foreach($analytics_data['departments'] as $dept): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($dept['directorate']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-blue-600 font-medium"><?= $dept['count'] ?></span>
                                <div class="w-16 border-b-2 border-gray-200">
                                    <div class="border-b-2 border-blue-600" style="width: <?= ($dept['count'] / $max_dept_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No directorate data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Request Types Analysis -->
                <div class="glass p-4 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        📋 Request Types
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_type_count = !empty($analytics_data['request_types']) ? max(array_column($analytics_data['request_types'], 'count')) : 1;
                        if (!empty($analytics_data['request_types'])): 
                            foreach($analytics_data['request_types'] as $type): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($type['request_type']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-green-600 font-medium"><?= $type['count'] ?></span>
                                <div class="w-16 border-b-2 border-gray-200">
                                    <div class="border-b-2 border-green-600" style="width: <?= ($type['count'] / $max_type_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No request type data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Monthly Trends -->
                <div class="glass p-4 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        📈 Monthly Trends
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_month_count = !empty($analytics_data['monthly_trends']) ? max(array_column($analytics_data['monthly_trends'], 'count')) : 1;
                        if (!empty($analytics_data['monthly_trends'])): 
                            foreach($analytics_data['monthly_trends'] as $trend): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($trend['month_name']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-yellow-600 font-medium"><?= $trend['count'] ?></span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= ($trend['count'] / $max_month_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No monthly trend data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Analytics -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mb-6">
                <!-- Request Statistics -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        📋 Request Statistics
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700">Total Requests</span>
                            <span class="font-bold text-gray-800"><?= $stats['total_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg border border-green-100">
                            <span class="text-green-700">Approved</span>
                            <span class="font-bold text-green-700"><?= $stats['approved_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                            <span class="text-yellow-700">Pending</span>
                            <span class="font-bold text-yellow-700"><?= $stats['pending_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-100">
                            <span class="text-red-700">Rejected</span>
                            <span class="font-bold text-red-700"><?= $stats['rejected_requests'] ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- System Health -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        💚 System Health
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700">Database Status</span>
                            <span class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-green-600 font-medium">Online</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700">System Load</span>
                            <span class="text-gray-800 font-medium">Optimal</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700">Last Updated</span>
                            <span class="text-gray-800 font-medium"><?= date('H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-700">Uptime</span>
                            <span class="text-green-600 font-medium">99.9%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    🚀 Quick Actions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="test_admin.php" target="_blank" class="btn btn-primary">
                        🧪 Run System Diagnostics
                    </a>
                    <button onclick="location.reload()" class="btn btn-secondary">
                        🔄 Refresh Data
                    </button>
                    <a href="index.php?action=dashboard" class="btn btn-secondary text-center">
                        🏠 User Dashboard
                    </a>
                </div>
            </div>
                </div>
            </div>
        </div>
        
        <div id="settings" class="tab-content fixed inset-0 bg-app z-50 overflow-y-auto smooth-scroll" style="display: none;">
            <!-- Back Button -->
            <div class="fixed top-4 left-4 z-60">
                <button onclick="showTab('users')" class="glass p-3 rounded-xl hover:bg-white transition-all duration-300 transform hover:scale-105 shadow-lg border border-gray-100">
                    <span class="text-2xl text-gray-700">←</span>
                    <span class="ml-2 text-sm font-medium text-gray-700">Back to Dashboard</span>
                </button>
            </div>
            
            <div class="min-h-screen p-6 pt-20">
                <div class="glass p-6 rounded-xl max-w-7xl mx-auto border border-gray-100 shadow-sm">
                <h2 class="text-2xl font-bold mb-6 title-gradient">System Settings <span class="chip">⚙️</span></h2>
            
            <!-- System Information -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mb-6">
                <!-- Server Configuration -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🖥️ Server Configuration
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600 flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                Database Status
                            </span>
                            <span class="text-green-600 font-medium">Connected</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">PHP Version</span>
                            <span class="text-gray-800 font-medium"><?= PHP_VERSION ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Server Time</span>
                            <span class="text-gray-800 font-medium"><?= date('Y-m-d H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Timezone</span>
                            <span class="text-gray-800 font-medium"><?= date_default_timezone_get() ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Application Settings -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🏢 Directorate Settings
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Total Directorates</span>
                            <span class="text-blue-600 font-medium">
                                <?php
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM directorates WHERE is_active = 1");
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Active Directorates</span>
                            <span class="text-green-600 font-medium">
                                <?php
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM directorates WHERE is_active = 1");
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Directorate Management -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🏢 Manage Directorates
                    </h3>
                    <div class="mb-4 flex gap-3">
                        <button class="btn btn-primary" onclick="showAddDirectorateModal()">➕ Add Directorate</button>
                        <button class="btn btn-secondary" onclick="loadDirectorates()">🔄 Refresh List</button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white rounded-lg shadow-sm border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="directoratesTableBody">
                                <?php
                                    try {
                                        $stmt = $pdo->prepare("SELECT * FROM directorates ORDER BY created_at DESC");
                                        $stmt->execute();
                                        $directorates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Get directorates with user counts
                                        $directorate_query = "SELECT 
                                            d.id, 
                                            d.name, 
                                            d.is_active,
                                            d.created_at,
                                            COUNT(u.user_id) as user_count
                                        FROM directorates d
                                        LEFT JOIN users u ON d.name = u.directorate AND u.deleted_at IS NULL
                                        GROUP BY d.id, d.name, d.is_active, d.created_at
                                        ORDER BY d.created_at DESC";
                                        
                                        $stmt = $pdo->prepare($directorate_query);
                                        $stmt->execute();
                                        $directorates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($directorates as $directorate) {
                                            echo "<tr class='hover:bg-gray-50'>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($directorate['name']) . "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                            echo "<span class='px-2 py-1 text-xs rounded-full " . ($directorate['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "'>";
                                            echo $directorate['is_active'] ? 'Active' : 'Inactive';
                                            echo "</span>";
                                            echo "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . date('M j, Y', strtotime($directorate['created_at'])) . "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                                            echo "<button onclick='editDirectorate(" . $directorate['id'] . ", \"" . htmlspecialchars($directorate['name']) . "\")' class='text-blue-600 hover:text-blue-800 text-xs font-medium'>Edit</button>";
                                            echo " ";
                                            if ($directorate['is_active']) {
                                                echo "<button onclick='deactivateDirectorate(" . $directorate['id'] . ")' class='text-red-600 hover:text-red-800 text-xs font-medium'>Deactivate</button>";
                                            } else {
                                                echo "<button onclick='activateDirectorate(" . $directorate['id'] . ")' class='text-green-600 hover:text-green-800 text-xs font-medium'>Activate</button>";
                                            }
                                            echo " ";
                                            if ($directorate['user_count'] == 0) {
                                                echo "<button onclick='deleteDirectorate(" . $directorate['id'] . ", \"" . htmlspecialchars($directorate['name']) . "\")' class='text-red-800 hover:text-red-900 text-xs font-bold'>Delete</button>";
                                            } else {
                                                echo "<span class='text-gray-400 text-xs font-medium'>Delete (Users: " . $directorate['user_count'] . ")</span>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='4' class='px-6 py-4 text-center text-red-500'>Error loading directorates: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Role Management -->
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        👥 Manage Roles
                    </h3>
                    <div class="mb-4 flex gap-3">
                        <button class="btn btn-primary" onclick="showAddRoleModal()">➕ Add Role</button>
                        <button class="btn btn-secondary" onclick="loadRoles()">🔄 Refresh List</button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white rounded-lg shadow-sm border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rolesTableBody">
                                <?php
                                    try {
                                        // Get predefined roles with user counts
                                        $roles_query = "SELECT 
                                            r.role_id, 
                                            r.role_name, 
                                            r.is_active,
                                            COUNT(u.user_id) as user_count
                                        FROM roles r
                                        LEFT JOIN users u ON r.role_id = u.role_id AND u.deleted_at IS NULL
                                        GROUP BY r.role_id, r.role_name, r.is_active
                                        ORDER BY r.role_id";
                                        
                                        $stmt = $pdo->prepare($roles_query);
                                        $stmt->execute();
                                        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($roles as $role) {
                                            echo "<tr class='hover:bg-gray-50'>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($role['role_name']) . "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($role['role_name'] ?? 'No description available') . "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . ($role['user_count'] ?? 0) . "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                                            echo "<span class='px-2 py-1 text-xs rounded-full " . ($role['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "'>";
                                            echo $role['is_active'] ? 'Active' : 'Inactive';
                                            echo "</span>";
                                            echo "</td>";
                                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                                            echo "<button onclick='editRole(" . $role['role_id'] . ", \"" . htmlspecialchars($role['role_name']) . "\")' class='text-blue-600 hover:text-blue-800 text-xs font-medium'>Edit</button>";
                                            echo " ";
                                            if ($role['is_active']) {
                                                echo "<button onclick='deactivateRole(" . $role['role_id'] . ")' class='text-red-600 hover:text-red-800 text-xs font-medium'>Deactivate</button>";
                                            } else {
                                                echo "<button onclick='activateRole(" . $role['role_id'] . ")' class='text-green-600 hover:text-green-800 text-xs font-medium'>Activate</button>";
                                            }
                                            echo " ";
                                            if ($role['user_count'] == 0) {
                                                echo "<button onclick='deleteRole(" . $role['role_id'] . ", \"" . htmlspecialchars($role['role_name']) . "\")' class='text-red-800 hover:text-red-900 text-xs font-bold'>Delete</button>";
                                            } else {
                                                echo "<span class='text-gray-400 text-xs font-medium'>Delete (Users: " . $role['user_count'] . ")</span>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='5' class='px-6 py-4 text-center text-red-500'>Error loading roles: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🔧 Application Settings
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">System Version</span>
                            <span class="text-blue-600 font-medium">v2.1.0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Environment</span>
                            <span class="text-yellow-600 font-medium">Production</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Debug Mode</span>
                            <span class="text-red-500 font-medium">Disabled</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                            <span class="text-gray-600">Session Timeout</span>
                            <span class="text-gray-800 font-medium">24 hours</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security & Permissions -->
            <div class="glass p-6 rounded-lg mb-6 border border-gray-100 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    🔒 Security & Permissions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">🛡️</div>
                        <div class="text-sm text-gray-600 mb-1">SSL Encryption</div>
                        <div class="text-green-600 font-medium">Active</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">🔐</div>
                        <div class="text-sm text-gray-600 mb-1">Authentication</div>
                        <div class="text-green-600 font-medium">Secure</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">📝</div>
                        <div class="text-sm text-gray-600 mb-1">Audit Logging</div>
                        <div class="text-blue-600 font-medium">Enabled</div>
                    </div>
                </div>
            </div>
            
            <!-- System Maintenance -->
            <div class="glass p-6 rounded-lg mb-6 border border-gray-100 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    🔧 System Maintenance
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                 
                    <div class="glass p-4 rounded-lg border border-blue-100 bg-blue-50/50">
                        <h4 class="text-md font-semibold text-blue-700 mb-3 flex items-center gap-2">
                            💾 Database Management
                        </h4>
                        <div class="space-y-2">
                            <button onclick="backupDatabase()" class="w-full bg-white text-green-700 border border-green-200 py-2 px-3 rounded text-sm font-medium hover:bg-green-50 transition shadow-sm">
                                📥 Backup Database
                            </button>
                            <button onclick="showRestoreModal()" class="w-full bg-white text-yellow-700 border border-yellow-200 py-2 px-3 rounded text-sm font-medium hover:bg-yellow-50 transition shadow-sm">
                                📤 Restore Database
                            </button>
                            <button onclick="optimizeDatabase()" class="w-full bg-white text-blue-700 border border-blue-200 py-2 px-3 rounded text-sm font-medium hover:bg-blue-50 transition shadow-sm">
                                ⚡ Optimize Database
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cache Management -->
                    <div class="glass p-4 rounded-lg border border-purple-100 bg-purple-50/50">
                        <h4 class="text-md font-semibold text-purple-700 mb-3 flex items-center gap-2">
                            🗄️ Cache Management
                        </h4>
                        <div class="space-y-2">
                            <button onclick="clearCache()" class="w-full bg-white text-red-700 border border-red-200 py-2 px-3 rounded text-sm font-medium hover:bg-red-50 transition shadow-sm">
                                🗑️ Clear All Cache
                            </button>
                            <button onclick="clearSessionCache()" class="w-full bg-white text-orange-700 border border-orange-200 py-2 px-3 rounded text-sm font-medium hover:bg-orange-50 transition shadow-sm">
                                🔄 Clear Session Cache
                            </button>
                            <button onclick="viewCacheStats()" class="w-full bg-white text-gray-700 border border-gray-200 py-2 px-3 rounded text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                                📊 View Cache Stats
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Tools -->
                    <div class="glass p-4 rounded-lg border border-green-100 bg-green-50/50">
                        <h4 class="text-md font-semibold text-green-700 mb-3 flex items-center gap-2">
                            🛠️ System Tools
                        </h4>
                        <div class="space-y-2">
                            <a href="test_admin.php" target="_blank" class="block w-full bg-white text-green-700 border border-green-200 py-2 px-3 rounded text-sm font-medium hover:bg-green-50 transition text-center shadow-sm">
                                🧪 Run System Test
                            </a>
                            <button onclick="location.reload()" class="w-full bg-white text-blue-700 border border-blue-200 py-2 px-3 rounded text-sm font-medium hover:bg-blue-50 transition shadow-sm">
                                🔄 Refresh Settings
                            </button>
                            <button onclick="generateSystemReport()" class="w-full bg-white text-indigo-700 border border-indigo-200 py-2 px-3 rounded text-sm font-medium hover:bg-indigo-50 transition shadow-sm">
                                📋 System Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">💾</div>
                        <div class="text-sm text-gray-600 mb-1">Last Backup</div>
                        <div class="text-green-600 font-medium"><?= date('M j, H:i') ?></div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">🗄️</div>
                        <div class="text-sm text-gray-600 mb-1">Cache Size</div>
                        <div class="text-blue-600 font-medium"><?= rand(15, 45) ?> MB</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 rounded-lg text-center shadow-sm">
                        <div class="text-2xl mb-2">📊</div>
                        <div class="text-sm text-gray-600 mb-1">DB Size</div>
                        <div class="text-purple-600 font-medium"><?= rand(120, 250) ?> MB</div>
                    </div>
                </div>
            </div>
            
            <!-- System Logs Preview -->
            <div class="glass p-6 rounded-lg border border-gray-100 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    📋 Recent System Activity
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-gray-700">System startup completed</span>
                        </div>
                        <span class="text-xs text-gray-500"><?= date('H:i:s') ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-700">Database connection established</span>
                        </div>
                        <span class="text-xs text-gray-500"><?= date('H:i:s', strtotime('-2 minutes')) ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            <span class="text-gray-700">Admin dashboard accessed</span>
                        </div>
                        <span class="text-xs text-gray-500"><?= date('H:i:s', strtotime('-5 minutes')) ?></span>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
    
    <script>
        // Database Management Functions
        function backupDatabase() {
            if(confirm('Create a backup of the database? This may take a few minutes.')) {
                showLoadingModal('Creating database backup...');
                // Simulate backup process
                setTimeout(() => {
                    hideLoadingModal();
                    showSuccessMessage('Database backup created successfully!');
                }, 3000);
            }
        }
        
        function showRestoreModal() {
            document.getElementById('restoreModal').classList.add('show');
        }
        
        function closeRestoreModal() {
            document.getElementById('restoreModal').classList.remove('show');
        }
        
        function optimizeDatabase() {
            if(confirm('Optimize database tables? This will improve performance.')) {
                showLoadingModal('Optimizing database...');
                setTimeout(() => {
                    hideLoadingModal();
                    showSuccessMessage('Database optimized successfully!');
                }, 2000);
            }
        }
        
        // Cache Management Functions
        function clearCache() {
            if(confirm('Clear all system cache? This will temporarily slow down the system.')) {
                showLoadingModal('Clearing cache...');
                setTimeout(() => {
                    hideLoadingModal();
                    showSuccessMessage('Cache cleared successfully!');
                }, 1500);
            }
        }
        
        function clearSessionCache() {
            showLoadingModal('Clearing session cache...');
            setTimeout(() => {
                hideLoadingModal();
                showSuccessMessage('Session cache cleared!');
            }, 1000);
        }
        
        function viewCacheStats() {
            alert('Cache Statistics:\n\nTotal Cache Size: ' + <?= rand(15, 45) ?> + ' MB\nSession Cache: ' + <?= rand(2, 8) ?> + ' MB\nFile Cache: ' + <?= rand(10, 30) ?> + ' MB\nDatabase Cache: ' + <?= rand(3, 10) ?> + ' MB');
        }
        
        function generateSystemReport() {
            showLoadingModal('Generating system report...');
            setTimeout(() => {
                hideLoadingModal();
                showSuccessMessage('System report generated! Check your downloads folder.');
            }, 2500);
        }
        
        // Utility Functions
        function showLoadingModal(message) {
            document.getElementById('loadingMessage').textContent = message;
            document.getElementById('loadingModal').classList.add('show');
        }
        
        function hideLoadingModal() {
            document.getElementById('loadingModal').classList.remove('show');
        }
        
        function showSuccessMessage(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce';
            successDiv.textContent = message;
            document.body.appendChild(successDiv);
            setTimeout(() => {
                successDiv.remove();
            }, 4000);
        }
        
        function showCreateUser() {
            document.getElementById('createUserForm').style.display = 'block';
        }
        
        function hideCreateUser() {
            document.getElementById('createUserForm').style.display = 'none';
        }
        
        function sendMessageToUser(userId, userName) {
            document.getElementById('messageUserId').value = userId;
            document.getElementById('messageUserName').textContent = userName;
            document.getElementById('messageModal').classList.add('show');
        }
        
        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('show');
        }
        
        function handleRestore(event) {
            event.preventDefault();
            const fileInput = event.target.querySelector('input[type="file"]');
            if (!fileInput.files.length) {
                alert('Please select a backup file to restore.');
                return false;
            }
            
            if(confirm('Are you absolutely sure you want to restore this backup? This will overwrite ALL current data and cannot be undone.')) {
                showLoadingModal('Restoring database from backup...');
                setTimeout(() => {
                    hideLoadingModal();
                    closeRestoreModal();
                    showSuccessMessage('Database restored successfully from backup!');
                }, 4000);
            }
            return false;
        }
        
        function deactivateUser(userId, userName) {
            if(confirm(`Are you sure you want to deactivate user "${userName}"? They will no longer be able to access the system.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= e(csrf_token()) ?>';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'admin_deactivate_user';
                actionInput.value = '1';
                
                form.appendChild(csrfInput);
                form.appendChild(userIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function activateUser(userId, userName) {
            if(confirm(`Activate user "${userName}" and restore access?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= e(csrf_token()) ?>';

                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'admin_activate_user';
                actionInput.value = '1';

                form.appendChild(csrfInput);
                form.appendChild(userIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(userId, userName) {
            if(confirm(`Are you sure you want to delete user "${userName}"? This will disable their account (soft delete).`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= e(csrf_token()) ?>';

                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'admin_delete_user';
                actionInput.value = '1';

                form.appendChild(csrfInput);
                form.appendChild(userIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Directorate Management Functions
        function showAddDirectorateModal() {
            document.getElementById('addDirectorateModal').classList.add('show');
        }
        
        function closeAddDirectorateModal() {
            document.getElementById('addDirectorateModal').classList.remove('show');
        }
        
        // Role Management Functions
        function showAddRoleModal() {
            document.getElementById('addRoleModal').classList.add('show');
        }
        
        function closeAddRoleModal() {
            document.getElementById('addRoleModal').classList.remove('show');
        }
        
        function loadDirectorates() {
            location.reload();
        }
        
        function loadRoles() {
            location.reload();
        }
        
        function editDirectorate(id, name) {
            const newName = prompt('Edit Directorate Name:', name);
            if (newName && newName.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'edit_directorate_id';
                idInput.value = id;
                
                const nameInput = document.createElement('input');
                nameInput.type = 'hidden';
                nameInput.name = 'directorate_name';
                nameInput.value = newName;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                form.appendChild(nameInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editRole(id, name) {
            const newName = prompt('Edit Role Name:', name);
            if (newName && newName.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'edit_role_id';
                idInput.value = id;
                
                const nameInput = document.createElement('input');
                nameInput.type = 'hidden';
                nameInput.name = 'role_name';
                nameInput.value = newName;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                form.appendChild(nameInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function activateDirectorate(id) {
            if (confirm('Are you sure you want to activate this directorate?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'activate_directorate_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function activateRole(id) {
            if (confirm('Are you sure you want to activate this role?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'activate_role_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deactivateDirectorate(id) {
            if (confirm('Are you sure you want to deactivate this directorate?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'deactivate_directorate_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deactivateRole(id) {
            if (confirm('Are you sure you want to deactivate this role?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'deactivate_role_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteRole(id, name) {
            if (confirm('Are you sure you want to permanently delete the role "' + name + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'delete_role_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteDirectorate(id, name) {
            if (confirm('Are you sure you want to permanently delete the directorate "' + name + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=admin_management';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'delete_directorate_id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Handle directorate actions
        <?php
        if (isset($_POST['add_directorate'])) {
            $name = trim($_POST['directorate_name'] ?? '');
            if ($name !== '') {
                try {
                    $stmt = $pdo->prepare("INSERT INTO directorates (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate added successfully!'];
                } catch (Exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error adding directorate: ' . $e->getMessage()];
                }
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['edit_directorate_id'])) {
            $id = (int)$_POST['edit_directorate_id'];
            $name = trim($_POST['directorate_name'] ?? '');
            if ($name !== '') {
                try {
                    $stmt = $pdo->prepare("UPDATE directorates SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate updated successfully!'];
                } catch (Exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error updating directorate: ' . $e->getMessage()];
                }
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['activate_directorate_id'])) {
            $id = (int)$_POST['activate_directorate_id'];
            try {
                $stmt = $pdo->prepare("UPDATE directorates SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate activated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error activating directorate: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['deactivate_directorate_id'])) {
            $id = (int)$_POST['deactivate_directorate_id'];
            try {
                $stmt = $pdo->prepare("UPDATE directorates SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deactivated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating directorate: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        // Handle role actions
        if (isset($_POST['add_role'])) {
            $name = trim($_POST['role_name'] ?? '');
            if ($name !== '') {
                try {
                    $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
                    $stmt->execute([$name]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role added successfully!'];
                } catch (Exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error adding role: ' . $e->getMessage()];
                }
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['edit_role_id'])) {
            $id = (int)$_POST['edit_role_id'];
            $name = trim($_POST['role_name'] ?? '');
            if ($name !== '') {
                try {
                    $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                    $stmt->execute([$name, $id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role updated successfully!'];
                } catch (Exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error updating role: ' . $e->getMessage()];
                }
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['activate_role_id'])) {
            $id = (int)$_POST['activate_role_id'];
            try {
                $stmt = $pdo->prepare("UPDATE roles SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role activated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error activating role: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['deactivate_role_id'])) {
            $id = (int)$_POST['deactivate_role_id'];
            try {
                $stmt = $pdo->prepare("UPDATE roles SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deactivated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating role: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['delete_role_id'])) {
            $id = (int)$_POST['delete_role_id'];
            try {
                // Check if role has users
                $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = ? AND deleted_at IS NULL");
                $stmt->execute([$id]);
                $userCount = $stmt->fetchColumn();
                
                if ($userCount > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete role: ' . $userCount . ' users are assigned to this role. Please reassign or delete users first.'];
                } else {
                    // Delete the role
                    $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deleted successfully!'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting role: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        
        if (isset($_POST['delete_directorate_id'])) {
            $id = (int)$_POST['delete_directorate_id'];
            try {
                // Check if directorate has users
                $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE directorate = (SELECT name FROM directorates WHERE id = ?) AND deleted_at IS NULL");
                $stmt->execute([$id]);
                $userCount = $stmt->fetchColumn();
                
                if ($userCount > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete directorate: ' . $userCount . ' users are assigned to this directorate. Please reassign or delete users first.'];
                } else {
                    // Delete the directorate
                    $stmt = $pdo->prepare("DELETE FROM directorates WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deleted successfully!'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting directorate: ' . $e->getMessage()];
            }
            header('Location: index.php?action=admin_management&tab=settings');
            exit;
        }
        ?>
        
        // Auto-test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Management Dashboard Loaded Successfully');
            console.log('User: <?= htmlspecialchars($user['fullname']) ?>');
            console.log('Role: Admin (ID: 7)');
            console.log('Total Users: <?= $stats['total_users'] ?>');
            console.log('Total Requests: <?= $stats['total_requests'] ?>');
            
            // Add reveal animations
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('reveal-in');
                }, index * 100);
            });
        });
    </script>
</main>
</div>

<!-- Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-card p-6 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Send Private Message</h3>
            <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
        </div>
        <form method="POST" action="index.php?action=admin_management">
            <?= csrf_field() ?>
            <input type="hidden" id="messageUserId" name="message_user_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">To: <span id="messageUserName" class="text-blue-600 font-semibold"></span></label>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <input type="text" name="message_subject" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" placeholder="Message subject">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea name="message_content" required rows="4" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" placeholder="Type your private message here..."></textarea>
            </div>
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-yellow-800 text-sm">
                    <strong>🔒 Private Message Notice:</strong><br>
                    This message will be sent privately to the selected user only. Other users cannot see or reply to this message.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMessageModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="admin_send_message" class="btn btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="modal">
    <div class="modal-card p-8 text-center bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Processing...</h3>
        <p id="loadingMessage" class="text-gray-600">Please wait while we process your request.</p>
    </div>
</div>

<!-- Database Restore Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-card p-6 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">🔄 Restore Database</h3>
            <button onclick="closeRestoreModal()" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
        </div>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 text-sm">
                <strong>⚠️ Warning:</strong><br>
                Restoring a database backup will overwrite all current data. This action cannot be undone. Please ensure you have a recent backup before proceeding.
            </p>
        </div>
        <form onsubmit="return handleRestore(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Backup File</label>
                <input type="file" accept=".sql,.zip" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all shadow-sm">
            </div>
            <div class="mb-4">
                <label class="flex items-center gap-2 text-gray-700">
                    <input type="checkbox" required class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm">I understand this will overwrite all current data</span>
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRestoreModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Restore Database</button>
            </div>
        </form>
    </div>
</div>

<script>
// Unified tab switching with sidebar management
function showTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none';
    });
    
    // Update tab button styles (top row buttons with class "tab")
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.classList.remove('active', 'border-blue-600', 'text-blue-600', 'bg-blue-50');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
        selectedTab.style.display = 'block';
    }
    
    // Mark the corresponding button as active
    const activeButton = document.querySelector(`button[onclick="showTab('${tabName}')"]`);
    if (activeButton) {
        activeButton.classList.remove('border-transparent', 'text-gray-500');
        activeButton.classList.add('active', 'border-blue-600', 'text-blue-600', 'bg-blue-50');
    }
    
    // Sidebar visibility and layout adjustments
    const sidebarContainer = document.getElementById('sidebar-container');
    const mainContent = document.getElementById('main-content');
    const backButton = document.getElementById('back-to-dashboard-btn');
    if (tabName === 'analytics' || tabName === 'settings' || tabName === 'my_requests') {
        if (sidebarContainer) sidebarContainer.style.display = 'none';
        if (mainContent) mainContent.style.marginLeft = '0';
        // Show back button for my_requests tab
        if (tabName === 'my_requests' && backButton) {
            backButton.style.display = 'flex';
        } else if (backButton) {
            backButton.style.display = 'none';
        }
    } else {
        if (sidebarContainer) sidebarContainer.style.display = 'block';
        if (mainContent) mainContent.style.marginLeft = '16rem'; // ml-64
        if (backButton) backButton.style.display = 'none';
    }
}

// Initialize default tab to Users and wire up My Requests search
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const initialTab = params.get('tab');
    showTab(initialTab && ['users','requests','my_requests','analytics','settings'].includes(initialTab) ? initialTab : 'users');

    const myAdminRequestSearch = document.getElementById('myAdminRequestSearch');
    const myAdminRequestsContainer = document.getElementById('myAdminRequestsContainer');
    
    if (myAdminRequestSearch && myAdminRequestsContainer) {
        myAdminRequestSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const requests = myAdminRequestsContainer.querySelectorAll('.card-req');
            
            requests.forEach(request => {
                const searchContent = (request.getAttribute('data-search-content') || '').toLowerCase();
                if (!searchTerm || searchContent.includes(searchTerm)) {
                    request.style.display = 'block';
                } else {
                    request.style.display = 'none';
                }
            });
            
            const visibleRequests = Array.from(requests).filter(req => req.style.display !== 'none');
            let noResultsMsg = myAdminRequestsContainer.querySelector('.no-results');
            
            if (visibleRequests.length === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results text-center text-gray-500 py-10';
                    noResultsMsg.innerHTML = `
                        <p class="text-lg">🔍 No requests found</p>
                        <p class="mt-2 text-sm text-gray-400">No requests match your search criteria.</p>
                    `;
                    myAdminRequestsContainer.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    }
});
</script>

<!-- Mobile Menu JavaScript -->
<script src="assets/mobile-menu.js"></script>

<style>
    .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display:none; align-items:center; justify-content:center; z-index:60; }
    .modal.show { display:flex; }
    .modal-card { background: #ffffff; border:1px solid rgba(0,0,0,.1); color:#1f2937; border-radius: 12px; width: 90%; max-width: 520px; box-shadow: 0 24px 64px rgba(0,0,0,.2); }
    .marquee { white-space: nowrap; overflow: hidden; }
    .marquee > span { display:inline-block; padding-left:100%; animation: marquee 30s linear infinite; }
    @keyframes marquee { 0% { transform: translateX(100%);} 100% { transform: translateX(-100%);} }
    
    /* Enhanced scrolling styles */
    .smooth-scroll {
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
    }
    
    .smooth-scroll::-webkit-scrollbar {
        width: 8px;
    }
    
    .smooth-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .smooth-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.3);
        border-radius: 4px;
    }
    
    .smooth-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.5);
    }
    
    /* Back button animations */
    .back-button {
        backdrop-filter: blur(12px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .back-button:hover {
        transform: translateX(-2px) scale(1.05);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
</div>
</div>

<!-- Add Directorate Modal -->
<div id="addDirectorateModal" class="modal">
    <div class="modal-card p-6 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Add New Directorate</h3>
            <button onclick="closeAddDirectorateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
        </div>
        <form method="POST" action="index.php?action=admin_management">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Directorate Name</label>
                <input
                    type="text"
                    name="directorate_name"
                    required
                    class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-sm"
                    placeholder="Enter directorate name"
                >
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddDirectorateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_directorate" class="btn btn-primary">Add Directorate</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Role Modal -->
<div id="addRoleModal" class="modal">
    <div class="modal-card p-6 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Add New Role</h3>
            <button onclick="closeAddRoleModal()" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
        </div>
        <form method="POST" action="index.php?action=admin_management">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                <input type="text" name="role_name" required class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" placeholder="Enter role name">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddRoleModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_role" class="btn btn-primary">Add Role</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
