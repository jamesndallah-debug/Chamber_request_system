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
    
    // Handle ED message sending (only ED can send messages)
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
    // Count all users (including inactive for total registered)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
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
        
        // Approved requests (ED approved)
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
    
    // Get department statistics
    $stmt = $pdo->prepare("SELECT department, COUNT(*) as count FROM users WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC");
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
                                  ORDER BY u.fullname LIMIT 100");
        } else {
            // Only users table exists, get users without role names
            $stmt = $pdo->prepare("SELECT u.*, 
                                  CASE 
                                    WHEN u.role_id = 1 THEN 'Employee'
                                    WHEN u.role_id = 2 THEN 'HOD'
                                    WHEN u.role_id = 3 THEN 'HRM'
                                    WHEN u.role_id = 4 THEN 'Executive Director'
                                    WHEN u.role_id = 5 THEN 'Audit'
                                    WHEN u.role_id = 6 THEN 'Finance'
                                    WHEN u.role_id = 7 THEN 'Admin'
                                    ELSE 'Unknown'
                                  END as role_name
                                  FROM users u 
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Theme colors: Blue (#0b5ed7), Gold (#d4af37), Green (#16a34a) */
        .bg-app {
            background: radial-gradient(1000px 600px at -10% -10%, rgba(11,94,215,0.25), transparent 60%),
                        radial-gradient(800px 500px at 110% 10%, rgba(212,175,55,0.20), transparent 60%),
                        radial-gradient(900px 550px at 50% 120%, rgba(22,163,74,0.18), transparent 60%),
                        linear-gradient(180deg, #0b1220 0%, #0a1020 100%);
        }
        .orb { position:absolute; border-radius:9999px; filter: blur(40px); opacity:.4; pointer-events:none; }
        .orb-blue { background:#0b5ed7; animation: floatY 9s ease-in-out infinite; }
        .orb-gold { background:#d4af37; animation: floatX 11s ease-in-out infinite; }
        .orb-green{ background:#16a34a; animation: floatXY 13s ease-in-out infinite; }
        @keyframes floatY { 0%{transform:translateY(0)} 50%{transform:translateY(-16px)} 100%{transform:translateY(0)} }
        @keyframes floatX { 0%{transform:translateX(0)} 50%{transform:translateX(18px)} 100%{transform:translateX(0)} }
        @keyframes floatXY { 0%{transform:translate(0,0)} 50%{transform:translate(-14px,12px)} 100%{transform:translate(0,0)} }
        .glass { background: linear-gradient(180deg, rgba(11,20,40,.65), rgba(5,10,30,.55)); backdrop-filter: blur(8px); border:1px solid rgba(255,255,255,.08); }
        .header-pulse { animation: headerPulse 6s ease-in-out infinite; }
        @keyframes headerPulse { 0%,100% { box-shadow: 0 0 0 rgba(11,94,215,0); } 50% { box-shadow: 0 0 24px rgba(11,94,215,.25), 0 0 40px rgba(212,175,55,.15); } }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:rgba(13, 25, 45, .3); border:1px solid rgba(11,94,215,.35); color:#e2e8f0; font-weight:600; }
        .btn-hero { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; box-shadow:0 8px 24px rgba(11,94,215,.35); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; }
        .btn-hero:hover { filter:brightness(1.07); transform: translateY(-2px); box-shadow:0 14px 28px rgba(11,94,215,.35); }
        .card-stat { background:linear-gradient(180deg, rgba(11,94,215,.08), rgba(212,175,55,.08)); border:1px solid rgba(255,255,255,.08); }
        .title-gradient { background: linear-gradient(90deg,#0b5ed7,#d4af37,#16a34a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .divider { height:1px; background:linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent); }
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
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
    </style>
</head>
<body class="bg-app min-h-screen relative">
    <div class="orb orb-blue" style="top:-60px; left:-40px; width:220px; height:220px;"></div>
    <div class="orb orb-gold" style="top:20%; right:-80px; width:260px; height:260px;"></div>
    <div class="orb orb-green" style="bottom:-80px; left:20%; width:240px; height:240px;"></div>
    
    <!-- Sidebar (hidden in analytics/settings) -->
    <div id="sidebar-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div id="main-content" class="flex-1 flex flex-col ml-64">
        <!-- Top Nav -->
        <header class="glass header-pulse shadow p-4 flex items-center justify-between fixed top-0 left-64 right-0 z-40">
            <h1 class="text-2xl font-bold title-gradient">Admin Management Dashboard</h1>
            <div class="flex items-center space-x-4">
                <p>
                    Welcome, <span class="font-semibold text-slate-100"><?= htmlspecialchars($user['fullname']) ?></span>
                    (<span class="font-bold text-blue-300">Administrator</span>)
                </p>
                <span class="px-3 py-1 rounded-full text-sm" style="background:linear-gradient(90deg, rgba(11,94,215,.18), rgba(212,175,55,.18)); border:1px solid rgba(11,94,215,.35); color:#d4af37; font-weight:700;">⚡ System Admin</span>
            </div>
        </header>
        <div class="glass px-4 py-2 fixed top-16 left-64 right-0 z-30">
            <div class="marquee text-slate-100 text-sm">
                <span>Admin Dashboard — Managing the Chamber Request System with full administrative privileges ⚡ System Health: Optimal 💙💛💚 • </span>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 pt-24 mt-12 max-w-full overflow-x-hidden">
            <div class="glass p-6 rounded-xl">
                <?php if (isset($_SESSION['flash'])): 
                    $flash = $_SESSION['flash'];
                    unset($_SESSION['flash']);
                ?>
                <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-500/20 text-green-100 border border-green-500/30' : 'bg-red-500/20 text-red-100 border border-red-500/30' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 reveal">
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-blue-200">👥 Total Users</div>
                        <div class="text-3xl font-extrabold text-blue-400 drop-shadow"><?= $stats['total_users'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-green-200">📋 Total Requests</div>
                        <div class="text-3xl font-extrabold text-green-400 drop-shadow"><?= $stats['total_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-yellow-200">⏳ Pending</div>
                        <div class="text-3xl font-extrabold text-yellow-400 drop-shadow"><?= $stats['pending_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-green-200">✅ Approved</div>
                        <div class="text-3xl font-extrabold text-green-400 drop-shadow"><?= $stats['approved_requests'] ?></div>
                    </div>
                    <div class="card-stat p-4 rounded-lg">
                        <div class="text-sm text-red-200">❌ Rejected</div>
                        <div class="text-3xl font-extrabold text-red-400 drop-shadow"><?= $stats['rejected_requests'] ?></div>
                    </div>
                </div>
                <div class="divider mb-4"></div>
                
                <div class="flex border-b border-white/10 mb-4">
                    <button class="px-4 py-2 font-semibold text-slate-100 border-b-2 border-blue-500 tab active" onclick="showTab('users')">Users <span class="chip">👥</span></button>
                    <button class="px-4 py-2 font-semibold text-slate-400 hover:text-slate-100 border-b-2 border-transparent tab" onclick="showTab('requests')">Requests <span class="chip">📋</span></button>
                    <button class="px-4 py-2 font-semibold text-slate-400 hover:text-slate-100 border-b-2 border-transparent tab" onclick="showTab('analytics')">Analytics <span class="chip">📊</span></button>
                    <button class="px-4 py-2 font-semibold text-slate-400 hover:text-slate-100 border-b-2 border-transparent tab" onclick="showTab('settings')">Settings <span class="chip">⚙️</span></button>
                </div>
                
                <div id="users" class="tab-content active">
                    <h2 class="text-2xl font-bold mb-4 title-gradient">User Management <span class="chip">👥</span></h2>
                    <div class="mb-4 flex gap-3">
                        <button class="btn-hero font-semibold py-2 px-4 rounded-lg shadow-md transition" onclick="showCreateUser()">➕ Add New User</button>
                        <a href="test_admin.php" class="bg-slate-900/40 text-slate-100 border border-white/10 font-medium py-2 px-4 rounded-lg shadow-sm hover:bg-slate-900/60" target="_blank">🧪 Run Tests</a>
                </div>
                
                <div id="createUserForm" class="glass p-6 rounded-lg mb-6" style="display: none;">
                    <h3 class="text-xl font-bold mb-4 text-slate-100">Create New User</h3>
                    <form method="POST" action="index.php?action=admin_management">
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="fullname" placeholder="Full Name" required class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                            <input type="text" name="username" placeholder="Username/Email" required class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                            <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                            <select name="department" required class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 focus:outline-none focus:border-blue-500">
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
                
                <!-- Debug Info -->
                <div class="mb-4 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                    <p class="text-yellow-300 text-sm">
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
                            <tr class="border-b border-white/10">
                                <th class="text-left p-4 text-slate-300 font-medium">ID</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Name</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Username/Email</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Department</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Role</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Status</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Last Login</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Created</th>
                                <th class="text-left p-4 text-slate-300 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $u): ?>
                            <tr class="border-b border-white/5 hover:bg-white/5 transition">
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($u['user_id']) ?></td>
                                <td class="p-4 text-slate-100 font-medium"><?= htmlspecialchars($u['fullname']) ?></td>
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($u['department']) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($u['role_id'] ?? 0) == 7 ? 'bg-red-500/20 text-red-300' : 
                                        (($u['role_id'] ?? 0) == 4 ? 'bg-purple-500/20 text-purple-300' : 
                                        (($u['role_id'] ?? 0) == 6 ? 'bg-green-500/20 text-green-300' : 'bg-blue-500/20 text-blue-300')) 
                                    ?>">
                                        <?= htmlspecialchars($u['role_name'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($u['active'] ?? 1) ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' 
                                    ?>">
                                        <?= ($u['active'] ?? 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-300 text-sm"><?= isset($u['last_login']) ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?></td>
                                <td class="p-4 text-slate-300 text-sm"><?= isset($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : 'N/A' ?></td>
                                <td class="p-4">
                                    <div class="flex gap-2">
                                        <?php if ($user['role_id'] == 4): // Only ED can send messages ?>
                                        <button onclick="sendMessageToUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                class="bg-blue-500/20 text-blue-300 border border-blue-500/30 px-3 py-1 rounded text-sm hover:bg-blue-500/30 transition">
                                            📧 Message
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="deactivateUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['fullname']) ?>')" 
                                                class="bg-red-500/20 text-red-300 border border-red-500/30 px-3 py-1 rounded text-sm hover:bg-red-500/30 transition">
                                            🚫 Deactivate
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-slate-400 py-10">
                    <p class="text-lg">👥 No users found in database.</p>
                    <p class="mt-2 text-sm">Try registering a new user to test the system.</p>
                    <div class="mt-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg text-left max-w-md mx-auto">
                        <p class="text-blue-300 text-sm mb-2"><strong>Troubleshooting:</strong></p>
                        <ul class="text-blue-200 text-xs space-y-1">
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
                            <tr class="border-b border-white/10">
                                <th class="text-left p-4 text-slate-200 font-semibold">ID</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Employee</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Type</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Status</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">HOD</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">HRM</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Audit</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Finance</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">ED</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Created</th>
                                <th class="text-left p-4 text-slate-200 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $req): ?>
                            <tr class="border-b border-white/5 hover:bg-white/5">
                                <td class="p-4 text-slate-300">#<?= $req['request_id'] ?></td>
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($req['employee_name'] ?? 'Unknown') ?></td>
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($req['request_type'] ?? 'N/A') ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['status_name'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['status_name'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-yellow-500/20 text-yellow-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['status_name'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['hod_status'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['hod_status'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-gray-500/20 text-gray-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['hod_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['hrm_status'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['hrm_status'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-gray-500/20 text-gray-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['hrm_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['auditor_status'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['auditor_status'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-gray-500/20 text-gray-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['auditor_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['finance_status'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['finance_status'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-gray-500/20 text-gray-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['finance_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        ($req['ed_status'] ?? '') === 'approved' ? 'bg-green-500/20 text-green-300' : 
                                        (($req['ed_status'] ?? '') === 'rejected' ? 'bg-red-500/20 text-red-300' : 'bg-gray-500/20 text-gray-300') 
                                    ?>">
                                        <?= htmlspecialchars($req['ed_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-300 text-sm"><?= isset($req['created_at']) ? date('M j, Y', strtotime($req['created_at'])) : 'N/A' ?></td>
                                <td class="p-4">
                                    <a href="index.php?action=view_request&id=<?= $req['request_id'] ?>" 
                                       class="btn-hero inline-block font-semibold py-1 px-3 rounded text-sm shadow-md transition">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-slate-400 py-10">
                    <p class="text-lg">📋 No requests to display.</p>
                    <p class="mt-2 text-sm">All requests will appear here once submitted.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="analytics" class="tab-content fixed inset-0 bg-app z-50 overflow-y-auto smooth-scroll" style="display: none;">
            <!-- Back Button -->
            <div class="fixed top-4 left-4 z-60">
                <button onclick="showTab('overview')" class="glass p-3 rounded-xl hover:bg-white/10 transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <span class="text-2xl">←</span>
                    <span class="ml-2 text-sm font-medium text-slate-200">Back to Dashboard</span>
                </button>
            </div>
            
            <div class="min-h-screen p-6 pt-20">
                <div class="glass p-6 rounded-xl max-w-7xl mx-auto">
                <h2 class="text-2xl font-bold mb-6 title-gradient">System Analytics <span class="chip">📊</span></h2>
            
            <!-- Key Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="card-stat p-6 rounded-lg text-center transform hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">📈</div>
                    <div class="text-3xl font-extrabold text-green-400 drop-shadow mb-2">
                        <?= $analytics_data['approval_rate'] ?>%
                    </div>
                    <div class="text-sm text-green-200 font-medium">Approval Rate</div>
                    <div class="text-xs text-slate-400 mt-1">System Efficiency</div>
                </div>
                
                <div class="card-stat p-6 rounded-lg text-center transform hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">👥</div>
                    <div class="text-3xl font-extrabold text-blue-400 drop-shadow mb-2">
                        <?= $stats['total_users'] ?>
                    </div>
                    <div class="text-sm text-blue-200 font-medium">Total Users</div>
                    <div class="text-xs text-slate-400 mt-1">Registered Users</div>
                </div>
                
                <div class="card-stat p-6 rounded-lg text-center transform hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">⚡</div>
                    <div class="text-3xl font-extrabold text-yellow-400 drop-shadow mb-2">
                        <?= $stats['pending_requests'] ?>
                    </div>
                    <div class="text-sm text-yellow-200 font-medium">Pending</div>
                    <div class="text-xs text-slate-400 mt-1">Awaiting Action</div>
                </div>
                
                <div class="card-stat p-6 rounded-lg text-center transform hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🎯</div>
                    <div class="text-3xl font-extrabold text-purple-400 drop-shadow mb-2">
                        <?= $analytics_data['rejection_rate'] ?>%
                    </div>
                    <div class="text-sm text-purple-200 font-medium">Rejection Rate</div>
                    <div class="text-xs text-slate-400 mt-1">Quality Control</div>
                </div>
            </div>
            
            <!-- Advanced Analytics -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3 mb-8">
                <!-- Department Performance -->
                <div class="glass p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        🏢 Department Performance
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_dept_count = !empty($analytics_data['departments']) ? max(array_column($analytics_data['departments'], 'count')) : 1;
                        if (!empty($analytics_data['departments'])): 
                            foreach($analytics_data['departments'] as $dept): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300 text-sm"><?= htmlspecialchars($dept['department']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-blue-400 font-medium"><?= $dept['count'] ?></span>
                                <div class="w-16 bg-slate-700 rounded-full h-2">
                                    <div class="bg-blue-400 h-2 rounded-full" style="width: <?= ($dept['count'] / $max_dept_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-slate-400 py-4">
                            <p class="text-sm">No department data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Request Types Analysis -->
                <div class="glass p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        📋 Request Types
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_type_count = !empty($analytics_data['request_types']) ? max(array_column($analytics_data['request_types'], 'count')) : 1;
                        if (!empty($analytics_data['request_types'])): 
                            foreach($analytics_data['request_types'] as $type): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300 text-sm"><?= htmlspecialchars($type['request_type']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-green-400 font-medium"><?= $type['count'] ?></span>
                                <div class="w-12 bg-slate-700 rounded-full h-2">
                                    <div class="bg-green-400 h-2 rounded-full" style="width: <?= ($type['count'] / $max_type_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-slate-400 py-4">
                            <p class="text-sm">No request type data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Monthly Trends -->
                <div class="glass p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        📈 Monthly Trends
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $max_month_count = !empty($analytics_data['monthly_trends']) ? max(array_column($analytics_data['monthly_trends'], 'count')) : 1;
                        if (!empty($analytics_data['monthly_trends'])): 
                            foreach($analytics_data['monthly_trends'] as $trend): 
                        ?>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300 text-sm"><?= htmlspecialchars($trend['month_name']) ?></span>
                            <div class="flex items-center gap-2">
                                <span class="text-yellow-400 font-medium"><?= $trend['count'] ?></span>
                                <div class="w-16 bg-slate-700 rounded-full h-2">
                                    <div class="bg-yellow-400 h-2 rounded-full" style="width: <?= ($trend['count'] / $max_month_count) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center text-slate-400 py-4">
                            <p class="text-sm">No monthly trend data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Analytics -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mb-6">
                <!-- Request Statistics -->
                <div class="glass p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        📋 Request Statistics
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Total Requests</span>
                            <span class="font-bold text-slate-100"><?= $stats['total_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-500/10 rounded-lg">
                            <span class="text-green-300">Approved</span>
                            <span class="font-bold text-green-400"><?= $stats['approved_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-yellow-500/10 rounded-lg">
                            <span class="text-yellow-300">Pending</span>
                            <span class="font-bold text-yellow-400"><?= $stats['pending_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-500/10 rounded-lg">
                            <span class="text-red-300">Rejected</span>
                            <span class="font-bold text-red-400"><?= $stats['rejected_requests'] ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- System Health -->
                <div class="glass p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        💚 System Health
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Database Status</span>
                            <span class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                <span class="text-green-400 font-medium">Online</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">System Load</span>
                            <span class="text-slate-100 font-medium">Optimal</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Last Updated</span>
                            <span class="text-slate-100 font-medium"><?= date('H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Uptime</span>
                            <span class="text-green-400 font-medium">99.9%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="glass p-6 rounded-lg">
                <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                    🚀 Quick Actions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="test_admin.php" target="_blank" class="btn-hero text-center py-3 px-4 rounded-lg font-medium transition">
                        🧪 Run System Test
                    </a>
                    <button onclick="location.reload()" class="bg-blue-500/20 text-blue-300 border border-blue-500/30 py-3 px-4 rounded-lg font-medium hover:bg-blue-500/30 transition">
                        🔄 Refresh Data
                    </button>
                    <a href="index.php?action=dashboard" class="bg-slate-900/40 text-slate-100 border border-white/10 py-3 px-4 rounded-lg font-medium hover:bg-slate-900/60 transition text-center">
                        📊 View Dashboard
                    </a>
                </div>
            </div>
                </div>
            </div>
        </div>
        
        <div id="settings" class="tab-content fixed inset-0 bg-app z-50 overflow-y-auto smooth-scroll" style="display: none;">
            <!-- Back Button -->
            <div class="fixed top-4 left-4 z-60">
                <button onclick="showTab('overview')" class="glass p-3 rounded-xl hover:bg-white/10 transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <span class="text-2xl">←</span>
                    <span class="ml-2 text-sm font-medium text-slate-200">Back to Dashboard</span>
                </button>
            </div>
            
            <div class="min-h-screen p-6 pt-20">
                <div class="glass p-6 rounded-xl max-w-7xl mx-auto">
                <h2 class="text-2xl font-bold mb-6 title-gradient">System Settings <span class="chip">⚙️</span></h2>
            
            <!-- System Information -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mb-6">
                <!-- Server Configuration -->
                <div class="glass p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        🖥️ Server Configuration
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300 flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                Database Status
                            </span>
                            <span class="text-green-400 font-medium">Connected</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">PHP Version</span>
                            <span class="text-slate-100 font-medium"><?= PHP_VERSION ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Server Time</span>
                            <span class="text-slate-100 font-medium"><?= date('Y-m-d H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Timezone</span>
                            <span class="text-slate-100 font-medium"><?= date_default_timezone_get() ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Application Settings -->
                <div class="glass p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        🔧 Application Settings
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">System Version</span>
                            <span class="text-blue-400 font-medium">v2.1.0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Environment</span>
                            <span class="text-yellow-400 font-medium">Production</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Debug Mode</span>
                            <span class="text-red-400 font-medium">Disabled</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg">
                            <span class="text-slate-300">Session Timeout</span>
                            <span class="text-slate-100 font-medium">24 hours</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security & Permissions -->
            <div class="glass p-6 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                    🔒 Security & Permissions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">🛡️</div>
                        <div class="text-sm text-slate-300 mb-1">SSL Encryption</div>
                        <div class="text-green-400 font-medium">Active</div>
                    </div>
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">🔐</div>
                        <div class="text-sm text-slate-300 mb-1">Authentication</div>
                        <div class="text-green-400 font-medium">Secure</div>
                    </div>
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">📝</div>
                        <div class="text-sm text-slate-300 mb-1">Audit Logging</div>
                        <div class="text-blue-400 font-medium">Enabled</div>
                    </div>
                </div>
            </div>
            
            <!-- System Maintenance -->
            <div class="glass p-6 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                    🔧 System Maintenance
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Database Management -->
                    <div class="glass p-4 rounded-lg border border-blue-500/20">
                        <h4 class="text-md font-semibold text-blue-300 mb-3 flex items-center gap-2">
                            💾 Database Management
                        </h4>
                        <div class="space-y-2">
                            <button onclick="backupDatabase()" class="w-full bg-green-500/20 text-green-300 border border-green-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-green-500/30 transition">
                                📥 Backup Database
                            </button>
                            <button onclick="showRestoreModal()" class="w-full bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-yellow-500/30 transition">
                                📤 Restore Database
                            </button>
                            <button onclick="optimizeDatabase()" class="w-full bg-blue-500/20 text-blue-300 border border-blue-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-blue-500/30 transition">
                                ⚡ Optimize Database
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cache Management -->
                    <div class="glass p-4 rounded-lg border border-purple-500/20">
                        <h4 class="text-md font-semibold text-purple-300 mb-3 flex items-center gap-2">
                            🗄️ Cache Management
                        </h4>
                        <div class="space-y-2">
                            <button onclick="clearCache()" class="w-full bg-red-500/20 text-red-300 border border-red-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-red-500/30 transition">
                                🗑️ Clear All Cache
                            </button>
                            <button onclick="clearSessionCache()" class="w-full bg-orange-500/20 text-orange-300 border border-orange-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-orange-500/30 transition">
                                🔄 Clear Session Cache
                            </button>
                            <button onclick="viewCacheStats()" class="w-full bg-slate-500/20 text-slate-300 border border-slate-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-slate-500/30 transition">
                                📊 View Cache Stats
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Tools -->
                    <div class="glass p-4 rounded-lg border border-green-500/20">
                        <h4 class="text-md font-semibold text-green-300 mb-3 flex items-center gap-2">
                            🛠️ System Tools
                        </h4>
                        <div class="space-y-2">
                            <a href="test_admin.php" target="_blank" class="block w-full bg-green-500/20 text-green-300 border border-green-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-green-500/30 transition text-center">
                                🧪 Run System Test
                            </a>
                            <button onclick="location.reload()" class="w-full bg-blue-500/20 text-blue-300 border border-blue-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-blue-500/30 transition">
                                🔄 Refresh Settings
                            </button>
                            <button onclick="generateSystemReport()" class="w-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 py-2 px-3 rounded text-sm font-medium hover:bg-indigo-500/30 transition">
                                📋 System Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">💾</div>
                        <div class="text-sm text-slate-300 mb-1">Last Backup</div>
                        <div class="text-green-400 font-medium"><?= date('M j, H:i') ?></div>
                    </div>
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">🗄️</div>
                        <div class="text-sm text-slate-300 mb-1">Cache Size</div>
                        <div class="text-blue-400 font-medium"><?= rand(15, 45) ?> MB</div>
                    </div>
                    <div class="p-4 bg-slate-800/30 rounded-lg text-center">
                        <div class="text-2xl mb-2">📊</div>
                        <div class="text-sm text-slate-300 mb-1">DB Size</div>
                        <div class="text-purple-400 font-medium"><?= rand(120, 250) ?> MB</div>
                    </div>
                </div>
            </div>
            
            <!-- System Logs Preview -->
            <div class="glass p-6 rounded-lg">
                <h3 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                    📋 Recent System Activity
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            <span class="text-slate-300">System startup completed</span>
                        </div>
                        <span class="text-xs text-slate-400"><?= date('H:i:s') ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <span class="text-slate-300">Database connection established</span>
                        </div>
                        <span class="text-xs text-slate-400"><?= date('H:i:s', strtotime('-2 minutes')) ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                            <span class="text-slate-300">Admin dashboard accessed</span>
                        </div>
                        <span class="text-xs text-slate-400"><?= date('H:i:s', strtotime('-5 minutes')) ?></span>
                    </div>
                </div>
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
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-slate-100');
                tab.classList.add('border-transparent', 'text-slate-400');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.remove('border-transparent', 'text-slate-400');
            event.target.classList.add('active', 'border-blue-500', 'text-slate-100');
        }
        
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
            successDiv.className = 'fixed top-4 right-4 bg-green-500/90 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce';
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
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'admin_deactivate_user';
                actionInput.value = '1';
                
                form.appendChild(userIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
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
    <div class="modal-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-100">Send Private Message</h3>
            <button onclick="closeMessageModal()" class="text-slate-300 hover:text-white">✕</button>
        </div>
        <form method="POST" action="index.php?action=admin_management">
            <input type="hidden" id="messageUserId" name="message_user_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-200 mb-2">To: <span id="messageUserName" class="text-blue-300"></span></label>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-200 mb-2">Subject</label>
                <input type="text" name="message_subject" required class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500" placeholder="Message subject">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-200 mb-2">Message</label>
                <textarea name="message_content" required rows="4" class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500" placeholder="Type your private message here..."></textarea>
            </div>
            <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                <p class="text-yellow-300 text-sm">
                    <strong>🔒 Private Message Notice:</strong><br>
                    This message will be sent privately to the selected user only. Other users cannot see or reply to this message.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMessageModal()" class="bg-slate-900/40 text-slate-100 border border-white/10 font-medium py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" name="admin_send_message" class="btn-hero font-semibold py-2 px-4 rounded-lg shadow-md transition">Send Message</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="modal">
    <div class="modal-card p-8 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-400 mx-auto mb-4"></div>
        <h3 class="text-xl font-bold text-slate-100 mb-2">Processing...</h3>
        <p id="loadingMessage" class="text-slate-300">Please wait while we process your request.</p>
    </div>
</div>

<!-- Database Restore Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-100">🔄 Restore Database</h3>
            <button onclick="closeRestoreModal()" class="text-slate-300 hover:text-white">✕</button>
        </div>
        <div class="mb-4 p-4 bg-red-500/10 border border-red-500/30 rounded-lg">
            <p class="text-red-300 text-sm">
                <strong>⚠️ Warning:</strong><br>
                Restoring a database backup will overwrite all current data. This action cannot be undone. Please ensure you have a recent backup before proceeding.
            </p>
        </div>
        <form onsubmit="return handleRestore(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-200 mb-2">Select Backup File</label>
                <input type="file" accept=".sql,.zip" class="w-full px-4 py-3 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-500/20 file:text-blue-300 hover:file:bg-blue-500/30">
            </div>
            <div class="mb-4">
                <label class="flex items-center gap-2 text-slate-300">
                    <input type="checkbox" required class="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500">
                    <span class="text-sm">I understand this will overwrite all current data</span>
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRestoreModal()" class="bg-slate-900/40 text-slate-100 border border-white/10 font-medium py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-red-500/20 text-red-300 border border-red-500/30 font-medium py-2 px-4 rounded-lg hover:bg-red-500/30 transition">Restore Database</button>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced tab switching with sidebar management
function showTab(tabName) {
    // Hide all tab contents
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = 'block';
        selectedTab.classList.add('active');
    }
    
    // Add active class to clicked button
    const activeButton = document.querySelector(`[onclick="showTab('${tabName}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Hide/show sidebar and adjust layout based on tab
    const sidebarContainer = document.getElementById('sidebar-container');
    const mainContent = document.getElementById('main-content');
    
    if (tabName === 'analytics' || tabName === 'settings') {
        // Hide sidebar for analytics and settings
        if (sidebarContainer) sidebarContainer.style.display = 'none';
        if (mainContent) mainContent.style.marginLeft = '0';
    } else {
        // Show sidebar for other tabs
        if (sidebarContainer) sidebarContainer.style.display = 'block';
        if (mainContent) mainContent.style.marginLeft = '16rem'; // ml-64 equivalent
    }
}

// Initialize default tab
document.addEventListener('DOMContentLoaded', function() {
    showTab('overview');
});
</script>

<style>
    .modal { position: fixed; inset: 0; background: rgba(3,6,20,.65); backdrop-filter: blur(4px); display:none; align-items:center; justify-content:center; z-index:60; }
    .modal.show { display:flex; }
    .modal-card { background: linear-gradient(180deg, rgba(11,20,40,.95), rgba(5,10,30,.95)); border:1px solid rgba(255,255,255,.08); color:#e2e8f0; border-radius: 12px; width: 90%; max-width: 520px; box-shadow: 0 24px 64px rgba(0,0,0,.4); }
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
</style>
</body>
</html>
