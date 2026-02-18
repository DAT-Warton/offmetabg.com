// Force dark theme only
document.addEventListener('DOMContentLoaded', function() {
    var theme = 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    var darkThemeStyle = document.getElementById('dark-theme-style');
    if (darkThemeStyle) {
        darkThemeStyle.disabled = false;
    }
    
    // Mobile sidebar dropdown functionality
    initSidebarDropdowns();
});

// Initialize sidebar dropdown functionality for mobile
function initSidebarDropdowns() {
    // Only apply on mobile devices
    if (window.innerWidth <= 768) {
        const sidebarSections = document.querySelectorAll('.sidebar-section');
        
        sidebarSections.forEach((section, index) => {
            const title = section.querySelector('.sidebar-section-title');
            
            if (title) {
                // Remove any existing click handlers
                const newTitle = title.cloneNode(true);
                title.parentNode.replaceChild(newTitle, title);
                
                // Collapse all sections except the first one by default
                if (index !== 0) {
                    section.classList.add('collapsed');
                }
                
                // Add click event to toggle
                newTitle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    section.classList.toggle('collapsed');
                });
            }
        });
    }
}

// Re-initialize on window resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Remove all collapsed classes when switching to desktop
        if (window.innerWidth > 768) {
            document.querySelectorAll('.sidebar-section').forEach(section => {
                section.classList.remove('collapsed');
            });
        } else {
            // Re-initialize for mobile
            initSidebarDropdowns();
        }
    }, 250);
});
