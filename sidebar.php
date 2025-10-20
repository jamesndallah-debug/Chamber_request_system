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
        <?php if (isset($user) && (int)$user['role_id'] === 7): ?>
        <a href="index.php?action=admin_requests" class="flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors
            <?= $isActive(['admin_requests']) ? 'bg-blue-600 text-white' : 'hover:bg-blue-600/20 text-white/90' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h13M8 12h13M8 17h13M3 7h.01M3 12h.01M3 17h.01" />
            </svg>
            <span>My Requests</span>
        </a>
        <?php endif; ?>
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
<!-- Inactivity Warning Modal -->
<div id="idleModal" class="modal fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-11/12 p-6">
    <h3 class="text-lg font-bold mb-2 text-gray-900">Session about to expire</h3>
    <p class="text-sm text-gray-700 mb-4">You've been inactive for a while. You will be logged out automatically in <span id="idleCountdown" class="font-semibold">3:00</span> unless you choose to stay signed in.</p>
    <div class="flex items-center justify-end gap-3">
      <button id="idleLogoutBtn" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Log out</button>
      <button id="idleStayBtn" class="px-4 py-2 rounded bg-blue-600 text-white font-semibold">Stay signed in</button>
    </div>
  </div>
  <style>
    #idleModal.hidden{ display:none; }
    #idleModal{ display:flex; }
  </style>
</div>

<script>
(function(){
  // Only run if user is logged in (sidebar is present in authenticated views)
  var lastActivity = Date.now();
  var warnAfterMs = 17 * 60 * 1000; // 17 minutes
  var logoutAfterMs = 20 * 60 * 1000; // 20 minutes
  var warningShown = false;
  var countdownTimer = null;
  var interval = null;

  function activity(){
    lastActivity = Date.now();
    if (warningShown) { hideWarning(); }
  }

  ['click','mousemove','keydown','scroll','touchstart'].forEach(function(evt){
    window.addEventListener(evt, activity, { passive: true });
  });

  function showWarning(){
    var modal = document.getElementById('idleModal');
    if (!modal) return;
    warningShown = true;
    modal.classList.remove('hidden');
    startCountdown(180); // 3 minutes
  }
  function hideWarning(){
    var modal = document.getElementById('idleModal');
    if (!modal) return;
    warningShown = false;
    modal.classList.add('hidden');
    if (countdownTimer){ clearInterval(countdownTimer); countdownTimer = null; }
  }
  function formatMMSS(sec){ var m = Math.floor(sec/60), s = sec%60; return m+':' + (s<10?'0'+s:s); }
  function startCountdown(seconds){
    var span = document.getElementById('idleCountdown');
    var remain = seconds;
    if (span) span.textContent = formatMMSS(remain);
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(function(){
      remain--;
      if (span) span.textContent = formatMMSS(Math.max(0, remain));
      if (remain <= 0){
        clearInterval(countdownTimer); countdownTimer = null;
        window.location.href = 'index.php?action=logout';
      }
    }, 1000);
  }

  // Buttons
  var stayBtn = document.getElementById('idleStayBtn');
  var logoutBtn = document.getElementById('idleLogoutBtn');
  if (stayBtn){
    stayBtn.addEventListener('click', function(){
      // Foreground ping to refresh LAST_ACTIVITY on server
      fetch('index.php?action=counts', { cache:'no-store' })
        .then(function(){ activity(); })
        .catch(function(){ activity(); });
      hideWarning();
    });
  }
  if (logoutBtn){
    logoutBtn.addEventListener('click', function(){ window.location.href = 'index.php?action=logout'; });
  }

  // Monitor inactivity periodically
  function tick(){
    var idleMs = Date.now() - lastActivity;
    if (!warningShown && idleMs >= warnAfterMs){ showWarning(); }
    if (idleMs >= logoutAfterMs){ window.location.href = 'index.php?action=logout'; }
  }
  interval = setInterval(tick, 15000); // check every 15s
})();
</script>
