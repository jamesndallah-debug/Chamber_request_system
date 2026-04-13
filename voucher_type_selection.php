<?php
// FILE: voucher_type_selection.php
// Page for selecting voucher type (Payment or Petty Cash)

// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Ensure user is logged in and is Finance
if (!isset($user) || $user['role_id'] != 5) {
    header('Location: index.php?action=dashboard');
    exit;
}

// Page title
$page_title = 'Select Voucher Type';
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
        
        /* Title gradient removed */
        .title-gradient { color:#1f2937; }
        
        /* Voucher type card styling */
        .voucher-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .voucher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #e5e7eb;
        }
        
        .voucher-card.payment {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.05) 100%);
        }
        
        .voucher-card.petty-cash {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Orb removed -->
    
    <div class="min-h-screen">
        <!-- Back to Dashboard Button -->
        <div class="fixed top-4 left-4 z-50">
            <a href="index.php?action=dashboard" class="flex items-center gap-2 bg-white hover:bg-gray-50 text-blue-600 border border-gray-200 px-4 py-2 rounded-lg shadow-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="font-medium">Back to Dashboard</span>
            </a>
        </div>
        
        <div class="flex flex-col overflow-hidden pt-16">
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-8">
                        <h1 class="text-4xl font-bold title-gradient mb-4">
                            Create New Voucher
                        </h1>
                        <p class="text-gray-600 text-lg">
                            Select the type of voucher you want to create
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <!-- Payment Voucher Card -->
                        <div class="voucher-card payment glass p-8 text-center cursor-pointer" onclick="openVoucherForm('payment')">
                            <div class="mb-6">
                                <i class="fas fa-credit-card text-6xl text-blue-500"></i>
                            </div>
                            <h2 class="text-2xl font-bold mb-4 text-blue-600">Payment Voucher</h2>
                            <p class="text-gray-600 mb-6">
                                Create a voucher for bank transfers and larger payments. Includes all required fields with bank transfer payment method.
                            </p>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <div class="flex items-center justify-center mb-2">
                                    <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                                    <span class="font-semibold text-blue-600">Bank Transfer</span>
                                </div>
                                <p class="text-sm text-gray-600">Professional payment processing</p>
                            </div>
                        </div>
                        
                        <!-- Petty Cash Voucher Card -->
                        <div class="voucher-card petty-cash glass p-8 text-center cursor-pointer" onclick="openVoucherForm('petty_cash')">
                            <div class="mb-6">
                                <i class="fas fa-money-bill-wave text-6xl text-green-500"></i>
                            </div>
                            <h2 class="text-2xl font-semibold mb-4 text-green-600">Petty Cash Voucher</h2>
                            <p class="text-gray-600 mb-6">
                                Create a voucher for small cash payments and everyday expenses. Perfect for minor transactions.
                            </p>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                <div class="flex items-center justify-center mb-2">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span class="font-semibold text-green-600">Cash Payment</span>
                                </div>
                                <p class="text-sm text-gray-600">Quick cash transactions</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="index.php?action=vouchers" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Vouchers
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        function openVoucherForm(type) {
            // Open the voucher creation form in a new tab with the selected type
            window.open('index.php?action=create_voucher&type=' + type, '_blank');
        }
        
        // Add keyboard support
        document.addEventListener('keydown', function(e) {
            if (e.key === '1') {
                openVoucherForm('payment');
            } else if (e.key === '2') {
                openVoucherForm('petty_cash');
            } else if (e.key === 'Escape') {
                window.location.href = 'index.php?action=vouchers';
            }
        });
        
        // Add visual feedback for keyboard shortcuts
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.voucher-card');
            
            // Add keyboard shortcut hints
            cards[0].innerHTML += '<div class="absolute top-4 right-4 bg-blue-600 text-white text-xs px-2 py-1 rounded">Press 1</div>';
            cards[1].innerHTML += '<div class="absolute top-4 right-4 bg-green-600 text-white text-xs px-2 py-1 rounded">Press 2</div>';
            
            // Make cards relative positioned for shortcuts
            cards.forEach(card => card.style.position = 'relative');
        });
    </script>
</body>
</html>
