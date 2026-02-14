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
    init() {
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
    applyTheme(themeName) {
        const themes = this.getAvailableThemes();
        
        if (!themes[themeName]) {
            console.warn(`Theme "${themeName}" not found. Using default theme.`);
            themeName = 'default';
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
     * Register callback for theme changes
     */
    onThemeChange(callback) {
        if (typeof callback === 'function') {
            this.themeChangeCallbacks.push(callback);
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
        Object.entries(themeData.variables).forEach(([varName, value]) => {
            root.style.setProperty(`--${varName}`, value);
        });
        
        // Set theme attribute
        root.setAttribute('data-theme', 'custom');
        document.body.setAttribute('data-theme', 'custom');
        
        // Store custom theme
        try {
            localStorage.setItem('offmeta_custom_theme', JSON.stringify(themeData));
            localStorage.setItem('offmeta_theme', 'custom');
        } catch (e) {
            console.error('Failed to store custom theme:', e);
        }
        
        this.currentTheme = 'custom';
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
