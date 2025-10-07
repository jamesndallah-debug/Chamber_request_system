<?php
// FILE: view_request.php
// Shows a single request and allows role-based approval/rejection.

// Check if this file is being accessed directly
if (!isset($pdo)) {
    require_once __DIR__ . '/function.php';
    require_once __DIR__ . '/Request.php';
    require_once __DIR__ . '/User.php';
    
    // Create instances of models
    $requestModel = new RequestModel($pdo);
    $userModel = new UserModel($pdo);
    
    // Get the current user from the session
    session_start();
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
    
    // Validate request ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: index.php?action=dashboard');
        exit;
    }
    
    // Get request data
    $request = $requestModel->get_request_by_id($_GET['id']);
    if (!$request) {
        header('Location: index.php?action=dashboard');
        exit;
    }
}
// Now $pdo, $requestModel, $user, and $request are available

// Process message submission and create table if needed - moved here before any HTML output
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        sender_user_id INT NOT NULL,
        recipient_user_id INT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_req (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_msg']) && isset($user)) {
    $message_id = (int)$_POST['message_id'];
    // Only allow users to delete their own messages or admins to delete any message
    $stmt = $pdo->prepare("SELECT sender_user_id FROM request_messages WHERE id = ? AND request_id = ?");
    $stmt->execute([$message_id, $request['request_id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($message && ($message['sender_user_id'] == $user['user_id'] || (int)$user['role_id'] === 1)) {
        $stmt = $pdo->prepare("DELETE FROM request_messages WHERE id = ?");
        $stmt->execute([$message_id]);
    }
    
    header('Location: index.php?action=view_request&id=' . urlencode($request['request_id']));
    exit;
}

// Process message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg']) && isset($user)) {
    $content = trim($_POST['content'] ?? '');
    $to = !empty($_POST['recipient_user_id']) ? (int)$_POST['recipient_user_id'] : null;
    
    // Only ED can send targeted messages, others can only send public messages
    if ((int)$user['role_id'] !== 4 && $to !== null) {
        $to = null; // Force public message for non-ED users
    }
    
    if ($content !== '') {
        $stmt = $pdo->prepare("INSERT INTO request_messages (request_id, sender_user_id, recipient_user_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$request['request_id'], $user['user_id'], $to, $content]);
        header('Location: index.php?action=view_request&id=' . urlencode($request['request_id']));
        exit;
    }
}

// Fetch messages with sender and recipient information - filter based on user permissions
$stmt = $pdo->prepare("SELECT rm.*, 
    sender.fullname AS sender_name,
    recipient.fullname AS recipient_name,
    recipient.user_id AS recipient_id
FROM request_messages rm 
JOIN users sender ON rm.sender_user_id = sender.user_id 
LEFT JOIN users recipient ON rm.recipient_user_id = recipient.user_id 
WHERE rm.request_id = ? 
AND (
    rm.recipient_user_id IS NULL OR  -- Public messages (everyone can see)
    rm.recipient_user_id = ? OR      -- Messages addressed to current user
    rm.sender_user_id = ? OR         -- Messages sent by current user
    ? = 4                            -- ED can see all messages
)
ORDER BY rm.created_at ASC");
$stmt->execute([$request['request_id'], $user['user_id'], $user['user_id'], (int)$user['role_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Request #<?= e($request['request_id']) ?> | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body{font-family:'Inter',sans-serif}
        @media print { .no-print { display: none !important; } }
        .orb-bg { position:absolute; inset:-40px; filter:blur(70px); opacity:.35; pointer-events:none; }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#0b5ed710; border:1px solid #0b5ed733; color:#0b5ed7; font-weight:600; }
        .btn-gradient { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; }
        .btn-gradient:hover { filter:brightness(1.05); }
        .confetti { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
        .confetti i { position:absolute; width:8px; height:14px; transform: rotate(15deg); opacity:.9; will-change: transform; }
        
        /* Improve dropdown option visibility */
    select option {
        font-weight: 600;
        color: #111827;
        padding: 10px;
        background-color: white;
        font-size: 1.05rem;
    }
    
    /* Enhance Send To field visibility */
    .send-to-field {
        position: relative;
    }
    
    .send-to-field::after {
        content: "";
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 8px solid #0b5ed7;
        pointer-events: none;
    }
    
    .send-to-field select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        cursor: pointer;
        border: 2px solid #0b5ed7;
        box-shadow: 0 2px 4px rgba(11, 94, 215, 0.1);
    }
    
    /* Enhance selected option visibility */
    select:focus option:checked {
        background-color: #0b5ed7 !important;
        color: white !important;
        font-weight: 600 !important;
    }
    
    /* Selected recipient indicator */
    .selected-recipient {
        display: inline-block;
        font-weight: 600;
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Improve remark field visibility */
    textarea[name="remark"] {
        border: 2px solid #0b5ed7;
        box-shadow: 0 2px 4px rgba(11, 94, 215, 0.1);
    }
    
    /* Improve message visibility */
    .message-content {
        font-weight: 500;
        color: #111827;
        line-height: 1.6;
        letter-spacing: 0.01em;
    }
    
    /* Delete button styling */
    .delete-btn {
        opacity: 0.7;
        transition: all 0.2s ease;
    }
    
    .delete-btn:hover {
        opacity: 1;
        transform: scale(1.1);
    }
        
        /* Enhanced styles for better UI */
        .text-brand-gold { color: #d4af37; }
        .text-brand-blue { color: #0b5ed7; }
        .text-brand-green { color: #16a34a; }
        
        /* Improved text clarity */
        body {
            font-size: 16px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            letter-spacing: -0.01em;
        }
        .text-xs {
            font-size: 0.8rem;
        }
        .text-sm {
            font-size: 0.95rem;
        }
        .text-lg {
            font-size: 1.15rem;
        }
        .text-xl {
            font-size: 1.35rem;
        }
        .text-2xl {
            font-size: 1.65rem;
        }
        
        /* Enhanced contrast for better readability */
        .text-gray-500 {
            color: #64748b;
        }
        .text-gray-700 {
            color: #334155;
        }
        .text-gray-800 {
            color: #1e293b;
        }
        .text-gray-900 {
            color: #0f172a;
        }
        
        /* Improved responsive behavior */
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr !important; }
            .hidden-mobile { display: none; }
            main { padding: 1rem !important; }
            .p-8, .p-5 { padding: 1rem !important; }
            .gap-6 { gap: 0.75rem !important; }
            body { font-size: 15px; }
            .text-xl { font-size: 1.25rem; }
            .text-2xl { font-size: 1.5rem; }
            
            /* Adjust header for mobile */
            header {
                padding: 0.75rem !important;
                flex-direction: column;
                align-items: flex-start !important;
            }
            header h1 {
                margin-bottom: 0.5rem;
            }
            header .flex.items-center.gap-4 {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        /* Smooth transitions */
        .btn, .input, select, a, .chip {
            transition: all 0.2s ease-in-out;
        }
        
        /* Better scrollbars for message container */
        #messages-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        #messages-container::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        #messages-container::-webkit-scrollbar-thumb {
            background: rgba(11,94,215,0.2);
            border-radius: 10px;
        }
        #messages-container::-webkit-scrollbar-thumb:hover {
            background: rgba(11,94,215,0.4);
        }
        
        /* Card hover effects */
        .bg-gray-50\/50 {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .bg-gray-50\/50:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        /* Focus styles for better accessibility */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid rgba(11,94,215,0.5);
            outline-offset: 2px;
        }
        
        /* Improved button styles */
        .btn {
            font-weight: 600;
            letter-spacing: 0.01em;
        }
    </style>
    <?php
        $type = (string)($request['request_type'] ?? '');
        $emojiMap = [
            'Impest request' => '💰',
            'Reimbursement request' => '↩️',
            'TCCIA retirement request' => '📑',
            'Salary advance' => '💵',
            'Travel form' => '✈️',
            'Annual leave' => '🏖️',
            'Compassionate leave' => '🤝',
            'Paternity leave' => '👶',
            'Maternity leave' => '🤱',
            'Sick leave' => '🤒',
            'Staff clearance form' => '📋',
        ];
        $typeEmoji = $emojiMap[$type] ?? '📝';
    ?>
</head>
<body class="bg-gray-900 flex min-h-screen">
    <div class="bg-orb"></div><!-- Added third orb for better background effect -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="flex-1 flex flex-col">
        <header class="bg-slate-800/80 backdrop-blur border-b border-white/10 sticky top-0 z-40 p-5 flex items-center justify-between text-white shadow-lg">
            <h1 class="text-2xl font-semibold flex items-center gap-3">
                <span class="flex items-center gap-2">
                    <span class="text-brand-gold">Request #<?= e($request['request_id']) ?></span>
                </span>
                <span class="chip bg-white/10 border-white/20"><span class="text-xl"><?= e($typeEmoji) ?></span><span><?= e($type) ?></span></span>
            </h1>
            <div class="no-print flex items-center gap-4">
                <a href="index.php?action=dashboard" class="flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-colors"><span>←</span> Back to Dashboard</a>
                <?php $isAdmin = ((int)$user['role_id'] === 7); ?>
                <?php if ($isAdmin): ?>
                <form method="POST" action="index.php?action=delete_request" onsubmit="return confirm('Delete this request permanently? This cannot be undone.');">
                    <input type="hidden" name="id" value="<?= e($request['request_id']) ?>" />
                    <button type="submit" class="px-5 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all" style="background:linear-gradient(90deg,#ef4444,#b91c1c); color:#fff;">🗑️ Delete</button>
                </form>
                <?php endif; ?>
                <button onclick="window.print()" class="btn-gradient px-5 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all">🖨️ Print</button>
            </div>
        </header>

        <main class="flex-1 p-8">
            <div class="relative">
                <div aria-hidden="true" class="orb-bg" style="background:radial-gradient(closest-side,#0b5ed7,transparent 70%),radial-gradient(closest-side,#d4af37,transparent 70%) 70% 10%/40% 40% no-repeat,radial-gradient(closest-side,#16a34a,transparent 70%) 30% 90%/35% 35% no-repeat;"></div>
            </div>
            <div class="bg-white/80 backdrop-blur p-8 rounded-2xl shadow-xl ring-1 ring-black/5 max-w-5xl mx-auto">
                <div class="mb-8 border-b border-gray-200 pb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Request Information</h2>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Title</div>
                            <div class="font-medium text-gray-900 text-lg"><?= e($request['title']) ?></div>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Type</div>
                            <div class="font-medium text-gray-900 text-lg"><?= e($request['request_type']) ?></div>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Status</div>
                            <div class="font-medium text-lg">
                                <?php 
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                    'approved' => 'bg-green-100 text-green-800 border border-green-200',
                                    'rejected' => 'bg-red-100 text-red-800 border border-red-200',
                                    'processing' => 'bg-blue-100 text-blue-800 border border-blue-200'
                                ];
                                $statusClass = $statusColors[strtolower($request['status_name'])] ?? 'bg-gray-100 text-gray-800 border border-gray-200';
                                ?>
                                <span class="px-3 py-1 rounded-full <?= $statusClass ?> font-semibold text-base"><?= e($request['status_name']) ?></span>
                            </div>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Employee</div>
                            <div class="font-medium text-gray-900"><?= e($request['fullname']) ?></div>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Department</div>
                            <div class="font-medium text-gray-900"><?= e($request['department']) ?></div>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-lg shadow-sm">
                            <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1">Created</div>
                            <div class="font-medium text-gray-900"><?= date('Y-m-d H:i', strtotime($request['created_at'])) ?></div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Description</h2>
                    <div class="bg-gray-50/50 p-5 rounded-lg shadow-sm">
                        <div class="text-gray-800 whitespace-pre-wrap leading-relaxed"><?= e($request['description']) ?></div>
                    </div>
                </div>

                <?php if (!empty($request['attachment_path'])): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Attachment</h2>
                        <div class="bg-gray-50/50 p-5 rounded-lg shadow-sm flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <a class="text-blue-600 hover:text-blue-800 font-medium" href="uploads/<?= e($request['attachment_path']) ?>" target="_blank">View attachment</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (can_approve($user['role_id'], $request)): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Request Action</h2>
                        <div class="bg-blue-50/50 p-5 rounded-lg shadow-sm border border-blue-100">
                            <form method="POST" action="index.php?action=process_request">
                                <input type="hidden" name="id" value="<?= e($request['request_id']) ?>">
                                <div class="grid md:grid-cols-3 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-base font-semibold text-gray-900 mb-2">Remark <span class="text-brand-blue">(optional)</span></label>
                                        <textarea class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-base font-medium text-gray-900" name="remark" placeholder="Add your remarks or notes here..." rows="3"></textarea>
                                    </div>
                                    <div class="flex flex-col justify-end gap-4">
                                        <button id="approve_btn" class="btn btn-success py-3 text-base font-medium flex justify-center" type="submit" name="status" value="approved">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            Approve Request
                                        </button>
                                        <button class="btn btn-danger py-3 text-base font-medium flex justify-center" type="submit" name="status" value="rejected">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                            Reject Request
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php $details = json_decode((string)($request['details_json'] ?? '{}'), true) ?: []; ?>
                <?php if (!empty($details)): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Additional Details</h2>
                        <div class="bg-gray-50/50 p-5 rounded-lg shadow-sm">
                            <div class="grid md:grid-cols-2 gap-5">
                                <?php foreach ($details as $k => $v): ?>
                                    <div class="border-b border-gray-100 pb-3">
                                        <div class="text-gray-500 text-xs uppercase tracking-wider font-semibold mb-1"><?= e(ucwords(str_replace('_',' ', $k))) ?></div>
                                        <div class="font-medium text-gray-900"><?= e((string)$v) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Messages thread: ED can remark to specific user; all can reply -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                        </svg>
                        Messages
                        <span class="text-sm text-gray-500 font-normal ml-2">(ED may address specific users; others can reply)</span>
                    </h2>
                    <div class="bg-gray-50/50 rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-5 border-b border-gray-200 bg-white/50">
                            <div class="max-h-96 overflow-y-auto pr-2 space-y-5" id="messages-container">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center py-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <div class="text-gray-500">No messages yet. Be the first to send a message!</div>
                                    </div>
                                <?php else: foreach ($messages as $m): ?>
                                    <?php 
                                    // Determine if this is a private message
                                    $isPrivateMessage = !empty($m['recipient_id']);
                                    $isMessageForCurrentUser = $isPrivateMessage && ($m['recipient_id'] == $user['user_id'] || $m['sender_user_id'] == $user['user_id']);
                                    $canUserReply = !$isPrivateMessage || $isMessageForCurrentUser || (int)$user['role_id'] === 4;
                                    ?>
                                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 <?= $isPrivateMessage ? 'border-l-4 border-l-blue-500 bg-blue-50/30' : '' ?>">
                                        <div class="flex flex-wrap items-center justify-between mb-2 gap-2">
                                            <div class="flex items-center gap-2">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-lg">
                                                    <?= strtoupper(substr($m['sender_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900"><?= e($m['sender_name']) ?></div>
                                                    <div class="text-xs text-gray-600 font-medium"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <?php if (!empty($m['recipient_name'])): ?>
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full flex items-center gap-1 font-medium">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                                        </svg>
                                                        To: <?= e($m['recipient_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full flex items-center gap-1 font-medium">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                        </svg>
                                                        Public
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($m['sender_user_id'] == $user['user_id'] || (int)$user['role_id'] === 1): ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" class="inline">
                                                        <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                                        <button type="submit" name="delete_msg" class="text-red-500 hover:text-red-700 transition-colors delete-btn" title="Delete message">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-gray-900 whitespace-pre-wrap leading-relaxed pl-4 md:pl-10 text-base font-medium message-content"><?= nl2br(e($m['content'])) ?></div>
                                        
                                        <?php if ($isPrivateMessage && $m['recipient_id'] == $user['user_id'] && $m['sender_user_id'] != $user['user_id']): ?>
                                            <div class="mt-3 pl-4 md:pl-10">
                                                <div class="text-xs text-blue-600 font-medium bg-blue-50 px-2 py-1 rounded inline-block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.894A1 1 0 0018 16V3z" clip-rule="evenodd" />
                                                    </svg>
                                                    This message was sent directly to you
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                        <div class="p-5 bg-white">
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Your Message</label>
                                    <div class="relative">
                                        <textarea id="message-content" name="content" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-base font-medium text-gray-900" placeholder="Write your message here..." required></textarea>
                                        <button type="button" onclick="document.getElementById('message-content').value = '';" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition-colors delete-btn" title="Clear message">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <?php if ((int)$user['role_id'] === 4): ?>
                                        <div class="flex-1 send-to-field">
                                            <label class="block text-base font-semibold text-gray-900 mb-2">Send To <span class="text-brand-blue">(Select recipient)</span></label>
                                            <select name="recipient_user_id" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-base font-semibold text-gray-900">
                                                <option value="" class="font-semibold">Everyone (Public Message)</option>
                                                <?php 
                                                // Get the requester
                                                $requester_id = $request['user_id'];
                                                $stmt = $pdo->prepare("SELECT user_id, fullname, role_id FROM users WHERE user_id = ?");
                                                $stmt->execute([$requester_id]);
                                                $requester = $stmt->fetch(PDO::FETCH_ASSOC);
                                                if ($requester) {
                                                    echo "<option value=\"" . e($requester['user_id']) . "\" class=\"font-semibold\">" . e($requester['fullname']) . " (Requester)</option>";
                                                }
                                                
                                                // Get users by roles
                                                $roles_to_fetch = [2 => 'HRM', 3 => 'HOD', 5 => 'Finance', 6 => 'Internal Auditor'];
                                                foreach ($roles_to_fetch as $role_id => $role_label) {
                                                    $stmt = $pdo->prepare("SELECT user_id, fullname FROM users WHERE role_id = ?");
                                                    $stmt->execute([$role_id]);
                                                    $role_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($role_users as $role_user) {
                                                        echo "<option value=\"" . e($role_user['user_id']) . "\" class=\"font-semibold\">" . e($role_user['fullname']) . " (" . e($role_label) . ")</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div class="<?= ((int)$user['role_id'] === 4) ? '' : 'flex-1' ?>">
                                        <button class="btn btn-primary w-full py-3 text-base font-medium flex justify-center items-center gap-2" name="send_msg" value="1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                            </svg>
                                            Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="confetti" class="confetti no-print" aria-hidden="true"></div>
</body>
</html>
<script>
(function(){
    // Confetti animation for approval button
    const btn = document.getElementById('approve_btn');
    const layer = document.getElementById('confetti');
    if (btn && layer) {
        function burst(){
            const colors = ['#0b5ed7','#d4af37','#16a34a','#60a5fa','#fde047','#86efac'];
            for (let i=0;i<120;i++){
                const p = document.createElement('i');
                const x = (Math.random()*100);
                const s = 8 + Math.random()*8;
                const d = 800 + Math.random()*1400;
                p.style.left = x + 'vw';
                p.style.top = '-10px';
                p.style.background = colors[Math.floor(Math.random()*colors.length)];
                p.style.width = s + 'px';
                p.style.height = (s*1.4) + 'px';
                const tx = (Math.random()*2-1) * 60;
                const ty = 100 + Math.random()*30;
                p.animate([
                    { transform: 'translate(0,0) rotate(0deg)', opacity: 1 },
                    { transform: `translate(${tx}vw, ${ty}vh) rotate(${Math.random()*720-360}deg)`, opacity: 0.9 }
                ], { duration: d, easing: 'cubic-bezier(.2,.8,.2,1)' });
                layer.appendChild(p);
                setTimeout(()=> p.remove(), d+50);
            }
        }
        btn.addEventListener('click', function(){ burst(); });
    }
    
    // Auto-scroll messages container to bottom
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer && messagesContainer.children.length > 0) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Enhance dropdown visibility
    const recipientSelect = document.querySelector('select[name="recipient_user_id"]');
    if (recipientSelect) {
        recipientSelect.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].textContent;
            const selectLabel = document.querySelector('.send-to-field label');
            const existingSpan = selectLabel.querySelector('.selected-recipient');
            
            if (existingSpan) {
                existingSpan.textContent = ' — ' + selectedText;
            } else {
                const span = document.createElement('span');
                span.className = 'selected-recipient text-brand-blue';
                span.textContent = ' — ' + selectedText;
                selectLabel.appendChild(span);
            }
        });
    }
})();
</script>
