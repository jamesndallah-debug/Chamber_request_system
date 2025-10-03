<?php
require_once __DIR__ . '/function.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $department = $_POST['department'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 1);

    if ($username === '' || $password === '' || $confirm_password === '' || $fullname === '' || $department === '' || $role_id === 0) {
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
            $stmt = $pdo->prepare('INSERT INTO users (fullname, username, password, department, role_id, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
            if ($stmt->execute([$fullname, $username, $hash, $department, $role_id])) {
				// Auto login with full DB row (ensures keys like user_id match elsewhere)
				$newId = (int)$pdo->lastInsertId();
				$fetch = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
				$fetch->execute([$newId]);
				$_SESSION['user'] = $fetch->fetch(PDO::FETCH_ASSOC) ?: [
					'username' => $username,
					'fullname' => $fullname,
					'department' => $department,
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

                    $roleNames = [1=>'Employee',2=>'HRM',3=>'HOD',4=>'ED',5=>'Finance Manager',6=>'Internal Auditor',7=>'Admin'];
                    $newRoleName = $roleNames[$role_id] ?? ('Role #' . (int)$role_id);
                    $title = 'New user registered';
                    $msg = sprintf('%s (%s) joined as %s in %s.', $fullname, $username, $newRoleName, $department);
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
				else if ($role === 4) $dest = 'ed_dashboard';
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
		
		/* Modern gradient overlay */
		body::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: 
				radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
				radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
				radial-gradient(circle at 40% 80%, rgba(120, 119, 255, 0.3) 0%, transparent 50%);
			z-index: 0;
		}
		
		.auth-container {
			position: relative;
			z-index: 1;
			width: 100%;
			max-width: 1000px;
			margin: 0 auto;
			display: grid;
			grid-template-columns: 1fr 1fr;
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border-radius: 20px;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
			overflow: hidden;
			min-height: 650px;
		}
		
		.welcome-section {
			background: linear-gradient(135deg, #1e40af 0%, #3730a3 50%, #1e3a8a 100%);
			padding: 60px 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: flex-start;
			color: white;
			position: relative;
			overflow: hidden;
		}
		
		.welcome-section::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: 200%;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
			animation: grain 8s linear infinite;
			z-index: 0;
		}
		
		@keyframes grain {
			0% { transform: translateX(0) translateY(0); }
			100% { transform: translateX(-100px) translateY(-100px); }
		}
		
		.objectives-animation {
			position: absolute;
			width: 100%;
			height: 100%;
			overflow: hidden;
			z-index: 0;
			pointer-events: none;
		}
		
		.objective-text {
			position: absolute;
			color: rgba(255,255,255,0.08);
			font-size: 1.2rem;
			font-weight: 600;
			white-space: nowrap;
			animation: slideObjective 30s infinite linear;
		}
		
		.objective-text:nth-child(1) { top: 15%; animation-delay: 0s; }
		.objective-text:nth-child(2) { top: 35%; animation-delay: -10s; }
		.objective-text:nth-child(3) { top: 55%; animation-delay: -20s; }
		.objective-text:nth-child(4) { top: 75%; animation-delay: -15s; }
		
		@keyframes slideObjective {
			0% { transform: translateX(-100%); }
			100% { transform: translateX(100vw); }
		}
		
		.welcome-content {
			position: relative;
			z-index: 1;
		}
		
		.welcome-section h1 {
			font-size: 2.5rem;
			font-weight: 800;
			margin: 0 0 10px 0;
			letter-spacing: -0.02em;
		}
		
		.welcome-section p {
			font-size: 1rem;
			opacity: 0.9;
			margin: 0;
			line-height: 1.6;
		}
		
		.brand-logo {
            position: absolute;
            top: 30px;
            left: 30px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }
		
		.register-section {
			padding: 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			overflow-y: auto;
			max-height: 650px;
		}
		
		.register-header {
			text-align: center;
			margin-bottom: 30px;
		}
		
		.register-header h2 {
			font-size: 1.75rem;
			font-weight: 700;
			color: #1a1a1a;
			margin: 0 0 8px 0;
		}
		
		.register-header p {
			color: #666;
			margin: 0;
			font-size: 0.9rem;
		}
		
		.form-group {
			margin-bottom: 24px;
			position: relative;
		}
		
		.form-group label {
			display: block;
			margin-bottom: 6px;
			font-weight: 500;
			color: #374151;
			font-size: 0.875rem;
		}
		
		.form-input {
			width: 100%;
			padding: 12px 14px;
			border: 2px solid #e1e5e9;
			border-radius: 12px;
			font-size: 0.95rem;
			transition: all 0.05s ease-out;
			background: #ffffff;
			color: #1f2937;
			box-sizing: border-box;
			-webkit-appearance: none;
			appearance: none;
		}
		
		/* Enhanced select dropdown styling */
		select.form-input {
			background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
			background-repeat: no-repeat;
			background-position: right 12px center;
			background-size: 12px;
			padding-right: 40px;
			cursor: pointer;
		}
		
		select.form-input:focus {
			background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%232563eb" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
		}
		
		select.form-input option {
			padding: 10px 14px;
			background: white;
			color: #1f2937;
			border: none;
		}
		
		select.form-input option:hover {
			background: #f3f4f6;
		}
		
		select.form-input option:checked {
			background: #2563eb;
			color: white;
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
		
		.form-input::placeholder {
			color: #9ca3af;
		}
		
		.password-toggle {
			position: absolute;
			right: 12px;
			top: 50%;
			transform: translateY(-50%);
			background: none;
			border: none;
			cursor: pointer;
			padding: 4px;
			color: #6b7280;
			font-size: 1.1rem;
			transition: color 0.05s ease;
			z-index: 10;
		}
		
		.password-toggle:hover {
			color: #2563eb;
		}
		
		.password-toggle:focus {
			outline: none;
			color: #2563eb;
		}
		
		.form-row {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
		}
		
		.sign-up-btn {
			width: 100%;
			padding: 14px;
			background: linear-gradient(135deg, #2563eb 0%, #3730a3 100%);
			color: white;
			border: none;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.05s ease;
			margin: 20px 0;
			box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
		}
		
		.sign-up-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
			background: linear-gradient(135deg, #1d4ed8 0%, #312e81 100%);
		}
		
		.sign-up-btn:active {
			transform: translateY(0);
			box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
		}
		
		.divider {
			display: flex;
			align-items: center;
			margin: 20px 0;
			color: #9ca3af;
			font-size: 0.875rem;
		}
		
		.divider::before,
		.divider::after {
			content: '';
			flex: 1;
			height: 1px;
			background: #e5e7eb;
		}
		
		.divider span {
			margin: 0 16px;
		}
		
		.other-signup-btn {
			width: 100%;
			padding: 14px;
			background: white;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.05s ease;
			margin-bottom: 20px;
			color: #374151;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.other-signup-btn:hover {
			border-color: #d1d5db;
			background: #f9fafb;
		}
		
		.signin-link {
			text-align: center;
			color: #666;
			font-size: 0.875rem;
		}
		
		.signin-link a {
			color: #2563eb;
			text-decoration: none;
			font-weight: 600;
		}
		
		.signin-link a:hover {
			text-decoration: underline;
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
		
		/* Responsive design */
		@media (max-width: 768px) {
			.auth-container {
				grid-template-columns: 1fr;
				max-width: 400px;
				margin: 20px;
				min-height: auto;
			}
			
			.welcome-section {
				padding: 40px 30px;
				text-align: center;
			}
			
			.welcome-section h1 {
				font-size: 2rem;
			}
			
			.register-section {
				padding: 30px;
				max-height: none;
			}
			
			.brand-logo {
				width: 64px;
				height: 64px;
				padding: 8px;
			}
			
			.form-row {
				grid-template-columns: 1fr;
				gap: 12px;
			}
		}
	</style>
</head>
<body>
	<div class="auth-container">
		<div class="welcome-section">
			<div class="objectives-animation">
				<div class="objective-text">Excellence in Service Delivery</div>
				<div class="objective-text">Innovation & Technology Leadership</div>
				<div class="objective-text">Private Sector Development</div>
				<div class="objective-text">Member Value Creation</div>
			</div>
			<div class="brand-logo">
				<img src="https://tccia.or.tz/wp-content/uploads/2025/05/cropped-tccia_retina_logo.png" alt="TCCIA Logo" />
			</div>
			<div class="welcome-content">
				<h1>WELCOME</h1>
				<h2 style="font-size: 1.5rem; font-weight: 600; margin: 10px 0; color: #e0e7ff;">Chamber Requests System</h2>
				<p style="margin-top: 15px; font-size: 0.95rem; opacity: 0.9; line-height: 1.6;">To facilitate private sector development in Tanzania by providing exceptional value to members and entire business community through the provision of demand-driven services using highly competent staff and modern technologies.</p>
			</div>
		</div>
		
		<div class="register-section">
			<div class="register-header">
				<h2>Sign Up</h2>
				<p style="font-style: italic; color: #4f46e5; font-weight: 500; margin-top: 8px;">Private Sector - Engine of Growth</p>
			</div>
			
			<?php if (!empty($error)): ?>
				<div class="error-message"><?= htmlspecialchars($error) ?></div>
			<?php endif; ?>
			
			<form method="POST">
				<div class="form-row">
					<div class="form-group">
						<label for="fullname">👤 Full Name</label>
						<input type="text" id="fullname" name="fullname" class="form-input" placeholder="Enter your full name" required>
					</div>
					<div class="form-group">
						<label for="username">📧 Email or 📱 Phone</label>
						<input type="text" id="username" name="username" class="form-input" placeholder="email@example.com or +255..." pattern="([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|\+?\d{10,15}" required>
					</div>
				</div>
				
				<div class="form-group">
					<label for="password">🔒 Password</label>
					<input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required style="padding-right: 45px;">
					<button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
						<span id="password-toggle-icon">👁️</span>
					</button>
				</div>
				
				<div class="form-group">
					<label for="confirm_password">🔒 Confirm Password</label>
					<input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required style="padding-right: 45px;">
					<button type="button" class="password-toggle" onclick="togglePassword('confirm_password')" aria-label="Toggle confirm password visibility">
						<span id="confirm_password-toggle-icon">👁️</span>
					</button>
				</div>
				
				<div class="form-row">
					<div class="form-group">
						<label for="department">🏢 Department</label>
						<select id="department" name="department" class="form-input" required>
							<option value="" disabled selected>Select your department</option>
							<option value="HR & Administration">👥 HR & Administration</option>
							<option value="PR & ICT">💻 PR & ICT</option>
							<option value="Membership">🤝 Membership</option>
							<option value="Finance">💰 Finance</option>
							<option value="Internal Auditor">🔍 Internal Auditor</option>
							<option value="Legal Officer">⚖️ Legal Officer</option>
							<option value="Industrial Development">🏭 Industrial Development</option>
							<option value="Business Development">📈 Business Development</option>
							<option value="Project">📋 Project</option>
							<option value="Agribusiness">🌾 Agribusiness</option>
						</select>
					</div>
					<div class="form-group">
						<label for="role_id">👨‍💼 Role</label>
						<select id="role_id" name="role_id" class="form-input" required>
							<option value="" disabled selected>Select your role</option>
							<option value="1">👤 Employee</option>
							<option value="3">👔 Head of Department (HOD)</option>
							<option value="2">👥 Human Resource Manager (HRM)</option>
							<option value="6">🔍 Internal Auditor</option>
							<option value="5">💰 Finance Manager</option>
							<option value="4">🎯 Executive Director</option>
							<option value="7">⚙️ System Administrator</option>
						</select>
					</div>
				</div>
				
				<button type="submit" class="sign-up-btn">Sign up</button>
				
				<div class="divider">
					<span>Or</span>
				</div>
				
				<button type="button" id="google-signup-btn" class="other-signup-btn" onclick="signUpWithGoogle()">
					<svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 10px;">
						<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
						<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
						<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
						<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
					</svg>
					Continue with Google
				</button>
				
				<div class="signin-link">
					Already have an account? <a href="index.php?action=login">Login</a>
				</div>
			</form>
		</div>
	</div>
	
	<!-- Google Identity Services -->
	<script src="https://accounts.google.com/gsi/client" async defer></script>
	
	<script>
		// Google OAuth Client ID from server config
		const GOOGLE_CLIENT_ID = '<?= addslashes(GOOGLE_CLIENT_ID) ?>';
		// Application base URL from server config
		const APP_BASE_URL = '<?= rtrim(BASE_URL, "/") ?>';
	</script>
	
	<script>
		// Enhanced field interactions for better responsiveness
		document.addEventListener('DOMContentLoaded', function() {
			const inputs = document.querySelectorAll('.form-input');
			
			inputs.forEach(input => {
				// Add instant visual feedback
				input.addEventListener('input', function() {
					if (this.value.length > 0) {
						this.style.borderColor = '#10b981';
					} else {
						this.style.borderColor = '#e1e5e9';
					}
				});
				
				// Enhanced select dropdown interactions
				if (input.tagName === 'SELECT') {
					input.addEventListener('change', function() {
						if (this.value) {
							this.style.borderColor = '#10b981';
							this.style.color = '#1f2937';
						}
					});
				}
				
				// Smooth focus transitions
				input.addEventListener('focus', function() {
					this.style.transform = 'translateY(-1px)';
				});
				
				input.addEventListener('blur', function() {
					if (this.value.length === 0) {
						this.style.transform = 'translateY(0)';
					}
				});
			});
		});
		
		// Google Sign-Up functionality
		function signUpWithGoogle() {
			const btn = document.getElementById('google-signup-btn');
			const originalText = btn.innerHTML;
            
            // Show loading state
            btn.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; gap: 8px;"><div style="width: 16px; height: 16px; border: 2px solid #fff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>Connecting...</div>';
            btn.disabled = true;
            
            // Check if we have a real Google Client ID configured
            if (GOOGLE_CLIENT_ID && GOOGLE_CLIENT_ID !== '1234567890-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com') {
                // Always use popup with account chooser (select_account)
                fallbackToPopup();
            } else {
                // Use demo mode for development
                setTimeout(() => {
                    simulateGoogleSignup();
                }, 1500);
            }
		}
		
		function fallbackToPopup() {
            if (GOOGLE_CLIENT_ID && GOOGLE_CLIENT_ID !== '1234567890-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com') {
                const authUrl = 'https://accounts.google.com/oauth2/auth?' +
                    'client_id=' + GOOGLE_CLIENT_ID + '&' +
					'redirect_uri=' + encodeURIComponent(APP_BASE_URL + '/google_callback.php') + '&' +
                    'response_type=code&' +
                    'scope=openid%20email%20profile&' +
                    'access_type=offline&' +
                    'prompt=select_account';
                    
                const popup = window.open(authUrl, 'google-signup', 'width=500,height=600,scrollbars=yes,resizable=yes');
                
                // Listen for popup close
                const checkClosed = setInterval(() => {
                    if (popup.closed) {
                        clearInterval(checkClosed);
                        const btn = document.getElementById('google-signup-btn');
                        btn.innerHTML = '🔍 Sign up with Google';
                        btn.disabled = false;
                        window.location.reload();
                    }
                }, 500);
            } else {
                // Demo mode fallback
                setTimeout(() => {
                    simulateGoogleSignup();
                }, 1000);
            }
        }
		
		function simulateGoogleSignup() {
			// For demo/development - simulate successful Google signup
			fetch('google_auth.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					demo: true,
					email: 'demo@chamber.co.tz',
					name: 'Demo User',
					action: 'register'
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Redirect based on user role
					const role = data.user_role || 1;
					let dest = 'dashboard';
					if (role === 7) dest = 'admin_management';
					else if (role === 1) dest = 'employee_dashboard';
					else if (role === 3) dest = 'hod_dashboard';
					else if (role === 2) dest = 'hrm_dashboard';
					else if (role === 6) dest = 'auditor_dashboard';
					else if (role === 5) dest = 'finance_dashboard';
					else if (role === 4) dest = 'ed_dashboard';
					window.location.href = 'index.php?action=' + dest;
				} else {
					alert('Demo registration failed: ' + data.message);
				}
			})
			.catch(error => {
				console.error('Demo registration error:', error);
				alert('Demo mode: Google sign-up simulation');
			});
		}
		
		function handleCredentialResponse(response) {
            fetch('google_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    credential: response.credential,
                    action: 'register'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect based on user role
                    const role = data.user_role || 1;
                    let dest = 'dashboard';
                    if (role === 7) dest = 'admin_management';
                    else if (role === 1) dest = 'employee_dashboard';
                    else if (role === 3) dest = 'hod_dashboard';
                    else if (role === 2) dest = 'hrm_dashboard';
                    else if (role === 6) dest = 'auditor_dashboard';
                    else if (role === 5) dest = 'finance_dashboard';
                    else if (role === 4) dest = 'ed_dashboard';
                    window.location.href = 'index.php?action=' + dest;
                } else {
                    alert('Google sign-up failed: ' + data.message);
                    const btn = document.getElementById('google-signup-btn');
                    btn.innerHTML = '🔍 Sign up with Google';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Google sign-up failed');
                const btn = document.getElementById('google-signup-btn');
                btn.innerHTML = '🔍 Sign up with Google';
                btn.disabled = false;
            });
        }
		
		// Add spinning animation for loading state
		const style = document.createElement('style');
		style.textContent = `
			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}
		`;
		document.head.appendChild(style);
		
		// Password visibility toggle function
		function togglePassword(fieldId) {
			const passwordField = document.getElementById(fieldId);
			const toggleIcon = document.getElementById(fieldId + '-toggle-icon');
			
			if (passwordField.type === 'password') {
				passwordField.type = 'text';
				toggleIcon.textContent = '🙈';
			} else {
				passwordField.type = 'password';
				toggleIcon.textContent = '👁️';
			}
		}
	</script>
</body>
</html>
