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
    // Handle user creation
    if (isset($_POST['admin_create_user'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $department = $_POST['department'] ?? '';
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        if ($fullname && $username && $password && $department && $role_id) {
            try {
                // Check if username exists
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $check->execute([$username]);
                
                if ($check->fetchColumn() == 0) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, department, role_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$fullname, $username, $hash, $department, $role_id])) {
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
}

// Get dashboard statistics
$stats = [
    'total_users' => 0,
    'total_requests' => 0,
    'pending_requests' => 0,
    'approved_requests' => 0,
    'rejected_requests' => 0
];

try {
    // Count users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE active = 1");
    $stmt->execute();
    $stats['total_users'] = (int)$stmt->fetchColumn();
    
    // Count requests if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'requests'");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests");
        $stmt->execute();
        $stats['total_requests'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status_id = 1");
        $stmt->execute();
        $stats['pending_requests'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status_id = 2");
        $stmt->execute();
        $stats['approved_requests'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status_id = 3");
        $stmt->execute();
        $stats['rejected_requests'] = (int)$stmt->fetchColumn();
    }
} catch (Exception $e) {
    error_log("Admin dashboard stats error: " . $e->getMessage());
}

// Get users list
$users_list = [];
try {
    $stmt = $pdo->prepare("SELECT u.*, COALESCE(r.role_name, 'Unknown') as role_name 
                          FROM users u 
                          LEFT JOIN roles r ON u.role_id = r.role_id 
                          WHERE u.active = 1
                          ORDER BY u.fullname LIMIT 100");
    $stmt->execute();
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management | Chamber Request System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 50%, #1e3a8a 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            color: #1e40af;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .users-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-badge.admin {
            background: #fef3c7;
            color: #d97706;
        }
        
        .btn {
            background: #1e40af;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #1d4ed8;
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .flash-message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .flash-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: #6b7280;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab.active {
            color: #1e40af;
            border-bottom-color: #1e40af;
        }
        
        .tab:hover {
            color: #1e40af;
        }
        
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
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ Admin Management</h1>
        <div class="user-info">
            <span>Welcome, <strong><?= htmlspecialchars($user['fullname']) ?></strong></span>
            <a href="index.php?action=logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="flash-message flash-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_requests'] ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_requests'] ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['approved_requests'] ?></div>
                <div class="stat-label">Approved Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['rejected_requests'] ?></div>
                <div class="stat-label">Rejected Requests</div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('users')">👥 Users</button>
            <button class="tab" onclick="showTab('requests')">📋 Requests</button>
            <button class="tab" onclick="showTab('analytics')">📊 Analytics</button>
            <button class="tab" onclick="showTab('settings')">⚙️ Settings</button>
        </div>
        
        <div id="users" class="tab-content active">
            <div class="section">
                <h2>👥 User Management</h2>
                <div class="actions">
                    <button class="btn" onclick="showCreateUser()">➕ Add New User</button>
                    <a href="test_admin.php" class="btn btn-secondary" target="_blank">🧪 Run Tests</a>
                </div>
                
                <div id="createUserForm" style="display: none; background: #f8fafc; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 1rem; color: #1e40af;">Create New User</h3>
                    <form method="POST" action="index.php?action=admin_management">
                        <div class="form-grid">
                            <input type="text" name="fullname" placeholder="Full Name" required class="form-input">
                            <input type="text" name="username" placeholder="Username/Email" required class="form-input">
                            <input type="password" name="password" placeholder="Password" required class="form-input">
                            <select name="department" required class="form-input">
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
                            <select name="role_id" required class="form-input">
                                <option value="">Select Role</option>
                                <option value="1">Employee</option>
                                <option value="2">HRM</option>
                                <option value="3">HOD</option>
                                <option value="4">Executive Director</option>
                                <option value="5">Finance</option>
                                <option value="6">Internal Auditor</option>
                                <option value="7">Admin</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="admin_create_user" value="1" class="btn">Create User</button>
                            <button type="button" onclick="hideCreateUser()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($users_list)): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_list as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['fullname']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['department']) ?></td>
                            <td>
                                <span class="role-badge <?= $u['role_name'] === 'Admin' ? 'admin' : '' ?>">
                                    <?= htmlspecialchars($u['role_name']) ?>
                                </span>
                            </td>
                            <td><?= isset($u['created_at']) ? date('Y-m-d', strtotime($u['created_at'])) : 'N/A' ?></td>
                            <td>
                                <?php if ($u['user_id'] != $user['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <button type="submit" name="admin_deactivate_user" value="1" 
                                            onclick="return confirm('Deactivate this user?')" 
                                            style="background: #ef4444; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Deactivate</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 2rem;">No users found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="requests" class="tab-content">
            <div class="section">
                <h2>📋 Recent Requests</h2>
                <?php if (!empty($recent_requests)): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_requests as $req): ?>
                        <tr>
                            <td>#<?= $req['request_id'] ?></td>
                            <td><?= htmlspecialchars($req['employee_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($req['request_type'] ?? 'N/A') ?></td>
                            <td>
                                <span class="role-badge">
                                    <?= htmlspecialchars($req['status_name'] ?? 'Unknown') ?>
                                </span>
                            </td>
                            <td><?= isset($req['created_at']) ? date('Y-m-d H:i', strtotime($req['created_at'])) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 2rem;">No requests found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="analytics" class="tab-content">
            <div class="section">
                <h2>📊 System Analytics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= round(($stats['approved_requests'] / max($stats['total_requests'], 1)) * 100, 1) ?>%</div>
                        <div class="stat-label">Approval Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= count($users_list) ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= date('Y-m-d') ?></div>
                        <div class="stat-label">Current Date</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="settings" class="tab-content">
            <div class="section">
                <h2>⚙️ System Settings</h2>
                <div class="form-grid">
                    <div>
                        <h3 style="color: #1e40af; margin-bottom: 0.5rem;">Database Status</h3>
                        <p style="color: #10b981;">✅ Connected and operational</p>
                    </div>
                    <div>
                        <h3 style="color: #1e40af; margin-bottom: 0.5rem;">PHP Version</h3>
                        <p><?= PHP_VERSION ?></p>
                    </div>
                    <div>
                        <h3 style="color: #1e40af; margin-bottom: 0.5rem;">Server Time</h3>
                        <p><?= date('Y-m-d H:i:s') ?></p>
                    </div>
                </div>
                <div style="margin-top: 2rem;">
                    <a href="test_admin.php" class="btn" target="_blank">🧪 Run Full System Test</a>
                    <a href="index.php?action=dashboard" class="btn btn-secondary">📊 View Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        function showCreateUser() {
            document.getElementById('createUserForm').style.display = 'block';
        }
        
        function hideCreateUser() {
            document.getElementById('createUserForm').style.display = 'none';
        }
        
        // Auto-test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Management Dashboard Loaded Successfully');
            console.log('User: <?= htmlspecialchars($user['fullname']) ?>');
            console.log('Role: Admin (ID: 7)');
            console.log('Total Users: <?= $stats['total_users'] ?>');
            console.log('Total Requests: <?= $stats['total_requests'] ?>');
        });
    </script>
</body>
</html>
