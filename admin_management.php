<?php
// FILE: admin_management.php
// Modern & High-Standard Admin Dashboard for Chamber Request System

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
    
    // User status actions (Activate/Deactivate/Delete)
    if (isset($_POST['admin_user_action'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['admin_user_action'];
        
        if ($user_id && $user_id != $user['user_id']) {
            try {
                if ($action === 'deactivate') {
                    $stmt = $pdo->prepare("UPDATE users SET active = 0, is_active = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User account has been successfully deactivated.'];
                } elseif ($action === 'activate') {
                    $stmt = $pdo->prepare("UPDATE users SET active = 1, is_active = 1 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User account has been successfully reactivated.'];
                } elseif ($action === 'delete') {
                    // Check for history
                    $hasHistory = false;
                    $tablesToCheck = ['requests' => 'user_id', 'vouchers' => 'prepared_by', 'user_messages' => 'from_user_id'];
                    foreach ($tablesToCheck as $tbl => $col) {
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM $tbl WHERE $col = ?");
                        $chk->execute([$user_id]);
                        if ((int)$chk->fetchColumn() > 0) {
                            $hasHistory = true;
                            break;
                        }
                    }

                    if ($hasHistory) {
                        $stmt = $pdo->prepare("UPDATE users SET active = 0, is_active = 0, deleted_at = NOW() WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User has history and was soft-deleted to preserve records.'];
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User has been permanently deleted from the system.'];
                    }
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action failed: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }

    // Directorate management
    if (isset($_POST['add_directorate'])) {
        $name = trim($_POST['directorate_name'] ?? '');
        if ($name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO directorates (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'New directorate added successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to add directorate: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Directorate management - Update
    if (isset($_POST['edit_directorate_id'])) {
        $id = (int)$_POST['edit_directorate_id'];
        $name = trim($_POST['directorate_name'] ?? '');
        if ($id > 0 && $name !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE directorates SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate updated successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Update failed: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Directorate management - Activate
    if (isset($_POST['activate_directorate_id'])) {
        $id = (int)$_POST['activate_directorate_id'];
        try {
            $stmt = $pdo->prepare("UPDATE directorates SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate activated.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to activate.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Directorate management - Deactivate
    if (isset($_POST['deactivate_directorate_id'])) {
        $id = (int)$_POST['deactivate_directorate_id'];
        try {
            $stmt = $pdo->prepare("UPDATE directorates SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate deactivated.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to deactivate.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Directorate management - Delete
    if (isset($_POST['delete_directorate_id'])) {
        $id = (int)$_POST['delete_directorate_id'];
        try {
            // Check for users in this directorate
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE directorate = (SELECT name FROM directorates WHERE id = ?) AND deleted_at IS NULL");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete directorate with assigned users.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM directorates WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate removed.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Deletion failed.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Role management - Add
    if (isset($_POST['add_role'])) {
        $name = trim($_POST['role_name'] ?? '');
        if ($name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'New system role added successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to add role: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Role management - Update
    if (isset($_POST['edit_role_id'])) {
        $id = (int)$_POST['edit_role_id'];
        $name = trim($_POST['role_name'] ?? '');
        if ($id > 0 && $name !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'System role updated.'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Update failed.'];
            }
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Role management - Activate
    if (isset($_POST['activate_role_id'])) {
        $id = (int)$_POST['activate_role_id'];
        try {
            $stmt = $pdo->prepare("UPDATE roles SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'System role activated.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to activate.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Role management - Deactivate
    if (isset($_POST['deactivate_role_id'])) {
        $id = (int)$_POST['deactivate_role_id'];
        try {
            $stmt = $pdo->prepare("UPDATE roles SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE role_id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'System role deactivated.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to deactivate.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Role management - Delete
    if (isset($_POST['delete_role_id'])) {
        $id = (int)$_POST['delete_role_id'];
        try {
            // Check for users in this role
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete role with assigned users.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role deleted.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Deletion failed.'];
        }
        header('Location: index.php?action=admin_management&tab=settings');
        exit;
    }

    // Messaging
    if (isset($_POST['admin_send_message'])) {
        $to_id = (int)$_POST['message_user_id'];
        $subject = trim($_POST['message_subject'] ?? '');
        $content = trim($_POST['message_content'] ?? '');
        if ($to_id && $subject && $content) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_messages (from_user_id, to_user_id, subject, message, is_private, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                if ($stmt->execute([$user['user_id'], $to_id, $subject, $content])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message transmitted successfully.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Transmission failed.'];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }

    // Handle user creation
    if (isset($_POST['admin_create_user'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $directorate = trim($_POST['directorate'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        if ($fullname && $username && $password && $directorate && $role_id) {
            try {
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $check->execute([$username]);
                if ((int)$check->fetchColumn() === 0) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, department, directorate, role_id, active, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())");
                    if ($stmt->execute([$fullname, $username, $hash, $directorate, $directorate, $role_id])) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User account created successfully.'];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error: Username already exists.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'System error: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?action=admin_management');
        exit;
    }
}

// Fetch Data for Display
try {
    $hasDeletedAt = false;
    try { $pdo->query("SELECT deleted_at FROM users LIMIT 1"); $hasDeletedAt = true; } catch (Exception $e) {}
    
    $userWhere = $hasDeletedAt ? "WHERE u.deleted_at IS NULL" : "WHERE 1=1";
    
    $stats = [
        'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn(),
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
                                    
    $directorates = $pdo->query("SELECT * FROM directorates ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $roles_list = $pdo->query("SELECT * FROM roles ORDER BY role_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
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
        .tab-content.active { display: block; animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); transition: all 0.2s; }
        .card:hover { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.07); }
        .btn-primary { background: #2563eb; color: white; padding: 0.625rem 1.25rem; border-radius: 12px; font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .modal { display: none; position: fixed; inset: 0; z-index: 100; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 1.5rem; }
        .modal.show { display: flex; }
        .form-input { width: 100%; padding: 0.75rem 1rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; outline: none; transition: all 0.2s; }
        .form-input:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .badge { padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.025em; }
        .badge-success { background: #f0fdf4; color: #166534; }
        .badge-danger { background: #fef2f2; color: #991b1b; }
        .badge-warning { background: #fffbeb; color: #92400e; }
    </style>
</head>
<body class="h-full flex overflow-hidden">

    <!-- Sidebar Integration -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 lg:ml-72 bg-slate-50 overflow-hidden">
        
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 px-6 sm:px-10 py-5 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Control</h1>
                <p class="text-sm font-medium text-slate-500">Global Administration & Oversight</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($user['fullname']) ?></p>
                    <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest">Master Admin</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-200">
                    <?= substr($user['fullname'], 0, 1) ?>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <div class="flex-1 overflow-y-auto p-6 sm:p-10 space-y-10">
            
            <?php if (isset($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="p-4 rounded-2xl border-2 <?= $f['type'] === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-rose-50 border-rose-100 text-rose-800' ?> flex items-center justify-between shadow-sm animate-bounce-short">
                    <div class="flex items-center gap-3 font-bold">
                        <span><?= $f['type'] === 'success' ? '✅' : '❌' ?></span>
                        <?= htmlspecialchars($f['message']) ?>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-current opacity-40 hover:opacity-100 transition-opacity">✕</button>
                </div>
            <?php endif; ?>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $cards = [
                    ['Users', $stats['total_users'], '👥', 'blue'],
                    ['Requests', $stats['total_requests'], '📋', 'indigo'],
                    ['Pending', $stats['pending_requests'], '⏳', 'amber'],
                    ['Success', $stats['approved_requests'], '✨', 'emerald']
                ];
                foreach ($cards as [$label, $val, $icon, $color]): ?>
                <div class="card p-6 border-t-4 border-<?= $color ?>-500">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-2xl"><?= $icon ?></span>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest"><?= $label ?></span>
                    </div>
                    <h3 class="text-3xl font-black text-slate-900"><?= $val ?></h3>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-200/50 rounded-2xl w-fit">
                <button onclick="switchTab('users')" data-tab-btn="users" class="px-6 py-2.5 rounded-xl text-sm font-black transition-all">Users</button>
                <button onclick="switchTab('requests')" data-tab-btn="requests" class="px-6 py-2.5 rounded-xl text-sm font-black transition-all">Requests</button>
                <button onclick="switchTab('settings')" data-tab-btn="settings" class="px-6 py-2.5 rounded-xl text-sm font-black transition-all">Settings</button>
            </div>

            <!-- Tab: Users -->
            <div id="tab-users" class="tab-content space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-black text-slate-900">User Management</h2>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-black rounded-lg uppercase tracking-wider">Total: <?= count($users_list) ?></span>
                    </div>
                    <button onclick="toggleModal('modal-create-user')" class="btn-primary">
                        <span>➕</span> Create User
                    </button>
                </div>
                
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Identification</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Organization</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Operations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($users_list as $u): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-sm group-hover:bg-white group-hover:shadow-md transition-all">
                                                <?= substr($u['fullname'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900"><?= htmlspecialchars($u['fullname']) ?></p>
                                                <p class="text-xs font-medium text-slate-400"><?= htmlspecialchars($u['username']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($u['role_name']) ?></p>
                                        <p class="text-[10px] font-black text-blue-500 uppercase"><?= htmlspecialchars($u['directorate']) ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <?php $isActive = (int)($u['active'] ?? $u['is_active'] ?? 1); ?>
                                        <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $isActive ? 'Active' : 'Deactivated' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="openMessageModal(<?= $u['user_id'] ?>, '<?= addslashes($u['fullname']) ?>')" class="p-2 hover:bg-blue-50 text-blue-600 rounded-lg" title="Message">📧</button>
                                            <?php if ($u['user_id'] != $user['user_id']): ?>
                                                <form method="POST" class="inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <?php if ($isActive): ?>
                                                        <button type="submit" name="admin_user_action" value="deactivate" class="p-2 hover:bg-amber-50 text-amber-600 rounded-lg" title="Deactivate">🚫</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="admin_user_action" value="activate" class="p-2 hover:bg-emerald-50 text-emerald-600 rounded-lg" title="Activate">✅</button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="admin_user_action" value="delete" onclick="return confirm('WARNING: Are you sure you want to delete this user? This cannot be undone.')" class="p-2 hover:bg-rose-50 text-rose-600 rounded-lg" title="Delete">🗑️</button>
                                                </form>
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

            <!-- Tab: Requests -->
            <div id="tab-requests" class="tab-content space-y-6">
                <h2 class="text-xl font-black text-slate-900">System Activity Logs</h2>
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reference</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Originator</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Classification</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Current Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Review</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($recent_requests as $r): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-5 font-mono text-sm font-bold text-slate-500">REQ-<?= str_pad($r['request_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td class="px-6 py-5 font-extrabold text-slate-900"><?= htmlspecialchars($r['employee_name']) ?></td>
                                    <td class="px-6 py-5 text-sm font-medium text-slate-600"><?= htmlspecialchars($r['request_type']) ?></td>
                                    <td class="px-6 py-5">
                                        <span class="badge <?= $r['status_name'] === 'approved' ? 'badge-success' : ($r['status_name'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                                            <?= ucfirst($r['status_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <a href="index.php?action=view_request&id=<?= $r['request_id'] ?>" class="text-blue-600 font-black text-xs hover:text-blue-800 transition-colors uppercase tracking-wider">Inspect</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Settings -->
            <div id="tab-settings" class="tab-content space-y-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-slate-900">System Parameters</h2>
                    <div class="flex gap-3">
                        <button onclick="toggleModal('modal-add-directorate')" class="btn-primary bg-slate-800 hover:bg-slate-900">🏢 Add Directorate</button>
                        <button onclick="toggleModal('modal-add-role')" class="btn-primary bg-slate-800 hover:bg-slate-900">🛡️ Add Role</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <!-- Directorates -->
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Managed Directorates</h3>
                            <span class="px-2 py-0.5 bg-slate-200 text-slate-600 text-[10px] font-black rounded-md uppercase tracking-tighter">Count: <?= count($directorates) ?></span>
                        </div>
                        <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                            <?php foreach ($directorates as $d): ?>
                            <div class="px-6 py-5 flex items-center justify-between group">
                                <div>
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($d['name']) ?></p>
                                    <p class="text-[10px] font-black <?= ($d['is_active'] ?? 1) ? 'text-emerald-500' : 'text-rose-500' ?> uppercase tracking-tighter">
                                        <?= ($d['is_active'] ?? 1) ? '• Operational' : '• Suspended' ?>
                                    </p>
                                </div>
                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="editDirectorate(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>')" class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">✏️</button>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <?php if ($d['is_active'] ?? 1): ?>
                                            <input type="hidden" name="deactivate_directorate_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="p-2 hover:bg-amber-50 text-amber-500 rounded-lg" title="Deactivate">🚫</button>
                                        <?php else: ?>
                                            <input type="hidden" name="activate_directorate_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="p-2 hover:bg-emerald-50 text-emerald-500 rounded-lg" title="Activate">✅</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_directorate_id" value="<?= $d['id'] ?>">
                                        <button type="submit" onclick="return confirm('Delete directorate?')" class="p-2 hover:bg-rose-50 text-rose-500 rounded-lg" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Access Roles</h3>
                            <span class="px-2 py-0.5 bg-slate-200 text-slate-600 text-[10px] font-black rounded-md uppercase tracking-tighter">Count: <?= count($roles_list) ?></span>
                        </div>
                        <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                            <?php foreach ($roles_list as $r): ?>
                            <div class="px-6 py-5 flex items-center justify-between group">
                                <div>
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($r['role_name']) ?></p>
                                    <p class="text-[10px] font-black <?= ($r['is_active'] ?? 1) ? 'text-emerald-500' : 'text-rose-500' ?> uppercase tracking-tighter">
                                        <?= ($r['is_active'] ?? 1) ? '• Active' : '• Inactive' ?>
                                    </p>
                                </div>
                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="editRole(<?= $r['role_id'] ?>, '<?= addslashes($r['role_name']) ?>')" class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">✏️</button>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <?php if ($r['is_active'] ?? 1): ?>
                                            <input type="hidden" name="deactivate_role_id" value="<?= $r['role_id'] ?>">
                                            <button type="submit" class="p-2 hover:bg-amber-50 text-amber-600 rounded-lg" title="Deactivate">🚫</button>
                                        <?php else: ?>
                                            <input type="hidden" name="activate_role_id" value="<?= $r['role_id'] ?>">
                                            <button type="submit" class="p-2 hover:bg-emerald-50 text-emerald-600 rounded-lg" title="Activate">✅</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_role_id" value="<?= $r['role_id'] ?>">
                                        <button type="submit" onclick="return confirm('Delete role?')" class="p-2 hover:bg-rose-50 text-rose-500 rounded-lg" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="modal-create-user" class="modal">
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-10">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Onboard New User</h3>
                <button onclick="toggleModal('modal-create-user')" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all">✕</button>
            </div>
            <form method="POST" class="grid grid-cols-2 gap-6">
                <?= csrf_field() ?>
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Legal Full Name</label>
                    <input type="text" name="fullname" required class="form-input">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">System Username</label>
                    <input type="text" name="username" required class="form-input">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Access Password</label>
                    <input type="password" name="password" required class="form-input">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Directorate</label>
                    <select name="directorate" required class="form-input">
                        <?php foreach($directorates as $d) if($d['is_active'] ?? 1) echo "<option value='{$d['name']}'>{$d['name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Access Role</label>
                    <select name="role_id" required class="form-input">
                        <?php foreach($roles_list as $r) if($r['is_active'] ?? 1) echo "<option value='{$r['role_id']}'>{$r['role_name']}</option>"; ?>
                    </select>
                </div>
                <div class="col-span-2 pt-4 flex justify-end gap-4">
                    <button type="button" onclick="toggleModal('modal-create-user')" class="px-6 py-3 text-sm font-bold text-slate-500">Cancel</button>
                    <button type="submit" name="admin_create_user" class="btn-primary px-10">Authorize User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-add-directorate" class="modal">
        <div class="bg-white w-full max-w-md rounded-3xl p-10">
            <h3 class="text-xl font-black mb-6">New Directorate</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Directorate Name</label>
                    <input type="text" name="directorate_name" required class="form-input">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleModal('modal-add-directorate')" class="font-bold text-slate-400">Cancel</button>
                    <button type="submit" name="add_directorate" class="btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-add-role" class="modal">
        <div class="bg-white w-full max-w-md rounded-3xl p-10">
            <h3 class="text-xl font-black mb-6">New System Role</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Role Name</label>
                    <input type="text" name="role_name" required class="form-input">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleModal('modal-add-role')" class="font-bold text-slate-400">Cancel</button>
                    <button type="submit" name="add_role" class="btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-message" class="modal">
        <div class="bg-white w-full max-w-md rounded-3xl p-10">
            <h3 class="text-xl font-black mb-2">Secure Message</h3>
            <p class="text-sm text-slate-400 mb-8">Recipient: <span id="msg-user-name" class="text-blue-600 font-black"></span></p>
            <form method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="message_user_id" id="msg-user-id">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Subject</label>
                    <input type="text" name="message_subject" required class="form-input">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Message Body</label>
                    <textarea name="message_content" rows="4" required class="form-input"></textarea>
                </div>
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="toggleModal('modal-message')" class="font-bold text-slate-400">Dismiss</button>
                    <button type="submit" name="admin_send_message" class="btn-primary">Transmit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('[data-tab-btn]').forEach(el => {
                el.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
                el.classList.add('text-slate-500');
            });

            const target = document.getElementById('tab-' + tabId);
            if (target) {
                target.classList.add('active');
                const btn = document.querySelector(`[data-tab-btn="${tabId}"]`);
                if (btn) {
                    btn.classList.remove('text-slate-500');
                    btn.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
                }
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            }
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.toggle('show');
        }

        function openMessageModal(id, name) {
            const idInput = document.getElementById('msg-user-id');
            const nameSpan = document.getElementById('msg-user-name');
            if (idInput && nameSpan) {
                idInput.value = id;
                nameSpan.innerText = name;
                toggleModal('modal-message');
            }
        }

        function editDirectorate(id, currentName) {
            const name = prompt("Enter new name for Directorate:", currentName);
            if (name && name.trim() !== "" && name !== currentName) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<?= csrf_field() ?><input type="hidden" name="edit_directorate_id" value="${id}"><input type="hidden" name="directorate_name" value="${name}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }

        function editRole(id, currentName) {
            const name = prompt("Enter new name for Role:", currentName);
            if (name && name.trim() !== "" && name !== currentName) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<?= csrf_field() ?><input type="hidden" name="edit_role_id" value="${id}"><input type="hidden" name="role_name" value="${name}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab') || 'users';
            switchTab(tab);
        });
    </script>
    <script src="assets/mobile-menu.js"></script>
</body>
</html>
