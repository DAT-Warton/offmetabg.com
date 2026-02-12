// Theme and Language Switcher for OffMeta E-Commerce
// Handles dark/light theme toggle and language switching

console.log('Theme.js loaded');

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing theme');
    initTheme();
    initThemeToggle();
});

// Initialize theme from localStorage
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    console.log('Initializing theme:', savedTheme);
    applyTheme(savedTheme);
}

// Apply theme to document
function applyTheme(theme) {
    console.log('Applying theme:', theme);
    const root = document.documentElement;
    const body = document.body;
    
    root.setAttribute('data-theme', theme);
    body.setAttribute('data-theme', theme);
    
    console.log('Theme attributes set. Root:', root.getAttribute('data-theme'), 'Body:', body.getAttribute('data-theme'));
    
    // Update theme toggle button icon
    updateThemeIcon(theme);
    
    // Save to localStorage
    localStorage.setItem('theme', theme);
}

// Toggle between light and dark theme
function toggleTheme() {
    console.log('Toggle theme clicked');
    const currentTheme = document.body.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    console.log('Current theme:', currentTheme, '-> New theme:', newTheme);
    
    applyTheme(newTheme);
    
    // Add animation effect
    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
}

// Update theme toggle button icon
function updateThemeIcon(theme) {
    console.log('Updating theme icon to:', theme);
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        console.log('Theme toggle button found');
        if (theme === 'dark') {
            // Sun icon for switching to light
            themeToggle.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';
            themeToggle.title = 'Switch to Light Theme';
        } else {
            // Moon icon for switching to dark
            themeToggle.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
            themeToggle.title = 'Switch to Dark Theme';
        }
    } else {
        console.error('Theme toggle button NOT found!');
    }
}

// Initialize theme toggle button
function initThemeToggle() {
    console.log('Initializing theme toggle button');
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        console.log('Adding click listener to theme toggle');
        themeToggle.addEventListener('click', function(e) {
            console.log('Theme button clicked!', e);
            toggleTheme();
        });
    } else {
        console.error('Theme toggle button NOT found during initialization!');
    }
}

// Get current theme
function getCurrentTheme() {
    return document.body.getAttribute('data-theme') || 'light';
}

// Language switcher (handled via PHP GET parameter)
function switchLanguage(lang) {
    const currentUrl = window.location.href.split('?')[0];
    window.location.href = currentUrl + '?lang=' + lang;
}

// Smooth scroll for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Add to cart animation
function addToCartAnimation(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Added!';
    button.style.background = '#4caf50';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.background = '';
        button.disabled = false;
    }, 2000);
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
