<?php
// FILE: messages.php
// User messages page for viewing and managing personal messages

if (!defined('ACCESS_ALLOWED')) {
    define('ACCESS_ALLOWED', true);
}

require_once __DIR__ . '/function.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$user = $_SESSION['user'];

// Handle delete message (allow deletion of both sent and received messages)
if (isset($_POST['delete_message'])) {
    require_csrf_post();
    $message_id = (int)$_POST['message_id'];
    try {
        // Allow user to delete messages they sent or received
        $stmt = $pdo->prepare("DELETE FROM user_messages WHERE id = ? AND (to_user_id = ? OR from_user_id = ?)");
        if ($stmt->execute([$message_id, $user['user_id'], $user['user_id']])) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message deleted successfully.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete message.'];
        }
    } catch (Exception $e) {
        error_log("Error deleting message: " . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete message.'];
    }
    header('Location: index.php?action=messages');
    exit;
}

// Handle mark as read
if (isset($_POST['mark_read'])) {
    require_csrf_post();
    $message_id = (int)$_POST['message_id'];
    $is_notification = isset($_POST['is_notification']) && $_POST['is_notification'] == 1;
    try {
        if ($is_notification) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$message_id, $user['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE user_messages SET is_read = 1 WHERE id = ? AND to_user_id = ?");
            $stmt->execute([$message_id, $user['user_id']]);
        }
    } catch (Exception $e) {
        error_log("Error marking message/notification as read: " . $e->getMessage());
    }
    header('Location: index.php?action=messages');
    exit;
}

// Handle delete message
if (isset($_POST['delete_message'])) {
    require_csrf_post();
    $message_id = (int)$_POST['message_id'];
    $is_notification = isset($_POST['is_notification']) && $_POST['is_notification'] == 1;
    try {
        if ($is_notification) {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$message_id, $user['user_id']]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM user_messages WHERE id = ? AND (to_user_id = ? OR from_user_id = ?)");
            $stmt->execute([$message_id, $user['user_id'], $user['user_id']]);
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item deleted successfully.'];
    } catch (Exception $e) {
        error_log("Error deleting message/notification: " . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete item.'];
    }
    header('Location: index.php?action=messages');
    exit;
}

// Get filter parameters
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_sender = $_GET['sender'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Get user's messages and notifications
$all_items = [];
try {
    // 1. Fetch private messages
    $msg_sql = "
        SELECT m.id, m.from_user_id, m.to_user_id, m.subject, m.message, m.is_read, m.created_at, 
               'message' as item_type,
               CASE WHEN m.from_user_id = ? THEN 'sent' ELSE 'received' END as message_type,
               sender.fullname as sender_name,
               receiver.fullname as receiver_name,
               NULL as request_id
        FROM user_messages m 
        LEFT JOIN users sender ON m.from_user_id = sender.user_id 
        LEFT JOIN users receiver ON m.to_user_id = receiver.user_id 
        WHERE (m.to_user_id = ? OR m.from_user_id = ?)
    ";
    
    $msg_params = [$user['user_id'], $user['user_id'], $user['user_id']];
    
    // 2. Fetch notifications
    $notif_sql = "
        SELECT n.id, 0 as from_user_id, n.user_id as to_user_id, n.title as subject, n.message, n.is_read, n.created_at,
               'notification' as item_type,
               'received' as message_type,
               'System' as sender_name,
               u.fullname as receiver_name,
               n.request_id
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE n.user_id = ?
    ";
    $notif_params = [$user['user_id']];

    // Combine them with filters
    $combined_sql = "($msg_sql) UNION ALL ($notif_sql)";
    
    $where_clauses = [];
    $final_params = array_merge($msg_params, $notif_params);

    if ($filter_year) {
        $where_clauses[] = "YEAR(created_at) = ?";
        $final_params[] = $filter_year;
    }
    if ($filter_month) {
        $where_clauses[] = "MONTH(created_at) = ?";
        $final_params[] = $filter_month;
    }
    if ($filter_date) {
        $where_clauses[] = "DATE(created_at) = ?";
        $final_params[] = $filter_date;
    }
    if ($filter_sender) {
        $where_clauses[] = "sender_name LIKE ?";
        $final_params[] = "%$filter_sender%";
    }

    $query = "SELECT * FROM ($combined_sql) as combined";
    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($final_params);
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique senders for filter dropdown (role and directorate aware)
    $roleId = (int)$user['role_id'];
    $userDir = $user['directorate'] ?? '';
    $userDept = $user['department'] ?? '';

    if ($roleId == 1) { // Employee: Only their HOD + Global Approvers
        $senders_stmt = $pdo->prepare("
            SELECT DISTINCT fullname FROM users 
            WHERE (role_id IN (2,4,5,6)) 
            OR (role_id = 3 AND (directorate = ? OR department = ?))
            ORDER BY fullname
        ");
        $senders_stmt->execute([$userDir, $userDept]);
    } elseif ($roleId == 3) { // HOD: Their staff + Global Approvers
        $senders_stmt = $pdo->prepare("
            SELECT DISTINCT fullname FROM users 
            WHERE (role_id IN (2,4,5,6)) 
            OR (directorate = ? OR department = ?)
            ORDER BY fullname
        ");
        $senders_stmt->execute([$userDir, $userDept]);
    } elseif (in_array($roleId, [2, 4, 5, 6])) { // HRM, CEO, Finance, Auditor: See all workflow participants
        $senders_stmt = $pdo->prepare("
            SELECT DISTINCT fullname FROM users 
            WHERE role_id IN (1,2,3,4,5,6)
            ORDER BY fullname
        ");
        $senders_stmt->execute();
    } else { // Admin or others
        $senders_stmt = $pdo->prepare("
            SELECT DISTINCT fullname FROM users 
            WHERE role_id IN (1,2,3,4,5,6) 
            ORDER BY fullname
        ");
        $senders_stmt->execute();
    }
    $flow_senders = $senders_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    error_log("Error fetching items: " . $e->getMessage());
}

// Count unread
$unread_count = 0;
foreach ($all_items as $item) {
    if (!$item['is_read'] && $item['message_type'] === 'received') {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
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
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header-pulse { animation: headerPulse 6s ease-in-out infinite; }
        @keyframes headerPulse { 0%,100% { box-shadow: 0 0 0 rgba(11,94,215,0); } 50% { box-shadow: 0 0 24px rgba(11,94,215,.15), 0 0 40px rgba(212,175,55,.1); } }
        /* Removed non-standard .btn-hero styles */
        .title-gradient { color:#1f2937; }
        .divider { height:1px; background:linear-gradient(90deg, transparent, rgba(0,0,0,.12), transparent); }
        .message-card { background: #ffffff; border:1px solid #e2e8f0; transition: all .3s ease; }
        .message-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(11,94,215,.1); }
        .message-unread { border-left: 4px solid #0b5ed7; background: #f0f9ff; }
        .marquee { white-space: nowrap; overflow: hidden; }
        .marquee > span { display:inline-block; padding-left:100%; animation: marquee 45s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%);} 100% { transform: translateX(-100%);} }
    </style>
</head>
<body class="bg-app flex min-h-screen relative">
    <!-- <div class="orb orb-blue" style="top:-60px; left:-40px; width:220px; height:220px;"></div>
    <div class="orb orb-gold" style="top:20%; right:-80px; width:260px; height:260px;"></div>
    <div class="orb orb-green" style="bottom:-80px; left:20%; width:240px; height:240px;"></div> -->
    
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col md:ml-72">
        <!-- Top Nav -->
        <header class="glass header-pulse shadow p-4 flex items-center justify-between fixed top-0 left-0 md:left-72 right-0 z-40 bg-white/80">
            <h1 class="text-2xl font-bold title-gradient">Messages</h1>
            <div class="flex items-center space-x-4">
                <p>
                    Welcome, <span class="font-semibold text-gray-800"><?= htmlspecialchars($user['fullname']) ?></span>
                </p>
                <?php if ($unread_count > 0): ?>
                <span class="px-3 py-1 rounded-full text-sm bg-red-100 text-red-800 border border-red-200 font-bold">
                    <?= $unread_count ?> Unread
                </span>
                <?php endif; ?>
                <a href="index.php?action=dashboard" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </header>
        <div class="glass px-4 py-3 fixed top-24 left-0 md:left-72 right-0 z-30 bg-white/90">
            <div class="marquee text-gray-800 text-base">
                <span>Messages — Your personal communication center 📧 Stay connected with your team 💙💛💚 • </span>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 pt-44 md:pt-40">
            <div class="glass p-6 rounded-xl">
                <?php if (isset($_SESSION['flash'])): 
                    $flash = $_SESSION['flash'];
                    unset($_SESSION['flash']);
                ?>
                <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-black title-gradient">Inbox & Notifications 📧</h2>
                    <div class="flex items-center gap-3">
                        <span class="chip">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Total: <?= count($all_items) ?>
                        </span>
                        <?php if ($unread_count > 0): ?>
                        <span class="chip bg-rose-50 border-rose-100 text-rose-700">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Unread: <?= $unread_count ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 mb-8">
                    <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <input type="hidden" name="action" value="messages">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sender</label>
                            <select name="sender" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                <option value="">All Senders</option>
                                <option value="System" <?= $filter_sender === 'System' ? 'selected' : '' ?>>System Notifications</option>
                                <?php if (isset($flow_senders) && is_array($flow_senders)): ?>
                                    <?php foreach ($flow_senders as $s): ?>                                      <option value="<?= htmlspecialchars($s) ?>" <?= $filter_sender === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Year</label>
                            <select name="year" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                <option value="">Any Year</option>
                                <?php 
                                $years = range(date('Y'), 2023);
                                foreach ($years as $y): ?>
                                    <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Month</label>
                            <select name="month" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                <option value="">Any Month</option>
                                <?php 
                                for ($m = 1; $m <= 12; $m++): 
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                ?>
                                    <option value="<?= $m ?>" <?= $filter_month == $m ? 'selected' : '' ?>><?= $monthName ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Specific Date</label>
                            <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl transition-all shadow-lg shadow-blue-200">
                                Filter
                            </button>
                            <a href="index.php?action=messages" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2.5 px-4 rounded-xl transition-all" title="Clear Filters">
                                ✕
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($all_items)): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($all_items as $item): ?>
                    <div class="message-card <?= !$item['is_read'] && $item['message_type'] === 'received' ? 'message-unread border-l-4 border-l-blue-500' : '' ?> p-6 rounded-2xl border border-slate-100 bg-white hover:shadow-xl hover:shadow-slate-200/50 transition-all group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-widest <?= $item['item_type'] === 'notification' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' ?>">
                                        <?= $item['item_type'] ?>
                                    </span>
                                    <h3 class="font-bold text-lg text-slate-800 truncate group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($item['subject']) ?></h3>
                                    <?php if (!$item['is_read'] && $item['message_type'] === 'received'): ?>
                                    <span class="animate-pulse flex h-2 w-2 rounded-full bg-blue-600"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-4 text-xs font-medium text-slate-400 mb-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-300">From:</span>
                                        <span class="text-slate-600"><?= htmlspecialchars($item['sender_name'] ?? 'System') ?></span>
                                    </div>
                                    <div class="w-1 h-1 rounded-full bg-slate-200"></div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-300">To:</span>
                                        <span class="text-slate-600"><?= htmlspecialchars($item['receiver_name'] ?? 'Unknown') ?></span>
                                    </div>
                                    <div class="w-1 h-1 rounded-full bg-slate-200"></div>
                                    <div class="text-slate-400">
                                        <?= date('M j, Y • g:i A', strtotime($item['created_at'])) ?>
                                    </div>
                                </div>

                                <div class="text-slate-600 leading-relaxed text-sm bg-slate-50/50 p-4 rounded-xl border border-slate-100 group-hover:bg-white transition-colors">
                                    <?= nl2br(htmlspecialchars($item['message'])) ?>
                                </div>

                                <?php if (!empty($item['request_id'])): ?>
                                <div class="mt-4">
                                    <a href="index.php?action=view_request&id=<?= $item['request_id'] ?>" class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                                        👁️ View Related Request
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-col gap-2">
                                <?php if (!$item['is_read'] && $item['message_type'] === 'received'): ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="message_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="is_notification" value="<?= $item['item_type'] === 'notification' ? 1 : 0 ?>">
                                    <button type="submit" name="mark_read" class="w-10 h-10 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all border border-blue-100" title="Mark as Read">
                                        ✓
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="message_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="is_notification" value="<?= $item['item_type'] === 'notification' ? 1 : 0 ?>">
                                    <button type="submit" name="delete_message" 
                                            onclick="return confirm('Permanently delete this item?')"
                                            class="w-10 h-10 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all border border-rose-100" title="Delete">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-slate-400 py-24 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="text-7xl mb-6">📧</div>
                    <p class="text-2xl font-black text-slate-800">No items found</p>
                    <p class="mt-2 text-slate-500">Try adjusting your filters or check back later.</p>
                    <a href="index.php?action=messages" class="inline-block mt-6 text-blue-600 font-bold hover:underline">Clear all filters</a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script src="assets/mobile-menu.js"></script>
</body>
</html>
