/**
 * Mobile Menu Toggle for Admin Panel
 * Handles hamburger menu interaction on mobile devices
 */

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    
    if (!menuToggle || !sidebar) {
        console.warn('Mobile menu elements not found');
        return;
    }
    
    // Toggle menu when hamburger clicked
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isActive = sidebar.classList.contains('mobile-active');
        
        if (isActive) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });
    
    // Close menu when clicking overlay (body::after)
    body.addEventListener('click', function(e) {
        if (body.classList.contains('sidebar-open') && 
            !sidebar.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            closeMobileMenu();
        }
    });
    
    // Close menu when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-active')) {
            closeMobileMenu();
        }
    });
    
    // Close menu when clicking sidebar link (navigate to section)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Only close on mobile (when menu is active)
            if (sidebar.classList.contains('mobile-active')) {
                closeMobileMenu();
            }
        });
    });
    
    // Handle window resize - close menu if resized to desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768 && sidebar.classList.contains('mobile-active')) {
                closeMobileMenu();
            }
        }, 250);
    });
    
    function openMobileMenu() {
        sidebar.classList.add('mobile-active');
        body.classList.add('sidebar-open');
        menuToggle.classList.add('active');
        menuToggle.setAttribute('aria-expanded', 'true');
        
        // Prevent body scroll on mobile when menu open
        body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        sidebar.classList.remove('mobile-active');
        body.classList.remove('sidebar-open');
        menuToggle.classList.remove('active');
        menuToggle.setAttribute('aria-expanded', 'false');
        
        // Restore body scroll
        body.style.overflow = '';
    }
});
