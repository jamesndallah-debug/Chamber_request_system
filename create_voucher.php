<?php
// FILE: create_voucher.php
// Standalone voucher creation page

// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/Voucher.php';
require_once __DIR__ . '/voucher_functions.php';

// Check if user is logged in and is Finance
if (!isset($user) || $user['role_id'] != 5) {
    header('Location: index.php?action=dashboard');
    exit;
}

// Get voucher type from URL
$voucher_type = isset($_GET['type']) && $_GET['type'] == 'petty_cash' ? 'petty_cash' : 'payment';

// Create voucher model instance
if (!isset($voucherModel)) {
    $voucherModel = new VoucherModel($pdo);
}

// PV number will be entered by Finance user
$pv_no = '';

// Page title
$page_title = ($voucher_type == 'petty_cash') ? 'Create Petty Cash Voucher' : 'Create Payment Voucher';
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
            min-height: 100vh;
        }
        
        /* Form styling */
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
            color: #1f2937;
            background-color: #ffffff;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control:read-only {
            background-color: #f9fafb;
            color: #6b7280;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-payment {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
        }
        
        .badge-petty-cash {
            background: rgba(34, 197, 94, 0.1);
            color: #15803d;
        }
        
        .required-text {
            font-weight: 700;
            color: #1f2937;
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <?php if ($voucher_type == 'petty_cash'): ?>
                        <i class="fas fa-money-bill-wave text-6xl text-green-400 mr-4"></i>
                        <div class="badge badge-petty-cash">Petty Cash Voucher</div>
                    <?php else: ?>
                        <i class="fas fa-credit-card text-6xl text-blue-400 mr-4"></i>
                        <div class="badge badge-payment">Payment Voucher</div>
                    <?php endif; ?>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2"><?= e($page_title) ?></h1>
                <p class="text-gray-300">Complete all required fields to create the voucher</p>
            </div>
            
            <!-- Voucher Form -->
            <form action="index.php?action=create_voucher" method="post" class="space-y-6">
                <input type="hidden" name="voucher_type" value="<?= e($voucher_type) ?>">
                <input type="hidden" name="prepared_by" value="<?= e($user['user_id']) ?>">
                
                <!-- Basic Details -->
                <div class="form-section p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                        Basic Details
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">PV No: <span class="text-red-500">*</span></label>
                            <input type="text" name="pv_no" value="<?= e($pv_no) ?>" class="form-control" placeholder="Enter PV Number (e.g., PV-202501-001)" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date: <span class="text-red-500">*</span></label>
                            <input type="date" name="date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="form-section p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-money-check-alt text-green-500 mr-2"></i>
                        Payment Information
                    </h2>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Activity: <span class="text-red-500">*</span></label>
                            <input type="text" name="activity" class="form-control" placeholder="Enter activity description" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name of Payee: <span class="text-red-500">*</span></label>
                            <input type="text" name="payee_name" class="form-control" placeholder="Enter full name of recipient" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Budget Post or Code: <span class="text-red-500">*</span></label>
                            <input type="text" name="budget_code" class="form-control" placeholder="Enter budget code" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Particulars of Payment: <span class="text-red-500">*</span></label>
                            <textarea name="particulars" rows="3" class="form-control" placeholder="Enter detailed payment description" required></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Amount Information -->
                <div class="form-section p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calculator text-purple-500 mr-2"></i>
                        Amount Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TShs with cents (if available): <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" id="amount" step="0.01" min="0" class="form-control" placeholder="0.00" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total: <span class="text-red-500">*</span></label>
                            <input type="number" name="total" id="total" step="0.01" min="0" class="form-control" placeholder="0.00" readonly>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount in Words: <span class="text-red-500">*</span></label>
                        <input type="text" name="amount_words" class="form-control" placeholder="Enter amount in words" required>
                    </div>
                </div>
                
                <!-- Required Text Display -->
                <div class="form-section p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stamp text-red-500 mr-2"></i>
                        Certification
                    </h2>
                    
                    <div class="required-text">
                        <p class="mb-2">
                            <strong>Paid by: <?= $voucher_type == 'payment' ? 'Bank transfer' : 'Cash' ?></strong>
                        </p>
                        <p>
                            This is to certify that the payment have been approved and Authorized for the sum of 
                            <span class="font-bold">TShs: <span id="display-amount">0.00</span></span>
                        </p>
                    </div>
                </div>
                
                <!-- Approval Information -->
                <div class="form-section p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-check text-indigo-500 mr-2"></i>
                        Approval Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Prepared by (Accountant):</p>
                            <p class="font-medium text-gray-800"><?= e($user['fullname']) ?></p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Approved by (FM):</p>
                            <p class="text-yellow-600 font-medium">Pending Approval</p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-6">
                    <button type="button" onclick="window.close()" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Create Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-sync amount and total fields
        document.getElementById('amount').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            document.getElementById('total').value = amount.toFixed(2);
            document.getElementById('display-amount').textContent = amount.toFixed(2);
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#ef4444';
                    isValid = false;
                } else {
                    field.style.borderColor = '#d1d5db';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to create this voucher?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Auto-close after successful creation
        <?php if (isset($_SESSION['voucher_created'])): ?>
            alert('Voucher created successfully!');
            <?php unset($_SESSION['voucher_created']); ?>
            window.close();
        <?php endif; ?>
    </script>
</body>
</html>
