<?php
// FILE: profile.php
// User profile management

/** @var array $user */
/** @var PDO $pdo */

if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();
    
    if (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password)) {
            $error = 'Please enter a new password.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                if ($upd->execute([$hash, $user['user_id']])) {
                    $success = 'Password updated successfully!';
                } else {
                    $error = 'Failed to update password.';
                }
            } catch (Exception $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        if ($fullname) {
            try {
                $stmt = $pdo->prepare('UPDATE users SET fullname = ? WHERE user_id = ?');
                $stmt->execute([$fullname, $user['user_id']]);
                $_SESSION['user']['fullname'] = $fullname;
                $user['fullname'] = $fullname;
                $success = 'Profile updated successfully!';
            } catch (Exception $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
        .form-input { width: 100%; padding: 0.875rem 1.25rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; outline: none; transition: all 0.2s; }
        .form-input:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .btn-primary { background: #2563eb; color: white; padding: 0.875rem 1.5rem; border-radius: 14px; font-weight: 700; transition: all 0.2s; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .btn-secondary { background: #f1f5f9; color: #475569; padding: 0.875rem 1.5rem; border-radius: 14px; font-weight: 700; transition: all 0.2s; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center; }
        .btn-secondary:hover { background: #e2e8f0; }
    </style>
</head>
<body class="h-full flex overflow-hidden">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 lg:ml-72 bg-slate-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 px-6 sm:px-10 py-6 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account Settings</h1>
                <p class="text-sm font-medium text-slate-500">Manage your profile and security</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($user['fullname']) ?></p>
                    <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest"><?= htmlspecialchars($roleLabel) ?></p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-200">
                    <?= substr($user['fullname'], 0, 1) ?>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 sm:p-10 space-y-10">
            <?php if ($success): ?>
                <div class="p-4 rounded-2xl bg-emerald-50 border-2 border-emerald-100 text-emerald-800 font-bold flex items-center gap-3">
                    <span>✅</span> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-100 text-rose-800 font-bold flex items-center gap-3">
                    <span>❌</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Profile Info -->
                <div class="card p-8 space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-black text-slate-900">Personal Information</h2>
                    </div>

                    <form method="POST" class="space-y-6">
                        <?= csrf_field() ?>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address (Username)</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled class="form-input opacity-60 cursor-not-allowed">
                            <p class="mt-2 text-[10px] text-slate-400">Username cannot be changed as it is tied to your identity.</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Full Name</label>
                            <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required class="form-input">
                        </div>

                        <div class="pt-4">
                            <button type="submit" name="update_profile" class="btn-primary w-full">Update Information</button>
                        </div>
                    </form>
                </div>

                <!-- Security / Password -->
                <div class="card p-8 space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-black text-slate-900">Change Password</h2>
                    </div>

                    <form method="POST" class="space-y-6">
                        <?= csrf_field() ?>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">New Password</label>
                            <input type="password" name="new_password" required minlength="8" class="form-input" placeholder="••••••••">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="8" class="form-input" placeholder="••••••••">
                        </div>

                        <div class="pt-4">
                            <button type="submit" name="update_password" class="btn-primary w-full">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/mobile-menu.js"></script>
</body>
</html>
