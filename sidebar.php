<?php
// FILE: partials/sidebar.php
// Reusable sidebar for navigation.
$current_action = $_GET['action'] ?? 'dashboard';
$isActive = function($actions) use ($current_action) {
    if (!is_array($actions)) $actions = [$actions];
    return in_array($current_action, $actions, true);
};
?>
<aside class="w-64 min-h-screen p-6 hidden md:block fixed left-0 top-0 z-30 bg-white border-r border-gray-200 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="text-2xl font-bold mb-8 flex items-center gap-2">
        <span style="width:10px;height:10px;border-radius:999px;background:#0b5ed7;box-shadow:0 0 0 4px rgba(11,94,215,.25)"></span>
        <span class="sidebar-brand text-gray-800">Chamber System</span>
    </div>
    <nav class="space-y-2">
        <a href="index.php?action=dashboard" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $isActive(['dashboard','admin_management','employee_dashboard','hod_dashboard','hrm_dashboard','auditor_dashboard','finance_dashboard','ed_dashboard','ceo_dashboard']) ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001 1h2a1 1 0 001-1m-6 0v-2.293a1 1 0 01.293-.707l.586-.586a1 1 0 01.707-.293H16a1 1 0 01.707.293l.586.586a1 1 0 01.293.707V12" />
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="index.php?action=new_request" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'new_request' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>New Request</span>
        </a>
        <?php if (in_array($user['role_id'], [4, 5])): // Only show vouchers link to Finance and CEO ?>
        <a href="index.php?action=vouchers" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'vouchers' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Vouchers</span>
        </a>
        <?php endif; ?>
        
        <?php if (in_array($user['role_id'], [7])): // Only show admin link to Admin ?>
        <a href="index.php?action=admin_management" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'admin_management' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37A1.724 1.724 0 001.518 4.657a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 3.572-1.066.756-3.35.756-3.35 0-1.756-2.924-1.756z" />
            </svg>
            <span>Admin Management</span>
        </a>
        <?php endif; ?>
        
        <a href="index.php?action=request_history" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'request_history' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Request History</span>
        </a>
        
        <a href="index.php?action=messages" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'messages' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>Messages</span>
        </a>
        
        <?php if (in_array($user['role_id'], [7])): // Only show settings link to Admin ?>
        <a href="index.php?action=settings" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $current_action === 'settings' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37A1.724 1.724 0 001.518 4.657a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 3.572-1.066.756-3.35.756-3.35 0-1.756-2.924-1.756z" />
            </svg>
            <span>Settings</span>
        </a>
        <?php endif; ?>
        
        <div class="border-t border-gray-100 pt-6 mt-auto">
            <a href="index.php?action=logout" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 hover:bg-red-50 text-red-600 border border-transparent hover:border-red-100 shadow-sm hover:shadow-md">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H9m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span class="font-bold tracking-wide">Sign Out</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </nav>
</aside>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay"></div>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" aria-label="Toggle menu">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // No complex activity monitoring needed - simplified for better performance
});
</script>
