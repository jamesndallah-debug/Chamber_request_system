<?php
// FILE: leave_balances.php
require_once __DIR__ . '/function.php';

$canAdmin = isset($user) && (int)$user['role_id'] === 7;

// Ensure table exists with last_reset_date column
try {
	$pdo->exec("CREATE TABLE IF NOT EXISTS leave_balances (
		user_id INT NOT NULL,
		leave_type VARCHAR(64) NOT NULL,
		year INT NOT NULL,
		balance_days INT NOT NULL,
		last_reset_date DATE DEFAULT NULL,
		PRIMARY KEY (user_id, leave_type, year)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	// Add last_reset_date column if it doesn't exist (for existing installations)
	$pdo->exec("ALTER TABLE leave_balances ADD COLUMN IF NOT EXISTS last_reset_date DATE DEFAULT NULL");
} catch (Throwable $e) { /* ignore */ }

// Handle admin actions for leave balance management
if ($canAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['admin_action'] ?? '';
	
	if ($action === 'reset_user_leave') {
		$target_user_id = (int)($_POST['target_user_id'] ?? 0);
		$target_year = (int)($_POST['target_year'] ?? date('Y'));
		$leave_type = $_POST['leave_type'] ?? '';
		
		if ($target_user_id > 0 && !empty($leave_type)) {
			if (reset_user_leave_balance($pdo, $target_user_id, $leave_type, $target_year)) {
				$success_message = "{$leave_type} balance reset successfully for user ID: $target_user_id";
			} else {
				$error_message = "Failed to reset {$leave_type} balance for user ID: $target_user_id";
			}
		}
	} elseif ($action === 'reset_all_leave_type') {
		$target_year = (int)($_POST['target_year'] ?? date('Y'));
		$leave_type = $_POST['leave_type'] ?? '';
		$reset_count = 0;
		
		if (!empty($leave_type)) {
			// Get all users with this leave type balance
			$users_stmt = $pdo->prepare("SELECT DISTINCT user_id FROM leave_balances WHERE leave_type = ? AND year = ?");
			$users_stmt->execute([$leave_type, $target_year]);
			$users = $users_stmt->fetchAll(PDO::FETCH_COLUMN);
			
			foreach ($users as $user_id) {
				if (reset_user_leave_balance($pdo, $user_id, $leave_type, $target_year)) {
					$reset_count++;
				}
			}
			
			$success_message = "{$leave_type} balances reset for $reset_count users in year $target_year";
		}
	} elseif ($action === 'reset_all_users_all_types') {
		$target_year = (int)($_POST['target_year'] ?? date('Y'));
		$reset_count = 0;
		$leave_types = ['Annual leave', 'Compassionate leave', 'Paternity leave', 'Maternity leave'];
		
		// Get all users with leave balances
		$users_stmt = $pdo->prepare("SELECT DISTINCT user_id FROM leave_balances WHERE year = ?");
		$users_stmt->execute([$target_year]);
		$users = $users_stmt->fetchAll(PDO::FETCH_COLUMN);
		
		foreach ($users as $user_id) {
			foreach ($leave_types as $leave_type) {
				if (reset_user_leave_balance($pdo, $user_id, $leave_type, $target_year)) {
					$reset_count++;
				}
			}
		}
		
		$success_message = "All leave balances reset for all users. Total resets: $reset_count in year $target_year";
	}
}

$filterYear = (int)($_GET['year'] ?? date('Y'));
$filterUser = $canAdmin ? (int)($_GET['user_id'] ?? 0) : (int)$user['user_id'];
$params = [$filterYear];
$sql = "SELECT lb.user_id, u.fullname, u.username, lb.leave_type, lb.year, lb.balance_days, lb.last_reset_date
		FROM leave_balances lb JOIN users u ON lb.user_id = u.user_id
		WHERE lb.year = ?";
if (!$canAdmin || $filterUser > 0) {
	$sql .= " AND lb.user_id = ?";
	$params[] = $filterUser ?: (int)$user['user_id'];
}
$sql .= " ORDER BY u.fullname, lb.leave_type";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent logs for the selected user/year
$logs = [];
try {
    $logParams = [$filterYear];
    $logSql = "SELECT lbl.*, u.fullname, u.username FROM leave_balance_logs lbl JOIN users u ON lbl.user_id = u.user_id WHERE lbl.year = ?";
    if (!$canAdmin || $filterUser > 0) {
        $logSql .= " AND lbl.user_id = ?";
        $logParams[] = $filterUser ?: (int)$user['user_id'];
    }
    $logSql .= " ORDER BY lbl.created_at DESC LIMIT 50";
    $stmt2 = $pdo->prepare($logSql);
    $stmt2->execute($logParams);
    $logs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Leave Balances</title>
	<link rel="stylesheet" href="assets/ui.css">
	<script src="https://cdn.tailwindcss.com"></script>
	<style>
		body {
			background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
			overflow-x: hidden;
		}
		
		/* Glass effect */
		.glass {
			background: rgba(255, 255, 255, 0.05);
			backdrop-filter: blur(10px);
			border: 1px solid rgba(255, 255, 255, 0.1);
			border-radius: 12px;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
		}
		
		/* Custom scrollbar */
		.custom-scrollbar::-webkit-scrollbar {
			width: 8px;
			height: 8px;
		}
		
		.custom-scrollbar::-webkit-scrollbar-track {
			background: rgba(255, 255, 255, 0.1);
			border-radius: 4px;
		}
		
		.custom-scrollbar::-webkit-scrollbar-thumb {
			background: rgba(255, 255, 255, 0.3);
			border-radius: 4px;
		}
		
		.custom-scrollbar::-webkit-scrollbar-thumb:hover {
			background: rgba(255, 255, 255, 0.5);
		}
	</style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
	<?php include __DIR__ . '/sidebar.php'; ?>
	
	<!-- Fixed header -->
	<header class="fixed top-0 left-64 right-0 bg-gray-800/80 backdrop-blur-sm border-b border-gray-700 z-50">
		<div class="px-6 py-4">
			<h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-green-400 bg-clip-text text-transparent">
				Leave Balances Dashboard
			</h1>
		</div>
	</header>
	
	<!-- Main content with proper margins -->
	<main class="ml-64 pt-20 p-6 min-h-screen overflow-x-hidden custom-scrollbar">
		<!-- Success/Error Messages -->
		<?php if (isset($success_message)): ?>
		<div class="glass p-4 mb-6 border-l-4 border-green-500 bg-green-500/10">
			<div class="flex items-center">
				<i class="fas fa-check-circle text-green-400 mr-3"></i>
				<p class="text-green-300"><?= e($success_message) ?></p>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if (isset($error_message)): ?>
		<div class="glass p-4 mb-6 border-l-4 border-red-500 bg-red-500/10">
			<div class="flex items-center">
				<i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
				<p class="text-red-300"><?= e($error_message) ?></p>
			</div>
		</div>
		<?php endif; ?>

		<!-- Admin Controls (Only for Admin users) -->
		<?php if ($canAdmin): ?>
		<div class="glass p-6 mb-6">
			<h3 class="text-lg font-semibold text-white mb-4">
				<i class="fas fa-cogs mr-2 text-blue-400"></i>Admin Controls - Leave Balance Management
			</h3>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				<!-- Reset Single User -->
				<div class="bg-gray-800/50 p-4 rounded-lg">
					<h4 class="text-md font-medium text-white mb-3">Reset Single User Leave</h4>
					<form method="POST" class="space-y-3">
						<input type="hidden" name="admin_action" value="reset_user_leave">
						<div>
							<label class="block text-sm text-gray-300 mb-1">User ID</label>
							<input class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
								   type="number" name="target_user_id" required min="1">
						</div>
						<div>
							<label class="block text-sm text-gray-300 mb-1">Leave Type</label>
							<select class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
									name="leave_type" required>
								<option value="">Select Leave Type</option>
								<option value="Annual leave">Annual Leave (28 days)</option>
								<option value="Compassionate leave">Compassionate Leave (7 days)</option>
								<option value="Paternity leave">Paternity Leave (3 days)</option>
								<option value="Maternity leave">Maternity Leave (84 days)</option>
							</select>
						</div>
						<div>
							<label class="block text-sm text-gray-300 mb-1">Year</label>
							<input class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
								   type="number" name="target_year" value="<?= date('Y') ?>" required>
						</div>
						<button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
							<i class="fas fa-user-clock mr-2"></i>Reset User Leave
						</button>
					</form>
				</div>
				
				<!-- Reset All Users for Specific Leave Type -->
				<div class="bg-gray-800/50 p-4 rounded-lg">
					<h4 class="text-md font-medium text-white mb-3">Reset All Users - Specific Leave Type</h4>
					<form method="POST" class="space-y-3" onsubmit="return confirm('Are you sure you want to reset this leave type for ALL users? This action cannot be undone.')">
						<input type="hidden" name="admin_action" value="reset_all_leave_type">
						<div>
							<label class="block text-sm text-gray-300 mb-1">Leave Type</label>
							<select class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
									name="leave_type" required>
								<option value="">Select Leave Type</option>
								<option value="Annual leave">Annual Leave (28 days)</option>
								<option value="Compassionate leave">Compassionate Leave (7 days)</option>
								<option value="Paternity leave">Paternity Leave (3 days)</option>
								<option value="Maternity leave">Maternity Leave (84 days)</option>
							</select>
						</div>
						<div>
							<label class="block text-sm text-gray-300 mb-1">Year</label>
							<input class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
								   type="number" name="target_year" value="<?= date('Y') ?>" required>
						</div>
						<div class="text-sm text-yellow-300 bg-yellow-500/10 p-2 rounded border border-yellow-500/20">
							<i class="fas fa-exclamation-triangle mr-1"></i>
							Warning: This will reset the selected leave type for ALL users.
						</div>
						<button class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors duration-200">
							<i class="fas fa-users-cog mr-2"></i>Reset All Users
						</button>
					</form>
				</div>
				
				<!-- Reset All Leave Types for All Users -->
				<div class="bg-gray-800/50 p-4 rounded-lg">
					<h4 class="text-md font-medium text-white mb-3">Reset Everything</h4>
					<form method="POST" class="space-y-3" onsubmit="return confirm('⚠️ DANGER: This will reset ALL leave types for ALL users! Are you absolutely sure?')">
						<input type="hidden" name="admin_action" value="reset_all_users_all_types">
						<div>
							<label class="block text-sm text-gray-300 mb-1">Year</label>
							<input class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
								   type="number" name="target_year" value="<?= date('Y') ?>" required>
						</div>
						<div class="text-sm text-red-300 bg-red-500/10 p-3 rounded border border-red-500/20">
							<i class="fas fa-skull-crossbones mr-1"></i>
							<strong>DANGER ZONE:</strong> This will reset ALL leave balances for ALL users:
							<ul class="mt-2 text-xs list-disc list-inside">
								<li>Annual Leave → 28 days</li>
								<li>Compassionate → 7 days</li>
								<li>Paternity → 3 days</li>
								<li>Maternity → 84 days</li>
							</ul>
						</div>
						<button class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
							<i class="fas fa-nuclear mr-2"></i>Reset Everything
						</button>
					</form>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Filter Form -->
		<div class="glass p-6 mb-6">
			<form class="flex flex-wrap gap-4 items-end" method="GET" action="index.php">
				<input type="hidden" name="action" value="leave_balances">
				<div>
					<label class="block text-sm text-gray-300 mb-2">Year</label>
					<input class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
						   type="number" name="year" value="<?= e($filterYear) ?>">
				</div>
				<?php if ($canAdmin): ?>
				<div>
					<label class="block text-sm text-gray-300 mb-2">User ID (optional)</label>
					<input class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
						   type="number" name="user_id" value="<?= e($filterUser ?: '') ?>">
				</div>
				<?php endif; ?>
				<div>
					<button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
						<i class="fas fa-filter mr-2"></i>Filter
					</button>
				</div>
			</form>
		</div>

		<!-- Leave Balances Table -->
		<div class="glass overflow-hidden mb-6">
			<div class="px-6 py-4 border-b border-gray-700">
				<h2 class="text-lg font-semibold text-white">Current Leave Balances</h2>
			</div>
			<div class="overflow-x-auto custom-scrollbar">
				<table class="min-w-full text-sm">
					<thead class="bg-gray-800">
						<tr>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Username</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Leave Type</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Year</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Balance (days)</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Last Reset</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-700">
					<?php if (empty($rows)): ?>
						<tr>
							<td colspan="6" class="px-6 py-8 text-center text-gray-400">
								<i class="fas fa-calendar-times text-3xl mb-2"></i>
								<p>No balances found.</p>
							</td>
						</tr>
					<?php else: foreach ($rows as $r): ?>
						<tr class="hover:bg-gray-700/50 transition-colors duration-200">
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($r['fullname']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($r['username']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300">
								<?= e($r['leave_type']) ?>
								<?php if ($r['leave_type'] === 'Annual leave' && $r['balance_days'] == 28): ?>
									<span class="ml-2 px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded-full">
										<i class="fas fa-sync-alt mr-1"></i>Fresh
									</span>
								<?php endif; ?>
							</td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($r['year']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap font-semibold text-blue-400"><?= e($r['balance_days']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300">
								<?php if ($r['last_reset_date']): ?>
									<?= date('d/m/Y', strtotime($r['last_reset_date'])) ?>
									<?php 
									$days_since_reset = (strtotime(date('Y-m-d')) - strtotime($r['last_reset_date'])) / (60 * 60 * 24);
									if ($days_since_reset >= 365): ?>
										<span class="ml-2 px-2 py-1 text-xs bg-yellow-500/20 text-yellow-400 rounded-full">
											<i class="fas fa-exclamation-triangle mr-1"></i>Due Reset
										</span>
									<?php endif; ?>
								<?php else: ?>
									<span class="text-gray-500 italic">Not set</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Recent Logs Table -->
		<div class="glass overflow-hidden">
			<div class="px-6 py-4 border-b border-gray-700">
				<h2 class="text-lg font-semibold text-white">Recent Leave Balance Logs</h2>
			</div>
			<div class="overflow-x-auto custom-scrollbar">
				<table class="min-w-full text-sm">
					<thead class="bg-gray-800">
						<tr>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Time</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Leave Type</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Change</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Balance After</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Reason</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Request ID</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-700">
					<?php if (empty($logs)): ?>
						<tr>
							<td colspan="7" class="px-6 py-8 text-center text-gray-400">
								<i class="fas fa-history text-3xl mb-2"></i>
								<p>No logs found.</p>
							</td>
						</tr>
					<?php else: foreach ($logs as $l): ?>
						<tr class="hover:bg-gray-700/50 transition-colors duration-200">
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['created_at']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['fullname']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['leave_type']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap <?= ($l['change_days'] < 0 ? 'text-red-400' : 'text-green-400') ?> font-semibold">
								<?= e($l['change_days']) ?>
							</td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['balance_after']) ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['reason'] ?? '') ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-gray-300"><?= e($l['request_id'] ?? '') ?></td>
						</tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</main>
</body>
</html>
