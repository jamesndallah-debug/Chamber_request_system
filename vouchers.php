<?php
// FILE: vouchers.php
// Vouchers listing page - only accessible to Finance and ED roles

// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Ensure user is logged in and has appropriate role
if (!isset($user) || !in_array($user['role_id'], [4, 5])) { // ED or Finance only
    header('Location: index.php?action=dashboard');
    exit;
}

// Include voucher model and functions
require_once 'Voucher.php';
require_once 'voucher_functions.php';

// Create voucher model instance
$voucherModel = new VoucherModel($pdo);

// Get vouchers for this user's role
$vouchers = $voucherModel->get_vouchers_for_role($user['role_id'], $user['user_id']);

// Get unread message counts
$unread_counts = [];
foreach ($vouchers as $voucher) {
    $unread_counts[$voucher['voucher_id']] = $voucherModel->has_unread_messages($voucher['voucher_id'], $user['user_id']) ? 1 : 0;
}

// Count vouchers by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($vouchers as $voucher) {
    $status = $user['role_id'] == 4 ? $voucher['ed_status'] : $voucher['finance_status'];
    
    if ($status == 'pending') {
        $pending_count++;
    } elseif ($status == 'approved') {
        $approved_count++;
    } elseif ($status == 'rejected') {
        $rejected_count++;
    }
}

// Page title
$page_title = 'Vouchers Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Chamber System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            z-index: -1;
        }
        
        .orb-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            top: -100px;
            left: -100px;
            animation: float1 15s ease-in-out infinite;
        }
        
        .orb-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            bottom: -50px;
            right: -50px;
            animation: float2 18s ease-in-out infinite;
        }
        
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(50px, 50px); }
        }
        
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-50px, -30px); }
        }
        
        /* Glass effect */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Status filter styling */
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
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status-filter button.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .status-filter button:not(.active) {
            color: #e2e8f0;
        }
        .status-filter button:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
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
        
        /* Search container */
        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-container input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .search-container input:focus {
            outline: none;
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        
        .search-container .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Title gradient */
        .title-gradient {
            background: linear-gradient(to right, #60a5fa, #34d399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Chip styling */
        .chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #60a5fa;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <!-- Animated background orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold title-gradient">
                            Vouchers Management <span class="chip">💳</span>
                        </h1>
                        <?php if ($user['role_id'] == 5): // Only Finance can create vouchers ?>
                        <a href="index.php?action=select_voucher_type" class="btn btn-primary" target="_blank">
                            <i class="fas fa-plus mr-2"></i> Create Voucher
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="mb-4 search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="w-full" placeholder="Search vouchers by PV number, request, amount...">
                    </div>
                    
                    <div class="status-filter">
                        <button class="filter-btn active" data-status="all">
                            All <span class="status-count"><?= count($vouchers) ?></span>
                        </button>
                        <button class="filter-btn" data-status="pending">
                            Pending <span class="status-count"><?= $pending_count ?></span>
                        </button>
                        <button class="filter-btn" data-status="approved">
                            Approved <span class="status-count"><?= $approved_count ?></span>
                        </button>
                        <button class="filter-btn" data-status="rejected">
                            Rejected <span class="status-count"><?= $rejected_count ?></span>
                        </button>
                    </div>
                    
                    <?php if (empty($vouchers)): ?>
                    <div class="glass p-6 text-center">
                        <p class="text-gray-400">No vouchers found.</p>
                    </div>
                    <?php else: ?>
                    <div class="glass overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">PV No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Request</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    <?php foreach ($vouchers as $voucher): ?>
                                    <?php 
                                        $status = $user['role_id'] == 4 ? $voucher['ed_status'] : $voucher['finance_status'];
                                        $statusClass = '';
                                        if ($status == 'approved') {
                                            $statusClass = 'bg-green-100 text-green-800';
                                        } elseif ($status == 'rejected') {
                                            $statusClass = 'bg-red-100 text-red-800';
                                        } else {
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                        }
                                    ?>
                                    <tr class="hover:bg-white/10 voucher-row" data-status="<?= $status ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= e($voucher['pv_no']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                            <?= $voucher['voucher_type'] == 'petty_cash' ? 'Petty Cash' : 'Payment' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= date('d/m/Y', strtotime($voucher['date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= e($voucher['request_title']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= format_amount($voucher['amount']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                <?= ucfirst(e($status)) ?>
                                            </span>
                                            <?php if ($unread_counts[$voucher['voucher_id']] > 0): ?>
                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?= $unread_counts[$voucher['voucher_id']] ?> new
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="index.php?action=view_voucher&id=<?= e($voucher['voucher_id']) ?>" class="text-blue-400 hover:text-blue-300">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter vouchers by status
            const filterButtons = document.querySelectorAll('.filter-btn');
            const voucherRows = document.querySelectorAll('.voucher-row');
            const searchInput = document.getElementById('searchInput');
            
            // Function to filter vouchers based on status and search term
            function filterVouchers() {
                const activeStatus = document.querySelector('.filter-btn.active').getAttribute('data-status');
                const searchTerm = searchInput.value.toLowerCase();
                
                voucherRows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const rowText = row.textContent.toLowerCase();
                    const statusMatch = activeStatus === 'all' || rowStatus === activeStatus;
                    const searchMatch = searchTerm === '' || rowText.includes(searchTerm);
                    
                    if (statusMatch && searchMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            // Status filter event listeners
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Apply filters
                    filterVouchers();
                });
            });
            
            // Search input event listener
            searchInput.addEventListener('input', filterVouchers);
        });
    </script>
</body>
</html>