<?php
// FILE: partials/sidebar.php
// Reusable sidebar for navigation.
$current_action = $_GET['action'] ?? 'dashboard';
$isActive = function($actions) use ($current_action) {
    if (!is_array($actions)) $actions = [$actions];
    return in_array($current_action, $actions, true);
};
?>
<aside class="w-64 min-h-screen p-6 hidden md:block fixed left-0 top-0 z-30" style="background:linear-gradient(180deg, rgba(11,94,215,.15), rgba(212,175,55,.10));backdrop-filter:saturate(140%) blur(6px);border-right:1px solid rgba(148,163,184,.15)">
    <div class="text-2xl font-bold mb-8 flex items-center gap-2">
        <span style="width:10px;height:10px;border-radius:999px;background:#0b5ed7;box-shadow:0 0 0 4px rgba(11,94,215,.25)"></span>
        <span class="sidebar-brand">Chamber System</span>
    </div>
    <nav class="space-y-2">
        <a href="index.php?action=dashboard" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $isActive(['dashboard','admin_management','employee_dashboard','hod_dashboard','hrm_dashboard','auditor_dashboard','finance_dashboard','ed_dashboard']) ? 'bg-blue-600 text-white' : 'hover:bg-blue-600/20 text-white/90' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001 1h2a1 1 0 001-1m-6 0v-2.293a1 1 0 01.293-.707l.586-.586a1 1 0 01.707-.293H16a1 1 0 01.707.293l.586.586a1 1 0 01.293.707V12" />
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="index.php?action=new_request" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'new_request' ? 'bg-blue-600 text-white' : 'hover:bg-blue-600/20 text-white/90' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>New Request</span>
        </a>
        <?php if (in_array($user['role_id'], [4, 5])): // Only show vouchers link to Finance and ED ?>
        <a href="index.php?action=vouchers" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'vouchers' ? 'bg-blue-600 text-white' : 'hover:bg-blue-600/20 text-white/90' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Vouchers</span>
        </a>
        <?php endif; ?>
        <a href="index.php?action=logout" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors hover:bg-red-600/80">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Logout</span>
        </a>
    </nav>
</aside>
