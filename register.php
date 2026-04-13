<?php
require_once __DIR__ . '/function.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $directorate = $_POST['directorate'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 1);

    if ($username === '' || $password === '' || $confirm_password === '' || $fullname === '' || $directorate === '' || $role_id === 0) {
        $error = 'Please fill all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
		// Check existing user
		$exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
		$exists->execute([$username]);
		if ($exists->fetchColumn()) {
			$error = 'Username already exists.';
		} else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (fullname, username, password, directorate, role_id, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
            if ($stmt->execute([$fullname, $username, $hash, $directorate, $role_id])) {
				// Auto login with full DB row (ensures keys like user_id match elsewhere)
				$newId = (int)$pdo->lastInsertId();
				$fetch = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
				$fetch->execute([$newId]);
				$_SESSION['user'] = $fetch->fetch(PDO::FETCH_ASSOC) ?: [
					'username' => $username,
					'fullname' => $fullname,
					'directorate' => $directorate,
					'role_id' => $role_id,
				];
				                // Send welcome email if username looks like an email address
                if (function_exists('send_email') && is_valid_email($username)) {
                    $appUrl = rtrim(BASE_URL, '/');
                    $subject = 'Welcome to Chamber Request System';
                    // Fix malformed HTML: add missing closing '>' on opening div tag
                    $body = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">'
                          . '<h2 style="margin:0 0 10px 0;">Welcome, ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . '!</h2>'
                          . '<p>Your account has been created successfully.</p>'
                          . '<ul style="margin:10px 0 10px 18px;">'
                          . '<li><strong>Username:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</li>'
                          . '</ul>'
                          . '<p>You can sign in anytime here: <a href="' . $appUrl . '/index.php?action=login">' . $appUrl . '</a></p>'
                          . '<p style="margin-top:16px;color:#555;">If you did not request this, please ignore this email.</p>'
                          . '</div>';
                    @send_email($username, $subject, $body);
                }

                // Notify all Admins about the new registration (in-app notification + email if available)
                try {
                    $adminStmt = $pdo->prepare('SELECT user_id, username, fullname FROM users WHERE role_id = 7');
                    $adminStmt->execute();
                    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    $roleNames = [1=>'Employee',2=>'HRM',3=>'HOD',4=>'CEO',5=>'Finance Manager',6=>'Internal Auditor',7=>'Admin'];
                    $newRoleName = $roleNames[$role_id] ?? ('Role #' . (int)$role_id);
                    $title = 'New user registered';
                    $msg = sprintf('%s (%s) joined as %s in %s.', $fullname, $username, $newRoleName, $directorate);
                    foreach ($admins as $adm) {
                        // In-app notification
                        $n = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                        $n->execute([(int)$adm['user_id'], $title, $msg]);
                        // Email admin if their username is an email
                        if (function_exists('send_email') && is_valid_email($adm['username'])) {
                            $adminSubject = '[Chamber] ' . $title;
                            $adminBody = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">'
                                       . '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
                                       . '</div>';
                            @send_email($adm['username'], $adminSubject, $adminBody);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Admin notify on registration failed: ' . $e->getMessage());
                }
				// Redirect based on role
				$role = (int)$role_id;
				$dest = 'dashboard';
				if ($role === 7) $dest = 'admin_management';
				else if ($role === 1) $dest = 'employee_dashboard';
				else if ($role === 3) $dest = 'hod_dashboard';
				else if ($role === 2) $dest = 'hrm_dashboard';
				else if ($role === 6) $dest = 'auditor_dashboard';
				else if ($role === 5) $dest = 'finance_dashboard';
				else if ($role === 4) $dest = 'ceo_dashboard';
				header('Location: index.php?action=' . $dest);
				exit;
			} else {
				$error = 'Failed to create account.';
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
	<title>Register | Chamber Request System</title>
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
                <h1>Join the Chamber</h1>
                <h2>Become a Part of Growth</h2>
                <p>Join our platform to streamline requests, collaborate effectively, and drive private sector development.</p>
                
                <div class="objectives-list">
                    <div class="objective-item">Seamless Request Management</div>
                    <div class="objective-item">Real-time Collaboration</div>
                    <div class="objective-item">Instant Notifications</div>
                    <div class="objective-item">Secure & Reliable</div>
                </div>
            </div>
            
            <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 20px;">
                &copy; <?= date('Y') ?> TNCC. All rights reserved.
            </div>
        </div>

		<div class="auth-section">
            <div class="auth-header">
                <h2>Create your account</h2>
                <p>Enter your details to get started.</p>
            </div>
            
			<div class="auth-tabs">
				<a href="index.php?action=login" class="auth-tab">Sign in</a>
				<span class="auth-tab active">Create account</span>
			</div>

			<?php if (!empty($error)): ?>
				<div class="error-message"><?= htmlspecialchars($error) ?></div>
			<?php endif; ?>
			
			<form method="POST">
                <?= csrf_field() ?>

                <div>
					<div class="form-group">
						<label for="fullname" class="form-label">Full Name</label>
						<input
							type="text"
							id="fullname"
							name="fullname"
							class="form-input"
							placeholder="John Doe"
							required
						>
					</div>
					<div class="form-group">
						<label for="username" class="form-label">Work Email or Phone</label>
						<input
							type="text"
							id="username"
							name="username"
							class="form-input"
							placeholder="name@company.com or +255..."
							pattern="([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|\+?\d{10,15}"
							required
						>
					</div>
				</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="directorate" class="form-label">Directorate</label>
                        <select id="directorate" name="directorate" class="form-input" required>
                            <option value="" disabled selected>Select...</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT name FROM directorates WHERE is_active = 1 ORDER BY name");
                                $stmt->execute();
                                $directorates = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                foreach ($directorates as $directorate) {
                                    echo "<option value='" . htmlspecialchars($directorate) . "'>" . htmlspecialchars($directorate) . "</option>";
                                }
                            } catch (Exception $e) {
                                // Fallback options if database fails
                                $fallbackOptions = [
                                    'Human Resource Directorate',
                                    'Finance Directorate', 
                                    'Operations Directorate',
                                    'Technical Services Directorate',
                                    'Corporate Services Directorate',
                                    'Legal Services Directorate',
                                    'Internal Audit Directorate'
                                ];
                                foreach ($fallbackOptions as $directorate) {
                                    echo "<option value='" . htmlspecialchars($directorate) . "'>" . htmlspecialchars($directorate) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="role_id" class="form-label">Role</label>
                        <select id="role_id" name="role_id" class="form-input" required>
                            <option value="" disabled selected>Select...</option>
                            <option value="1">Employee</option>
                            <option value="3">HOD</option>
                            <option value="2">HRM</option>
                            <option value="6">Internal Auditor</option>
                            <option value="5">Finance Manager</option>
                            <option value="4">Chief Executive Officer</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Min 8 chars"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            placeholder="Repeat password"
                            required
                        >
                    </div>
                </div>

				<button type="submit" class="btn-auth">Create Account</button>
				
				<div class="signup-footer">
					Already have an account? <a href="index.php?action=login">Sign in</a>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
