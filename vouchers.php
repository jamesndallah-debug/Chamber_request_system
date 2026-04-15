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

// Handle Excel export for Finance Manager
if (isset($_GET['export_excel']) && $user['role_id'] == 5) {
    exportVouchersToExcel($pdo, $user['user_id']);
    exit;
}

// Function to export vouchers to Excel
function exportVouchersToExcel($pdo, $user_id) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="vouchers_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Disable output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    echo '<Worksheet ss:Name="Vouchers">';
    
    // Table headers
    echo '<Table>';
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">PV No</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Voucher Type</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Date</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Request</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Amount</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Amount in Words</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Payee Name</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Budget Code</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Particulars</Data></Cell>';
    echo '<Cell><Data ss:Type="Number">Total Amount</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Payment Method</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Finance Status</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Finance Remark</Data></Cell>';
    echo '<Cell><Data ss:Type="String">ED Status</Data></Cell>';
    echo '<Cell><Data ss:Type="String">ED Remark</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Created At</Data></Cell>';
    echo '</Row>';
    
    try {
        // Get all vouchers for this Finance Manager
        $stmt = $pdo->prepare("
            SELECT v.*, r.request_id, r.title as request_title, r.request_type
            FROM vouchers v
            LEFT JOIN requests r ON v.request_id = r.request_id
            WHERE v.prepared_by = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($vouchers as $voucher) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($voucher['pv_no']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['voucher_type']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(date('Y-m-d', strtotime($voucher['date']))) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['request_title']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . htmlspecialchars(number_format($voucher['amount'], 2, '.', '')) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['amount_words']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['payee_name']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['budget_code']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['particulars']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . htmlspecialchars(number_format($voucher['total'], 2, '.', '')) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['payment_method']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst($voucher['finance_status'])) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['finance_remark']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst($voucher['ed_status'])) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($voucher['ed_remark']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(date('Y-m-d H:i', strtotime($voucher['created_at']))) . '</Data></Cell>';
            echo '</Row>';
        }
        
    } catch (Exception $e) {
        echo '<Row><Cell ss:Type="String" colspan="14">Error: ' . htmlspecialchars($e->getMessage()) . '</Cell></Row>';
    }
    
    echo '</Table>';
    echo '</Worksheet>';
    echo '</Workbook>';
    
    exit;
}

// Get vouchers for this user's role

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
            background-color: #f9fafb;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Glass effect */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
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
            font-weight: 400;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        .status-filter button.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            border-color: transparent;
        }
        .status-filter button:not(.active):hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }
        .status-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background-color: rgba(0, 0, 0, 0.1);
            margin-left: 8px;
            font-size: 12px;
            color: #1f2937;
        }
        .status-filter button.active .status-count {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        /* Search container */
        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-container input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #1f2937;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .search-container input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-container .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        /* Title gradient removed */
        .title-gradient { color:#1f2937; }
        
        /* Chip styling */
        .chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            background: #eff6ff;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 400;
            color: #3b82f6;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col ml-64">
            <!-- Top Nav -->
            <header class="bg-white/80 backdrop-blur border-b border-gray-200 sticky top-0 z-40 p-5 flex items-center justify-between text-gray-800 shadow-sm">
                <div class="flex items-center gap-4">
                    <a href="index.php?action=dashboard" class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="font-medium">Back</span>
                    </a>
                    <h1 class="text-2xl font-semibold">Vouchers Management <span class="chip">💳</span></h1>
                </div>
                <?php if ($user['role_id'] == 5): // Only Finance can create vouchers ?>
                <a href="index.php?action=select_voucher_type" class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg" target="_blank">
                    <i class="fas fa-plus mr-2"></i> Create Voucher
                </a>
                <?php endif; ?>
            </header>

            <main class="flex-1 p-6">
                    
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
                        <?php if ($user['role_id'] == 5): // Only Finance can export ?>
                            <button onclick="exportVouchersToExcel()" class="filter-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-color: transparent;">
                                📊 Export to Excel
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($vouchers)): ?>
                    <div class="glass p-6 text-center">
                        <p class="text-gray-500">No vouchers found.</p>
                    </div>
                    <?php else: ?>
                    <div class="glass overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PV No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
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
                                    <tr class="hover:bg-gray-50 voucher-row" data-status="<?= $status ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= e($voucher['pv_no']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $voucher['voucher_type'] == 'petty_cash' ? 'Petty Cash' : 'Payment' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($voucher['date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= e($voucher['request_title']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= format_amount($voucher['amount']) ?></td>
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
                                            <a href="index.php?action=view_voucher&id=<?= e($voucher['voucher_id']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
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
            
            // Excel export function for Finance Manager
            function exportVouchersToExcel() {
                window.location.href = 'vouchers.php?export_excel=1';
            }
        });
    </script>
</body>
</html>
