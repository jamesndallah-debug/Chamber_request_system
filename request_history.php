<?php
// FILE: request_history.php
// View for displaying the complete history of a user's requests

/** @var array $user */
/** @var array $requests */

// Get all requests for the current user
// $requests is already fetched in index.php case 'request_history'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; }
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-req { background:#ffffff; border:1px solid #e2e8f0; transition: transform .25s ease, box-shadow .25s ease; }
        .card-req:hover { transform: translateY(-2px); box-shadow:0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .status-pill { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-approved { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #fef9c3; color: #854d0e; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .filter-btn { padding: 6px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; border: 1px solid #e2e8f0; background: white; color: #64748b; }
        .filter-btn.active { background: #0b5ed7; color: white; border-color: #0b5ed7; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col ml-72">
        <!-- Top Nav -->
        <header class="glass shadow-sm p-6 flex items-center justify-between sticky top-0 z-40 bg-white/90">
            <h1 class="text-2xl font-bold text-gray-800">My Request History</h1>
            <div class="flex items-center space-x-4">
                <p class="text-sm text-gray-600">
                    Welcome, <span class="font-medium text-gray-900"><?= e($user['fullname']) ?></span>
                </p>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-8">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">All Requests</h2>
                        <p class="text-sm text-gray-500">View and track all your submitted requests</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search requests..." class="pl-4 pr-10 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
                            <svg class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button class="filter-btn active" data-status="all">All Requests</button>
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="approved">Approved</button>
                    <button class="filter-btn" data-status="rejected">Rejected</button>
                </div>

                <?php if (empty($requests)): ?>
                <div class="glass p-12 text-center rounded-2xl">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">No requests found</h3>
                    <p class="text-gray-500 mb-6">You haven't submitted any requests yet.</p>
                    <a href="index.php?action=new_request" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">Create Your First Request</a>
                </div>
                <?php else: ?>
                <div class="space-y-4" id="requestsContainer">
                    <?php foreach ($requests as $req): 
                        $status = strtolower($req['status_name'] ?? 'pending');
                        $statusClass = "status-" . $status;
                        
                        $emojiMap = [
                            'Imprest request' => '💰',
                            'Reimbursement request' => '↩️',
                            'Retirement' => '📑',
                            'Salary advance' => '💵',
                            'Travel form' => '✈️',
                            'Annual leave' => '🏖️',
                            'Compassionate leave' => '🤝',
                            'Sick leave' => '🤒',
                            'Staff clearance form' => '📋'
                        ];
                        $icon = $emojiMap[$req['request_type']] ?? '📝';
                    ?>
                    <div class="card-req p-5 rounded-xl flex items-center justify-between request-item" data-status="<?= $status ?>" data-search="<?= strtolower(e($req['title'] . ' ' . $req['request_type'])) ?>">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-xl shadow-sm border border-gray-100">
                                <?= $icon ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900"><?= e($req['title']) ?></h4>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs font-medium text-gray-500 px-2 py-0.5 bg-gray-100 rounded"><?= e($req['request_type']) ?></span>
                                    <span class="text-xs text-gray-400"><?= date('M d, Y • g:i a', strtotime($req['created_at'])) ?></span>
                                    <?php if ($req['amount'] > 0): ?>
                                    <span class="text-xs font-bold text-blue-600"><?= number_format($req['amount'], 2) ?> TZS</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6">
                            <span class="status-pill <?= $statusClass ?>"><?= e($req['status_name']) ?></span>
                            <a href="index.php?action=view_request&id=<?= $req['request_id'] ?>" class="text-blue-600 hover:text-blue-800 font-bold text-sm flex items-center gap-1 group">
                                Details 
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterBtns = document.querySelectorAll('.filter-btn');
            const requestItems = document.querySelectorAll('.request-item');

            function filter() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeStatus = document.querySelector('.filter-btn.active').dataset.status;

                requestItems.forEach(item => {
                    const status = item.dataset.status;
                    const searchText = item.dataset.search;
                    
                    const statusMatch = activeStatus === 'all' || status === activeStatus;
                    const searchMatch = searchText.includes(searchTerm);

                    if (statusMatch && searchMatch) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filter);

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filter();
                });
            });
        });
    </script>
</body>
</html>