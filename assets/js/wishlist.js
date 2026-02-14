/**
 * Wishlist Manager
 * Handle add/remove products to/from wishlist
 */

// Toggle wishlist
async function toggleWishlist(productId) {
    const btn = document.getElementById('wishlist-btn');
    const icon = document.getElementById('wishlist-icon');
    const text = document.getElementById('wishlist-text');
    
    if (!btn || !productId) return;
    
    // Disable button during request
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('product_id', productId);
        
        const response = await fetch('/wishlist.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update button state
            if (data.data.in_wishlist) {
                icon.textContent = '❤️';
                text.textContent = document.documentElement.lang === 'bg' 
                    ? 'Премахни от любими' 
                    : 'Remove from Wishlist';
                btn.classList.add('active');
            } else {
                icon.textContent = '🤍';
                text.textContent = document.documentElement.lang === 'bg' 
                    ? 'Добави в любими' 
                    : 'Add to Wishlist';
                btn.classList.remove('active');
            }
            
            // Show notification
            showNotification(data.message, 'success');
            
            // Update wishlist count if exists
            updateWishlistCount(data.data.count);
        } else {
            showNotification(data.message || 'Грешка при актуализиране', 'error');
        }
    } catch (error) {
        console.error('Wishlist error:', error);
        showNotification('Грешка при връзката със сървъра', 'error');
    } finally {
        btn.disabled = false;
    }
}

// Check wishlist status
async function checkWishlistStatus(productId) {
    if (!productId) return;
    
    try {
        const response = await fetch(`/wishlist.php?action=check&product_id=${encodeURIComponent(productId)}`);
        const data = await response.json();
        
        if (data.success && data.data.in_wishlist) {
            const btn = document.getElementById('wishlist-btn');
            const icon = document.getElementById('wishlist-icon');
            const text = document.getElementById('wishlist-text');
            
            if (btn && icon && text) {
                icon.textContent = '❤️';
                text.textContent = document.documentElement.lang === 'bg' 
                    ? 'Премахни от любими' 
                    : 'Remove from Wishlist';
                btn.classList.add('active');
            }
        }
    } catch (error) {
        console.error('Check wishlist error:', error);
    }
}

// Update wishlist count badge
function updateWishlistCount(count) {
    const badges = document.querySelectorAll('.wishlist-count, .wishlist-badge');
    badges.forEach(badge => {
        badge.textContent = count || '0';
        if (count > 0) {
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    });
}

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.wishlist-notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `wishlist-notification wishlist-notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : '#f44336'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
if (!document.getElementById('wishlist-animations')) {
    const style = document.createElement('style');
    style.id = 'wishlist-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Get wishlist count on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('/wishlist.php?action=count');
        const data = await response.json();
        if (data.success) {
            updateWishlistCount(data.data.count);
        }
    } catch (error) {
        console.error('Get wishlist count error:', error);
    }
});
