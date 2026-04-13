// Mobile Menu Handler for Chamber Request System
// Enhanced responsive navigation

class MobileMenu {
    constructor() {
        this.isOpen = false;
        this.sidebar = null;
        this.toggle = null;
        this.overlay = null;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.sidebar = document.querySelector('aside');
        
        // Create mobile menu toggle if it doesn't exist
        if (!document.querySelector('.mobile-menu-toggle')) {
            this.createToggle();
        }
        
        this.toggle = document.querySelector('.mobile-menu-toggle');
        this.createOverlay();
        this.bindEvents();
        this.checkScreenSize();
    }

    createToggle() {
        const toggle = document.createElement('button');
        toggle.className = 'mobile-menu-toggle';
        toggle.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        `;
        toggle.setAttribute('aria-label', 'Toggle menu');
        document.body.appendChild(toggle);
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'mobile-menu-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        `;
        document.body.appendChild(this.overlay);
    }

    bindEvents() {
        // Toggle menu
        this.toggle?.addEventListener('click', () => this.toggleMenu());
        
        // Close menu when clicking overlay
        this.overlay?.addEventListener('click', () => this.closeMenu());
        
        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => this.checkScreenSize());
        
        // Close menu when navigating
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && this.isOpen) {
                this.closeMenu();
            }
        });
    }

    checkScreenSize() {
        const isMobile = window.innerWidth < 768;
        
        if (!isMobile && this.isOpen) {
            this.closeMenu();
        }
        
        // Show/hide toggle button based on screen size
        if (this.toggle) {
            this.toggle.style.display = isMobile ? 'block' : 'none';
        }
    }

    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        if (!this.sidebar) return;
        
        this.isOpen = true;
        this.sidebar.classList.add('mobile-open');
        this.overlay.style.opacity = '1';
        this.overlay.style.visibility = 'visible';
        document.body.style.overflow = 'hidden';
        
        // Update toggle icon
        if (this.toggle) {
            this.toggle.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            `;
        }
        
        // Focus first menu item
        const firstLink = this.sidebar.querySelector('a');
        firstLink?.focus();
    }

    closeMenu() {
        if (!this.sidebar) return;
        
        this.isOpen = false;
        this.sidebar.classList.remove('mobile-open');
        this.overlay.style.opacity = '0';
        this.overlay.style.visibility = 'hidden';
        document.body.style.overflow = '';
        
        // Update toggle icon
        if (this.toggle) {
            this.toggle.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            `;
        }
    }
}

// Initialize mobile menu
const mobileMenu = new MobileMenu();

// Helper function for responsive tables
function makeTablesResponsive() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        // Add responsive wrapper if not already present
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}

// Initialize responsive features
document.addEventListener('DOMContentLoaded', () => {
    makeTablesResponsive();
    
    // Add touch-friendly interactions
    if ('ontouchstart' in window) {
        document.body.classList.add('touch-device');
    }
    
    // Handle orientation changes
    window.addEventListener('orientationchange', () => {
        setTimeout(() => {
            mobileMenu.checkScreenSize();
            makeTablesResponsive();
        }, 100);
    });
});

// Export for global access
window.MobileMenu = MobileMenu;
window.makeTablesResponsive = makeTablesResponsive;
