<?php
// FILE: admin_management.php
// Modern Admin Dashboard for Chamber Request System

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
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE directorate = (SELECT name FROM directorates WHERE id = ?) AND deleted_at IS NULL");
                $stmt->execute([$id]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete directorate while users are assigned to it.'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM directorates WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deleted successfully!'];
                }
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
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL");
                $stmt->execute([$id]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete role while users are assigned to it.'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deleted successfully!'];
                }
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
        $directorate = trim($_POST['directorate'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        // Since department is removed from form, we use directorate as department
        $department = $directorate;
        
        if ($fullname && $username && $password && $directorate && $role_id) {
            try {
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetchColumn() == 0) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, department, directorate, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$fullname, $username, $hash, $department, $directorate, $role_id])) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User created successfully!'];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Username already exists.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error creating user: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }
    
    // User status actions (Activate/Deactivate/Delete)
    if (isset($_POST['admin_user_action'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['admin_user_action'];
        if ($user_id && $user_id != $user['user_id']) {
            try {
                if ($action === 'deactivate') {
                    // Update both possible status columns for compatibility
                    $stmt = $pdo->prepare("UPDATE users SET active = 0, is_active = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deactivated.'];
                } elseif ($action === 'activate') {
                    $stmt = $pdo->prepare("UPDATE users SET active = 1, is_active = 1 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User activated.'];
                } elseif ($action === 'delete') {
                    // Check for associated requests or vouchers first
                    $hasHistory = false;
                    
                    $checkReq = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ?");
                    $checkReq->execute([$user_id]);
                    if ($checkReq->fetchColumn() > 0) $hasHistory = true;
                    
                    if (!$hasHistory) {
                        $checkVou = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE prepared_by = ?");
                        $checkVou->execute([$user_id]);
                        if ($checkVou->fetchColumn() > 0) $hasHistory = true;
                    }

                    if ($hasHistory) {
                        // Has history, use soft delete
                        $stmt = $pdo->prepare("UPDATE users SET active = 0, is_active = 0, deleted_at = NOW() WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User has history, so they were soft-deleted (deactivated and hidden).'];
                    } else {
                        // No history, safe to hard delete
                        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted permanently.'];
                    }
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }

    // Messaging
    if (isset($_POST['admin_send_message'])) {
        $to_id = (int)$_POST['message_user_id'];
        $subject = trim($_POST['message_subject']);
        $content = trim($_POST['message_content']);
        if ($to_id && $subject && $content) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_messages (from_user_id, to_user_id, subject, message, is_private) VALUES (?, ?, ?, ?, 1)");
                if ($stmt->execute([$user['user_id'], $to_id, $subject, $content])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message sent successfully.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }
}

// Fetch stats & data
try {
    // Check if deleted_at exists in users table
    $hasDeletedAt = false;
    try {
        $pdo->query("SELECT deleted_at FROM users LIMIT 1");
        $hasDeletedAt = true;
    } catch (Exception $e) {}

    // Check if active exists
    $hasActive = false;
    try {
        $pdo->query("SELECT active FROM users LIMIT 1");
        $hasActive = true;
    } catch (Exception $e) {}

    $userWhere = $hasDeletedAt ? "WHERE u.deleted_at IS NULL" : "WHERE 1=1";
    $userCountQuery = $hasDeletedAt ? "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL" : "SELECT COUNT(*) FROM users";

    $stats = [
        'total_users' => (int)$pdo->query($userCountQuery)->fetchColumn(),
        'total_requests' => (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn(),
        'pending_requests' => (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE ed_status = 'pending' AND hod_status != 'rejected'")->fetchColumn(),
        'approved_requests' => (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE ed_status = 'approved'")->fetchColumn()
    ];

    $users_list = $pdo->query("SELECT u.*, COALESCE(r.role_name, 'Unknown') as role_name 
                               FROM users u LEFT JOIN roles r ON u.role_id = r.role_id 
                               $userWhere ORDER BY u.fullname")->fetchAll(PDO::FETCH_ASSOC);

    $recent_requests = $pdo->query("SELECT r.*, u.fullname as employee_name, rs.status_name
                                    FROM requests r LEFT JOIN users u ON r.user_id = u.user_id
                                    LEFT JOIN request_statuses rs ON r.status_id = rs.status_id
                                    ORDER BY r.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Admin Dashboard | Chamber System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .sidebar-link.active { background-color: #f3f4f6; color: #2563eb; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .btn-action { transition: all 0.2s; }
        .btn-action:hover { transform: translateY(-1px); }
        .modal { display: none; position: fixed; inset: 0; z-index: 50; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal.show { display: flex; }
    </style>
</head>
<body class="h-full overflow-hidden flex">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-72 flex flex-col min-w-0 overflow-hidden bg-gray-50">
        
        <!-- Top Header -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-8 py-4 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Admin Dashboard</h1>
                <p class="text-sm text-gray-500">System management and monitoring</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($user['fullname']) ?></p>
                    <p class="text-xs text-blue-600 font-semibold uppercase">System Administrator</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                    <?= substr($user['fullname'], 0, 1) ?>
                </div>
            </div>
        </header>

        <!-- Scrollable Body -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-8">
            
            <?php if (isset($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="p-4 rounded-lg border <?= $f['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> flex items-center justify-between">
                    <p class="font-medium"><?= htmlspecialchars($f['message']) ?></p>
                    <button onclick="this.parentElement.remove()" class="text-current opacity-50 hover:opacity-100">✕</button>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card p-6 border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Users</p>
                    <h3 class="text-3xl font-extrabold text-gray-900"><?= $stats['total_users'] ?></h3>
                </div>
                <div class="card p-6 border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Requests</p>
                    <h3 class="text-3xl font-extrabold text-gray-900"><?= $stats['total_requests'] ?></h3>
                </div>
                <div class="card p-6 border-l-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pending Action</p>
                    <h3 class="text-3xl font-extrabold text-gray-900"><?= $stats['pending_requests'] ?></h3>
                </div>
                <div class="card p-6 border-l-4 border-emerald-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Approved</p>
                    <h3 class="text-3xl font-extrabold text-gray-900"><?= $stats['approved_requests'] ?></h3>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-1 border-b border-gray-200">
                <button onclick="switchTab('users')" data-tab-btn="users" class="px-6 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600">Users</button>
                <button onclick="switchTab('requests')" data-tab-btn="requests" class="px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700">Requests</button>
                <button onclick="switchTab('settings')" data-tab-btn="settings" class="px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700">System Settings</button>
            </div>

            <!-- Users Tab -->
            <div id="tab-users" class="tab-content active space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-extrabold text-gray-900">User Management</h2>
                    <button onclick="toggleModal('modal-create-user')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                        + Create New User
                    </button>
                </div>
                
                <div class="card overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">User</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Role / Directorate</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($users_list as $u): ?>
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-500 text-xs">
                                            <?= substr($u['fullname'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900"><?= htmlspecialchars($u['fullname']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($u['username']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($u['role_name']) ?></p>
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($u['directorate']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($hasActive): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= (int)($u['active'] ?? $u['is_active'] ?? 1) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= (int)($u['active'] ?? $u['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openMessageModal(<?= $u['user_id'] ?>, '<?= addslashes($u['fullname']) ?>')" class="text-gray-400 hover:text-blue-600 transition">📧</button>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <?php if ($hasActive): ?>
                                            <?php if ((int)($u['active'] ?? $u['is_active'] ?? 1)): ?>
                                                <button type="submit" name="admin_user_action" value="deactivate" class="text-gray-400 hover:text-amber-600 transition">🚫</button>
                                            <?php else: ?>
                                                <button type="submit" name="admin_user_action" value="activate" class="text-gray-400 hover:text-green-600 transition">✅</button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button type="submit" name="admin_user_action" value="delete" onclick="return confirm('Delete this user?')" class="text-gray-400 hover:text-red-600 transition">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Requests Tab -->
            <div id="tab-requests" class="tab-content space-y-6">
                <h2 class="text-xl font-extrabold text-gray-900">Recent System Requests</h2>
                <div class="card overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Request ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recent_requests as $r): ?>
                            <tr>
                                <td class="px-6 py-4 font-mono text-sm">#<?= $r['request_id'] ?></td>
                                <td class="px-6 py-4 font-bold text-gray-900"><?= htmlspecialchars($r['employee_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($r['request_type']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $r['status_name'] === 'approved' ? 'bg-green-100 text-green-700' : ($r['status_name'] === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') ?>">
                                        <?= ucfirst($r['status_name']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="index.php?action=view_request&id=<?= $r['request_id'] ?>" class="text-blue-600 font-bold text-sm hover:underline">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="tab-settings" class="tab-content space-y-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-extrabold text-gray-900">System Configuration</h2>
                    <div class="flex gap-3">
                        <button onclick="toggleModal('modal-add-directorate')" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition shadow-sm">🏢 Add Directorate</button>
                        <button onclick="toggleModal('modal-add-role')" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition shadow-sm">🛡️ Add Role</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Directorates List -->
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-extrabold text-gray-800 uppercase tracking-wider text-xs">Directorates</h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                            <?php 
                            try {
                                $dirs = $pdo->query("SELECT * FROM directorates ORDER BY name ASC")->fetchAll();
                                if (empty($dirs)) echo "<p class='p-6 text-sm text-gray-400 italic'>No directorates found.</p>";
                                foreach ($dirs as $d): ?>
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($d['name']) ?></p>
                                        <p class="text-[10px] font-bold <?= ($d['is_active'] ?? 1) ? 'text-green-500' : 'text-red-500' ?> uppercase"><?= ($d['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></p>
                                    </div>
                                    <div class="flex gap-3">
                                        <button onclick="editDirectorate(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>')" class="text-gray-400 hover:text-blue-600">✏️</button>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_directorate_id" value="<?= $d['id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete directorate?')" class="text-gray-400 hover:text-red-600">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach;
                            } catch (Exception $e) {
                                echo "<p class='p-6 text-sm text-red-400 italic'>Error loading directorates. Table may be missing.</p>";
                            } ?>
                        </div>
                    </div>

                    <!-- Roles List -->
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-extrabold text-gray-800 uppercase tracking-wider text-xs">User Roles</h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                            <?php 
                            try {
                                $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name ASC")->fetchAll();
                                if (empty($roles)) echo "<p class='p-6 text-sm text-gray-400 italic'>No roles found.</p>";
                                foreach ($roles as $r): ?>
                                <div class="px-6 py-4 flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($r['role_name']) ?></p>
                                        <p class="text-[10px] font-bold <?= ($r['is_active'] ?? 1) ? 'text-green-500' : 'text-red-500' ?> uppercase"><?= ($r['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></p>
                                    </div>
                                    <div class="flex gap-3">
                                        <button onclick="editRole(<?= $r['role_id'] ?>, '<?= addslashes($r['role_name']) ?>')" class="text-gray-400 hover:text-blue-600">✏️</button>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_role_id" value="<?= $r['role_id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete role?')" class="text-gray-400 hover:text-red-600">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach;
                            } catch (Exception $e) {
                                echo "<p class='p-6 text-sm text-red-400 italic'>Error loading roles. Table may be missing.</p>";
                            } ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Modals -->
    <div id="modal-create-user" class="modal">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden p-8">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-extrabold text-gray-900">Create New System User</h3>
                <button onclick="toggleModal('modal-create-user')" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Full Name</label>
                        <input type="text" name="fullname" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Username / Email</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Directorate</label>
                        <select name="directorate" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach($dirs as $d) if($d['is_active']) echo "<option value='{$d['name']}'>{$d['name']}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">System Role</label>
                        <select name="role_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach($roles as $r) if($r['is_active']) echo "<option value='{$r['role_id']}'>{$r['role_name']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="toggleModal('modal-create-user')" class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700">Cancel</button>
                    <button type="submit" name="admin_create_user" class="bg-blue-600 text-white px-8 py-3 rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">Register User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-add-directorate" class="modal">
        <div class="bg-white w-full max-w-md rounded-2xl p-8">
            <h3 class="text-xl font-extrabold mb-6">Add New Directorate</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Directorate Name</label>
                    <input type="text" name="directorate_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleModal('modal-add-directorate')" class="text-gray-500 font-bold">Cancel</button>
                    <button type="submit" name="add_directorate" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-add-role" class="modal">
        <div class="bg-white w-full max-w-md rounded-2xl p-8">
            <h3 class="text-xl font-extrabold mb-6">Add New System Role</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Role Name</label>
                    <input type="text" name="role_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleModal('modal-add-role')" class="text-gray-500 font-bold">Cancel</button>
                    <button type="submit" name="add_role" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-message" class="modal">
        <div class="bg-white w-full max-w-md rounded-2xl p-8">
            <h3 class="text-xl font-extrabold mb-2">Send Message</h3>
            <p class="text-sm text-gray-500 mb-6">To: <span id="msg-user-name" class="font-bold text-blue-600"></span></p>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="message_user_id" id="msg-user-id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Subject</label>
                        <input type="text" name="message_subject" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Message</label>
                        <textarea name="message_content" rows="4" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="toggleModal('modal-message')" class="text-gray-500 font-bold">Cancel</button>
                    <button type="submit" name="admin_send_message" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-bold shadow-lg shadow-blue-200">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            // Hide all
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('[data-tab-btn]').forEach(el => {
                el.classList.remove('text-blue-600', 'border-blue-600');
                el.classList.add('text-gray-500', 'border-transparent');
            });

            // Show target
            document.getElementById('tab-' + tabId).classList.add('active');
            const btn = document.querySelector(`[data-tab-btn="${tabId}"]`);
            btn.classList.remove('text-gray-500', 'border-transparent');
            btn.classList.add('text-blue-600', 'border-blue-600');
            
            // Save state
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
        }

        function toggleModal(id) {
            document.getElementById(id).classList.toggle('show');
        }

        function openMessageModal(id, name) {
            document.getElementById('msg-user-id').value = id;
            document.getElementById('msg-user-name').innerText = name;
            toggleModal('modal-message');
        }

        function editDirectorate(id, currentName) {
            const name = prompt("Edit Directorate Name:", currentName);
            if (name && name.trim() !== "" && name !== currentName) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<?= csrf_field() ?><input type="hidden" name="edit_directorate_id" value="${id}"><input type="hidden" name="directorate_name" value="${name}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }

        function editRole(id, currentName) {
            const name = prompt("Edit Role Name:", currentName);
            if (name && name.trim() !== "" && name !== currentName) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<?= csrf_field() ?><input type="hidden" name="edit_role_id" value="${id}"><input type="hidden" name="role_name" value="${name}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }

        // Initialize based on URL
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab') || 'users';
            switchTab(tab);
        });
    </script>
    <script src="assets/mobile-menu.js"></script>
</body>
</html>
