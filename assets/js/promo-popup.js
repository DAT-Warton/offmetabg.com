/**
 * Promotion Popup System
 * Shows promotional popups to users (once per session)
 */

(function() {
    'use strict';
    
    // Check if popup was already shown this session
    if (sessionStorage.getItem('promo_popup_shown')) {
        return;
    }
    
    // Wait for page to load
    document.addEventListener('DOMContentLoaded', function() {
        // Delay popup by 2 seconds for better UX
        setTimeout(loadAndShowPopup, 2000);
    });
    
    function loadAndShowPopup() {
        fetch('/api/handler.php?action=get_active_popup')
            .then(response => response.json())
            .then(data => {
                if (data && data.promotion) {
                    showPromoPopup(data.promotion);
                    sessionStorage.setItem('promo_popup_shown', '1');
                }
            })
            .catch(error => {
                console.error('Error loading popup:', error);
            });
    }
    
    function showPromoPopup(promo) {
        const overlay = document.createElement('div');
        overlay.className = 'promo-popup-overlay';
        
        const hasImage = promo.image && promo.image.trim() !== '';
        const link = promo.link || '#';
        
        if (hasImage) {
            // Image-based popup
            overlay.innerHTML = `
                <div class="promo-popup">
                    <button class="close-popup"aria-label="Close popup">&times;</button>
                    <a href="${escapeHtml(link)}">
                        <img src="${escapeHtml(promo.image)}"alt="${escapeHtml(promo.title || 'Promotion')}">
                    </a>
                </div>
            `;
        } else {
            // Text-based popup
            overlay.innerHTML = `
                <div class="promo-popup">
                    <button class="close-popup"aria-label="Close popup">&times;</button>
                    <div class="promo-popup-content">
                        <h2>${escapeHtml(promo.title || '')}</h2>
                        <p>${escapeHtml(promo.description || '')}</p>
                        <a href="${escapeHtml(link)}"class="promo-popup-cta">Learn More</a>
                    </div>
                </div>
            `;
        }
        
        document.body.appendChild(overlay);
        
        // Close handlers
        const closeBtn = overlay.querySelector('.close-popup');
        closeBtn.addEventListener('click', function() {
            closePopup(overlay);
        });
        
        // Close on overlay click (but not popup itself)
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closePopup(overlay);
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closePopup(overlay);
                document.removeEventListener('keydown', escHandler);
            }
        });
    }
    
    function closePopup(overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
