<?php
// Basic settings page - step by step approach
require_once __DIR__ . '/function.php';

// Check user
$user = current_user();
if (!$user || $user['role_id'] != 7) {
    header('Location: index.php?action=dashboard');
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch");
    }
    
    global $pdo;
    
    // Simple directorate add
    if (isset($_POST['add_directorate'])) {
        $name = trim($_POST['directorate_name'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO directorates (name, created_at) VALUES (?, CURRENT_TIMESTAMP)");
            $stmt->execute([$name]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Directorate added!'];
        }
        header('Location: index.php?action=settings_basic');
        exit;
    }
}

// Get data
global $pdo;
$directorates = $pdo->query("SELECT * FROM directorates ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">System Settings</h1>
        
        <?php if (isset($_SESSION['flash'])): 
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        ?>
        <div class="p-4 mb-4 rounded <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Directorates -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Directorates</h2>
                
                <form method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="flex gap-2">
                        <input type="text" name="directorate_name" placeholder="New directorate name" 
                               class="flex-1 px-3 py-2 border rounded" required>
                        <button type="submit" name="add_directorate" 
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Add
                        </button>
                    </div>
                </form>
                
                <table class="w-full">
                    <tr class="border-b">
                        <th class="text-left p-2">Name</th>
                        <th class="text-left p-2">Status</th>
                    </tr>
                    <?php foreach ($directorates as $directorate): ?>
                    <tr class="border-b">
                        <td class="p-2"><?= htmlspecialchars($directorate['name']) ?></td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded text-sm <?= $directorate['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $directorate['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <!-- Roles -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Roles</h2>
                <table class="w-full">
                    <tr class="border-b">
                        <th class="text-left p-2">Name</th>
                        <th class="text-left p-2">Status</th>
                    </tr>
                    <?php foreach ($roles as $role): ?>
                    <tr class="border-b">
                        <td class="p-2"><?= htmlspecialchars($role['role_name']) ?></td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded text-sm <?= $role['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $role['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
