<?php
// FILE: request_history.php
// View for displaying the complete history of a user's requests

require_once __DIR__ . '/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?action=login');
    exit;
}

// Get the logged-in user
$user = $userModel->get_user_by_id($_SESSION['user_id']);
if (!$user) {
    header('Location: index.php?action=login');
    exit;
}

// Get all requests for the current user
$requests = $requestModel->get_requests_by_user_id($user['user_id']);

// Include header and sidebar
include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">My Request History</h2>
                    <p class="text-muted">View the complete history of all your requests</p>
                    
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search requests...">
                        </div>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="status-filter mb-4">
                        <button class="filter-btn active" data-status="all">All</button>
                        <button class="filter-btn" data-status="pending">Pending</button>
                        <button class="filter-btn" data-status="approved">Approved</button>
                        <button class="filter-btn" data-status="rejected">Rejected</button>
                    </div>
                    
                    <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        <p>You haven't made any requests yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                <tr class="request-row" data-status="<?= strtolower($req['status_name']) ?>">
                                    <td><?= $req['request_id'] ?></td>
                                    <td><?= htmlspecialchars($req['request_type']) ?></td>
                                    <td><?= htmlspecialchars($req['title']) ?></td>
                                    <td><?= number_format($req['amount'], 2) ?> TZS</td>
                                    <td><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $req['status_name'] == 'Approved' ? 'bg-success' : ($req['status_name'] == 'Rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                            <?= htmlspecialchars($req['status_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?action=view_request&id=<?= $req['request_id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const requestRows = document.querySelectorAll('.request-row');
        
        // Filter buttons
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Function to filter requests based on search term and status
        function filterRequests() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeStatus = document.querySelector('.filter-btn.active').getAttribute('data-status');
            
            requestRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const statusMatch = activeStatus === 'all' || rowStatus === activeStatus;
                const searchMatch = searchTerm === '' || rowText.includes(searchTerm);
                
                if (statusMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Search input event listener
        searchInput.addEventListener('input', filterRequests);
        
        // Filter button event listeners
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Apply filters
                filterRequests();
            });
        });
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>