/**
 * Theme Admin Manager
 * Handles theme management in admin panel
 */

const ThemeAdmin = {
    /**
     * Initialize theme admin
     */
    init() {
        this.loadThemes();
        this.updateCurrentThemeDisplay();
        this.setupEventListeners();
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Import file input change
        const importInput = document.getElementById('theme-import-input');
        if (importInput) {
            importInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.importTheme(e.target.files[0]);
                }
            });
        }

        // Color input sync (color picker <-> text input)
        document.querySelectorAll('.color-input-item').forEach(item => {
            const colorInput = item.querySelector('input[type="color"]');
            const textInput = item.querySelector('input[type="text"]');
            
            if (colorInput && textInput) {
                colorInput.addEventListener('input', (e) => {
                    textInput.value = e.target.value;
                });
                
                textInput.addEventListener('input', (e) => {
                    if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                        colorInput.value = e.target.value;
                    }
                });
            }
        });

        // Listen for theme changes
        window.addEventListener('themeChanged', (e) => {
            this.updateCurrentThemeDisplay();
            this.highlightActiveTheme();
        });
    },

    /**
     * Load and display all themes
     */
    loadThemes() {
        const grid = document.getElementById('themes-grid');
        if (!grid) return;

        const themes = window.themeManager.getAvailableThemes();
        const currentTheme = window.themeManager.getCurrentTheme();

        grid.innerHTML = '';

        Object.entries(themes).forEach(([slug, theme]) => {
            const card = this.createThemeCard(slug, theme, slug === currentTheme);
            grid.appendChild(card);
        });
    },

    /**
     * Create theme card element
     */
    createThemeCard(slug, theme, isActive) {
        const card = document.createElement('div');
        card.className = `theme-card ${isActive ? 'active' : ''}`;
        card.dataset.themeSlug = slug;
        
        // Create preview with theme colors
        const preview = document.createElement('div');
        preview.className = 'theme-card-preview';
        preview.style.background = theme.primary;
        preview.style.position = 'relative';
        preview.style.overflow = 'hidden';
        
        // Add gradient overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, ${theme.primary} 0%, ${theme.primary}dd 50%, ${theme.primary}aa 100%);
        `;
        preview.appendChild(overlay);
        
        card.appendChild(preview);

        // Card info
        const info = document.createElement('div');
        info.className = 'theme-card-info';
        info.innerHTML = `
            <h4>${theme.name}</h4>
            <p>${theme.description}</p>
            <span class="theme-badge">${theme.category}</span>
            ${isActive ? '<span class="theme-badge" style="background: var(--success); margin-left: 8px;">Active</span>' : ''}
        `;
        card.appendChild(info);

        // Click to activate theme
        card.addEventListener('click', () => {
            this.activateTheme(slug);
        });

        return card;
    },

    /**
     * Activate theme
     */
    async activateTheme(slug) {
        try {
            // Apply theme on frontend first (immediate feedback)
            window.themeManager.applyTheme(slug);
            
            // Try to save to database 
            try {
                const response = await fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ theme: slug })
                });

                // Check if we got valid JSON response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();

                    if (data.success) {
                        this.showNotification('Theme activated and saved! Reloading...', 'success');
                        this.highlightActiveTheme();
                        
                        // Reload page to apply theme everywhere
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                        return;
                    }
                }
                
                // API blocked or failed - fallback to localStorage only
                throw new Error('API temporarily unavailable');
                
            } catch (apiError) {
                console.warn('API save failed, using localStorage:', apiError.message);
                
                // Theme is already applied via localStorage, just notify user
                this.showNotification('Theme applied locally. Configure Cloudflare Page Rule for API to persist across devices.', 'warning');
                this.highlightActiveTheme();
            }
        } catch (error) {
            console.error('Error activating theme:', error);
            this.showNotification('Failed to activate theme: ' + error.message, 'error');
        }
    },

    /**
     * Update current theme display
     */
    updateCurrentThemeDisplay() {
        const currentTheme = window.themeManager.getCurrentTheme();
        const themes = window.themeManager.getAvailableThemes();
        const theme = themes[currentTheme];

        if (!theme) return;

        const nameEl = document.getElementById('current-theme-name');
        const descEl = document.getElementById('current-theme-description');
        const categoryEl = document.getElementById('current-theme-category');
        const previewEl = document.getElementById('current-theme-preview');

        if (nameEl) nameEl.textContent = theme.name;
        if (descEl) descEl.textContent = theme.description;
        if (categoryEl) {
            categoryEl.textContent = theme.category;
            categoryEl.style.background = theme.primary;
        }
    },

    /**
     * Highlight active theme card
     */
    highlightActiveTheme() {
        const currentTheme = window.themeManager.getCurrentTheme();
        
        document.querySelectorAll('.theme-card').forEach(card => {
            if (card.dataset.themeSlug === currentTheme) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    },

    /**
     * Export current theme
     */
    exportTheme() {
        try {
            window.themeManager.downloadTheme();
            this.showNotification('Theme exported successfully!', 'success');
        } catch (error) {
            console.error('Export error:', error);
            this.showNotification('Failed to export theme: ' + error.message, 'error');
        }
    },

    /**
     * Import theme from file
     */
    async importTheme(file) {
        const statusEl = document.getElementById('import-status');
        
        try {
            statusEl.textContent = 'Importing theme...';
            statusEl.className = 'import-status';

            const themeData = await window.themeManager.importTheme(file);

            // Save to database
            const response = await fetch(`${window.location.origin}/api/handler.php?action=save-custom-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(themeData)
            });

            const data = await response.json();

            if (data.success) {
                statusEl.textContent = `✓ Theme "${themeData.name}" imported successfully!`;
                statusEl.className = 'import-status success';
                
                // Reload page after 2 seconds to show new theme
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to save theme');
            }
        } catch (error) {
            console.error('Import error:', error);
            statusEl.textContent = `✗ Error: ${error.message}`;
            statusEl.className = 'import-status error';
        }

        // Clear file input
        document.getElementById('theme-import-input').value = '';
    },

    /**
     * Apply custom colors from customizer
     */
    async applyCustomColors() {
        const colors = {
            primary: document.getElementById('color-primary').value,
            'primary-hover': document.getElementById('color-primary-hover').value,
            secondary: document.getElementById('color-secondary').value,
            accent: document.getElementById('color-accent').value,
            'bg-primary': document.getElementById('color-bg-primary').value,
            'bg-secondary': document.getElementById('color-bg-secondary').value,
            'text-primary': document.getElementById('color-text-primary').value,
            'text-secondary': document.getElementById('color-text-secondary').value
        };

        // Apply to root immediately for preview
        const root = document.documentElement;
        Object.entries(colors).forEach(([key, value]) => {
            root.style.setProperty(`--${key}`, value);
        });

        // Create a preview theme name
        const previewThemeName = 'custom-preview-' + Date.now();
        
        // Create theme data
        const themeData = {
            name: 'Custom Preview',
            slug: previewThemeName,
            description: 'Preview of custom colors',
            category: 'custom',
            variables: colors,
            version: '1.0'
        };

        try {
            // Save as temporary preview theme
            const response = await fetch(`${window.location.origin}/api/handler.php?action=save-custom-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(themeData)
            });

            const data = await response.json();

            if (data.success) {
                // Now activate this theme
                const activateResponse = await fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ theme: previewThemeName })
                });

                const activateData = await activateResponse.json();

                if (activateData.success) {
                    this.showNotification('Custom colors applied and saved! Reloading...', 'success');
                    
                    // Reload page to apply everywhere
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(activateData.message || 'Failed to activate theme');
                }
            } else {
                throw new Error(data.message || 'Failed to save theme');
            }
        } catch (error) {
            console.error('Apply custom colors error:', error);
            this.showNotification('Preview applied locally. Click "Save as New Theme" to keep permanently.', 'warning');
        }
    },

    /**
     * Save custom theme
     */
    async saveCustomTheme() {
        const name = prompt('Enter theme name:');
        if (!name) return;

        const description = prompt('Enter theme description (optional):');

        try {
            const colors = {
                primary: document.getElementById('color-primary').value,
                'primary-hover': document.getElementById('color-primary-hover').value,
                secondary: document.getElementById('color-secondary').value,
                accent: document.getElementById('color-accent').value,
                'bg-primary': document.getElementById('color-bg-primary').value,
                'bg-secondary': document.getElementById('color-bg-secondary').value,
                'text-primary': document.getElementById('color-text-primary').value,
                'text-secondary': document.getElementById('color-text-secondary').value
            };

            const themeData = {
                name: name,
                slug: name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
                description: description || '',
                category: 'custom',
                variables: colors,
                version: '1.0'
            };

            // Save theme to database
            const response = await fetch(`${window.location.origin}/api/handler.php?action=save-custom-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(themeData)
            });

            const data = await response.json();

            if (data.success) {
                // Now activate this theme
                const activateResponse = await fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ theme: themeData.slug })
                });

                const activateData = await activateResponse.json();

                if (activateData.success) {
                    this.showNotification('Theme saved and activated successfully! Reloading...', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification('Theme saved but not activated. You can activate it from the themes list.', 'warning');
                    setTimeout(() => location.reload(), 2000);
                }
            } else {
                throw new Error(data.message || 'Failed to save theme');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showNotification('Failed to save theme: ' + error.message, 'error');
        }
    },

    /**
     * Reset customizer to current theme colors
     */
    resetCustomizer() {
        const root = document.documentElement;
        const computedStyle = getComputedStyle(root);

        const colorIds = [
            'primary', 'primary-hover', 'secondary', 'accent',
            'bg-primary', 'bg-secondary', 'text-primary', 'text-secondary'
        ];

        colorIds.forEach(id => {
            const value = computedStyle.getPropertyValue(`--${id}`).trim();
            const colorInput = document.getElementById(`color-${id}`);
            const textInput = document.getElementById(`color-${id}-text`);
            
            if (colorInput && value.startsWith('#')) {
                colorInput.value = value;
            }
            if (textInput && value.startsWith('#')) {
                textInput.value = value;
            }
        });

        this.showNotification('Customizer reset to current theme', 'success');
    },

    /**
     * Delete custom theme
     */
    async deleteCustomTheme(themeId) {
        if (!confirm('Are you sure you want to delete this theme?')) return;

        try {
            const response = await fetch(`${window.location.origin}/api/handler.php?action=delete-custom-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: themeId })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Theme deleted successfully!', 'success');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to delete theme');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Failed to delete theme: ' + error.message, 'error');
        }
    },

    /**
     * Apply custom theme from database
     */
    async applyCustomTheme(themeId) {
        try {
            const response = await fetch(`${window.location.origin}/api/handler.php?action=get-custom-theme&id=${themeId}`);
            const data = await response.json();

            if (data.success && data.theme) {
                const theme = data.theme;
                
                // Apply theme variables
                window.themeManager.applyCustomTheme(theme);
                
                // Now save this theme as active to backend
                const activateResponse = await fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ theme: theme.slug })
                });

                const activateData = await activateResponse.json();

                if (activateData.success) {
                    this.showNotification('Custom theme applied successfully! Reloading...', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(activateData.message || 'Failed to activate theme');
                }
            } else {
                throw new Error(data.message || 'Failed to load theme');
            }
        } catch (error) {
            console.error('Apply error:', error);
            this.showNotification('Failed to apply theme: ' + error.message, 'error');
        }
    },

    /**
     * Edit custom theme
     */
    async editCustomTheme(themeId) {
        this.showNotification('Edit functionality coming soon!', 'info');
    },

    /**
     * Refresh themes grid
     */
    refreshThemes() {
        this.loadThemes();
        this.updateCurrentThemeDisplay();
        this.showNotification('Themes refreshed!', 'success');
    },

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `admin-notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            background: ${type === 'success' ? 'var(--success-bg)' : type === 'error' ? 'var(--danger-bg)' : 'var(--info-bg)'};
            color: ${type === 'success' ? 'var(--success-text)' : type === 'error' ? 'var(--danger-text)' : 'var(--info-text)'};
            border: 1px solid ${type === 'success' ? 'var(--success-border)' : type === 'error' ? 'var(--danger-border)' : 'var(--info-border)'};
            box-shadow: 0 4px 12px var(--shadow-md);
            z-index: 10000;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
};

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

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeAdmin.init());
} else {
    ThemeAdmin.init();
}
