<?php
// FILE: finance_dashboard.php
// Dashboard for Finance to view requests and manage vouchers

// Check if this file is being accessed directly
if (!isset($pdo)) {
    require_once __DIR__ . '/function.php';
    require_once __DIR__ . '/Request.php';
    require_once __DIR__ . '/User.php';
    require_once __DIR__ . '/Voucher.php';
    require_once __DIR__ . '/voucher_functions.php';
    
    // Create instances of models
    $requestModel = new RequestModel($pdo);
    $userModel = new UserModel($pdo);
    $voucherModel = new VoucherModel($pdo);
    
    // Get the current user from the session
    session_start();
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
    
    // Redirect if not Finance
    if (!$user || (int)$user['role_id'] !== 5) {
        header('Location: index.php?action=login');
        exit;
    }
}

// Get all requests for Finance
$requests = $requestModel->get_requests_for_role($user['role_id'], $user);

// Get Finance user's own requests for "My Requests" tab
$my_requests = $requestModel->get_my_requests($user['user_id']);

// Filter out any null or invalid requests
$requests = array_filter($requests, function($request) {
    return is_array($request) && !empty($request);
});

// Get all vouchers for Finance
$vouchers = $voucherModel->get_vouchers_for_role($user['role_id'], $user['user_id']);

// Filter out any null or invalid vouchers
$vouchers = array_filter($vouchers, function($voucher) {
    return is_array($voucher) && !empty($voucher);
});

// Count unread voucher messages
$unread_count = $voucherModel->count_unread_messages($user['user_id']);

// Filter financial requests
$financial_request_types = ['Imprest request', 'Reimbursement request', 'Salary advance', 'TCCIA retirement request'];
$financial_requests = array_filter($requests, function($request) use ($financial_request_types) {
    return in_array($request['request_type'], $financial_request_types);
});

// Count requests by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($financial_requests as $request) {
    if ($request['status_name'] === 'pending') {
        $pending_count++;
    } elseif ($request['status_name'] === 'approved') {
        $approved_count++;
    } elseif ($request['status_name'] === 'rejected') {
        $rejected_count++;
    }
}

// Count vouchers by status
$pending_vouchers = 0;
$approved_vouchers = 0;
$rejected_vouchers = 0;

foreach ($vouchers as $voucher) {
    if ($voucher['finance_status'] === 'pending') {
        $pending_vouchers++;
    } elseif ($voucher['finance_status'] === 'approved') {
        $approved_vouchers++;
    } elseif ($voucher['finance_status'] === 'rejected') {
        $rejected_vouchers++;
    }
}

// Count vouchers by ED status
$ed_pending_count = 0;
$ed_approved_count = 0;
$ed_rejected_count = 0;

foreach ($vouchers as $voucher) {
    if ($voucher['ed_status'] === 'pending') {
        $ed_pending_count++;
    } elseif ($voucher['ed_status'] === 'approved') {
        $ed_approved_count++;
    } elseif ($voucher['ed_status'] === 'rejected') {
        $ed_rejected_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Finance Dashboard | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body{font-family:'Inter',sans-serif}
        .orb-bg { position:absolute; inset:-40px; filter:blur(70px); opacity:.35; pointer-events:none; }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#0b5ed710; border:1px solid #0b5ed733; color:#0b5ed7; font-weight:600; }
        .btn-gradient { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; }
        .btn-gradient:hover { filter:brightness(1.05); }
        
        /* Enhanced styles for better UI */
        .text-brand-gold { color: #d4af37; }
        .text-brand-blue { color: #0b5ed7; }
        .text-brand-green { color: #16a34a; }
        
        /* Search bar styles */
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            transition: all 0.3s;
        }
        .search-input:focus {
            border-color: #0b5ed7;
            background-color: rgba(255, 255, 255, 0.1);
            outline: none;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Tab styles */
        .tab-container {
            display: flex;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            transition: all 0.3s;
        }
        .tab.active {
            color: #0b5ed7;
        }
        .tab.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #0b5ed7;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        /* Status filter styles */
        .status-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .status-filter button {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-filter button.active {
            background-color: #0b5ed7;
            color: white;
        }
        .status-filter button:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
        }
        .status-filter button:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .status-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.2);
            margin-left: 8px;
            font-size: 12px;
        }
    </style>
        body{font-family:'Inter',sans-serif}
        .orb-bg { position:absolute; inset:-40px; filter:blur(70px); opacity:.35; pointer-events:none; }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#0b5ed710; border:1px solid #0b5ed733; color:#0b5ed7; font-weight:600; }
        .btn-gradient { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; }
        .btn-gradient:hover { filter:brightness(1.05); }
        
        /* Enhanced styles for better UI */
        .text-brand-gold { color: #d4af37; }
        .text-brand-blue { color: #0b5ed7; }
        .text-brand-green { color: #16a34a; }
        
        /* Improved text clarity */
        body {
            font-size: 16px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            letter-spacing: -0.01em;
        }
        
        /* Search input styling */
        .search-container {
            position: relative;
        }
        .search-container input {
            padding-left: 40px;
            transition: all 0.2s ease;
        }
        .search-container svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .search-container input:focus {
            box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.2);
        }
        
        /* Tab styling */
        .tab {
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #0b5ed7;
            border-radius: 3px;
        }
        .tab:hover:not(.active) {
            color: #0b5ed7;
        }
        
        /* Card hover effects */
        .request-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        /* Badge styling */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-900 flex min-h-screen">
    <div class="bg-orb"></div>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="flex-1 flex flex-col">
        <header class="bg-slate-800/80 backdrop-blur border-b border-white/10 sticky top-0 z-40 p-5 flex items-center justify-between text-white shadow-lg">
            <h1 class="text-2xl font-semibold">Finance Dashboard</h1>
            <div class="flex items-center gap-4">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search requests or vouchers..." class="bg-slate-700/50 border border-slate-600 rounded-lg py-2 px-4 text-white placeholder-slate-400 w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8">
            <div class="relative">
                <div aria-hidden="true" class="orb-bg" style="background:radial-gradient(closest-side,#0b5ed7,transparent 70%),radial-gradient(closest-side,#d4af37,transparent 70%) 70% 10%/40% 40% no-repeat,radial-gradient(closest-side,#16a34a,transparent 70%) 30% 90%/35% 35% no-repeat;"></div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/80 backdrop-blur p-6 rounded-xl shadow-lg ring-1 ring-black/5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Financial Requests</h3>
                        <div class="bg-yellow-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900"><?= count($financial_requests) ?></span>
                    </div>
                </div>
                
                <div class="bg-white/80 backdrop-blur p-6 rounded-xl shadow-lg ring-1 ring-black/5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Created Vouchers</h3>
                        <div class="bg-green-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900"><?= count($vouchers) ?></span>
                    </div>
                </div>
                
                <div class="bg-white/80 backdrop-blur p-6 rounded-xl shadow-lg ring-1 ring-black/5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">ED Pending</h3>
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900"><?= $ed_pending_count ?></span>
                    </div>
                </div>
                
                <div class="bg-white/80 backdrop-blur p-6 rounded-xl shadow-lg ring-1 ring-black/5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Unread Messages</h3>
                        <div class="bg-purple-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900"><?= $unread_count ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="bg-white/80 backdrop-blur rounded-xl shadow-lg ring-1 ring-black/5 mb-8">
                <div class="border-b border-gray-200">
                    <div class="flex p-4 space-x-8">
                        <div class="tab active text-blue-600 font-semibold" data-target="requests">Financial Requests <span class="status-count"><?= count($financial_requests) ?></span></div>
                        <div class="tab text-gray-600 font-semibold" data-target="vouchers">Vouchers <span class="status-count"><?= count($vouchers) ?></span></div>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="search-container px-6 pt-4">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input bg-gray-100 w-full" placeholder="Search by ID, type, title, employee name...">                
                </div>
                
                <!-- Requests Tab Content -->
                <div id="requests-tab" class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <?php if (empty($financial_requests)): ?>
                            <div class="text-center py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div class="text-gray-500">No financial requests found.</div>
                            </div>
                        <?php else: foreach ($financial_requests as $request): ?>
                            <div class="request-card bg-gray-50 rounded-lg shadow-md overflow-hidden border border-gray-200 request-item">
                                <div class="p-5">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-1"><?= e($request['title']) ?></h3>
                                            <div class="flex items-center gap-2 mb-3">
                                                <span class="text-sm text-gray-600">#<?= e($request['request_id']) ?></span>
                                                <span class="text-sm text-gray-600">•</span>
                                                <span class="text-sm text-gray-600"><?= e($request['request_type']) ?></span>
                                                <span class="text-sm text-gray-600">•</span>
                                                <span class="text-sm text-gray-600"><?= e($request['fullname']) ?></span>
                                            </div>
                                        </div>
                                        <?php 
                                        $statusClass = '';
                                        if ($request['status_name'] === 'pending') {
                                            $statusClass = 'badge-pending';
                                        } elseif ($request['status_name'] === 'approved') {
                                            $statusClass = 'badge-approved';
                                        } elseif ($request['status_name'] === 'rejected') {
                                            $statusClass = 'badge-rejected';
                                        }
                                        
                                        // Check if voucher exists for this request
                                        $has_voucher = false;
                                        if ($request['status_name'] === 'approved') {
                                            $has_voucher = $voucherModel->has_voucher_for_request($request['request_id']);
                                        }
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <span class="badge <?= $statusClass ?>"><?= e($request['status_name']) ?></span>
                                            <?php if ($request['status_name'] === 'approved' && !$has_voucher): ?>
                                                <span class="badge bg-blue-100 text-blue-800 border border-blue-200">Needs Voucher</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 mb-4 line-clamp-2"><?= e(substr($request['description'], 0, 150)) ?><?= strlen($request['description']) > 150 ? '...' : '' ?></p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500"><?= date('M d, Y', strtotime($request['created_at'])) ?></span>
                                        <div class="flex gap-2">
                                            <a href="index.php?action=view_request&id=<?= e($request['request_id']) ?>" class="btn btn-primary py-1.5 px-4 text-sm font-medium">View Details</a>
                                            <?php if ($request['status_name'] === 'approved' && !$has_voucher): ?>
                                                <a href="index.php?action=create_voucher&request_id=<?= e($request['request_id']) ?>" class="btn btn-success py-1.5 px-4 text-sm font-medium">Create Voucher</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                
                <!-- Vouchers Tab Content -->
                <div id="vouchers-tab" class="p-6 hidden">
                    <!-- Create New Voucher Button -->
                    <div class="mb-6 flex justify-end">
                        <a href="index.php?action=select_voucher_type" class="btn btn-success flex items-center gap-2">
                            <i class="fas fa-plus"></i> Create New Voucher
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <?php if (empty($vouchers)): ?>
                            <div class="text-center py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div class="text-gray-500">No vouchers found.</div>
                            </div>
                        <?php else: foreach ($vouchers as $voucher): ?>
                            <?php 
                            // Get the associated request
                            $request = $requestModel->get_request_by_id($voucher['request_id']);
                            if (!$request) continue;
                            
                            // Determine finance status class
                            $financeStatusClass = '';
                            if ($voucher['finance_status'] === 'pending') {
                                $financeStatusClass = 'badge-pending';
                            } elseif ($voucher['finance_status'] === 'approved') {
                                $financeStatusClass = 'badge-approved';
                            } elseif ($voucher['finance_status'] === 'rejected') {
                                $financeStatusClass = 'badge-rejected';
                            }
                            
                            // Determine ED status class
                            $edStatusClass = '';
                            if ($voucher['ed_status'] === 'pending') {
                                $edStatusClass = 'badge-pending';
                            } elseif ($voucher['ed_status'] === 'approved') {
                                $edStatusClass = 'badge-approved';
                            } elseif ($voucher['ed_status'] === 'rejected') {
                                $edStatusClass = 'badge-rejected';
                            }
                            
                            // Check for unread messages
                            $has_unread = $voucherModel->has_unread_messages($voucher['voucher_id'], $user['user_id']);
                            ?>
                            <div class="request-card bg-gray-50 rounded-lg shadow-md overflow-hidden border border-gray-200 voucher-item hover:shadow-lg transition-shadow duration-200">
                                <div class="p-5">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                                <?php if ($voucher['voucher_type'] === 'petty_cash'): ?>
                                                    <i class="fas fa-money-bill-wave text-green-600"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-credit-card text-blue-600"></i>
                                                <?php endif; ?>
                                                PV #<?= e($voucher['pv_no']) ?>
                                                <?php if ($has_unread): ?>
                                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full flex items-center gap-1">
                                                    <i class="fas fa-envelope-open-text text-xs"></i> New Message
                                                </span>
                                                <?php endif; ?>
                                            </h3>
                                            <div class="flex items-center gap-2 mb-3">
                                                <span class="text-sm text-gray-600">Voucher #<?= e($voucher['voucher_id']) ?></span>
                                                <span class="text-sm text-gray-600">•</span>
                                                <span class="text-sm text-gray-600">Request #<?= e($voucher['request_id']) ?></span>
                                                <span class="text-sm text-gray-600">•</span>
                                                <span class="text-sm text-gray-600"><?= e($request['request_type']) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex flex-col items-end gap-1">
                                                <span class="text-xs text-gray-500">Finance:</span>
                                                <span class="badge <?= $financeStatusClass ?> flex items-center gap-1">
                                                    <?php if ($voucher['finance_status'] === 'approved'): ?>
                                                        <i class="fas fa-check-circle text-green-600"></i>
                                                    <?php elseif ($voucher['finance_status'] === 'rejected'): ?>
                                                        <i class="fas fa-times-circle text-red-600"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock text-yellow-600"></i>
                                                    <?php endif; ?>
                                                    <?= e($voucher['finance_status']) ?>
                                                </span>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                <span class="text-xs text-gray-500">ED:</span>
                                                <span class="badge <?= $edStatusClass ?> flex items-center gap-1">
                                                    <?php if ($voucher['ed_status'] === 'approved'): ?>
                                                        <i class="fas fa-check-circle text-green-600"></i>
                                                    <?php elseif ($voucher['ed_status'] === 'rejected'): ?>
                                                        <i class="fas fa-times-circle text-red-600"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock text-yellow-600"></i>
                                                    <?php endif; ?>
                                                    <?= e($voucher['ed_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Payee</p>
                                            <p class="text-gray-700 font-medium"><?= e($voucher['payee_name']) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Amount</p>
                                            <p class="text-gray-700 font-medium font-mono">TShs <?= number_format($voucher['amount'], 2) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Created</p>
                                            <p class="text-gray-700"><?= date('M d, Y', strtotime($voucher['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-gray-200 pt-3 mt-2">
                                        <div>
                                            <?php if ($voucher['finance_status'] === 'pending'): ?>
                                                <span class="text-sm text-yellow-600 font-medium"><i class="fas fa-exclamation-circle"></i> Needs your approval</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="index.php?action=view_voucher&id=<?= e($voucher['voucher_id']) ?>" class="btn btn-primary py-1.5 px-4 text-sm font-medium flex items-center gap-1">
                                                <i class="fas fa-eye"></i> View Voucher
                                            </a>
                                            <?php if ($voucher['finance_status'] === 'approved' && $voucher['ed_status'] === 'approved'): ?>
                                                <button onclick="window.open('index.php?action=view_voucher&id=<?= e($voucher['voucher_id']) ?>&print=true', '_blank')" class="btn btn-secondary py-1.5 px-4 text-sm font-medium flex items-center gap-1">
                                                    <i class="fas fa-print"></i> Print
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = {
            'requests': document.getElementById('requests-tab'),
            'vouchers': document.getElementById('vouchers-tab')
        };
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active', 'text-blue-600'));
                tabs.forEach(t => t.classList.add('text-gray-600'));
                
                // Add active class to clicked tab
                this.classList.add('active', 'text-blue-600');
                this.classList.remove('text-gray-600');
                
                // Hide all tab contents
                Object.values(tabContents).forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab content
                const target = this.getAttribute('data-target');
                tabContents[target].classList.remove('hidden');
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const requestItems = document.querySelectorAll('.request-item');
        const voucherItems = document.querySelectorAll('.voucher-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Search in requests
            requestItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Search in vouchers
            voucherItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>