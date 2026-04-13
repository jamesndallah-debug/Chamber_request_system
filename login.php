<?php
// FILE: views/login.php
// Login page for users.
require_once __DIR__ . '/function.php';

$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Chamber Request System</title>
    <link rel="stylesheet" href="assets/ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="ui-auth-page">
    <div class="auth-container">
        <!-- Left Side: Branding & Welcome -->
        <div class="welcome-section">
            <div class="brand-logo-container">
                <div class="brand-logo">
                    <img src="https://tncc.or.tz/wp-content/uploads/2025/05/TNCC-LOGO-PNG.png" alt="TNCC Logo">
                </div>
            </div>
            
            <div class="welcome-content">
                <h1>Welcome Back</h1>
                <h2>Chamber Requests System</h2>
                <p>Facilitating private sector development in Tanzania by providing exceptional value to members and the entire business community.</p>
                
                <div class="objectives-list">
                    <div class="objective-item">Excellence in Service Delivery</div>
                    <div class="objective-item">Innovation & Technology Leadership</div>
                    <div class="objective-item">Private Sector Development</div>
                    <div class="objective-item">Member Value Creation</div>
                </div>
            </div>
            
            <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 20px;">
                &copy; <?= date('Y') ?> TNCC. All rights reserved.
            </div>
        </div>
        
        <!-- Right Side: Login Form -->
        <div class="auth-section">
            <div class="auth-header">
                <h2>Sign in to your account</h2>
                <p>Welcome back! Please enter your details.</p>
            </div>

            <div class="auth-tabs">
                <span class="auth-tab active">Sign in</span>
                    <a href="index.php?action=register" class="auth-tab">Create account</a>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="index.php?action=login" method="POST">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="login" class="form-label">Email or Phone</label>
                    <input 
                        type="text" 
                        id="login" 
                        name="username" 
                        class="form-input" 
                        placeholder="name@company.com" 
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
                        placeholder="••••••••" 
                        required
                    >
                </div>
                
                <div class="form-footer">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="index.php?action=forgot_password" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-auth">Sign in</button>
                
                <div class="signup-footer">
                    Don't have an account? <a href="index.php?action=register">Sign up</a>
                </div>
                
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <a href="index.php?action=admin_login" style="color: #6b7280; font-size: 14px; text-decoration: none;">👑 Administrator Login</a>
                </div>
            </form>
        </div>
    </div>
    
    </body>
</html>
