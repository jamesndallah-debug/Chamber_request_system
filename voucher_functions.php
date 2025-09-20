<?php
// FILE: voucher_functions.php
// Functions for handling voucher operations and UI

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/Voucher.php';

// Create voucher model instance
if (!isset($voucherModel) && isset($pdo)) {
    $voucherModel = new VoucherModel($pdo);
}

/**
 * Get ED user ID for voucher communication
 */
function get_ed_user_id($pdo) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role_id = 4 LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn() ?: null;
}

/**
 * Get Finance Manager user ID for voucher communication
 */
function get_finance_manager_id($pdo) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role_id = 5 LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn() ?: null;
}

/**
 * Create notification for voucher actions
 */
function create_voucher_notification($pdo, $user_id, $title, $message, $voucher_id = null) {
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, voucher_id) VALUES (?, ?, ?, ?)"; 
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $title, $message, $voucher_id]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Format currency amount
 */
function format_amount($amount) {
    return number_format((float)$amount, 2, '.', ',');
}

/**
 * Generate a unique PV number
 */
function generate_pv_number($pdo, $voucher_type) {
    $prefix = ($voucher_type == 'petty_cash') ? 'PC' : 'PV';
    $year = date('Y');
    $month = date('m');
    
    // Get the latest voucher number for this type and month
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(pv_no, '-', -1) AS UNSIGNED)) as max_num 
            FROM vouchers 
            WHERE voucher_type = ? 
            AND pv_no LIKE ?"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$voucher_type, "{$prefix}-{$year}{$month}-%"]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $next_num = 1;
    if ($result && $result['max_num']) {
        $next_num = (int)$result['max_num'] + 1;
    }
    
    return "{$prefix}-{$year}{$month}-{$next_num}";
}

/**
 * Render voucher form for creating new vouchers
 */
function render_voucher_form($request, $voucher_type) {
    global $pdo, $user;
    
    $pv_no = ''; // PV number will be entered manually by Finance
    $voucher_title = ($voucher_type == 'petty_cash') ? 'Petty Cash Voucher' : 'Payment Voucher';
    $voucher_icon = ($voucher_type == 'petty_cash') ? '<i class="fas fa-money-bill-wave text-green-500"></i>' : '<i class="fas fa-credit-card text-blue-500"></i>';
    
    // Format the current date as YYYY-MM-DD for the date input
    $current_date = date('Y-m-d');
    
    // Get the request amount as default
    $amount = $request['amount'] ?? 0;
    
    ob_start();
    ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-t-4 <?= ($voucher_type == 'petty_cash') ? 'border-green-500' : 'border-blue-500' ?>">
        <div class="flex items-center mb-6">
            <div class="text-3xl mr-3"><?= $voucher_icon ?></div>
            <h2 class="text-2xl font-bold"><?= e($voucher_title) ?></h2>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">Request Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Request:</span> 
                    <span class="font-medium"><?= e($request['title']) ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Type:</span> 
                    <span class="font-medium"><?= e($request['request_type']) ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Amount:</span> 
                    <span class="font-medium"><?= format_amount($request['amount']) ?> TShs</span>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-gray-700 mb-4">Amount Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Amount (TShs):</p>
                    <p class="font-medium text-lg"><?= format_amount($amount) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total (TShs):</p>
                    <p class="font-medium text-lg"><?= format_amount($amount) ?></p>
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-1">Amount in Words:</p>
                <div class="bg-white p-3 rounded border border-gray-200">
                    <p class="font-medium italic">To be specified</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-gray-700 mb-4">Approval Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Prepared By:</p>
                    <p class="font-medium"><?= e($user['fullname']) ?></p>
                    <p class="text-xs text-gray-500 mt-1">Accountant</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Finance Status:</p>
                    <div><?= render_status_badge('pending') ?></div>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-sm text-gray-600 mb-1">ED Status:</p>
                <div class="flex items-center">
                    <div><?= render_status_badge('pending') ?></div>
                </div>
            </div>
        </div>
        
        <!-- ED Approval Form (Hidden by default) -->
        <div id="approvalForm" class="hidden bg-white p-4 rounded-lg border border-gray-300 mb-6">
            <h3 class="font-semibold text-gray-700 mb-4" id="approvalFormTitle">Approve Voucher</h3>
            
            <form action="index.php?action=update_voucher_status" method="post" class="space-y-4">
                <input type="hidden" name="voucher_id" value="0">
                <input type="hidden" name="status" id="approvalStatus" value="approved">
                <input type="hidden" name="role" value="ed">  <!-- Add role parameter for ED approval -->
                
                <div>
                    <label for="remark" class="block text-sm font-medium text-gray-700 mb-1">Remark (Optional)</label>
                    <textarea name="remark" id="remark" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideApprovalForm()" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Submit
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Voucher Messages Section -->
        <?php if ($user['role_id'] == 5 || $user['role_id'] == 2): // Finance or ED ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="font-semibold text-gray-700 mb-4">Messages</h3>
            
            <div id="messages-container" class="mb-4 max-h-64 overflow-y-auto">
                <p class="text-gray-500 text-center py-4">No messages yet</p>
            </div>
            
            <form action="index.php?action=send_voucher_message" method="post" class="space-y-4">
                <input type="hidden" name="voucher_id" value="0">
                <input type="hidden" name="recipient_id" value="<?= ($user['role_id'] == 5) ? get_ed_user_id($pdo) : get_finance_manager_id($pdo) ?>">
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Send Message</label>
                    <textarea name="message" id="message" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Type your message here..."></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Send
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <script>
        function showApprovalForm(action) {
            const form = document.getElementById('approvalForm');
            const title = document.getElementById('approvalFormTitle');
            const status = document.getElementById('approvalStatus');
            
            if (action === 'approve') {
                title.textContent = 'Approve Voucher';
                status.value = 'approved';
            } else {
                title.textContent = 'Reject Voucher';
                status.value = 'rejected';
            }
            
            form.classList.remove('hidden');
        }
        
        function hideApprovalForm() {
            document.getElementById('approvalForm').classList.add('hidden');
        }
        
        function printVoucher() {
            window.print();
        }
        </script>
        
        <form action="index.php?action=create_voucher" method="post" class="space-y-6">
            <input type="hidden" name="request_id" value="<?= e($request['request_id']) ?>">
            <input type="hidden" name="voucher_type" value="<?= e($voucher_type) ?>">
            <input type="hidden" name="prepared_by" value="<?= e($user['user_id']) ?>">
            
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Voucher Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="pv_no" class="block text-sm font-medium text-gray-700 mb-1">PV No: <span class="text-red-500">*</span></label>
                        <input type="text" id="pv_no" name="pv_no" value="<?= e($pv_no) ?>" class="form-control" placeholder="Enter PV Number (e.g., PV-202501-001)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date:</label>
                        <input type="date" id="date" name="date" value="<?= e($current_date) ?>" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Payment Information</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Paid by:</label>
                        <div class="font-bold text-blue-700">
                            <?php if ($voucher_type == 'payment'): ?>
                            Bank Transfer
                            <?php else: ?>
                            Cash
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="payment_method" value="<?= $voucher_type == 'payment' ? 'Bank Transfer' : 'Cash' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="activity" class="block text-sm font-medium text-gray-700 mb-1">Activity:</label>
                        <input type="text" id="activity" name="activity" class="form-control" placeholder="Enter activity description" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payee_name" class="block text-sm font-medium text-gray-700 mb-1">Name of Payee:</label>
                        <input type="text" id="payee_name" name="payee_name" class="form-control" placeholder="Enter full name of recipient" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_code" class="block text-sm font-medium text-gray-700 mb-1">Budget Post/Code:</label>
                        <input type="text" id="budget_code" name="budget_code" class="form-control" placeholder="Enter budget code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="particulars" class="block text-sm font-medium text-gray-700 mb-1">Particulars of Payments:</label>
                        <textarea id="particulars" name="particulars" rows="3" class="form-control" placeholder="Enter payment details" required></textarea>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Amount Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">TShs:</label>
                        <input type="number" id="amount" name="amount" value="<?= e($amount) ?>" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="total" class="block text-sm font-medium text-gray-700 mb-1">Total (TShs):</label>
                        <input type="number" id="total" name="total" value="<?= e($amount) ?>" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group col-span-2">
                        <label for="amount_words" class="block text-sm font-medium text-gray-700 mb-1">TShs in Words:</label>
                        <input type="text" id="amount_words" name="amount_words" class="form-control" placeholder="Enter amount in words" required>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium">This is to certify that the payment have been approved and Authorized for the sum of <span class="font-bold">TShs: <span id="display-amount"><?= e($amount) ?></span></span></p>
                </div>
                
                <script>
                // Update the displayed amount when the amount field changes
                document.getElementById('amount').addEventListener('input', function() {
                    document.getElementById('display-amount').textContent = this.value;
                    document.getElementById('total').value = this.value;
                });
                </script>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Approval Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prepared by (Accountant):</label>
                        <p class="form-control bg-gray-50 py-2"><?= e($user['fullname']) ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approved by (FM):</label>
                        <p class="form-control bg-gray-50 py-2 text-yellow-600">Pending</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-between">
                <a href="index.php?action=view_request&id=<?= e($request['request_id']) ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Request
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Create Voucher
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render voucher details for viewing
 */
function render_voucher_details($voucher = null, $can_approve = false) {
    global $pdo, $user;
    
    // Check if voucher is provided
    if (!$voucher) {
        return '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">Voucher not found</div>';
    }
    
    $voucher_title = ($voucher['voucher_type'] == 'petty_cash') ? 'Petty Cash Voucher' : 'Payment Voucher';
    $voucher_icon = ($voucher['voucher_type'] == 'petty_cash') ? '<i class="fas fa-money-bill-wave text-green-500"></i>' : '<i class="fas fa-credit-card text-blue-500"></i>';
    
    // Set default can_approve if not provided
    if (!isset($can_approve)) {
        $can_approve = false;
    }
    
    ob_start();
    ?>
    <!-- Voucher Basic Information -->
    <div class="voucher-card voucher-container mb-6">
        <div class="section-header">
            <i class="fas <?= ($voucher['voucher_type'] == 'petty_cash') ? 'fa-money-bill-wave' : 'fa-credit-card' ?> text-xl"></i>
            Basic Voucher Information
        </div>
        <div class="section-content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">PV Number</div>
                    <div class="info-value"><?= e($voucher['pv_no']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($voucher['date'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Voucher Type</div>
                    <div class="info-value">
                        <span class="voucher-type-badge <?= ($voucher['voucher_type'] == 'petty_cash') ? 'voucher-type-petty' : 'voucher-type-payment' ?>">
                            <i class="fas <?= ($voucher['voucher_type'] == 'petty_cash') ? 'fa-money-bill-wave' : 'fa-credit-card' ?>"></i>
                            <?= ucfirst(str_replace('_', ' ', $voucher['voucher_type'])) ?> Voucher
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Prepared By</div>
                    <div class="info-value">
                        <?= e($voucher['prepared_by_name']) ?>
                        <div class="text-sm text-gray-600 mt-1"><?= date('d/m/Y H:i', strtotime($voucher['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Information -->
    <div class="voucher-card mb-6">
        <div class="section-header">
            <i class="fas fa-money-check-alt text-xl"></i>
            Payment Information
        </div>
        <div class="section-content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Activity</div>
                    <div class="info-value"><?= e($voucher['activity']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payee Name</div>
                    <div class="info-value"><?= e($voucher['payee_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Budget Post/Code</div>
                    <div class="info-value"><?= e($voucher['budget_code']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">
                        <span class="status-badge status-approved">
                            <i class="fas fa-university"></i>
                            <?= $voucher['voucher_type'] == 'petty_cash' ? 'Cash Payment' : 'Bank Transfer' ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div class="info-label">Particulars of Payment</div>
                <div class="info-item mt-2">
                    <div class="info-value"><?= nl2br(e($voucher['particulars'])) ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Amount Information -->
    <div class="voucher-card mb-6">
        <div class="section-header">
            <i class="fas fa-calculator text-xl"></i>
            Amount Information
        </div>
        <div class="section-content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Amount (TShs)</div>
                    <div class="info-value amount-highlight">TShs <?= format_amount($voucher['amount']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total (TShs)</div>
                    <div class="info-value amount-highlight">TShs <?= format_amount($voucher['total']) ?></div>
                </div>
            </div>
            <div class="mt-6">
                <div class="info-label">Amount in Words</div>
                <div class="info-item mt-2">
                    <div class="info-value" style="font-style: italic; text-transform: capitalize;">
                        <?= e($voucher['amount_words'] ?: 'Amount in words not specified') ?> Only
                    </div>
                </div>
            </div>
            
            <!-- Certification Section -->
            <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-l-4 border-blue-500">
                <div class="flex items-start gap-3">
                    <i class="fas fa-certificate text-blue-600 text-2xl mt-1"></i>
                    <div>
                        <h4 class="font-bold text-blue-900 mb-2">Payment Certification</h4>
                        <p class="text-blue-800 leading-relaxed">
                            This is to certify that the payment has been approved and authorized for the sum of 
                            <span class="font-bold text-green-700">TShs <?= format_amount($voucher['total']) ?></span>
                            (<?= e($voucher['amount_words'] ?: 'Amount in words not specified') ?>)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Approval Information -->
    <div class="voucher-card mb-6">
        <div class="section-header">
            <i class="fas fa-user-check text-xl"></i>
            Approval Status & Information
        </div>
        <div class="section-content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Finance Status</div>
                    <div class="info-value">
                        <?php 
                        $financeStatusClass = $voucher['finance_status'] == 'approved' ? 'status-approved' : ($voucher['finance_status'] == 'rejected' ? 'status-rejected' : 'status-pending');
                        $financeStatusIcon = $voucher['finance_status'] == 'approved' ? 'fa-check-circle' : ($voucher['finance_status'] == 'rejected' ? 'fa-times-circle' : 'fa-clock');
                        ?>
                        <span class="status-badge <?= $financeStatusClass ?>">
                            <i class="fas <?= $financeStatusIcon ?>"></i>
                            <?= ucfirst($voucher['finance_status']) ?>
                        </span>
                        <?php if ($voucher['finance_approved_at']): ?>
                            <div class="text-sm text-gray-600 mt-2">On: <?= date('d/m/Y H:i', strtotime($voucher['finance_approved_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($voucher['finance_remark']): ?>
                            <div class="mt-3 p-3 bg-gray-100 rounded-lg">
                                <div class="info-label text-xs">Finance Remark</div>
                                <div class="text-sm mt-1"><?= nl2br(e($voucher['finance_remark'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Executive Director Status</div>
                    <div class="info-value">
                        <?php 
                        $edStatusClass = $voucher['ed_status'] == 'approved' ? 'status-approved' : ($voucher['ed_status'] == 'rejected' ? 'status-rejected' : 'status-pending');
                        $edStatusIcon = $voucher['ed_status'] == 'approved' ? 'fa-check-circle' : ($voucher['ed_status'] == 'rejected' ? 'fa-times-circle' : 'fa-clock');
                        ?>
                        <span class="status-badge <?= $edStatusClass ?>">
                            <i class="fas <?= $edStatusIcon ?>"></i>
                            <?= ucfirst($voucher['ed_status']) ?>
                        </span>
                        <?php if ($voucher['ed_approved_at']): ?>
                            <div class="text-sm text-gray-600 mt-2">On: <?= date('d/m/Y H:i', strtotime($voucher['ed_approved_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($voucher['ed_remark']): ?>
                            <div class="mt-3 p-3 bg-gray-100 rounded-lg">
                                <div class="info-label text-xs">ED Remark</div>
                                <div class="text-sm mt-1"><?= nl2br(e($voucher['ed_remark'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($can_approve && $user['role_id'] == 4 && $voucher['ed_status'] == 'pending'): ?>
    <!-- ED Approval Form -->
    <div class="voucher-card no-print">
        <div class="section-header">
            <i class="fas fa-crown text-xl"></i>
            Executive Director Action Required
        </div>
        <div class="section-content">
            <form action="index.php?action=update_voucher_status" method="post" class="space-y-6">
                <input type="hidden" name="voucher_id" value="<?= e($voucher['voucher_id']) ?>">
                <input type="hidden" name="role" value="ed">
                
                <div>
                    <div class="info-label">Remark (Optional)</div>
                    <textarea id="ed_remark" name="remark" rows="4" class="form-control mt-2" placeholder="Add your comments or feedback here..."></textarea>
                </div>
                
                <div class="flex space-x-4 pt-4">
                    <button type="submit" name="status" value="approved" class="btn btn-success flex-1">
                        <i class="fas fa-check"></i> Approve Voucher
                    </button>
                    <button type="submit" name="status" value="rejected" class="btn btn-danger flex-1">
                        <i class="fas fa-times"></i> Reject Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($can_approve && $user['role_id'] == 5 && $voucher['finance_status'] == 'pending'): ?>
    <!-- Finance Approval Form -->
    <div class="voucher-card no-print">
        <div class="section-header">
            <i class="fas fa-calculator text-xl"></i>
            Finance Manager Action Required
        </div>
        <div class="section-content">
            <form action="index.php?action=update_voucher_status" method="post" class="space-y-6">
                <input type="hidden" name="voucher_id" value="<?= e($voucher['voucher_id']) ?>">
                <input type="hidden" name="role" value="finance">
                
                <div>
                    <div class="info-label">Remark (Optional)</div>
                    <textarea id="finance_remark" name="remark" rows="4" class="form-control mt-2" placeholder="Add your comments or feedback here..."></textarea>
                </div>
                
                <div class="flex space-x-4 pt-4">
                    <button type="submit" name="status" value="approved" class="btn btn-success flex-1">
                        <i class="fas fa-check"></i> Approve Voucher
                    </button>
                    <button type="submit" name="status" value="rejected" class="btn btn-danger flex-1">
                        <i class="fas fa-times"></i> Reject Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render voucher messages between Finance and ED
 */
function render_voucher_messages($voucher_id) {
    global $pdo, $user, $voucherModel;
    
    // Mark messages as read
    $voucherModel->mark_messages_as_read($voucher_id, $user['user_id']);
    
    // Get messages for this voucher
    $messages = $voucherModel->get_voucher_messages($voucher_id);
    
    // Get ED and Finance user IDs for the message form
    $ed_id = get_ed_user_id($pdo);
    $finance_id = get_finance_manager_id($pdo);
    
    // Determine recipient based on user role using model method
    $recipient_id = $voucherModel->get_voucher_communication_recipient($user['role_id']);
    
    // Check if user can communicate about vouchers
    $can_communicate = $voucherModel->can_communicate_about_voucher($user['role_id'], $voucher_id);
    
    ob_start();
    ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6 no-print max-w-4xl mx-auto">
        <h2 class="text-xl font-bold mb-4 text-center">Communication</h2>
        
        <?php if (empty($messages)): ?>
            <p class="text-gray-500 italic text-center">No messages yet.</p>
        <?php else: ?>
            <div class="space-y-4 mb-6">
                <?php foreach ($messages as $message): ?>
                    <div class="p-3 rounded-lg <?= $message['sender_id'] == $user['user_id'] ? 'bg-blue-100 ml-8' : 'bg-gray-100 mr-8' ?>">
                        <div class="flex justify-between items-start">
                            <p class="font-medium"><?= e($message['sender_name']) ?> 
                                <?php if ($message['recipient_id']): ?>
                                    <span class="text-gray-500 text-sm">to <?= e($message['recipient_name']) ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></p>
                        </div>
                        <p class="mt-1"><?= nl2br(e($message['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($can_communicate && $recipient_id): ?>
            <form action="index.php?action=send_voucher_message" method="post" class="space-y-4">
                <input type="hidden" name="voucher_id" value="<?= isset($voucher_id) ? e($voucher_id) : 0 ?>">
                <input type="hidden" name="recipient_id" value="<?= isset($recipient_id) ? e($recipient_id) : 0 ?>">
                
                <div class="form-group">
                    <label for="message" class="block text-sm font-medium text-gray-700 text-center">New Message</label>
                    <textarea id="message" name="message" rows="3" class="form-control w-full" style="color: #1f2937; background-color: #ffffff;" required></textarea>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Send Message
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render voucher list for dashboard
 */
function render_voucher_list($vouchers, $show_actions = true) {
    ob_start();
    ?>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold">Vouchers</h2>
        </div>
        
        <?php if (empty($vouchers)): ?>
            <div class="p-6 text-center text-gray-500">
                <p>No vouchers found.</p>
            </div>
        <?php else: ?>
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
                            <?php if ($show_actions): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?= e($voucher['pv_no']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= $voucher['voucher_type'] == 'petty_cash' ? 'Petty Cash' : 'Payment' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($voucher['date'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= e($voucher['request_title']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= format_amount($voucher['amount']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= render_status_badge($voucher['ed_status']) ?>
                                </td>
                                <?php if ($show_actions): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="index.php?action=view_voucher&id=<?= e($voucher['voucher_id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render status badge for voucher status
 */
function render_status_badge($status) {
    $class = 'bg-yellow-100 text-yellow-800'; // Default for pending
    
    if ($status == 'approved') {
        $class = 'bg-green-100 text-green-800';
    } elseif ($status == 'rejected') {
        $class = 'bg-red-100 text-red-800';
    }
    
    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $class . '">' . 
           ucfirst($status) . '</span>';
}

/**
 * Render detailed status badges for finance and ED statuses
 */
function render_status_badges($finance_status, $ed_status) {
    $output = '<div class="flex flex-col space-y-1">';
    
    // Finance status badge
    $finance_class = 'bg-gray-100 text-gray-800'; // Default
    if ($finance_status == 'approved') {
        $finance_class = 'bg-green-100 text-green-800';
    } elseif ($finance_status == 'rejected') {
        $finance_class = 'bg-red-100 text-red-800';
    } elseif ($finance_status == 'pending') {
        $finance_class = 'bg-yellow-100 text-yellow-800';
    }
    
    $output .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $finance_class . '">';
    $output .= 'Finance: ' . ucfirst($finance_status);
    $output .= '</span>';
    
    // ED status badge
    $ed_class = 'bg-gray-100 text-gray-800'; // Default
    if ($ed_status == 'approved') {
        $ed_class = 'bg-green-100 text-green-800';
    } elseif ($ed_status == 'rejected') {
        $ed_class = 'bg-red-100 text-red-800';
    } elseif ($ed_status == 'pending') {
        $ed_class = 'bg-yellow-100 text-yellow-800';
    }
    
    $output .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $ed_class . '">';
    $output .= 'ED: ' . ucfirst($ed_status);
    $output .= '</span>';
    $output .= '</div>';
    
    return $output;
}