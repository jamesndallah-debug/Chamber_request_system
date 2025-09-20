<?php
// FILE: views/dashboard.php
// User dashboard view. Displays requests and vouchers relevant to the user's role.

// Get requests for the current user's role.
$requests = $requestModel->get_requests_for_role($user['role_id'], $user);

// Get user's own requests for "My Requests" tab (for roles that need it)
$my_requests = [];
if (in_array($user['role_id'], [2, 3, 5, 6, 7])) { // HRM, HOD, Finance, Internal Auditor, Admin
    $my_requests = $requestModel->get_my_requests($user['user_id']);
}

// Include voucher model and functions
require_once __DIR__ . '/Voucher.php';
require_once __DIR__ . '/voucher_functions.php';

// Create voucher model instance
if (!isset($voucherModel)) {
    $voucherModel = new VoucherModel($pdo);
}

// Get vouchers only for Finance and ED roles (roles 5 and 4)
$vouchers = [];
if (in_array($user['role_id'], [4, 5])) {
    $all_vouchers = $voucherModel->get_vouchers_for_role($user['role_id'], $user['user_id']);
    
    // Filter to show only pending vouchers in dashboard
    $vouchers = array_filter($all_vouchers, function($voucher) use ($user) {
        if ($user['role_id'] == 4) { // ED
            return $voucher['ed_status'] === 'pending' && $voucher['finance_status'] === 'approved';
        } elseif ($user['role_id'] == 5) { // Finance
            return $voucher['finance_status'] === 'pending';
        }
        return false;
    });
}

// Fetch recent unread notifications for the logged-in user
$notifications = [];
$unreadCount = 0;
try {
    $stmtN = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
    $stmtN->execute([(int)$user['user_id']]);
    $notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC);
    $unreadCount = count($notifications);
} catch (Throwable $e) { /* ignore */ }

$role_name = $userModel->get_role_name($user['role_id']);
$dashboard_title = ucfirst($role_name) . ' Dashboard';
// Compute summary counts for cards
$total_requests = count($requests);
$count_pending = 0;
$count_approved = 0;
$count_rejected = 0;
foreach ($requests as $r) {
    $statusValue = $r['status_name'] ?? '';
    if (isset($r['hod_status']) || isset($r['hrm_status']) || isset($r['auditor_status']) || isset($r['finance_status']) || isset($r['ed_status'])) {
        switch ($user['role_id']) {
            case 3: $statusValue = $r['hod_status'] ?? $statusValue; break;
            case 2: $statusValue = $r['hrm_status'] ?? $statusValue; break;
            case 6: $statusValue = $r['auditor_status'] ?? $statusValue; break;
            case 5: $statusValue = $r['finance_status'] ?? $statusValue; break;
            case 4: $statusValue = $r['ed_status'] ?? $statusValue; break;
        }
    }
    $sv = strtolower((string)$statusValue);
    if ($sv === 'approved') $count_approved++;
    else if ($sv === 'rejected') $count_rejected++;
    else $count_pending++;
}
$pct_div = max(1, $total_requests);
$pending_pct = (int)round(($count_pending / $pct_div) * 100);
$approved_pct = (int)round(($count_approved / $pct_div) * 100);
$rejected_pct = (int)round(($count_rejected / $pct_div) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($dashboard_title) ?> | Chamber Request System</title>
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
        .card-req { background:linear-gradient(180deg, rgba(11,94,215,.06), rgba(22,163,74,.06)); border:1px solid rgba(255,255,255,.08); transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-req:hover { transform: translateY(-3px); box-shadow:0 14px 30px rgba(11,94,215,.18); border-color: rgba(212,175,55,.35); }
        .title-gradient { background: linear-gradient(90deg,#0b5ed7,#d4af37,#16a34a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .divider { height:1px; background:linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent); }
        .meter { position:relative; height:10px; border-radius:999px; overflow:hidden; background:rgba(255,255,255,.12); }
        .meter-bar { height:100%; border-radius:999px; background:linear-gradient(90deg,#0b5ed7,#d4af37); box-shadow:0 6px 16px rgba(11,94,215,.25) inset; width:0; animation: fill 1.2s ease forwards; }
        .meter-bar.green { background:linear-gradient(90deg,#16a34a,#d4af37); box-shadow:0 6px 16px rgba(22,163,74,.25) inset; }
        .meter-bar.red { background:linear-gradient(90deg,#ef4444,#d4af37); box-shadow:0 6px 16px rgba(239,68,68,.25) inset; }
        .meter-shimmer { position:absolute; top:0; left:-40%; width:40%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); filter: blur(2px); animation: shimmer 1.8s ease-in-out infinite; }
        @keyframes shimmer { 0% { left:-40%; } 100% { left:110%; } }
        @keyframes fill { from { width:0 } to { width: var(--pct, 0%); } }
        @keyframes marquee { 0% { transform: translateX(100%);} 100% { transform: translateX(-100%);} }
        .marquee { white-space: nowrap; overflow: hidden; }
        .marquee > span { display:inline-block; padding-left:100%; animation: marquee 30s linear infinite; }
        /* Modal for avatar crop */
        .modal { position: fixed; inset: 0; background: rgba(3,6,20,.65); backdrop-filter: blur(4px); display:none; align-items:center; justify-content:center; z-index:60; }
        .modal.show { display:flex; }
        .modal-card { background: linear-gradient(180deg, rgba(11,20,40,.95), rgba(5,10,30,.95)); border:1px solid rgba(255,255,255,.08); color:#e2e8f0; border-radius: 12px; width: 90%; max-width: 520px; box-shadow: 0 24px 64px rgba(0,0,0,.4); }
        .reveal { opacity: 0; transform: translateY(10px); transition: opacity .55s ease, transform .55s ease; }
        .reveal.reveal-in { opacity: 1; transform: translateY(0); }
        .notif-panel { 
            background: linear-gradient(180deg, rgba(11,20,40,.98), rgba(5,10,30,.98)); 
            color: #f8fafc; 
            border: 2px solid rgba(255,255,255,.15); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .notif-item-unread { background: rgba(11,94,215,.15); }
        
        /* Enhanced notification text visibility */
        .notif-panel .font-semibold { 
            color: #f1f5f9 !important; 
            font-weight: 700 !important; 
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .notif-panel .font-medium { 
            color: #e2e8f0 !important; 
            font-weight: 600 !important; 
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }
        .notif-panel .text-slate-300 { 
            color: #cbd5e1 !important; 
            font-weight: 500 !important; 
        }
        .notif-panel .text-slate-400 { 
            color: #94a3b8 !important; 
            font-weight: 500 !important; 
        }
        .notif-panel .text-blue-300 { 
            color: #93c5fd !important; 
            font-weight: 600 !important; 
        }
        .notif-panel .text-blue-200 { 
            color: #bfdbfe !important; 
            font-weight: 600 !important; 
        }
        /* Ripple effect */
        .ripple { position: relative; overflow: hidden; }
        .ripple span.r { position:absolute; border-radius:9999px; transform: translate(-50%,-50%); background: rgba(255,255,255,.35); animation: rip 600ms ease-out forwards; pointer-events:none; }
        @keyframes rip { from { width:0; height:0; opacity:.7;} to { width:300px; height:300px; opacity:0;} }
    </style>
</head>
<body class="bg-app min-h-screen relative">
    <div class="orb orb-blue" style="top:-60px; left:-40px; width:220px; height:220px;"></div>
    <div class="orb orb-gold" style="top:20%; right:-80px; width:260px; height:260px;"></div>
    <div class="orb orb-green" style="bottom:-80px; left:20%; width:240px; height:240px;"></div>
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col ml-64">
        <!-- Top Nav -->
        <header class="glass header-pulse shadow p-4 flex items-center justify-between fixed top-0 left-64 right-0 z-40">
            <h1 class="text-2xl font-bold title-gradient"><?= e($dashboard_title) ?></h1>
            <div class="flex items-center space-x-4">
                <p>
                    Welcome, <span class="font-semibold text-slate-100"><?= e($user['fullname']) ?></span>
                    (<span class="font-bold text-blue-300"><?= e($role_name) ?></span>)
                </p>
                <?php $avatar = $user['profile_image'] ?? ''; ?>
                <button id="openAvatarModal" class="hidden md:inline-flex items-center gap-2 text-slate-200 hover:text-white">
                    <img src="<?= $avatar ? 'uploads/' . e($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname']) . '&background=0b5ed7&color=fff' ?>" alt="avatar" class="w-9 h-9 rounded-full border border-white/40 object-cover">
                    <span class="text-xs">Change</span>
                </button>
                <div class="relative" id="notif_root">
                    <button type="button" id="notif_btn" class="inline-flex items-center text-gray-700 focus:outline-none <?= $unreadCount>0 ? 'animate-bounce' : '' ?>">
                        <span class="material-icons">notifications</span>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full px-2 py-0.5"><?= e($unreadCount) ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notif_panel" class="hidden absolute right-0 mt-2 w-80 notif-panel rounded-lg shadow-lg overflow-hidden z-50">
                        <div class="px-3 py-2 border-b border-white/10 flex items-center justify-between">
                            <div class="font-semibold">Notifications</div>
                            <a href="index.php?action=mark_all_notifications_read" class="text-sm text-blue-300 hover:text-blue-200">Mark all as read</a>
                        </div>
                        <div id="notif_container" class="max-h-80 overflow-auto divide-y divide-white/10">
                            <?php if (empty($notifications)): ?>
                            <div class="p-4 text-sm text-slate-300">No notifications.</div>
                            <?php else: foreach ($notifications as $n): ?>
                            <div class="p-3 flex items-start gap-3 <?= (int)$n['is_read'] === 0 ? 'notif-item-unread' : '' ?>">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate"><?= e($n['title']) ?></div>
                                    <div class="text-slate-300 text-sm line-clamp-2"><?= e($n['message']) ?></div>
                                    <div class="mt-1 text-xs text-slate-400"><?= e($n['created_at']) ?></div>
                                    <div class="mt-1 flex gap-3">
                                        <?php if (!empty($n['request_id'])): ?>
                                        <a class="text-xs text-blue-300 hover:text-blue-200" href="index.php?action=view_request&id=<?= e($n['request_id']) ?>">View</a>
                                        <?php endif; ?>
                                        <?php if ((int)$n['is_read'] === 0): ?>
                                        <a class="text-xs text-blue-300 hover:text-blue-200" href="index.php?action=mark_notification_read&id=<?= e($n['id']) ?>">Mark as read</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ((int)$user['role_id'] === 4): ?>
                <span class="px-3 py-1 rounded-full text-sm" style="background:linear-gradient(90deg, rgba(11,94,215,.18), rgba(212,175,55,.18)); border:1px solid rgba(11,94,215,.35); color:#d4af37; font-weight:700;">👑 Welcome, the King</span>
                <?php endif; ?>
                <?php if ($user['role_id'] == 1): ?>
                <a href="index.php?action=new_request" class="btn-hero font-semibold py-2 px-4 rounded-lg shadow-md transition">New Request</a>
                <?php endif; ?>
                <a href="index.php?action=leave_balances" class="bg-slate-900/40 text-slate-100 border border-white/10 font-medium py-2 px-4 rounded-lg shadow-sm hover:bg-slate-900/60">Leave Balances</a>
            </div>
        </header>
        <div class="glass px-4 py-2 fixed top-16 left-64 right-0 z-30">
            <div class="marquee text-slate-100 text-sm">
                <span>Welcome <?= e($user['fullname']) ?> — Wishing you a productive day at the Chamber! ✨ Empowering businesses across Tanzania and Africa 💙💛💚 • </span>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 pt-24 mt-12">
            <div class="relative">
                <div aria-hidden="true" class="orb-bg" style="background:radial-gradient(closest-side,#0b5ed7,transparent 70%),radial-gradient(closest-side,#d4af37,transparent 70%) 70% 10%/40% 40% no-repeat,radial-gradient(closest-side,#16a34a,transparent 70%) 30% 90%/35% 35% no-repeat;"></div>
            </div>
            <div class="glass p-6 rounded-xl">
                <?php if (!empty($notifications) && $unreadCount > 0): ?>
                <div class="mb-6">
                    <h2 class="text-lg font-bold mb-2 text-slate-100" style="color: #f1f5f9 !important; font-weight: 700 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.3); letter-spacing: 0.025em;">New Notifications (<?= $unreadCount ?>)</h2>
                    <div class="glass rounded-lg border-2 border-white/20 divide-y divide-white/10" style="background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95)); backdrop-filter: blur(8px);">
                        <div class="p-4 text-right border-b border-slate-200">
                            <a href="index.php?action=mark_all_notifications_read" class="text-sm font-semibold text-blue-700 hover:text-blue-800 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200 transition">Mark all as read</a>
                        </div>
                        <?php foreach ($notifications as $n): ?>
                        <div class="p-4 flex items-start justify-between gap-4 hover:bg-slate-50/80 transition">
                            <div class="flex-1">
                                <div class="font-bold text-slate-900 text-base mb-2" style="color: #0f172a !important; font-weight: 700 !important; line-height: 1.4;"><?= e($n['title']) ?></div>
                                <div class="text-slate-700 text-sm leading-relaxed mb-3" style="color: #334155 !important; font-weight: 500 !important; line-height: 1.5;"><?= e($n['message']) ?></div>
                                <?php if (!empty($n['request_id'])): ?>
                                <a class="inline-flex items-center gap-1 text-blue-700 text-sm font-semibold hover:text-blue-800 px-2 py-1 bg-blue-50 rounded border border-blue-200 transition" href="index.php?action=view_request&id=<?= e($n['request_id']) ?>">
                                    👁️ View request
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs font-medium text-slate-600 whitespace-nowrap mb-2 px-2 py-1 bg-slate-100 rounded" style="color: #475569 !important; font-weight: 600 !important;"><?= e($n['created_at']) ?></div>
                                <?php if ((int)$n['is_read'] === 0): ?>
                                <a class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 hover:text-green-800 px-2 py-1 bg-green-50 rounded border border-green-200 transition" href="index.php?action=mark_notification_read&id=<?= e($n['id']) ?>">
                                    ✓ Mark as read
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 reveal">
                    <div class="card-stat p-4 rounded-lg" style="transition-delay:.05s">
                        <div class="text-sm text-blue-200">⏳ Pending</div>
                        <div class="text-3xl font-extrabold text-yellow-400 drop-shadow"><?= $count_pending ?></div>
                        <div class="mt-2 meter"><div class="meter-bar" style="--pct: <?= $pending_pct ?>%; position:relative;"></div><div class="meter-shimmer"></div></div>
                        <div class="mt-1 text-xs text-slate-300"><?= $pending_pct ?>%</div>
                    </div>
                    <div class="card-stat p-4 rounded-lg" style="transition-delay:.15s">
                        <div class="text-sm text-green-200">✅ Approved</div>
                        <div class="text-3xl font-extrabold text-green-400 drop-shadow"><?= $count_approved ?></div>
                        <div class="mt-2 meter"><div class="meter-bar green" style="--pct: <?= $approved_pct ?>%; position:relative;"></div><div class="meter-shimmer"></div></div>
                        <div class="mt-1 text-xs text-slate-300"><?= $approved_pct ?>%</div>
                    </div>
                    <div class="card-stat p-4 rounded-lg" style="transition-delay:.25s">
                        <div class="text-sm text-red-200">❌ Rejected</div>
                        <div class="text-3xl font-extrabold text-red-400 drop-shadow"><?= $count_rejected ?></div>
                        <div class="mt-2 meter"><div class="meter-bar red" style="--pct: <?= $rejected_pct ?>%; position:relative;"></div><div class="meter-shimmer"></div></div>
                        <div class="mt-1 text-xs text-slate-300"><?= $rejected_pct ?>%</div>
                    </div>
                </div>
                <div class="divider mb-4"></div>
                <div class="flex border-b border-white/10 mb-4">
                    <button id="requestsTab" class="px-4 py-2 font-semibold text-slate-100 border-b-2 border-blue-500 tab-button" onclick="showTab('requests')">
                        <?php if ($user['role_id'] == 1): ?>
                            My Requests <span class="chip">📄</span>
                        <?php else: ?>
                            Requests <span class="chip">📄</span>
                        <?php endif; ?>
                    </button>
                    <?php if (in_array($user['role_id'], [2, 3, 5, 6, 7])): // HRM, HOD, Finance, Internal Auditor, Admin ?>
                    <button id="myRequestsTab" class="px-4 py-2 font-semibold text-slate-400 hover:text-slate-100 border-b-2 border-transparent tab-button" onclick="showTab('myRequests')">My Requests <span class="chip">📝</span></button>
                    <?php endif; ?>
                    <?php if (in_array($user['role_id'], [4, 5])): // Only show vouchers tab for Finance and ED ?>
                    <button class="px-4 py-2 font-semibold text-slate-400 hover:text-slate-100 border-b-2 border-transparent" onclick="window.location.href='index.php?action=vouchers'">Vouchers <span class="chip">💳</span></button>
                    <?php endif; ?>
                </div>
                <div id="requestsContent">
                    <h2 class="text-2xl font-bold mb-4 title-gradient">
                        <?php if ($user['role_id'] == 1): ?>
                            My Requests <span class="chip">📄</span>
                        <?php else: ?>
                            Pending Requests <span class="chip">🧾</span>
                        <?php endif; ?>
                    </h2>

                <?php if (empty($requests)): ?>
                    <div class="text-center text-gray-500 py-10">
                        <p class="text-lg">🎉 No requests to display.</p>
                        <p class="mt-2 text-sm text-gray-400">Your queue is clear.</p>
                    </div>
        <?php else: ?>
                    <!-- Role-specific helper text -->
                    <?php if ($user['role_id'] == 3): ?>
                        <div class="mb-4 text-sm text-gray-500">HOD queue: Requests from your department awaiting your decision.</div>
                    <?php elseif ($user['role_id'] == 2): ?>
                        <div class="mb-4 text-sm text-gray-500">HRM queue: Requests approved by HOD, awaiting HRM review.</div>
                    <?php elseif ($user['role_id'] == 6): ?>
                        <div class="mb-4 text-sm text-gray-500">Internal Audit queue: Requests approved by HRM, awaiting audit.</div>
                    <?php elseif ($user['role_id'] == 5): ?>
                        <div class="mb-4 text-sm text-gray-500">Finance queue: Requests approved by Audit, pending finance decision.</div>
                    <?php elseif ($user['role_id'] == 4): ?>
                        <div class="mb-4 text-sm text-gray-500">ED queue: Final approvals after finance stage.</div>
                    <?php endif; ?>
                    
                    <!-- Vouchers Section - Only visible to Finance and ED roles -->
                    <?php if (in_array($user['role_id'], [4, 5]) && !empty($vouchers)): ?>
                    <div class="mt-4 mb-6 p-4 bg-white/5 rounded-lg">
                        <h3 class="text-lg font-semibold mb-2">Pending Vouchers <span class="chip">💳</span></h3>
                        <p class="text-sm text-gray-400 mb-2">You have <?= count($vouchers) ?> voucher(s) awaiting your review</p>
                        <a href="index.php?action=vouchers" class="btn-hero inline-block text-sm font-semibold py-1 px-3 rounded-lg shadow-md transition">View Vouchers</a>
                    </div>
                    <?php endif; ?>

                    <!-- Search Bar -->
                    <div class="mb-6">
                        <div class="relative">
                            <input type="text" id="requestSearch" placeholder="Search by title, type, status, or submitter name..." class="w-full px-4 py-3 pr-12 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400">
                                🔍
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6" id="requestsContainer">
                        <?php $__idx = 0; foreach ($requests as $req): $__idx++; $__delay = number_format(0.05 + ($__idx * 0.05), 2); ?>
                                <?php
                                // Get the correct status field based on the user's role.
                                $status_field = 'status_name';
                                switch ($user['role_id']) {
                                    case 3: $status_field = 'hod_status'; break;
                                    case 2: $status_field = 'hrm_status'; break;
                                    case 6: $status_field = 'auditor_status'; break;
                                    case 5: $status_field = 'finance_status'; break;
                                    case 4: $status_field = 'ed_status'; break;
                                }
                                $status = $req[$status_field] ?? 'N/A';
                                
                                $status_color_map = [
                                    'pending'  => 'bg-yellow-200 text-yellow-800',
                                    'approved' => 'bg-green-200 text-green-800',
                                    'rejected' => 'bg-red-200 text-red-800',
                                    'N/A'      => 'bg-gray-200 text-gray-800',
                                ];
                                $status_color = $status_color_map[strtolower($status)] ?? $status_color_map['N/A'];
                            ?>
                            <div class="card-req p-6 rounded-lg reveal mb-6" style="transition-delay: <?= $__delay ?>s" data-search-content="<?= strtolower(e($req['title'] . ' ' . $req['request_type'] . ' ' . $status . ' ' . ($req['employee_fullname'] ?? $req['fullname'] ?? ''))) ?>">
                                    <div class="flex justify-between items-center">
                                    <h3 class="font-semibold text-lg text-slate-100"><?= e($req['title']) ?></h3>
                                    <span class="px-3 py-1 text-sm rounded-full <?= e($status_color) ?> font-medium">
                                <?= ucfirst(e($status)) ?>
                            </span>
                        </div>
                                <?php 
                                    $emojiMap = [
                                        'Impest request' => '💰',
                                        'Reimbursement request' => '↩️',
                                        'TCCIA retirement request' => '📑',
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
                                <p class="text-sm text-slate-200 mt-2">
                                    Type: <span class="font-medium"><?= e($eicon . ' ' . $etype) ?></span>
                                </p>
                                <p class="text-sm text-slate-300">
                                    Date: <?= date('F j, Y, g:i a', strtotime($req['created_at'])) ?>
                                </p>
                                <div class="mt-4 pt-4 divider text-right">
                                    <a href="index.php?action=view_request&id=<?= e($req['request_id']) ?>" class="btn-hero ripple inline-block font-semibold py-2 px-4 rounded-lg shadow-md transition">
                                        View Details
                                    </a>
                                    <?php if (in_array((int)$user['role_id'], [3, 2, 6, 5, 4]) && can_approve((int)$user['role_id'], $req)): ?>
                                    <form method="POST" action="index.php?action=process_request" class="mt-3 flex flex-col md:flex-row md:items-center gap-3 text-left">
                                        <input type="hidden" name="id" value="<?= e($req['request_id']) ?>">
                                        <input type="text" name="remark" placeholder="Remark (optional)" class="input" style="flex:1">
                                        <div class="flex gap-2">
                                            <button type="submit" name="status" value="approved" class="btn btn-success">Approve</button>
                                            <button type="submit" name="status" value="rejected" class="btn btn-danger">Reject</button>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                </div>
                    </div>
                <?php endforeach; ?>
        </div>
                                                     <?php endif; ?>
                                                 </div>
                </div>

                <!-- My Requests Tab Content -->
                <?php if (in_array($user['role_id'], [2, 3, 5, 6, 7])): ?>
                <div id="myRequestsContent" style="display: none;">
                    <h2 class="text-2xl font-bold mb-4 title-gradient">My Requests <span class="chip">📝</span></h2>

                    <?php if (empty($my_requests)): ?>
                        <div class="text-center text-gray-500 py-10">
                            <p class="text-lg">📝 No personal requests found.</p>
                            <p class="mt-2 text-sm text-gray-400">You haven't submitted any requests yet.</p>
                        </div>
                    <?php else: ?>
                        <!-- Search Bar for My Requests -->
                        <div class="mb-6">
                            <div class="relative">
                                <input type="text" id="myRequestSearch" placeholder="Search your requests by title, type, or status..." class="w-full px-4 py-3 pr-12 bg-slate-900/40 border border-white/10 rounded-lg text-slate-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400">
                                    🔍
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6" id="myRequestsContainer">
                            <?php $__idx = 0; foreach ($my_requests as $req): $__idx++; $__delay = number_format(0.05 + ($__idx * 0.05), 2); ?>
                                <?php
                                // For user's own requests, show overall status
                                $status = $req['status_name'] ?? 'N/A';
                                
                                $status_color_map = [
                                    'pending'  => 'bg-yellow-200 text-yellow-800',
                                    'approved' => 'bg-green-200 text-green-800',
                                    'rejected' => 'bg-red-200 text-red-800',
                                    'N/A'      => 'bg-gray-200 text-gray-800',
                                ];
                                $status_color = $status_color_map[strtolower($status)] ?? $status_color_map['N/A'];
                            ?>
                            <div class="card-req p-6 rounded-lg reveal mb-6" style="transition-delay: <?= $__delay ?>s" data-search-content="<?= strtolower(e($req['title'] . ' ' . $req['request_type'] . ' ' . $status)) ?>">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-semibold text-lg text-slate-100"><?= e($req['title']) ?></h3>
                                    <span class="px-3 py-1 text-sm rounded-full <?= e($status_color) ?> font-medium">
                                        <?= ucfirst(e($status)) ?>
                                    </span>
                                </div>
                                <?php 
                                    $emojiMap = [
                                        'Impest request' => '💰',
                                        'Reimbursement request' => '↩️',
                                        'TCCIA retirement request' => '📑',
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
                                <div class="flex items-center gap-2 mt-2 text-slate-300">
                                    <span><?= $eicon ?></span>
                                    <span class="text-sm"><?= e($req['request_type']) ?></span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-sm"><?= e(date('M j, Y', strtotime($req['created_at']))) ?></span>
                                </div>
                                
                                <!-- Approval Status Progress -->
                                <div class="mt-4 grid grid-cols-5 gap-2 text-xs">
                                    <?php
                                    $stages = [
                                        'HOD' => $req['hod_status'] ?? 'pending',
                                        'HRM' => $req['hrm_status'] ?? 'pending', 
                                        'Audit' => $req['auditor_status'] ?? 'pending',
                                        'Finance' => $req['finance_status'] ?? 'pending',
                                        'ED' => $req['ed_status'] ?? 'pending'
                                    ];
                                    
                                    foreach ($stages as $stage => $stage_status):
                                        $stage_color = 'bg-gray-600 text-gray-300';
                                        if ($stage_status === 'approved') $stage_color = 'bg-green-600 text-green-100';
                                        elseif ($stage_status === 'rejected') $stage_color = 'bg-red-600 text-red-100';
                                        elseif ($stage_status === 'pending') $stage_color = 'bg-yellow-600 text-yellow-100';
                                    ?>
                                    <div class="text-center">
                                        <div class="<?= $stage_color ?> px-2 py-1 rounded text-xs font-medium mb-1">
                                            <?= $stage ?>
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            <?= ucfirst($stage_status) ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="flex justify-between items-center mt-4">
                                    <a href="index.php?action=view_request&id=<?= e($req['request_id']) ?>" class="btn-hero text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition">View Details</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
        </main>
    </div>
</body>
</html>
<script>
// Avatar crop modal
(function(){
    const openBtn = document.getElementById('openAvatarModal');
    if (!openBtn) return;
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-card p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="font-semibold">Update Profile Photo</div>
          <button id="closeAvatarModal" class="text-slate-300 hover:text-white">✕</button>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <input id="avatarFile" type="file" accept="image/*" class="w-full text-sm">
            <div class="text-xs text-red-300" id="avatarError" style="display:none"></div>
            <div class="text-xs text-slate-400">Pick an image (JPG/PNG/WEBP). We'll crop to a square.</div>
            <div class="bg-slate-900/40 border border-white/10 rounded p-2">
              <canvas id="avatarCanvas" class="w-full h-auto"></canvas>
            </div>
          </div>
          <div class="space-y-2">
            <div class="text-xs">Preview</div>
            <div class="flex items-center gap-3">
              <canvas id="avatarPreview" width="96" height="96" class="rounded-full border border-white/20"></canvas>
            </div>
            <div class="flex items-center gap-2 mt-2">
              <label class="text-xs">Zoom</label>
              <input id="avatarZoom" type="range" min="1" max="3" step="0.01" value="1">
            </div>
            <div class="flex items-center gap-2">
              <label class="text-xs">Move</label>
              <input id="avatarX" type="range" min="-1" max="1" step="0.01" value="0">
              <input id="avatarY" type="range" min="-1" max="1" step="0.01" value="0">
            </div>
          </div>
        </div>
        <form id="avatarForm" class="mt-4 flex items-center justify-end gap-2" method="POST" action="index.php?action=upload_avatar" enctype="multipart/form-data">
          <input id="avatarBlob" name="avatar" type="file" style="display:none">
          <button type="button" id="cancelAvatar" class="px-3 py-2 text-slate-200">Cancel</button>
          <button type="submit" id="saveAvatar" class="btn-hero px-4 py-2 rounded">Save</button>
        </form>
      </div>`;
    document.body.appendChild(modal);

    const fileInput = modal.querySelector('#avatarFile');
    const errorBox = modal.querySelector('#avatarError');
    const canvas = modal.querySelector('#avatarCanvas');
    const preview = modal.querySelector('#avatarPreview');
    const zoom = modal.querySelector('#avatarZoom');
    const rx = modal.querySelector('#avatarX');
    const ry = modal.querySelector('#avatarY');
    const form = modal.querySelector('#avatarForm');
    const blobInput = modal.querySelector('#avatarBlob');
    const ctx = canvas.getContext('2d');
    const pctx = preview.getContext('2d');
    let img = null;

    function draw(){
      if (!img) return;
      const cw = 360, ch = 360; canvas.width=cw; canvas.height=ch;
      const scale = parseFloat(zoom.value);
      const offx = parseFloat(rx.value) * 100;
      const offy = parseFloat(ry.value) * 100;
      ctx.clearRect(0,0,cw,ch);
      const iw = img.width, ih = img.height;
      const s = Math.min(iw, ih);
      const sx = (iw - s)/2 - offx; const sy = (ih - s)/2 - offy;
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(img, sx, sy, s, s, (cw - cw*scale)/2, (ch - ch*scale)/2, cw*scale, ch*scale);
      // preview circle
      pctx.clearRect(0,0,96,96);
      pctx.save();
      pctx.beginPath(); pctx.arc(48,48,48,0,Math.PI*2); pctx.clip();
      pctx.drawImage(canvas, 0,0,96,96);
      pctx.restore();
    }
    function open(){ modal.classList.add('show'); }
    function close(){ modal.classList.remove('show'); }
    openBtn.addEventListener('click', open);
    modal.querySelector('#closeAvatarModal').addEventListener('click', close);
    modal.querySelector('#cancelAvatar').addEventListener('click', close);
    [zoom, rx, ry].forEach(el=> el.addEventListener('input', draw));
    fileInput.addEventListener('change', function(){
      errorBox.style.display='none'; errorBox.textContent='';
      const f = this.files && this.files[0]; if (!f) return;
      if (f.size > 2 * 1024 * 1024) { errorBox.textContent = 'Max size is 2MB.'; errorBox.style.display='block'; this.value=''; return; }
      const r = new FileReader(); r.onload = function(){ img = new Image(); img.onload = draw; img.src = r.result; };
      r.readAsDataURL(f);
    });
    form.addEventListener('submit', function(e){
      e.preventDefault();
      if (!img) return;
      canvas.toBlob(function(blob){
        const dt = new DataTransfer(); dt.items.add(new File([blob], 'avatar.png', {type:'image/png'}));
        blobInput.files = dt.files; form.submit();
      }, 'image/png', 0.9);
    });
})();

// Tab switching functionality
function showTab(tabName) {
    const requestsTab = document.getElementById('requestsTab');
    const myRequestsTab = document.getElementById('myRequestsTab');
    const requestsContent = document.getElementById('requestsContent');
    const myRequestsContent = document.getElementById('myRequestsContent');
    
    if (tabName === 'requests') {
        // Show requests tab
        requestsTab.classList.remove('text-slate-400', 'border-transparent');
        requestsTab.classList.add('text-slate-100', 'border-blue-500');
        if (myRequestsTab) {
            myRequestsTab.classList.remove('text-slate-100', 'border-blue-500');
            myRequestsTab.classList.add('text-slate-400', 'border-transparent');
        }
        
        requestsContent.style.display = 'block';
        if (myRequestsContent) {
            myRequestsContent.style.display = 'none';
        }
    } else if (tabName === 'myRequests') {
        // Show my requests tab
        if (myRequestsTab) {
            myRequestsTab.classList.remove('text-slate-400', 'border-transparent');
            myRequestsTab.classList.add('text-slate-100', 'border-blue-500');
        }
        requestsTab.classList.remove('text-slate-100', 'border-blue-500');
        requestsTab.classList.add('text-slate-400', 'border-transparent');
        
        if (myRequestsContent) {
            myRequestsContent.style.display = 'block';
        }
        requestsContent.style.display = 'none';
    }
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('requestSearch');
    const requestsContainer = document.getElementById('requestsContainer');
    const myRequestSearch = document.getElementById('myRequestSearch');
    const myRequestsContainer = document.getElementById('myRequestsContainer');
    
    // Search for main requests
    if (searchInput && requestsContainer) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const requestCards = requestsContainer.querySelectorAll('.card-req');
            
            requestCards.forEach(card => {
                const searchContent = card.getAttribute('data-search-content') || '';
                if (searchTerm === '' || searchContent.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show "no results" message if no cards are visible
            const visibleCards = Array.from(requestCards).filter(card => card.style.display !== 'none');
            let noResultsMsg = requestsContainer.querySelector('.no-results-message');
            
            if (visibleCards.length === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results-message text-center text-slate-400 py-10';
                    noResultsMsg.innerHTML = '<p class="text-lg">🔍 No requests found</p><p class="mt-2 text-sm">Try adjusting your search terms</p>';
                    requestsContainer.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    }
    
    // Search for my requests
    if (myRequestSearch && myRequestsContainer) {
        myRequestSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const requestCards = myRequestsContainer.querySelectorAll('.card-req');
            
            requestCards.forEach(card => {
                const searchContent = card.getAttribute('data-search-content') || '';
                if (searchTerm === '' || searchContent.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show "no results" message if no cards are visible
            const visibleCards = Array.from(requestCards).filter(card => card.style.display !== 'none');
            let noResultsMsg = myRequestsContainer.querySelector('.no-results-message');
            
            if (visibleCards.length === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results-message text-center text-slate-400 py-10';
                    noResultsMsg.innerHTML = '<p class="text-lg">🔍 No requests found</p><p class="mt-2 text-sm">Try adjusting your search terms</p>';
                    myRequestsContainer.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    }
});
// Server-triggered confetti (after ED approval)
(function(){
    <?php if (!empty($_SESSION['confetti']) && $_SESSION['confetti'] === 'ed_approved') { unset($_SESSION['confetti']); ?>
    function confetti(){
        const colors = ['#0b5ed7','#d4af37','#16a34a','#60a5fa','#fde047','#86efac'];
        const layer = document.body;
        for (let i=0;i<160;i++){
            const p = document.createElement('i');
            p.style.position='fixed'; p.style.pointerEvents='none'; p.style.width='8px'; p.style.height='14px';
            p.style.left = (Math.random()*100)+'vw'; p.style.top='-10px';
            p.style.background = colors[Math.floor(Math.random()*colors.length)]; p.style.opacity='0.95';
            const s = 8 + Math.random()*8; p.style.width=s+'px'; p.style.height=(s*1.4)+'px';
            const tx = (Math.random()*2-1)*60; const ty= 100 + Math.random()*30;
            p.animate([{ transform:'translate(0,0) rotate(0deg)', opacity:1 },{ transform:`translate(${tx}vw, ${ty}vh) rotate(${Math.random()*720-360}deg)`, opacity:0.9 }],{ duration: 1200+Math.random()*1400, easing:'cubic-bezier(.2,.8,.2,1)' });
            layer.appendChild(p); setTimeout(()=>p.remove(), 2800);
        }
    }
    window.addEventListener('load', confetti);
    <?php } ?>
})();
// Notification dropdown toggle and outside click handler
(function(){
    const btn = document.getElementById('notif_btn');
    const panel = document.getElementById('notif_panel');
    const root = document.getElementById('notif_root');
    if (!btn || !panel || !root) return;
    function hide(){ panel.classList.add('hidden'); }
    function show(){ panel.classList.remove('hidden'); }
    function toggle(){ panel.classList.toggle('hidden'); }
    btn.addEventListener('click', function(e){ e.stopPropagation(); toggle(); });
    document.addEventListener('click', function(e){ if (!root.contains(e.target)) hide(); });
    
    // Request notification permission
    let notificationPermission = false;
    if ("Notification" in window) {
        Notification.requestPermission().then(function(permission) {
            notificationPermission = permission === "granted";
        });
    }
    
    // Notification sound function
    function playNotificationSound() {
        // Try to play audio file first
        const audio = new Audio('assets/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => {
            console.log('Audio file play prevented, using Web Audio API:', e);
            // Fallback to Web Audio API if file playback fails
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(587.33, audioContext.currentTime); // D5
            
            gainNode.gain.setValueAtTime(0.5, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.5);
        });
    }
    
    // Track last notification count to detect new notifications
    let lastNotificationCount = 0;
    
    // Check for new notifications every 30 seconds
    function checkNotifications() {
        fetch('index.php?action=check_notifications')
            .then(response => response.json())
            .then(data => {
                // Update notification badge
                if (data.unread > 0) {
                    const badge = btn.querySelector('span.bg-red-600');
                    if (badge) {
                        badge.textContent = data.unread;
                        // Only animate and play sound if count increased
                        if (data.unread > lastNotificationCount) {
                            btn.classList.add('animate-bounce');
                            playNotificationSound();
                            
                            // Show desktop notification if supported
                            if (notificationPermission && data.notifications && data.notifications.length > 0) {
                                const latestNotif = data.notifications[0];
                                new Notification(latestNotif.title || "New Notification", {
                                    body: latestNotif.message || "You have " + data.unread + " unread notifications",
                                    icon: "/favicon.ico"
                                });
                            }
                        }
                    } else {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full px-2 py-0.5';
                        newBadge.textContent = data.unread;
                        btn.appendChild(newBadge);
                        btn.classList.add('animate-bounce');
                        
                        // Play notification sound for new badge
                        playNotificationSound();
                    }
                } else {
                    const badge = btn.querySelector('span.bg-red-600');
                    if (badge) {
                        badge.remove();
                    }
                    btn.classList.remove('animate-bounce');
                }
                
                // Update notification panel content if there are notifications
                if (data.notifications && data.notifications.length > 0) {
                    const container = document.getElementById('notif_container');
                    if (container) {
                        // Clear existing notifications
                        container.innerHTML = '';
                        
                        // Add new notifications
                        data.notifications.forEach(n => {
                            const div = document.createElement('div');
                            div.className = `p-3 flex items-start gap-3 ${parseInt(n.is_read) === 0 ? 'notif-item-unread' : ''}`;
                            
                            const content = document.createElement('div');
                            content.className = 'flex-1 min-w-0';
                            
                            const title = document.createElement('div');
                            title.className = 'font-medium truncate';
                            title.textContent = n.title;
                            
                            const message = document.createElement('div');
                            message.className = 'text-slate-300 text-sm line-clamp-2';
                            message.textContent = n.message;
                            
                            const time = document.createElement('div');
                            time.className = 'mt-1 text-xs text-slate-400';
                            time.textContent = n.created_at;
                            
                            const actions = document.createElement('div');
                            actions.className = 'mt-1 flex gap-3';
                            
                            if (n.request_id) {
                                const viewLink = document.createElement('a');
                                viewLink.className = 'text-xs text-blue-300 hover:text-blue-200';
                                viewLink.href = `index.php?action=view_request&id=${n.request_id}`;
                                viewLink.textContent = 'View';
                                actions.appendChild(viewLink);
                            }
                            
                            if (parseInt(n.is_read) === 0) {
                                const markReadLink = document.createElement('a');
                                markReadLink.className = 'text-xs text-blue-300 hover:text-blue-200';
                                markReadLink.href = `index.php?action=mark_notification_read&id=${n.id}`;
                                markReadLink.textContent = 'Mark as read';
                                actions.appendChild(markReadLink);
                            }
                            
                            content.appendChild(title);
                            content.appendChild(message);
                            content.appendChild(time);
                            content.appendChild(actions);
                            
                            div.appendChild(content);
                            container.appendChild(div);
                        });
                    }
                }
                
                // Update last notification count
                lastNotificationCount = data.unread;
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
    
    // Initial check and then every 30 seconds
    checkNotifications();
    setInterval(checkNotifications, 30000);
})();
// Scroll reveal for cards
(function(){
    const els = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries)=>{
            entries.forEach(e=>{ if (e.isIntersecting) { e.target.classList.add('reveal-in'); io.unobserve(e.target);} });
        }, { threshold: 0.12 });
        els.forEach(el=> io.observe(el));
    } else {
        els.forEach(el=> el.classList.add('reveal-in'));
    }
})();
// Ripple on buttons
(function(){
    document.addEventListener('click', function(e){
        const t = e.target.closest('.ripple');
        if (!t) return;
        const rect = t.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const r = document.createElement('span');
        r.className = 'r';
        r.style.left = x + 'px';
        r.style.top = y + 'px';
        t.appendChild(r);
        setTimeout(()=>{ r.remove(); }, 650);
    });
})();
// Parallax effect for orbs
(function(){
    const orbs = Array.from(document.querySelectorAll('.orb'));
    if (orbs.length === 0) return;
    function onMove(e){
        const w = window.innerWidth, h = window.innerHeight;
        const mx = (e.clientX / w - .5) * 2; // -1..1
        const my = (e.clientY / h - .5) * 2;
        orbs.forEach((el, i)=>{
            const depth = (i+1) * 4; // different layers
            el.style.transform = `translate(${mx * depth}px, ${my * depth}px)`;
        });
    }
    window.addEventListener('mousemove', onMove);
})();
</script>
</html>
