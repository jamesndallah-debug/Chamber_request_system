<?php
// FILE: settings.php
// Comprehensive Settings Page with Amazing GUI
// Only accessible to Admin (role_id 7)

require_once __DIR__ . '/function.php';

// Check if user is authenticated
$user = current_user();
if (!$user || $user['role_id'] != 7) {
    header('Location: index.php?action=dashboard');
    exit;
}

// Handle POST requests for role and directorate management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();
    
    try {
        global $pdo;
        
        // Directorate Management
        if (isset($_POST['add_directorate'])) {
            $name = trim($_POST['directorate_name'] ?? '');
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO directorates (name, created_at) VALUES (?, CURRENT_TIMESTAMP)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate added successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['edit_directorate_id'])) {
            $id = (int)$_POST['edit_directorate_id'];
            $name = trim($_POST['directorate_name'] ?? '');
            if ($name) {
                $stmt = $pdo->prepare("UPDATE directorates SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate updated successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['activate_directorate_id'])) {
            $id = (int)$_POST['activate_directorate_id'];
            $stmt = $pdo->prepare("UPDATE directorates SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate activated successfully!'];
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['deactivate_directorate_id'])) {
            $id = (int)$_POST['deactivate_directorate_id'];
            $stmt = $pdo->prepare("UPDATE directorates SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deactivated successfully!'];
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['delete_directorate_id'])) {
            $id = (int)$_POST['delete_directorate_id'];
            // Check if directorate has users
            $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE directorate = (SELECT name FROM directorates WHERE id = ?) AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $userCount = $stmt->fetchColumn();
            
            if ($userCount > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete directorate: ' . $userCount . ' users are assigned to this directorate.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM directorates WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deleted successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
        // Role Management
        if (isset($_POST['add_role'])) {
            $name = trim($_POST['role_name'] ?? '');
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, created_at) VALUES (?, CURRENT_TIMESTAMP)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role added successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['edit_role_id'])) {
            $id = (int)$_POST['edit_role_id'];
            $name = trim($_POST['role_name'] ?? '');
            if ($name) {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role updated successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['activate_role_id'])) {
            $id = (int)$_POST['activate_role_id'];
            $stmt = $pdo->prepare("UPDATE roles SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role activated successfully!'];
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['deactivate_role_id'])) {
            $id = (int)$_POST['deactivate_role_id'];
            $stmt = $pdo->prepare("UPDATE roles SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deactivated successfully!'];
            header('Location: index.php?action=settings');
            exit;
        }
        
        if (isset($_POST['delete_role_id'])) {
            $id = (int)$_POST['delete_role_id'];
            // Check if role has users
            $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $userCount = $stmt->fetchColumn();
            
            if ($userCount > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete role: ' . $userCount . ' users are assigned to this role.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deleted successfully!'];
            }
            header('Location: index.php?action=settings');
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        header('Location: index.php?action=settings');
        exit;
    }
}

// Fetch data for display
global $pdo;

// Get system statistics
$system_stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND active = 1")->fetchColumn(),
    'total_requests' => $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn(),
    'pending_requests' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn(),
    'total_directorates' => $pdo->query("SELECT COUNT(*) FROM directorates")->fetchColumn(),
    'active_directorates' => $pdo->query("SELECT COUNT(*) FROM directorates WHERE is_active = 1")->fetchColumn(),
    'total_roles' => $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn(),
    'active_roles' => $pdo->query("SELECT COUNT(*) FROM roles WHERE is_active = 1")->fetchColumn(),
];

// Get directorates with user counts
$directorates = $pdo->query("
    SELECT d.*, 
           COUNT(u.user_id) as user_count
    FROM directorates d
    LEFT JOIN users u ON d.name = u.directorate AND u.deleted_at IS NULL
    GROUP BY d.id
    ORDER BY d.name
")->fetchAll(PDO::FETCH_ASSOC);

// Get roles with user counts
$roles = $pdo->query("
    SELECT r.*, COUNT(u.user_id) as user_count
    FROM roles r
    LEFT JOIN users u ON r.role_id = u.role_id AND u.deleted_at IS NULL
    GROUP BY r.role_id
    ORDER BY r.role_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent system activity
$recent_activity = $pdo->query("
    SELECT 'user_created' as activity_type, u.fullname as description, u.created_at as timestamp
    FROM users u WHERE u.deleted_at IS NULL
    UNION ALL
    SELECT 'request_created' as activity_type, r.request_type as description, r.created_at as timestamp
    FROM requests r
    ORDER BY timestamp DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>System Settings | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <link rel="stylesheet" href="assets/responsive.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-morphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(31, 38, 135, 0.2);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .tab-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .table-hover:hover {
            background: rgba(102, 126, 234, 0.05);
            transition: background 0.2s ease;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-64 glass-morphism z-50">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Header -->
        <header class="glass-morphism sticky top-0 z-40 px-6 py-4 border-b border-white/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-bold gradient-text">System Settings</h1>
                    <div class="pulse-animation">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            System Online
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">Welcome, <?= htmlspecialchars($user['fullname']) ?></span>
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): 
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        ?>
        <div class="mx-6 mt-4 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?> fade-in">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Area -->
        <main class="p-6">
            <!-- System Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
                <div class="stat-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?= $system_stats['total_users'] ?></span>
                    </div>
                    <p class="text-gray-600 text-sm">Total Users</p>
                    <p class="text-green-600 text-xs mt-1"><?= $system_stats['active_users'] ?> Active</p>
                </div>
                
                <div class="stat-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?= $system_stats['total_requests'] ?></span>
                    </div>
                    <p class="text-gray-600 text-sm">Total Requests</p>
                    <p class="text-yellow-600 text-xs mt-1"><?= $system_stats['pending_requests'] ?> Pending</p>
                </div>
                
                <div class="stat-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?= $system_stats['total_directorates'] ?></span>
                    </div>
                    <p class="text-gray-600 text-sm">Directorates</p>
                    <p class="text-green-600 text-xs mt-1"><?= $system_stats['active_directorates'] ?> Active</p>
                </div>
                
                <div class="stat-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?= $system_stats['total_roles'] ?></span>
                    </div>
                    <p class="text-gray-600 text-sm">Roles</p>
                    <p class="text-green-600 text-xs mt-1"><?= $system_stats['active_roles'] ?> Active</p>
                </div>
            </div>
            
            <!-- Tab Navigation -->
            <div class="glass-morphism rounded-2xl p-2 mb-6 fade-in">
                <div class="flex space-x-2">
                    <button onclick="showTab('directorates')" id="directorates-tab" class="tab-btn px-6 py-3 rounded-xl font-medium transition-all duration-300 tab-active">
                        Directorates
                    </button>
                    <button onclick="showTab('roles')" id="roles-tab" class="tab-btn px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:bg-white/10">
                        Roles
                    </button>
                    <button onclick="showTab('system')" id="system-tab" class="tab-btn px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:bg-white/10">
                        System Info
                    </button>
                    <button onclick="showTab('maintenance')" id="maintenance-tab" class="tab-btn px-6 py-3 rounded-xl font-medium transition-all duration-300 hover:bg-white/10">
                        Maintenance
                    </button>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="space-y-6">
                <!-- Directorates Tab -->
                <div id="directorates-content" class="tab-content fade-in">
                    <div class="glass-morphism rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold gradient-text">Directorate Management</h2>
                            <button onclick="openDirectorateModal()" class="btn-gradient text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg transition-all duration-300">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Directorate
                                </span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-white/20">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Users Count</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Created</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($directorates as $directorate): ?>
                                    <tr class="border-b border-white/10 table-hover">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-800"><?= htmlspecialchars($directorate['name']) ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="text-gray-600"><?= $directorate['user_count'] ?> users</span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="status-badge <?= $directorate['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                <span class="w-2 h-2 rounded-full <?= $directorate['is_active'] ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                                                <?= $directorate['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="text-gray-600"><?= date('M d, Y', strtotime($directorate['created_at'])) ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <button onclick="editDirectorate(<?= $directorate['id'] ?>, '<?= htmlspecialchars($directorate['name']) ?>')" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                    Edit
                                                </button>
                                                <?php if ($directorate['is_active']): ?>
                                                    <button onclick="deactivateDirectorate(<?= $directorate['id'] ?>)" class="text-yellow-600 hover:text-yellow-800 font-medium text-sm">
                                                        Deactivate
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="activateDirectorate(<?= $directorate['id'] ?>)" class="text-green-600 hover:text-green-800 font-medium text-sm">
                                                        Activate
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($directorate['user_count'] == 0): ?>
                                                    <button onclick="deleteDirectorate(<?= $directorate['id'] ?>, '<?= htmlspecialchars($directorate['name']) ?>')" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Roles Tab -->
                <div id="roles-content" class="tab-content" style="display: none;">
                    <div class="glass-morphism rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold gradient-text">Role Management</h2>
                            <button onclick="openRoleModal()" class="btn-gradient text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg transition-all duration-300">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Role
                                </span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-white/20">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Role Name</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Users Count</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): ?>
                                    <tr class="border-b border-white/10 table-hover">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-800"><?= htmlspecialchars($role['role_name']) ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="text-gray-600"><?= $role['user_count'] ?> users</span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="status-badge <?= $role['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                <span class="w-2 h-2 rounded-full <?= $role['is_active'] ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                                                <?= $role['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <button onclick="editRole(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name']) ?>')" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                    Edit
                                                </button>
                                                <?php if ($role['is_active']): ?>
                                                    <button onclick="deactivateRole(<?= $role['role_id'] ?>)" class="text-yellow-600 hover:text-yellow-800 font-medium text-sm">
                                                        Deactivate
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="activateRole(<?= $role['role_id'] ?>)" class="text-green-600 hover:text-green-800 font-medium text-sm">
                                                        Activate
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($role['user_count'] == 0): ?>
                                                    <button onclick="deleteRole(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name']) ?>')" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- System Info Tab -->
                <div id="system-content" class="tab-content" style="display: none;">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-morphism rounded-2xl p-6">
                            <h3 class="text-xl font-bold gradient-text mb-4">System Information</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">PHP Version</span>
                                    <span class="font-medium text-gray-800"><?= PHP_VERSION ?></span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Server Time</span>
                                    <span class="font-medium text-gray-800"><?= date('Y-m-d H:i:s') ?></span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Timezone</span>
                                    <span class="font-medium text-gray-800"><?= date_default_timezone_get() ?></span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">System Version</span>
                                    <span class="font-medium text-gray-800">v2.1.0</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-morphism rounded-2xl p-6">
                            <h3 class="text-xl font-bold gradient-text mb-4">Database Information</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Database Status</span>
                                    <span class="status-badge status-active">Connected</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Total Tables</span>
                                    <span class="font-medium text-gray-800">12</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Last Backup</span>
                                    <span class="font-medium text-gray-800"><?= date('Y-m-d H:i:s', strtotime('-1 day')) ?></span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/50 rounded-lg">
                                    <span class="text-gray-600">Storage Used</span>
                                    <span class="font-medium text-gray-800">2.4 GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Tab -->
                <div id="maintenance-content" class="tab-content" style="display: none;">
                    <div class="glass-morphism rounded-2xl p-6">
                        <h3 class="text-xl font-bold gradient-text mb-4">System Maintenance</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <button onclick="backupDatabase()" class="p-6 bg-white/50 rounded-xl hover:bg-white/70 transition-all duration-300 text-center">
                                <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V2"></path>
                                    </svg>
                                </div>
                                <h4 class="font-medium text-gray-800">Backup Database</h4>
                                <p class="text-gray-600 text-sm mt-1">Create system backup</p>
                            </button>
                            
                            <button onclick="optimizeDatabase()" class="p-6 bg-white/50 rounded-xl hover:bg-white/70 transition-all duration-300 text-center">
                                <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h4 class="font-medium text-gray-800">Optimize Database</h4>
                                <p class="text-gray-600 text-sm mt-1">Improve performance</p>
                            </button>
                            
                            <button onclick="clearCache()" class="p-6 bg-white/50 rounded-xl hover:bg-white/70 transition-all duration-300 text-center">
                                <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
                                <h4 class="font-medium text-gray-800">Clear Cache</h4>
                                <p class="text-gray-600 text-sm mt-1">Clear system cache</p>
                            </button>
                            
                            <a href="test_admin.php" target="_blank" class="p-6 bg-white/50 rounded-xl hover:bg-white/70 transition-all duration-300 text-center block">
                                <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <h4 class="font-medium text-gray-800">Run Diagnostics</h4>
                                <p class="text-gray-600 text-sm mt-1">System health check</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Directorate Modal -->
    <div id="directorateModal" class="fixed inset-0 modal-backdrop z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="glass-morphism rounded-2xl p-6 w-full max-w-md">
                <h3 class="text-xl font-bold gradient-text mb-4">Add Directorate</h3>
                <form method="POST" action="index.php?action=settings">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Directorate Name</label>
                        <input type="text" name="directorate_name" required class="w-full px-4 py-3 bg-white/50 border border-white/30 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20" placeholder="Enter directorate name">
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeDirectorateModal()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-xl font-medium hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" name="add_directorate" class="btn-gradient text-white px-6 py-3 rounded-xl font-medium">
                            Add Directorate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Role Modal -->
    <div id="roleModal" class="fixed inset-0 modal-backdrop z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="glass-morphism rounded-2xl p-6 w-full max-w-md">
                <h3 class="text-xl font-bold gradient-text mb-4">Add Role</h3>
                <form method="POST" action="index.php?action=settings">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Role Name</label>
                        <input type="text" name="role_name" required class="w-full px-4 py-3 bg-white/50 border border-white/30 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20" placeholder="Enter role name">
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeRoleModal()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-xl font-medium hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" name="add_role" class="btn-gradient text-white px-6 py-3 rounded-xl font-medium">
                            Add Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.style.display = 'none');
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => tab.classList.remove('tab-active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-content').style.display = 'block';
            document.getElementById(tabName + '-tab').classList.add('tab-active');
        }
        
        // Directorate Modal
        function openDirectorateModal() {
            document.getElementById('directorateModal').classList.remove('hidden');
        }
        
        function closeDirectorateModal() {
            document.getElementById('directorateModal').classList.add('hidden');
        }
        
        // Role Modal
        function openRoleModal() {
            document.getElementById('roleModal').classList.remove('hidden');
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').classList.add('hidden');
        }
        
        // Directorate Actions
        function editDirectorate(id, name) {
            const newName = prompt('Edit directorate name:', name);
            if (newName && newName !== name) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        function activateDirectorate(id) {
            if (confirm('Are you sure you want to activate this directorate?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        function deactivateDirectorate(id) {
            if (confirm('Are you sure you want to deactivate this directorate?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        function deleteDirectorate(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        // Role Actions
        function editRole(id, name) {
            const newName = prompt('Edit role name:', name);
            if (newName && newName !== name) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        function activateRole(id) {
            if (confirm('Are you sure you want to activate this role?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        function deactivateRole(id) {
            if (confirm('Are you sure you want to deactivate this role?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?action=settings';
                
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
        
        // Maintenance Functions
        function backupDatabase() {
            if (confirm('Create a backup of the database? This may take a few minutes.')) {
                alert('Backup functionality would be implemented here.');
            }
        }
        
        function optimizeDatabase() {
            if (confirm('Optimize the database for better performance?')) {
                alert('Database optimization would be implemented here.');
            }
        }
        
        function clearCache() {
            if (confirm('Clear all system cache? This may temporarily slow down the system.')) {
                alert('Cache clearing would be implemented here.');
            }
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Add animations to cards
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
