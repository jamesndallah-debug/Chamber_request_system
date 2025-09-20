<?php
// FILE: messages.php
// User messages page for viewing and managing personal messages

if (!defined('ACCESS_ALLOWED')) {
    define('ACCESS_ALLOWED', true);
}

require_once __DIR__ . '/function.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$user = $_SESSION['user'];

// Handle delete message (allow deletion of both sent and received messages)
if (isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];
    try {
        // Allow user to delete messages they sent or received
        $stmt = $pdo->prepare("DELETE FROM user_messages WHERE id = ? AND (to_user_id = ? OR from_user_id = ?)");
        if ($stmt->execute([$message_id, $user['user_id'], $user['user_id']])) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message deleted successfully.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete message.'];
        }
    } catch (Exception $e) {
        error_log("Error deleting message: " . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete message.'];
    }
    header('Location: index.php?action=messages');
    exit;
}

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $message_id = (int)$_POST['message_id'];
    try {
        $stmt = $pdo->prepare("UPDATE user_messages SET is_read = 1 WHERE id = ? AND to_user_id = ?");
        $stmt->execute([$message_id, $user['user_id']]);
    } catch (Exception $e) {
        error_log("Error marking message as read: " . $e->getMessage());
    }
    header('Location: index.php?action=messages');
    exit;
}

// Get user's messages (both received and sent messages)
$messages = [];
try {
    // First check if user_messages table exists
    $check_table = $pdo->prepare("SHOW TABLES LIKE 'user_messages'");
    $check_table->execute();
    
    if ($check_table->fetchColumn()) {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   CASE 
                       WHEN m.from_user_id = ? THEN 'sent'
                       ELSE 'received'
                   END as message_type,
                   sender.fullname as sender_name,
                   receiver.fullname as receiver_name
            FROM user_messages m 
            LEFT JOIN users sender ON m.from_user_id = sender.user_id 
            LEFT JOIN users receiver ON m.to_user_id = receiver.user_id 
            WHERE (m.to_user_id = ? OR m.from_user_id = ?)
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user['user_id'], $user['user_id'], $user['user_id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log message count
        error_log("Messages page: Found " . count($messages) . " messages for user " . $user['user_id']);
        
        // Debug: Log first message if any exist
        if (!empty($messages)) {
            error_log("First message: " . json_encode($messages[0]));
        }
    } else {
        error_log("user_messages table does not exist");
        // Create the table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT NOT NULL,
            to_user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT DEFAULT 0,
            is_private TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_user_id) REFERENCES users(user_id),
            FOREIGN KEY (to_user_id) REFERENCES users(user_id)
        )");
        error_log("Created user_messages table");
    }
} catch (Exception $e) {
    error_log("Error fetching messages: " . $e->getMessage());
}

// Count unread messages (all messages for this user)
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_messages WHERE to_user_id = ? AND is_read = 0");
    $stmt->execute([$user['user_id']]);
    $unread_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error counting unread messages: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-app {
            background: radial-gradient(1000px 600px at -10% -10%, rgba(11,94,215,0.25), transparent 60%),
                        radial-gradient(800px 500px at 110% 10%, rgba(212,175,55,0.20), transparent 60%),
                        radial-gradient(900px 550px at 50% 120%, rgba(22,163,74,0.18), transparent 60%),
                        linear-gradient(180deg, #0b1220 0%, #0a1020 100%);
        }
        .orb { position:absolute; border-radius:9999px; filter: blur(40px); opacity:.4; pointer-events:none; }
        .orb-blue { background:#0b5ed7; animation: floatY 9s ease-in-out infinite; }
        .orb-gold { background:#d4af37; animation: floatX 11s ease-in-out infinite; }
        .orb-green{ background:#16a34a; animation: floatXY 13s ease-in-out infinite; }
        @keyframes floatY { 0%{transform:translateY(0)} 50%{transform:translateY(-16px)} 100%{transform:translateY(0)} }
        @keyframes floatX { 0%{transform:translateX(0)} 50%{transform:translateX(18px)} 100%{transform:translateX(0)} }
        @keyframes floatXY { 0%{transform:translate(0,0)} 50%{transform:translate(-14px,12px)} 100%{transform:translate(0,0)} }
        .glass { background: linear-gradient(180deg, rgba(11,20,40,.65), rgba(5,10,30,.55)); backdrop-filter: blur(8px); border:1px solid rgba(255,255,255,.08); }
        .header-pulse { animation: headerPulse 6s ease-in-out infinite; }
        @keyframes headerPulse { 0%,100% { box-shadow: 0 0 0 rgba(11,94,215,0); } 50% { box-shadow: 0 0 24px rgba(11,94,215,.25), 0 0 40px rgba(212,175,55,.15); } }
        .btn-hero { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; box-shadow:0 8px 24px rgba(11,94,215,.35); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; }
        .btn-hero:hover { filter:brightness(1.07); transform: translateY(-2px); box-shadow:0 14px 28px rgba(11,94,215,.35); }
        .title-gradient { background: linear-gradient(90deg,#0b5ed7,#d4af37,#16a34a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .divider { height:1px; background:linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent); }
        .message-card { background: linear-gradient(180deg, rgba(11,20,40,.4), rgba(5,10,30,.4)); border:1px solid rgba(255,255,255,.08); transition: all .3s ease; }
        .message-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(11,94,215,.15); }
        .message-unread { border-left: 4px solid #0b5ed7; background: linear-gradient(180deg, rgba(11,94,215,.1), rgba(11,94,215,.05)); }
        .marquee { white-space: nowrap; overflow: hidden; }
        .marquee > span { display:inline-block; padding-left:100%; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%);} 100% { transform: translateX(-100%);} }
    </style>
</head>
<body class="bg-app flex min-h-screen relative">
    <div class="orb orb-blue" style="top:-60px; left:-40px; width:220px; height:220px;"></div>
    <div class="orb orb-gold" style="top:20%; right:-80px; width:260px; height:260px;"></div>
    <div class="orb orb-green" style="bottom:-80px; left:20%; width:240px; height:240px;"></div>
    
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Top Nav -->
        <header class="glass header-pulse shadow p-4 flex items-center justify-between sticky top-0 z-40">
            <h1 class="text-2xl font-bold title-gradient">Messages</h1>
            <div class="flex items-center space-x-4">
                <p>
                    Welcome, <span class="font-semibold text-slate-100"><?= htmlspecialchars($user['fullname']) ?></span>
                </p>
                <?php if ($unread_count > 0): ?>
                <span class="px-3 py-1 rounded-full text-sm bg-red-500/20 text-red-300 border border-red-500/30 font-bold">
                    <?= $unread_count ?> Unread
                </span>
                <?php endif; ?>
                <a href="index.php?action=dashboard" class="bg-slate-900/40 text-slate-100 border border-white/10 font-medium py-2 px-4 rounded-lg shadow-sm hover:bg-slate-900/60">
                    ← Back to Dashboard
                </a>
            </div>
        </header>
        <div class="glass px-4 py-2">
            <div class="marquee text-slate-100 text-sm">
                <span>Messages — Your personal communication center 📧 Stay connected with your team 💙💛💚 • </span>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6">
            <div class="glass p-6 rounded-xl">
                <?php if (isset($_SESSION['flash'])): 
                    $flash = $_SESSION['flash'];
                    unset($_SESSION['flash']);
                ?>
                <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-500/20 text-green-100 border border-green-500/30' : 'bg-red-500/20 text-red-100 border border-red-500/30' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold title-gradient">Your Messages 📧</h2>
                    <div class="text-slate-300">
                        Total: <?= count($messages) ?> | Unread: <?= $unread_count ?>
                    </div>
                </div>
                
                <!-- Debug Info -->
                <div class="mb-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <p class="text-blue-300 text-sm">
                        <strong>Debug:</strong> Found <?= count($messages) ?> messages for user ID <?= $user['user_id'] ?>.
                        <?php if (empty($messages)): ?>
                        <br>This could mean: 1) No messages sent/received yet, 2) Database table issue, 3) Message sending not working.
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (!empty($messages)): ?>
                <div class="space-y-4">
                    <?php foreach ($messages as $message): ?>
                    <div class="message-card <?= !$message['is_read'] ? 'message-unread' : '' ?> p-5 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-bold text-lg text-slate-100"><?= htmlspecialchars($message['subject']) ?></h3>
                                    <?php if (!$message['is_read']): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-slate-400 mb-3">
                                    <?php if ($message['message_type'] === 'sent'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-300 mr-2">SENT</span>
                                        To: <span class="font-medium text-slate-300"><?= htmlspecialchars($message['receiver_name'] ?? 'Unknown') ?></span> • 
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300 mr-2">RECEIVED</span>
                                        From: <span class="font-medium text-slate-300"><?= htmlspecialchars($message['sender_name'] ?? 'System') ?></span> • 
                                    <?php endif; ?>
                                    <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                </div>
                                <div class="text-slate-200 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($message['message'])) ?>
                                </div>
                                <?php if ($message['message_type'] === 'received' && isset($message['is_private']) && $message['is_private']): ?>
                                <div class="mt-4 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                                    <p class="text-yellow-300 text-sm">
                                        <strong>📧 Private Message from Executive Director</strong><br>
                                        This message is confidential and intended only for you. Other users cannot see or reply to this message.
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <?php if (!$message['is_read'] && $message['message_type'] === 'received'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <button type="submit" name="mark_read" class="bg-blue-500/20 text-blue-300 border border-blue-500/30 font-medium py-1 px-3 rounded text-sm hover:bg-blue-500/30">
                                        ✓ Mark Read
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <button type="submit" name="delete_message" 
                                            onclick="return confirm('Are you sure you want to delete this <?= $message['message_type'] === 'sent' ? 'sent' : 'received' ?> message?')"
                                            class="bg-red-500/20 text-red-300 border border-red-500/30 font-medium py-1 px-3 rounded text-sm hover:bg-red-500/30">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-slate-400 py-20">
                    <div class="text-6xl mb-4">📧</div>
                    <p class="text-xl mb-2">No messages yet</p>
                    <p class="text-sm">Messages from administrators and system notifications will appear here.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
