<?php
require_once __DIR__ . '/function.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare('SELECT user_id, fullname FROM users WHERE username = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token and 6-digit OTP
            $token = bin2hex(random_bytes(32));
            $otp = sprintf('%06d', mt_rand(0, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTPs should expire faster
            
            // Store reset token and OTP in database
            try {
                $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, otp, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, otp = ?, expires_at = ?');
                $stmt->execute([$user['user_id'], $token, $otp, $expires, $token, $otp, $expires]);
                
                // Send the reset email with OTP
                $resetLink = rtrim(BASE_URL, '/') . '/index.php?action=reset_password&token=' . urlencode($token);
                $subject = $otp . ' is your Password Reset Code';
                $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;color:#333;line-height:1.6;max-width:600px;margin:0 auto;padding:20px;border:1px solid #eee;border-radius:10px;">'
                    . '<div style="text-align:center;margin-bottom:20px;">'
                    . '<img src="https://tncc.or.tz/wp-content/uploads/2025/05/TNCC-LOGO-PNG.png" alt="TNCC Logo" style="max-width:150px;">'
                    . '</div>'
                    . '<p>Hello ' . e($user['fullname'] ?? 'User') . ',</p>'
                    . '<p>We received a request to reset your password. Use the verification code below to proceed:</p>'
                    . '<div style="text-align:center;margin:30px 0;">'
                    . '<div style="display:inline-block;background:#f3f4f6;padding:15px 30px;border-radius:12px;font-size:32px;font-weight:bold;letter-spacing:5px;color:#1e3a8a;border:1px solid #e5e7eb;">' . $otp . '</div>'
                    . '</div>'
                    . '<p style="text-align:center;color:#666;font-size:14px;">This code will expire in 15 minutes.</p>'
                    . '<p>Alternatively, you can click the button below to reset your password directly:</p>'
                    . '<div style="text-align:center;margin:30px 0;">'
                    . '<a href="' . $resetLink . '" style="background:#2563eb;color:#fff;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;">Reset Password</a>'
                    . '</div>'
                    . '<p style="font-size:14px;color:#666;">If you did not request this, please ignore this email and your password will remain unchanged.</p>'
                    . '<hr style="border:0;border-top:1px solid #eee;margin:20px 0;">'
                    . '<p style="font-size:12px;color:#999;text-align:center;">&copy; ' . date('Y') . ' TNCC Chamber Request System. All rights reserved.</p>'
                    . '</div>';

                @send_email($email, $subject, $html);

                // Redirect to reset page with token to allow OTP entry
                header('Location: index.php?action=reset_password&token=' . urlencode($token));
                exit;
                
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again later.';
            }
        } else {
            // Don't reveal if email exists or not for security, but redirect anyway to make it look consistent
            $message = 'If an account with that email exists, password reset instructions have been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Chamber Request System</title>
    <link rel="stylesheet" href="assets/ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 50%, #1e3a8a 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .reset-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 60px 40px;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .reset-header h1 {
            font-size: 2rem;
            font-weight: 400;
            color: #1a1a1a;
            margin: 0 0 10px 0;
        }
        
        .reset-header p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 400;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.08s ease-out;
            background: #ffffff;
            color: #1f2937;
            box-sizing: border-box;
            -webkit-appearance: none;
            appearance: none;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input:hover {
            border-color: #cbd5e1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .reset-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #3730a3 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }
        
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
            background: linear-gradient(135deg, #1d4ed8 0%, #312e81 100%);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 400;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
        }
        
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
        }
    </style>
</head>
<body class="ui-auth-page">
    <div class="reset-container">
        <div class="reset-header">
            <h1>Forgot Password?</h1>
            <p>Enter your email address and we'll send you instructions to reset your password.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
            </div>
            
            <button type="submit" class="reset-btn">Send Reset Instructions</button>
        </form>
        
        <div class="back-link">
            <a href="index.php?action=login">← Back to Login</a>
        </div>
    </div>
</body>
</html>
