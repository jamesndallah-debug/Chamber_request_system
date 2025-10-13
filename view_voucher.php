<?php
// FILE: view_voucher.php
// View for displaying voucher details and handling voucher messages

// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/Voucher.php';
require_once __DIR__ . '/voucher_functions.php';

// Check if user is logged in and has appropriate role
if (!isset($user) || !$user || !in_array($user['role_id'], [4, 5])) { // ED or Finance only
    header('Location: index.php?action=dashboard');
    exit;
}

// Create voucher model instance
if (!isset($voucherModel)) {
    $voucherModel = new VoucherModel($pdo);
}

// Get voucher ID from URL
$voucher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get voucher details
$voucher = $voucherModel->get_voucher_by_id($voucher_id);

// Check if voucher exists
if (!$voucher) {
    header('Location: index.php?action=dashboard');
    exit;
}

// Determine if user can approve this voucher
$can_approve = false;
if ($user['role_id'] == 4 && $voucher['ed_status'] == 'pending') {
    $can_approve = true;
} elseif ($user['role_id'] == 5 && $voucher['finance_status'] == 'pending') {
    $can_approve = true;
}

// Get the related request
$request = null;
if ($voucher['request_id']) {
    $requestModel = new RequestModel($pdo);
    $request = $requestModel->get_request_by_id($voucher['request_id']);
}

// Get user names for prepared_by
$stmt = $pdo->prepare("SELECT fullname FROM users WHERE user_id = ?");
$stmt->execute([$voucher['prepared_by']]);
$voucher['prepared_by_name'] = $stmt->fetchColumn() ?: 'Unknown';

// Handle session messages
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

// Page title
$page_title = ($voucher['voucher_type'] == 'petty_cash') ? 'Petty Cash Voucher' : 'Payment Voucher';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> Details | Chamber System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            min-height: 100vh;
            color: #f8fafc;
        }
        
        /* Enhanced visibility styles */
        .voucher-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            color: #1e293b;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .voucher-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        .section-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-content {
            padding: 2rem;
            background: #ffffff;
            border-radius: 0 0 12px 12px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            transition: all 0.2s ease;
        }
        
        .info-item:hover {
            background: #f1f5f9;
            border-left-color: #1d4ed8;
            transform: translateX(4px);
        }
        
        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }
        
        .amount-highlight {
            font-size: 1.5rem;
            font-weight: 800;
            color: #059669;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #78350f;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #064e3b;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #7f1d1d;
        }
        
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(107, 114, 128, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.4);
        }
        
        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .voucher-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 1rem;
        }
        
        .voucher-type-payment {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .voucher-type-petty {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            color: #064e3b;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            color: #7f1d1d;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="container mx-auto px-4 py-8">
                    <!-- Enhanced Page Header -->
                    <div class="page-header">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="page-title"><?= e($page_title) ?> Details</h1>
                                <div class="voucher-type-badge <?= $voucher['voucher_type'] == 'petty_cash' ? 'voucher-type-petty' : 'voucher-type-payment' ?>">
                                    <?php if ($voucher['voucher_type'] == 'petty_cash'): ?>
                                        <i class="fas fa-money-bill-wave"></i>
                                        Petty Cash Voucher
                                    <?php else: ?>
                                        <i class="fas fa-credit-card"></i>
                                        Payment Voucher
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button onclick="printVoucher()" class="btn btn-primary no-print">
                                    <i class="fas fa-print"></i> Print Voucher
                                </button>
                                
                                <?php if ($request): ?>
                                <a href="index.php?action=view_request&id=<?= e($request['request_id']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Request
                                </a>
                                <?php else: ?>
                                <a href="index.php?action=dashboard" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
        
                    <?php if (isset($error)): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                        <span><?= e($error) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle text-xl"></i>
                        <span><?= e($success) ?></span>
                    </div>
                    <?php endif; ?>
        
                    <?php if ($request): ?>
                    <div class="voucher-card mb-6 related-request-section">
                        <div class="section-header">
                            <i class="fas fa-link text-xl"></i>
                            Related Request Information
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Request Title</div>
                                    <div class="info-value"><?= e($request['title']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Request Type</div>
                                    <div class="info-value"><?= e($request['request_type']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Request Status</div>
                                    <div class="info-value">
                                        <?php 
                                        $status = $request['ed_status'] ?? 'pending';
                                        $statusClass = $status == 'approved' ? 'status-approved' : ($status == 'rejected' ? 'status-rejected' : 'status-pending');
                                        $statusIcon = $status == 'approved' ? 'fa-check-circle' : ($status == 'rejected' ? 'fa-times-circle' : 'fa-clock');
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Request ID</div>
                                    <div class="info-value">#<?= e($request['request_id']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
        
        <!-- Voucher Details -->
        <div class="voucher-container">
            <?= render_voucher_details($voucher, $can_approve) ?>
        </div>
        
                    <!-- Voucher Messages -->
                    <div class="voucher-messages-section">
                        <?= render_voucher_messages($voucher_id) ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Print Styles -->
<style type="text/css" media="print">
    /* Hide non-printable elements */
    .no-print, .no-print * {
        display: none !important;
    }
    
    /* Hide sidebar, header, and footer */
    aside, header, footer, nav {
        display: none !important;
    }
    /* Hide the on-page header bar in print (title and voucher-type badge) */
    .page-header { display: none !important; }
    /* Also hide related request card and alerts in print */
    .related-request-section, .alert { display: none !important; }
    
    /* Reset body and main container */
    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 12pt;
        font-family: Arial, sans-serif !important;
    }
    
    .content-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Voucher container styling */
    .voucher-container {
        border: 2px solid #000;
        padding: 12px;
        max-width: 100%;
        margin: 0 auto;
        box-shadow: none !important;
    }
    
    /* Page settings */
    @page {
        size: A4;
        margin: 1.5cm;
    }
    
    /* Typography for print */
    h1, h2 {
        color: #000 !important;
    }

    /* Hide voucher messages in print */
    .voucher-messages-section { display: none !important; }

    /* Reduce internal paddings to fit one page */
    .section-content { padding: 8px !important; }
    .info-grid { gap: 8px !important; margin-bottom: 8px !important; }
    .info-item { padding: 8px !important; }
    .voucher-card { margin-bottom: 8px !important; }

    /* Voucher: force two-column layout (left and right) in print */
    .voucher-container { font-size: 11pt; }
    .voucher-container .info-grid,
    .voucher-container .grid,
    .voucher-container .md\:grid-cols-2,
    .voucher-container .md\:grid-cols-3 {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        column-gap: 12px !important;
        row-gap: 8px !important;
        width: 100% !important;
    }
    .voucher-container .info-item,
    .voucher-container .section-content > * {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    /* Remove CSS-injected TCCIA heading to prevent duplicates */
    .voucher-container:before { content: none !important; display: none !important; }
    
    /* Add voucher number watermark */
    .voucher-container:after {
        content: "Voucher #" attr(data-voucher-id);
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 48pt;
        color: rgba(0,0,0,0.05);
        transform: rotate(-45deg);
        z-index: -1;
    }
    
    /* Status badges for print */
    .status-badge {
        border: 1px solid #000;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
    }
    
    .status-approved {
        background-color: #e8f5e9 !important;
        color: #2e7d32 !important;
    }
    
    .status-rejected {
        background-color: #ffebee !important;
        color: #c62828 !important;
    }
    
    .status-pending {
        background-color: #fff8e1 !important;
        color: #f57f17 !important;
    }
    
    /* Approval section styling */
    .approval-info {
        margin-top: 30px;
        border-top: 1px solid #000;
        padding-top: 20px;
    }
    
    .signature-line {
        margin-top: 50px;
        border-top: 1px solid #000;
        width: 200px;
        display: inline-block;
        text-align: center;
        margin-right: 30px;
    }
    
    /* Grid layout improvements */
    .grid {
        display: grid !important;
    }
    
    .grid-cols-1 {
        grid-template-columns: 1fr !important;
    }
    
    .md\:grid-cols-2 {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .md\:grid-cols-3 {
        grid-template-columns: 1fr 1fr 1fr !important;
    }
</style>

<!-- Approval and Print Scripts -->
<script>
    // Functions for ED approval form
    function showApprovalForm(action) {
        const approvalForm = document.getElementById('approvalForm');
        const approvalFormTitle = document.getElementById('approvalFormTitle');
        const approvalStatus = document.getElementById('approvalStatus');
        
        if (action === 'approve') {
            approvalFormTitle.textContent = 'Approve Voucher';
            approvalStatus.value = 'approved';
        } else {
            approvalFormTitle.textContent = 'Reject Voucher';
            approvalStatus.value = 'rejected';
        }
        
        approvalForm.classList.remove('hidden');
        document.getElementById('remark').focus();
    }
    
    function hideApprovalForm() {
        document.getElementById('approvalForm').classList.add('hidden');
        document.getElementById('remark').value = '';
    }
    
    // Function for printing voucher
    function printVoucher() {
        // Ensure voucher ID attribute is set for watermark
        const voucherContainer = document.querySelector('.voucher-container');
        if (voucherContainer && !voucherContainer.hasAttribute('data-voucher-id')) {
            voucherContainer.setAttribute('data-voucher-id', '<?= $voucher["voucher_id"] ?>');
        }
        // Print without adding extra elements to keep within one A4 page
        window.print();
    }
</script>
</body>
</html>
