// Theme and Language Switcher for OffMeta E-Commerce
// Handles dark/light theme toggle and language switching

document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initThemeToggle();
    initSmoothScroll();
});

function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
}

function applyTheme(theme) {
    const root = document.documentElement;
    const body = document.body;

    root.setAttribute('data-theme', theme);
    body.setAttribute('data-theme', theme);
    updateThemeIcon(theme);
    localStorage.setItem('theme', theme);
}

function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    applyTheme(newTheme);
    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) {
        return;
    }

    if (theme === 'dark') {
        themeToggle.innerHTML = '☀️';
        themeToggle.title = 'Switch to Light Theme';
    } else {
        themeToggle.innerHTML = '🌙';
        themeToggle.title = 'Switch to Dark Theme';
    }
}

function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

function getCurrentTheme() {
    return document.body.getAttribute('data-theme') || 'light';
}

function switchLanguage(lang) {
    const currentUrl = window.location.href.split('?')[0];
    window.location.href = currentUrl + '?lang=' + lang;
}

function initSmoothScroll() {
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
}

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
