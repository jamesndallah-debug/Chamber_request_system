<?php
// FILE: partials/sidebar.php
// Reusable sidebar for navigation.

/** @var array $user */

$current_action = $_GET['action'] ?? 'dashboard';
$isActive = function($actions) use ($current_action) {
    if (!is_array($actions)) $actions = [$actions];
    return in_array($current_action, $actions, true);
};

$roleLabel = trim((string)($user['role_name'] ?? ''));
if ($roleLabel === '') {
    $roleMap = [
        1 => 'Employee',
        2 => 'HRM',
        3 => 'HOD',
        4 => 'CEO',
        5 => 'Finance',
        6 => 'Internal Auditor',
        7 => 'Administrator',
    ];
    $roleLabel = $roleMap[(int)($user['role_id'] ?? 0)] ?? 'User';
}
?>
<aside class="w-72 h-screen p-6 hidden lg:flex flex-col fixed left-0 top-0 z-40 bg-white border-r border-gray-100 shadow-sm transition-all duration-300 ease-in-out overflow-y-auto">
    <!-- Sidebar Header -->
    <div class="mb-10 px-2">
        <div class="flex items-center gap-3 group cursor-pointer">
            <div class="relative flex items-center justify-center">
                <div class="absolute inset-0 bg-blue-600 blur-md opacity-20 group-hover:opacity-40 transition-opacity rounded-full"></div>
                <div class="relative w-12 h-12 bg-gradient-to-tr from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-lg font-black text-slate-800 tracking-tight leading-none">Chamber Request</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">System</span>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 space-y-1.5">
        <div class="px-3 mb-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Main Navigation</div>
        
        <a href="index.php?action=dashboard" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= $isActive(['dashboard','admin_management','employee_dashboard','hod_dashboard','hrm_dashboard','auditor_dashboard','finance_dashboard','ed_dashboard','ceo_dashboard']) 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-blue-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300
                    <?= $isActive(['dashboard','admin_management','employee_dashboard','hod_dashboard','hrm_dashboard','auditor_dashboard','finance_dashboard','ed_dashboard','ceo_dashboard']) ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001 1h2a1 1 0 001-1m-6 0v-2.293a1 1 0 01.293-.707l.586-.586a1 1 0 01.707-.293H16a1 1 0 01.707.293l.586.586a1 1 0 01.293.707V12" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">Dashboard</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>

        <a href="index.php?action=new_request" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= $current_action === 'new_request' 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-blue-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300
                    <?= $current_action === 'new_request' ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">New Request</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>

        <?php if (in_array($user['role_id'], [4, 5])): ?>
        <a href="index.php?action=vouchers" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= $current_action === 'vouchers' 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-blue-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300
                    <?= $current_action === 'vouchers' ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">Vouchers</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        <?php endif; ?>
        
        <div class="px-3 pt-4 mb-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Activity</div>
        <a href="index.php?action=request_history" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= $current_action === 'request_history' 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-blue-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300
                    <?= $current_action === 'request_history' ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">Request History</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        
        <a href="index.php?action=messages" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= $current_action === 'messages' 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-blue-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300
                    <?= $current_action === 'messages' ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">Messages</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        
        <?php if (in_array($user['role_id'], [7])): // Only show settings to Admin ?>
        <div class="px-3 pt-4 mb-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">System</div>
        <a href="index.php?action=admin_management&tab=settings" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 
            <?= ($current_action === 'admin_management' && ($_GET['tab'] ?? '') === 'settings') 
                ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-200' 
                : 'hover:bg-slate-50 text-slate-600 hover:text-purple-600' ?>">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300
                    <?= ($current_action === 'admin_management' && ($_GET['tab'] ?? '') === 'settings') ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-purple-100 group-hover:text-purple-600' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37A1.724 1.724 0 001.518 4.657a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 3.572-1.066.756-3.35.756-3.35 0-1.756-2.924-1.756z" />
                    </svg>
                </div>
                <span class="font-bold tracking-wide">Settings</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        <?php endif; ?>
    </nav>

    <!-- User Profile & Sign Out -->
    <div class="mt-auto pt-6 border-t border-slate-100">
        <div class="px-4 py-4 mb-4 bg-slate-50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-inner">
                    <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-sm font-black text-slate-800 truncate"><?= htmlspecialchars($roleLabel) ?></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight"><?= htmlspecialchars($user['fullname'] ?? 'User') ?></span>
                </div>
            </div>
        </div>
        
        <a href="index.php?action=logout" class="group flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 hover:bg-rose-50 text-rose-600 border border-transparent hover:border-rose-100 shadow-sm hover:shadow-md">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition-colors duration-300">
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
