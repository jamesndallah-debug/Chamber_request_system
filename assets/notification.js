/**
 * Enhanced Notification System for Chamber Request System
 * This script handles real-time notifications, sound alerts, and desktop notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get notification elements
    const notifBtn = document.getElementById('notif_btn');
    const notifPanel = document.getElementById('notif_panel');
    const notifRoot = document.getElementById('notif_root');
    
    if (!notifBtn || !notifPanel || !notifRoot) return;
    
    // Notification panel toggle functions
    function hidePanel() { notifPanel.classList.add('hidden'); }
    function showPanel() { notifPanel.classList.remove('hidden'); }
    function togglePanel() { notifPanel.classList.toggle('hidden'); }
    
    // Event listeners for notification panel
    notifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        togglePanel();
    });
    
    document.addEventListener('click', function(e) {
        if (!notifRoot.contains(e.target)) hidePanel();
    });
    
    // Request notification permission
    let notificationPermission = false;
    if ("Notification" in window) {
        Notification.requestPermission().then(function(permission) {
            notificationPermission = permission === "granted";
        });
    }
    
    // Notification sound function with fallback
    function playNotificationSound() {
        // Try to play audio file first
        const audio = new Audio('assets/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => {
            console.log('Audio file play prevented, using Web Audio API fallback:', e);
            // Fallback to Web Audio API
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(587.33, audioContext.currentTime); // D5
                
                gainNode.gain.setValueAtTime(0.5, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (audioError) {
                console.error('Failed to play notification sound:', audioError);
            }
        });
    }
    
    // Track last notification count to detect new notifications
    let lastNotificationCount = 0;
    
    // Update notification panel with new notifications
    function updateNotificationPanel(notifications) {
        const container = document.getElementById('notif_container');
        if (!container) return;
        
        // Clear existing notifications
        container.innerHTML = '';
        
        if (!notifications || notifications.length === 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'p-4 text-sm text-slate-300';
            emptyDiv.textContent = 'No notifications.';
            container.appendChild(emptyDiv);
            return;
        }
        
        // Add new notifications
        notifications.forEach(n => {
            const div = document.createElement('div');
            div.className = `p-3 flex items-start gap-3 ${parseInt(n.is_read) === 0 ? 'notif-item-unread' : ''}`;
            
            const content = document.createElement('div');
            content.className = 'flex-1 min-w-0';
            
            const title = document.createElement('div');
            title.className = 'font-medium truncate';
            title.textContent = n.title;
            
            const message = document.createElement('div');
            message.className = 'text-slate-300 text-sm line-clamp-2';
            message.textContent = n.message;
            
            const time = document.createElement('div');
            time.className = 'mt-1 text-xs text-slate-400';
            time.textContent = n.created_at;
            
            const actions = document.createElement('div');
            actions.className = 'mt-1 flex gap-3';
            
            if (n.request_id) {
                const viewLink = document.createElement('a');
                viewLink.className = 'text-xs text-blue-300 hover:text-blue-200';
                viewLink.href = `index.php?action=view_request&id=${n.request_id}`;
                viewLink.textContent = 'View';
                actions.appendChild(viewLink);
            }
            
            if (parseInt(n.is_read) === 0) {
                const markReadLink = document.createElement('a');
                markReadLink.className = 'text-xs text-blue-300 hover:text-blue-200';
                markReadLink.href = `index.php?action=mark_notification_read&id=${n.id}`;
                markReadLink.textContent = 'Mark as read';
                actions.appendChild(markReadLink);
            }
            
            content.appendChild(title);
            content.appendChild(message);
            content.appendChild(time);
            content.appendChild(actions);
            
            div.appendChild(content);
            container.appendChild(div);
        });
    }
    
    // Update notification badge
    function updateNotificationBadge(unreadCount) {
        const badge = notifBtn.querySelector('span.bg-red-600');
        
        if (unreadCount > 0) {
            if (badge) {
                badge.textContent = unreadCount;
            } else {
                const newBadge = document.createElement('span');
                newBadge.className = 'absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full px-2 py-0.5';
                newBadge.textContent = unreadCount;
                notifBtn.appendChild(newBadge);
            }
            
            // Only animate and play sound if count increased
            if (unreadCount > lastNotificationCount) {
                notifBtn.classList.add('animate-bounce');
                playNotificationSound();
                
                // Show desktop notification if supported
                if (notificationPermission && window.Notification) {
                    try {
                        const latestNotif = document.querySelector('#notif_container .notif-item-unread');
                        let title = "New Notification";
                        let body = "You have " + unreadCount + " unread notifications";
                        
                        if (latestNotif) {
                            const titleEl = latestNotif.querySelector('.font-medium');
                            const messageEl = latestNotif.querySelector('.text-sm');
                            if (titleEl) title = titleEl.textContent;
                            if (messageEl) body = messageEl.textContent;
                        }
                        
                        new Notification(title, {
                            body: body,
                            icon: "/favicon.ico"
                        });
                    } catch (e) {
                        console.error('Failed to create desktop notification:', e);
                    }
                }
            }
        } else {
            if (badge) {
                badge.remove();
            }
            notifBtn.classList.remove('animate-bounce');
        }
    }
    
    // Check for new notifications
    function checkNotifications() {
        fetch('index.php?action=check_notifications')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                updateNotificationBadge(data.unread);
                updateNotificationPanel(data.notifications);
                
                // Update last notification count
                lastNotificationCount = data.unread;
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
    
    // Initial check
    checkNotifications();
    
    // Check for new notifications every 15 seconds
    setInterval(checkNotifications, 15000);
});