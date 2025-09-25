<?php
// FILE: index.php
// Main controller for the Chamber Request Management System.
// Handles routing and includes the correct view.

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/User.php';

// Get the requested action from the URL.
$action = $_GET['action'] ?? 'login';

// Get the current user from the session.
$user = current_user();

// Check authentication for all actions except login and register.
if (!$user && $action !== 'login' && $action !== 'register') {
    $action = 'login';
}

// Create instances of models
// $pdo is already defined in function.php which is included above
$requestModel = new RequestModel($pdo);
$userModel = new UserModel($pdo);

// Route based on the action.
// Define constant to prevent direct file access
define('ACCESS_ALLOWED', true);

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = $_POST['password'] ?? '';
            
            $user_data = $userModel->get_user_by_username($username);

            if ($user_data) {
                $storedHash = (string)($user_data['password'] ?? '');
                $passwordMatches = false;
                if ($storedHash !== '' && substr($storedHash, 0, 4) === '$2y$') {
                    $passwordMatches = password_verify($password, $storedHash);
                } else {
                    // Legacy/plaintext compatibility: match raw, then rehash
                    if (hash_equals($storedHash, $password)) {
                        $passwordMatches = true;
                        $rehash = password_hash($password, PASSWORD_BCRYPT);
                        if ($rehash) {
                            $userModel->update_password_hash($user_data['user_id'], $rehash);
                            $user_data['password'] = $rehash;
                        }
                    }
                }

                if ($passwordMatches) {
                    $_SESSION['user'] = $user_data;
                    // Ensure yearly caps exist for the authenticated user
                    ensure_leave_caps($pdo, (int)$user_data['user_id'], (int)date('Y'));
                    // Redirect to role-specific dashboard
                    $role = (int)$user_data['role_id'];
                    $dest = 'dashboard';
                    if ($role === 7) $dest = 'admin_management';
                    else if ($role === 1) $dest = 'employee_dashboard';
                    else if ($role === 3) $dest = 'hod_dashboard';
                    else if ($role === 2) $dest = 'hrm_dashboard';
                    else if ($role === 6) $dest = 'auditor_dashboard';
                    else if ($role === 5) $dest = 'finance_dashboard';
                    else if ($role === 4) $dest = 'ed_dashboard';
                    header('Location: index.php?action=' . $dest);
                    exit;
                }
            }

            // More explicit feedback for debugging
            if (!$user_data) {
                $error = "User not found.";
            } else {
                $error = "Wrong password.";
            }
            include __DIR__ . '/login.php';
        } else {
            include __DIR__ . '/login.php';
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/register.php';
        } else {
            include __DIR__ . '/register.php';
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    
    case 'dashboard':
        // Redirect generic dashboard to role-specific dashboard
        if ($user) {
            $role = (int)$user['role_id'];
            if ($role === 7) { header('Location: index.php?action=admin_management'); exit; }
            if ($role === 1) { header('Location: index.php?action=employee_dashboard'); exit; }
            if ($role === 3) { header('Location: index.php?action=hod_dashboard'); exit; }
            if ($role === 2) { header('Location: index.php?action=hrm_dashboard'); exit; }
            if ($role === 6) { header('Location: index.php?action=auditor_dashboard'); exit; }
            if ($role === 5) { header('Location: index.php?action=finance_dashboard'); exit; }
            if ($role === 4) { header('Location: index.php?action=ed_dashboard'); exit; }
        }
        include __DIR__ . '/dashboard.php';
        break;

    case 'upload_avatar':
        if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            // Server-side size limit: 2MB
            if ((int)$file['size'] > 2 * 1024 * 1024) {
                $_SESSION['flash'] = ['type'=>'error','message'=>'Image too large. Max size is 2MB.'];
                header('Location: index.php?action=dashboard');
                exit;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                // Basic image validation and dimension limits
                $dim = @getimagesize($file['tmp_name']);
                if (!$dim) {
                    $_SESSION['flash'] = ['type'=>'error','message'=>'Invalid image file.'];
                    header('Location: index.php?action=dashboard');
                    exit;
                }
                $w = (int)$dim[0]; $h = (int)$dim[1];
                if ($w < 64 || $h < 64) {
                    $_SESSION['flash'] = ['type'=>'error','message'=>'Image too small. Minimum 64x64.'];
                    header('Location: index.php?action=dashboard');
                    exit;
                }
                if ($w > 4000 || $h > 4000) {
                    $_SESSION['flash'] = ['type'=>'error','message'=>'Image too large. Maximum 4000x4000.'];
                    header('Location: index.php?action=dashboard');
                    exit;
                }
                $name = 'avatar_' . (int)$user['user_id'] . '_' . uniqid() . '.' . $ext;
                if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }
                $dest = UPLOAD_PATH . $name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // Optionally create a relative path only
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    $stmt->execute([$name, (int)$user['user_id']]);
                    $_SESSION['user']['profile_image'] = $name;
                    $_SESSION['flash'] = ['type'=>'success','message'=>'Profile photo updated.'];
                } else {
                    $_SESSION['flash'] = ['type'=>'error','message'=>'Failed to save the image.'];
                }
            } else {
                $_SESSION['flash'] = ['type'=>'error','message'=>'Invalid image type. Use JPG, PNG, or WEBP.'];
            }
        }
        header('Location: index.php?action=dashboard');
        exit;

    case 'mark_notification_read':
        if ($user) {
            $nid = (int)($_GET['id'] ?? 0);
            if ($nid > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$nid, (int)$user['user_id']]);
                } catch (Throwable $e) { /* ignore */ }
            }
        }
        header('Location: index.php?action=dashboard');
        exit;

    case 'mark_all_notifications_read':
        if ($user) {
            try {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
                $stmt->execute([(int)$user['user_id']]);
            } catch (Throwable $e) { /* ignore */ }
        }
        header('Location: index.php?action=dashboard');
        exit;
        
    case 'check_notifications':
        if ($user) {
            try {
                $stmtNc = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                $stmtNc->execute([(int)$user['user_id']]);
                $unreadCount = (int)$stmtNc->fetchColumn();
                
                // Get the latest notifications
                $stmtN = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                $stmtN->execute([(int)$user['user_id']]);
                $notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'unread' => $unreadCount,
                    'notifications' => $notifications
                ]);
                exit;
            } catch (Throwable $e) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Failed to check notifications']);
                exit;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['unread' => 0, 'notifications' => []]);
        exit;

    case 'employee_dashboard':
    case 'hod_dashboard':
    case 'hrm_dashboard':
    case 'auditor_dashboard':
    case 'finance_dashboard':
    case 'ed_dashboard':
        include __DIR__ . '/dashboard.php';
        break;
        
    case 'vouchers':
        // Only allow Finance and ED to access vouchers page
        if (!$user || !in_array($user['role_id'], [4, 5])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        include __DIR__ . '/vouchers.php';
        break;

    case 'new_request':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $details_json = $_POST['details_json'] ?? '';
            $request_type = $_POST['request_type'] ?? '';
            // Leave balance validation using actual balances where available
            $days = (int)($_POST['days_applied'] ?? 0);
            if (in_array($request_type, ['Annual leave','Compassionate leave','Paternity leave','Maternity leave'], true)) {
                if ($request_type !== 'Sick leave') {
                    try {
                        ensure_leave_caps($pdo, (int)$user['user_id'], (int)date('Y'));
                    } catch (Throwable $e) { /* ignore */ }
                    $available = null;
                    try {
                        $stmt = $pdo->prepare("SELECT balance_days FROM leave_balances WHERE user_id = ? AND leave_type = ? AND year = ?");
                        $stmt->execute([(int)$user['user_id'], $request_type, (int)date('Y')]);
                        $val = $stmt->fetchColumn();
                        if ($val !== false) { $available = (int)$val; }
                    } catch (Throwable $e) { /* ignore */ }
                    // Fallback caps if balance not found
                    if ($available === null) {
                        $fallbackCaps = [
                            'Annual leave' => 28,
                            'Compassionate leave' => 7,
                            'Paternity leave' => 3,
                            'Maternity leave' => 84,
                        ];
                        $available = $fallbackCaps[$request_type] ?? 0;
                    }
                    if ($days <= 0 || $days > $available) {
                        $error = "Days applied must be between 1 and " . $available . ".";
                    }
                }
            }
            $request_data = [
                'user_id' => $user['user_id'],
                'request_type' => $request_type,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'amount' => $_POST['amount'] ?? null,
                'attachment_path' => null,
                'details_json' => $details_json ?: null
            ];
            
            $upload_success = false;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['attachment']['name'];
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = uniqid('req_') . '.' . $file_ext;
                    $destination = UPLOAD_PATH . $new_file_name;
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $request_data['attachment_path'] = $new_file_name;
                        $upload_success = true;
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG, PDF, DOC, and DOCX are allowed.";
                }
            } else if ($_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = "File upload failed with error code: " . $_FILES['attachment']['error'];
            }
            
            if (!isset($error)) {
                $result = $requestModel->create_request($request_data);
                if ($result) {
                    // Workflow routing based on user role and request type
                    try {
                        $type = $request_data['request_type'];
                        $lastId = $pdo->lastInsertId();
                        $userRole = (int)$user['role_id'];
                        
                        // Get user department for HR and Administration workflow
                        $userDept = $user['department'] ?? '';
                        
                        if ($type === 'TCCIA retirement request') {
                            // Skip HOD, HRM, Auditor → Finance then ED
                            $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='approved', auditor_status='approved', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                            $stmt->execute([$lastId]);
                        } else if ($type === 'Salary advance') {
                            // Salary advance chain depends on requester:
                            // - Default (Employee/HOD/Auditor/Admin): HRM (pending) -> Finance (pending) -> ED (pending)
                            // - HRM requester: Finance (pending) -> ED (pending) [skip HRM]
                            // - Finance requester: HRM (pending) -> ED (pending) [skip Finance]
                            // HOD and Auditor stages are always skipped (auto-approved)
                            if ($userRole === 2) {
                                // HRM requester: skip HRM stage
                                $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='approved', auditor_status='approved', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                $stmt->execute([$lastId]);
                            } else if ($userRole === 5) {
                                // Finance requester: skip Finance stage
                                $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='approved', finance_status='approved', ed_status='pending' WHERE request_id = ?");
                                $stmt->execute([$lastId]);
                            } else {
                                // Everyone else: HRM then Finance then ED
                                $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='approved', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                $stmt->execute([$lastId]);
                            }
                        } else {
                            // Role-based workflow routing
                            switch ($userRole) {
                                case 1: // Employee
                                    if (strtolower($userDept) === 'hr and administration' || strtolower($userDept) === 'hr' || strtolower($userDept) === 'human resources') {
                                        // HR and Administration: HRM → Internal Auditor → Finance → ED
                                        $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='pending', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                        $stmt->execute([$lastId]);
                                    } else {
                                        // Other employees: Default workflow (HOD → HRM → Internal Auditor → Finance → ED)
                                        // Leave default status (all pending)
                                    }
                                    break;
                                    
                                case 2: // HRM
                                    // HRM → Internal Auditor → Finance → ED
                                    $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='approved', auditor_status='pending', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                    $stmt->execute([$lastId]);
                                    break;
                                    
                                case 3: // HOD
                                    // HOD → HRM → Internal Auditor → Finance → ED
                                    $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='pending', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                    $stmt->execute([$lastId]);
                                    break;
                                    
                                case 5: // Finance
                                    // Finance → HRM → Internal Auditor → Finance (self-approve) → ED
                                    $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='pending', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                    $stmt->execute([$lastId]);
                                    break;
                                    
                                case 6: // Internal Auditor
                                    // Internal Auditor → HRM → Finance → ED
                                    $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='approved', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                    $stmt->execute([$lastId]);
                                    break;
                                    
                                case 7: // Admin
                                    // Admin → HRM → Internal Auditor → Finance → ED
                                    $stmt = $pdo->prepare("UPDATE requests SET hod_status='approved', hrm_status='pending', auditor_status='pending', finance_status='pending', ed_status='pending' WHERE request_id = ?");
                                    $stmt->execute([$lastId]);
                                    break;
                                    
                                default:
                                    // Default workflow for other roles
                                    break;
                            }
                        }
                    } catch (Throwable $e) {
                        error_log('Post-create workflow init failed: ' . $e->getMessage());
                    }
                    header('Location: index.php?action=dashboard');
                    exit;
                } else {
                    $error = "Failed to create request. Please try again.";
                }
            }
        }
        include __DIR__ . '/new_request.php';
        break;

    case 'view_request':
        // Check if a request_id is provided.
        if (!isset($_GET['id'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $request_id = $_GET['id'];
        $request = $requestModel->get_request_by_id($request_id);
        
        // Ensure the request exists and is viewable by the user.
        if (!$request) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        // Enforce visibility rules for special request types
        if (!can_view_request((int)$user['role_id'], $user, $request)) {
            // Optionally set a flash message, then redirect
            $_SESSION['error'] = 'You do not have permission to view this request.';
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        include __DIR__ . '/view_request.php';
        break;

    case 'leave_balances':
        // Show leave balances for current user. Admin (7) can see all.
        include __DIR__ . '/leave_balances.php';
        break;
        
    case 'process_request':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $request_id = $_POST['id'] ?? null;
            $status = $_POST['status'] ?? null;
            $remark = $_POST['remark'] ?? null;
            
            if ($request_id && $status) {
                $request = $requestModel->get_request_by_id($request_id);
                if ($request && can_approve($user['role_id'], $request)) {
                    $result = $requestModel->update_status($request_id, $user['role_id'], $status, $remark);
                    if ($result) {
                        // Trigger confetti on next page load when ED gives final approval
                        if ((int)$user['role_id'] === 4 && $status === 'approved') {
                            $_SESSION['confetti'] = 'ed_approved';
                        }
                        header('Location: index.php?action=dashboard');
                        exit;
                    } else {
                        $error = "Failed to update request status.";
                        include __DIR__ . '/view_request.php';
                    }
                } else {
                    $error = "You do not have permission to perform this action.";
                    include __DIR__ . '/view_request.php';
                }
            }
        }
        header('Location: index.php?action=dashboard');
        exit;
    
    case 'admin_management':
        // Check if the user is an admin (role_id 7).
        if ($user['role_id'] != 7) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        // Handle admin POST actions (create/deactivate/broadcast)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // User creation is now handled in admin_dashboard_simple.php
            if (isset($_POST['admin_deactivate_user'])) {
                $uid = (int)($_POST['user_id'] ?? 0);
                if ($uid) {
                    try {
                        $ok = $userModel->deactivate_user($uid);
                        $_SESSION['flash'] = ['type' => $ok ? 'success' : 'error', 'message' => $ok ? 'User deactivated.' : 'Failed to deactivate user.'];
                    } catch (Throwable $e) {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deactivating user.'];
                    }
                }
            }
            if (isset($_POST['admin_broadcast'])) {
                // Placeholder for announcements; would insert into messages table if present
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Announcement sent.'];
            }
            header('Location: index.php?action=admin_management&tab=' . urlencode($_GET['tab'] ?? 'users'));
            exit;
        }
        // Include the admin management dashboard.
        include __DIR__ . '/admin_management.php';
        break;

    case 'select_voucher_type':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        // Only finance can access voucher creation
        if (!$user || $user['role_id'] != 5) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        include __DIR__ . '/voucher_type_selection.php';
        break;
        
    case 'create_voucher':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        // Only finance can access voucher creation
        if (!$user || $user['role_id'] != 5) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // If accessed via GET, direct to create_voucher.php if type is specified
            $request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
            $voucher_type = isset($_GET['type']) ? $_GET['type'] : '';
            
            // If voucher type is not specified, redirect to voucher type selection
            if (!in_array($voucher_type, ['payment', 'petty_cash'])) {
                header('Location: index.php?action=select_voucher_type' . ($request_id ? '&request_id=' . $request_id : ''));
                exit;
            }
            
            // If request_id is provided, check if it's valid
            if ($request_id > 0) {
                // Create request model instance if not exists
                if (!isset($requestModel)) {
                    require_once __DIR__ . '/Request.php';
                    $requestModel = new RequestModel($pdo);
                }
                
                // Create voucher model instance if not exists
                if (!isset($voucherModel)) {
                    require_once __DIR__ . '/Voucher.php';
                    $voucherModel = new VoucherModel($pdo);
                }
                
                // Check if request exists and is financial
                $request = $requestModel->get_request_by_id($request_id);
                if (!$request || !$voucherModel->is_financial_request($request['request_type'])) {
                    header('Location: index.php?action=dashboard');
                    exit;
                }
                
                // Check if voucher already exists for this request
                if ($voucherModel->voucher_exists_for_request($request_id)) {
                    header('Location: index.php?action=view_request&id=' . $request_id);
                    exit;
                }
            }
            
            // Include the create voucher page
            include __DIR__ . '/create_voucher.php';
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $user['role_id'] == 5) {
            // Only finance can create vouchers
            $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
            $voucher_type = $_POST['voucher_type'] ?? '';
            
            // Create voucher model instance if not exists
            if (!isset($voucherModel)) {
                require_once __DIR__ . '/Voucher.php';
                $voucherModel = new VoucherModel($pdo);
            }
            
            // For standalone vouchers (no request_id), we can proceed without checking request
            if ($request_id > 0) {
                // Check if request exists and is financial
                $request = $requestModel->get_request_by_id($request_id);
                if (!$request || !$voucherModel->is_financial_request($request['request_type'])) {
                    header('Location: index.php?action=dashboard');
                    exit;
                }
                
                // Check if voucher already exists for this request
                if ($voucherModel->voucher_exists_for_request($request_id)) {
                    header('Location: index.php?action=view_request&id=' . $request_id);
                    exit;
                }
            }
            
            // Prepare voucher data
            $voucher_data = [
                'request_id' => $request_id > 0 ? $request_id : null,
                'voucher_type' => $voucher_type,
                'pv_no' => $_POST['pv_no'] ?? '',
                'date' => $_POST['date'] ?? date('Y-m-d'),
                'activity' => $_POST['activity'] ?? '',
                'payee_name' => $_POST['payee_name'] ?? '',
                'budget_code' => $_POST['budget_code'] ?? '',
                'particulars' => $_POST['particulars'] ?? '',
                'amount' => $_POST['amount'] ?? 0,
                'total' => $_POST['total'] ?? 0,
                'amount_words' => $_POST['amount_words'] ?? '',
                'prepared_by' => $user['user_id']
            ];
            
            // Create the voucher
            $voucher_id = $voucherModel->create_voucher($voucher_data);
            
            if ($voucher_id) {
                // Get ED user ID for notification
                require_once __DIR__ . '/voucher_functions.php';
                $ed_id = get_ed_user_id($pdo);
                
                // Create notification for ED
                if ($ed_id) {
                    $title = ($voucher_type == 'petty_cash') ? 'New Petty Cash Voucher' : 'New Payment Voucher';
                    $message_text = "A new {$title} (PV: {$voucher_data['pv_no']}) has been created by Finance and is awaiting your approval.";
                    create_voucher_notification($pdo, $ed_id, $title, $message_text, $voucher_id);
                }
                
                // Set success flag for JavaScript to close window
                $_SESSION['voucher_created'] = true;
                
                // Redirect to view voucher in the same window
                echo '<script>window.opener.location.reload(); window.close();</script>';
                exit;
            } else {
                // If creation failed, show error
                $_SESSION['error'] = 'Failed to create voucher. Please try again.';
                if ($request_id > 0) {
                    header('Location: index.php?action=view_request&id=' . $request_id);
                } else {
                    header('Location: index.php?action=select_voucher_type');
                }
                exit;
            }
        } else {
            // If not POST or not finance, redirect to dashboard
            header('Location: index.php?action=dashboard');
            exit;
        }
        break;
        
    case 'view_voucher':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        // Check if a voucher_id is provided
        if (!isset($_GET['id'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        // Only allow Finance and ED to access voucher details
        if (!$user || !in_array($user['role_id'], [4, 5])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        // Create voucher model instance if not exists
        if (!isset($voucherModel)) {
            require_once __DIR__ . '/Voucher.php';
            $voucherModel = new VoucherModel($pdo);
        }
        
        // Include the voucher view page
        include __DIR__ . '/view_voucher.php';
        break;
        
    case 'update_voucher_status':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
            $status = $_POST['status'] ?? '';
            $role = $_POST['role'] ?? '';
            $remark = $_POST['remark'] ?? '';
            
            // Create voucher model instance if not exists
            if (!isset($voucherModel)) {
                require_once __DIR__ . '/Voucher.php';
                $voucherModel = new VoucherModel($pdo);
            }
            
            // Get voucher details
            $voucher = $voucherModel->get_voucher_by_id($voucher_id);
            
            // Check if voucher exists
            if (!$voucher) {
                header('Location: index.php?action=dashboard');
                exit;
            }
            
            // Check if user has permission to update status
            $can_update = false;
            if ($role == 'ed' && $user['role_id'] == 4) {
                $can_update = true;
                $result = $voucherModel->update_ed_status($voucher_id, $status, $remark);
                
                // Create notification for Finance
                require_once __DIR__ . '/voucher_functions.php';
                $finance_id = get_finance_manager_id($pdo);
                if ($finance_id) {
                    $title = "Voucher {$status} by ED";
                    $message = "Voucher {$voucher['pv_no']} has been {$status} by Executive Director";
                    create_voucher_notification($pdo, $finance_id, $title, $message, $voucher_id);
                }
            } elseif ($role == 'finance' && $user['role_id'] == 5) {
                $can_update = true;
                $result = $voucherModel->update_finance_status($voucher_id, $status, $remark);
                
                // Create notification for ED
                require_once __DIR__ . '/voucher_functions.php';
                $ed_id = get_ed_user_id($pdo);
                if ($ed_id) {
                    $title = "Voucher {$status} by Finance";
                    $message = "Voucher {$voucher['pv_no']} has been {$status} by Finance Manager";
                    create_voucher_notification($pdo, $ed_id, $title, $message, $voucher_id);
                }
            }
            
            if ($can_update && $result) {
                $_SESSION['success'] = 'Voucher status updated successfully.';
            } else {
                $_SESSION['error'] = 'Failed to update voucher status.';
            }
            
            header('Location: index.php?action=view_voucher&id=' . $voucher_id);
            exit;
        } else {
            header('Location: index.php?action=dashboard');
            exit;
        }
        break;
        
    case 'send_voucher_message':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
            $recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
            $message = $_POST['message'] ?? '';
            
            // Create voucher model instance if not exists
            if (!isset($voucherModel)) {
                require_once __DIR__ . '/Voucher.php';
                $voucherModel = new VoucherModel($pdo);
            }
            
            // Get voucher details
            $voucher = $voucherModel->get_voucher_by_id($voucher_id);
            
            // Check if voucher exists
            if (!$voucher) {
                header('Location: index.php?action=dashboard');
                exit;
            }
            
            // Check if user can communicate about vouchers and validate recipient
            if ($voucherModel->can_communicate_about_voucher($user['role_id'], $voucher_id) && $message) {
                // Get the correct recipient based on user role
                $valid_recipient = $voucherModel->get_voucher_communication_recipient($user['role_id']);
                
                if ($valid_recipient && $recipient_id == $valid_recipient) {
                    $result = $voucherModel->add_voucher_message($voucher_id, $user['user_id'], $recipient_id, $message);
                    
                    if ($result) {
                        // Create notification for recipient
                        require_once __DIR__ . '/voucher_functions.php';
                        $title = "New message on voucher {$voucher['pv_no']}";
                        $message_preview = substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');
                        create_voucher_notification($pdo, $recipient_id, $title, $message_preview, $voucher_id);
                        
                        $_SESSION['success'] = 'Message sent successfully.';
                    } else {
                        $_SESSION['error'] = 'Failed to send message.';
                    }
                } else {
                    $_SESSION['error'] = 'Invalid recipient for voucher communication.';
                }
            } else {
                $_SESSION['error'] = 'You are not authorized to send messages about vouchers.';
            }
            
            header('Location: index.php?action=view_voucher&id=' . $voucher_id);
            exit;
        } else {
            header('Location: index.php?action=dashboard');
            exit;
        }
        break;
        
    case 'vouchers':
        // Define constant to prevent direct file access
        if (!defined('ACCESS_ALLOWED')) {
            define('ACCESS_ALLOWED', true);
        }
        
        // Only allow Finance and ED to access vouchers
        if (!$user || !in_array($user['role_id'], [4, 5])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        // Include the vouchers listing page
        include __DIR__ . '/vouchers.php';
        break;
        
    case 'messages':
        // Messages page for all users
        include __DIR__ . '/messages.php';
        break;
        
    case 'error':
        // Display error page
        include __DIR__ . '/error.php';
        break;
        
    default:
        // Fallback to login if action is unknown.
        header('Location: index.php?action=login');
        exit;
}
