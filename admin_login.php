<?php
// FILE: admin_login.php
// Dedicated admin login page

require_once __DIR__ . '/function.php';

$error = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Get user by username
        $user_data = $userModel->get_user_by_username($username);
        
        if ($user_data) {
            // Check if user is active and not deleted
            if ((isset($user_data['active']) && (int)$user_data['active'] === 0) || (!empty($user_data['deleted_at']))) {
                $error = "Your account has been deactivated. Please contact the administrator.";
            } else {
                // Verify password
                $storedHash = (string)($user_data['password'] ?? '');
                $passwordMatches = false;
                
                if ($storedHash !== '' && substr($storedHash, 0, 4) === '$2y$') {
                    $passwordMatches = password_verify($password, $storedHash);
                } else {
                    // Legacy/plaintext compatibility
                    if (hash_equals($storedHash, $password)) {
                        $passwordMatches = true;
                        // Rehash if needed
                        $rehash = password_hash($password, PASSWORD_BCRYPT);
                        if ($rehash) {
                            $userModel->update_password_hash($user_data['user_id'], $rehash);
                            $user_data['password'] = $rehash;
                        }
                    }
                }
                
                if ($passwordMatches) {
                    // Check if user is admin (role_id = 7)
                    if ((int)$user_data['role_id'] !== 7) {
                        $error = "Access denied. This login page is for administrators only.";
                    } else {
                        // Successful admin login
                        // Prevent session fixation
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            @session_regenerate_id(true);
                        }
                        $_SESSION['user'] = $user_data;
                        // Record last login timestamp
                        set_last_login($pdo, (int)$user_data['user_id']);
                        
                        // Redirect to admin management dashboard
                        header('Location: index.php?action=admin_management');
                        exit;
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Chamber Request System</title>
    <link rel="stylesheet" href="assets/ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-login-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .admin-login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .admin-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .admin-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        .admin-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        .admin-form {
            padding: 30px;
        }
        .admin-form .form-group {
            margin-bottom: 20px;
        }
        .admin-form .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        .admin-form .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .admin-form .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .admin-btn {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .back-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .admin-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-header">
                <div class="admin-icon">👑</div>
                <h1>Administrator Login</h1>
                <p>Chamber Request Management System</p>
            </div>
            
            <div class="admin-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Admin Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="Enter your admin username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="admin-btn">
                        Sign In as Administrator
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="index.php?action=login">← Back to Regular Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>