/**
 * OffMeta Theme Manager
 * Handles theme switching, persistence, and import/export
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'default';
        this.themeChangeCallbacks = [];
        this.init();
    }

    /**
     * Initialize theme system
     */
    async init() {
        // Load theme from backend first
        await this.loadThemeFromBackend();
        
        // Apply stored theme
        this.applyTheme(this.currentTheme);
        
        // Listen for theme changes from other tabs
        window.addEventListener('storage', (e) => {
            if (e.key === 'offmeta_theme') {
                this.applyTheme(e.newValue);
            }
        });
        
        // Expose to global scope
        window.ThemeManager = this;
    }

    /**
     * Load active theme from backend
     */
    async loadThemeFromBackend() {
        try {
            // Add timestamp to prevent caching
            const timestamp = Date.now();
            const response = await fetch(`${window.location.origin}/api/handler.php?action=get-active-theme&_t=${timestamp}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            });
            
            // Check if response is HTML (Cloudflare challenge) or JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('API blocked by security - using localStorage fallback');
            }
            
            const data = await response.json();
            
            if (data.success && data.theme) {
                this.currentTheme = data.theme;
                this.storeTheme(data.theme);
                console.log('Theme loaded from backend:', data.theme);
            }
        } catch (error) {
            console.warn('Backend theme loading failed, using localStorage:', error.message);
            // Fallback to localStorage - this is OK until Cloudflare is configured
            const storedTheme = this.getStoredTheme();
            if (storedTheme) {
                this.currentTheme = storedTheme;
            }
        }
    }

    /**
     * Get all available themes
     */
    getAvailableThemes() {
        return {
            'default': {
                name: 'Default (Purple Gradient)',
                description: 'Elegant purple gradient theme',
                primary: '#6b46c1',
                category: 'light'
            },
            'dark': {
                name: 'Dark (Midnight Purple)',
                description: 'Dark mode with purple accents',
                primary: '#9f7aea',
                category: 'dark'
            },
            'ocean': {
                name: 'Ocean Blue',
                description: 'Cool and professional blue theme',
                primary: '#0284c7',
                category: 'light'
            },
            'forest': {
                name: 'Forest Green',
                description: 'Fresh and natural green theme',
                primary: '#059669',
                category: 'light'
            },
            'sunset': {
                name: 'Sunset Orange',
                description: 'Warm and energetic orange theme',
                primary: '#ea580c',
                category: 'light'
            },
            'rose': {
                name: 'Rose Pink',
                description: 'Elegant and modern pink theme',
                primary: '#e11d48',
                category: 'light'
            }
        };
    }

    /**
     * Apply theme
     * @param {string} themeName - Name of the theme to apply
     */
    async applyTheme(themeName) {
        const themes = this.getAvailableThemes();
        
        // Check if it's a custom theme (not in built-in themes)
        if (!themes[themeName]) {
            console.log(`Theme "${themeName}" not in built-in list, trying to load as custom theme...`);
            // Try to load custom theme from backend
            try {
                await this.loadCustomThemeBySlug(themeName);
                return; // Custom theme will be applied in loadCustomThemeBySlug
            } catch (error) {
                console.error('Failed to load custom theme:', error);
                console.warn(`Theme "${themeName}" not found. Falling back to default theme.`);
                
                // Clean up invalid theme from storage
                this.cleanupInvalidTheme(themeName);
                
                // Fallback to default theme
                themeName = 'default';
            }
        }

        // Set data-theme attribute on root element
        document.documentElement.setAttribute('data-theme', themeName);
        
        // Update body theme attribute (for legacy compatibility)
        document.body.setAttribute('data-theme', themeName);
        
        // Store theme preference
        this.storeTheme(themeName);
        
        // Update current theme
        this.currentTheme = themeName;
        
        // Trigger callbacks
        this.triggerCallbacks(themeName);
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: themeName, themeData: themes[themeName] }
        }));
    }

    /**
     * Load custom theme by slug from backend
     */
    async loadCustomThemeBySlug(slug) {
        try {
            const response = await fetch(`${window.location.origin}/api/handler.php?action=list-custom-themes`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // Check if response is valid JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('API blocked - falling back to stored theme');
            }
            
            const data = await response.json();
            
            if (data.success && data.themes) {
                const customTheme = data.themes.find(t => t.slug === slug);
                
                if (customTheme) {
                    this.applyCustomTheme(customTheme);
                    return;
                }
            }
            
            throw new Error('Custom theme not found');
        } catch (error) {
            console.warn('Failed to load custom theme from backend:', error.message);
            
            // Try to load from localStorage
            const storedCustomTheme = localStorage.getItem('offmeta_custom_theme');
            if (storedCustomTheme) {
                try {
                    const themeData = JSON.parse(storedCustomTheme);
                    if (themeData.slug === slug) {
                        this.applyCustomTheme(themeData);
                        return;
                    }
                } catch (e) {
                    console.error('Failed to parse stored custom theme:', e);
                }
            }
            
            throw error;
        }
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Store theme in localStorage
     */
    storeTheme(themeName) {
        try {
            localStorage.setItem('offmeta_theme', themeName);
        } catch (e) {
            console.error('Failed to store theme:', e);
        }
    }

    /**
     * Get stored theme from localStorage
     */
    getStoredTheme() {
        try {
            return localStorage.getItem('offmeta_theme');
        } catch (e) {
            console.error('Failed to retrieve stored theme:', e);
            return null;
        }
    }

    /**
     * Clean up invalid theme from storage and database
     * @param {string} themeName - Invalid theme name to clean up
     */
    async cleanupInvalidTheme(themeName) {
        console.log(`Cleaning up invalid theme: ${themeName}`);
        
        // Remove from localStorage
        try {
            const storedTheme = localStorage.getItem('offmeta_theme');
            if (storedTheme === themeName) {
                localStorage.removeItem('offmeta_theme');
                localStorage.removeItem('offmeta_custom_theme');
                console.log('Removed invalid theme from localStorage');
            }
        } catch (e) {
            console.error('Failed to clean localStorage:', e);
        }
        
        // Try to reset active theme in database to default
        try {
            const response = await fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ theme: 'default' })
            });
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                if (data.success) {
                    console.log('Reset active theme to default in database');
                }
            }
        } catch (e) {
            console.warn('Could not reset theme in database (using localStorage fallback):', e.message);
        }
    }

    /**
     * Register callback for theme changes
     */
    onThemeChange(callback) {
        if (typeof callback === 'function') {
            this.themeChangeCallbacks.push(callback);
        }
    }

    /**
     * Clean up invalid theme from storage and database
     */
    cleanupInvalidTheme(themeName) {
        console.log(`Cleaning up invalid theme: ${themeName}`);
        
        // Remove from localStorage
        try {
            const storedTheme = localStorage.getItem('offmeta_theme');
            if (storedTheme === themeName) {
                localStorage.removeItem('offmeta_theme');
                localStorage.removeItem('offmeta_custom_theme');
                console.log('Removed invalid theme from localStorage');
            }
        } catch (e) {
            console.error('Failed to clean localStorage:', e);
        }
        
        // Try to reset active theme in database to default
        try {
            fetch(`${window.location.origin}/api/handler.php?action=set-theme`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ theme: 'default' })
            }).then(response => {
                if (response.ok) {
                    console.log('Reset active theme to default in database');
                }
            }).catch(err => {
                console.error('Failed to reset theme in database:', err);
            });
        } catch (e) {
            console.error('Failed to reset theme in database:', e);
        }
    }

    /**
     * Trigger all registered callbacks
     */
    triggerCallbacks(themeName) {
        this.themeChangeCallbacks.forEach(callback => {
            try {
                callback(themeName);
            } catch (e) {
                console.error('Theme callback error:', e);
            }
        });
    }

    /**
     * Export current theme to JSON
     */
    exportTheme() {
        const theme = this.getCurrentTheme();
        const themes = this.getAvailableThemes();
        const themeData = themes[theme];
        
        // Get computed CSS variables
        const root = document.documentElement;
        const computedStyle = getComputedStyle(root);
        
        const cssVariables = {};
        const variableNames = [
            'primary', 'primary-hover', 'primary-light', 'primary-dark',
            'secondary', 'secondary-hover', 'accent', 'accent-hover',
            'bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-hover', 'bg-card', 'bg-sidebar',
            'text-primary', 'text-secondary', 'text-muted', 'text-inverse',
            'border-color', 'border-light', 'border-dark',
            'shadow-sm', 'shadow-md', 'shadow-lg', 'shadow-xl',
            'success', 'success-bg', 'success-border', 'success-text',
            'warning', 'warning-bg', 'warning-border', 'warning-text',
            'danger', 'danger-bg', 'danger-border', 'danger-text',
            'info', 'info-bg', 'info-border', 'info-text'
        ];
        
        variableNames.forEach(varName => {
            const value = computedStyle.getPropertyValue(`--${varName}`).trim();
            if (value) {
                cssVariables[varName] = value;
            }
        });
        
        const exportData = {
            name: themeData.name,
            slug: theme,
            description: themeData.description,
            category: themeData.category,
            variables: cssVariables,
            exportDate: new Date().toISOString(),
            version: '1.0'
        };
        
        return exportData;
    }

    /**
     * Download theme as JSON file
     */
    downloadTheme() {
        const themeData = this.exportTheme();
        const blob = new Blob([JSON.stringify(themeData, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `offmeta-theme-${themeData.slug}-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Import theme from JSON
     */
    async importTheme(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const themeData = JSON.parse(e.target.result);
                    
                    // Validate theme data
                    if (!themeData.slug || !themeData.variables) {
                        throw new Error('Invalid theme file format');
                    }
                    
                    // Apply theme variables
                    this.applyCustomTheme(themeData);
                    
                    resolve(themeData);
                } catch (error) {
                    reject(error);
                }
            };
            
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    }

    /**
     * Apply custom theme from imported data
     */
    applyCustomTheme(themeData) {
        const root = document.documentElement;
        
        // Set all CSS variables
        if (themeData.variables && typeof themeData.variables === 'object') {
            Object.entries(themeData.variables).forEach(([varName, value]) => {
                root.style.setProperty(`--${varName}`, value);
            });
        }
        
        // Set theme attribute to the theme's slug
        const themeSlug = themeData.slug || 'custom';
        root.setAttribute('data-theme', themeSlug);
        document.body.setAttribute('data-theme', themeSlug);
        
        // Store custom theme
        try {
            localStorage.setItem('offmeta_custom_theme', JSON.stringify(themeData));
            localStorage.setItem('offmeta_theme', themeSlug);
        } catch (e) {
            console.error('Failed to store custom theme:', e);
        }
        
        this.currentTheme = themeSlug;
        
        // Trigger callbacks
        this.triggerCallbacks(themeSlug);
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: themeSlug, themeData: themeData }
        }));
    }

    /**
     * Toggle between light and dark mode
     */
    toggleDarkMode() {
        const currentCategory = this.getAvailableThemes()[this.currentTheme]?.category;
        
        if (currentCategory === 'dark') {
            this.applyTheme('default');
        } else {
            this.applyTheme('dark');
        }
    }

    /**
     * Get theme color for meta tags
     */
    getThemeColor() {
        const themes = this.getAvailableThemes();
        return themes[this.currentTheme]?.primary || '#6b46c1';
    }

    /**
     * Update meta theme color
     */
    updateMetaThemeColor() {
        let metaTheme = document.querySelector('meta[name="theme-color"]');
        
        if (!metaTheme) {
            metaTheme = document.createElement('meta');
            metaTheme.name = 'theme-color';
            document.head.appendChild(metaTheme);
        }
        
        metaTheme.content = this.getThemeColor();
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
    
    // Update meta theme color
    window.themeManager.updateMetaThemeColor();
    
    // Listen for theme changes to update meta color
    window.addEventListener('themeChanged', () => {
        window.themeManager.updateMetaThemeColor();
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
