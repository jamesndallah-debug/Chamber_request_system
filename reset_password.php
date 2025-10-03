<?php
require_once __DIR__ . '/function.php';

$token = $_GET['token'] ?? '';
$error = '';
$message = '';
$showForm = false;

if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Invalid or missing reset token.';
} else {
    // Lookup token
    $stmt = $pdo->prepare('SELECT pr.user_id, pr.expires_at, u.fullname FROM password_resets pr JOIN users u ON u.user_id = pr.user_id WHERE pr.token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $error = 'The reset link is invalid or has already been used.';
    } elseif (strtotime($row['expires_at']) < time()) {
        $error = 'This reset link has expired. Please request a new one.';
    } else {
        $showForm = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $token    = $_POST['token'] ?? '';

    if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        $error = 'Invalid reset token.';
        $showForm = false;
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $showForm = true;
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
        $showForm = true;
    } else {
        // Re-validate token and get user
        $stmt = $pdo->prepare('SELECT user_id, expires_at FROM password_resets WHERE token = ?');
        $stmt->execute([$token]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec || strtotime($rec['expires_at']) < time()) {
            $error = 'The reset link is invalid or expired.';
            $showForm = false;
        } else {
            // Update user password
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $ok  = $upd->execute([$hash, $rec['user_id']]);
            if ($ok) {
                // Invalidate token
                $del = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $del->execute([$rec['user_id']]);
                $message = 'Your password has been reset successfully. You can now log in.';
                $showForm = false;
            } else {
                $error = 'Failed to update password. Please try again.';
                $showForm = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Chamber Request System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin:0; padding:0; min-height:100vh; background: linear-gradient(135deg, #1e40af 0%, #3730a3 50%, #1e3a8a 100%); font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display:flex; align-items:center; justify-content:center; }
        .reset-container { width:100%; max-width:480px; background:rgba(255,255,255,0.95); backdrop-filter: blur(20px); border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,0.1); padding:40px; }
        .reset-header { text-align:center; margin-bottom:24px; }
        .reset-header h1 { margin:0; font-size:1.5rem; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:500; color:#374151; font-size:0.9rem; }
        .form-input { width:100%; padding:12px 14px; border:2px solid #e1e5e9; border-radius:12px; font-size:1rem; background:#fff; }
        .form-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
        .reset-btn { width:100%; padding:12px; background: linear-gradient(135deg, #2563eb 0%, #3730a3 100%); color:#fff; border:none; border-radius:12px; font-weight:600; cursor:pointer; }
        .success-message { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem; text-align:center; }
        .error-message { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem; text-align:center; }
        .back-link { text-align:center; margin-top:16px; }
        .back-link a { color:#2563eb; text-decoration:none; font-weight:500; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Reset your password</h1>
            <p>Enter a new password for your account.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-message"><?php echo e($message); ?></div>
            <div class="back-link"><a href="index.php?action=login">← Back to Login</a></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($showForm): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo e($token); ?>" />
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="At least 8 characters" required />
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required />
            </div>
            <button type="submit" class="reset-btn">Update Password</button>
        </form>
        <?php endif; ?>

        <?php if (!$showForm && !$message): ?>
            <div class="back-link"><a href="forgot_password.php">← Request a new reset link</a></div>
        <?php endif; ?>
    </div>
</body>
</html>
