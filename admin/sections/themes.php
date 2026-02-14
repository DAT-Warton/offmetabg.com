<?php
/**
 * Theme Manager Section - Admin Panel
 * Manage site themes, import/export, and customize colors
 */

defined('CMS_ROOT') or die('Access denied');

// Get current theme from options
$db = new Database();
$currentTheme = $db->getOption('active_theme', 'default');

// Get custom themes from database
$customThemes = $db->query("SELECT * FROM themes WHERE type = 'custom' ORDER BY created_at DESC")->fetchAll();
?>

<div class="theme-manager-section">
    <div class="section-header">
        <h2>🎨 Theme Manager</h2>
        <p>Customize the appearance of your website with different color themes</p>
    </div>

    <!-- Current Active Theme -->
    <div class="card current-theme-card">
        <h3>Current Active Theme</h3>
        <div class="current-theme-display">
            <div class="theme-preview" id="current-theme-preview">
                <div class="theme-preview-header"></div>
                <div class="theme-preview-body">
                    <div class="theme-preview-box"></div>
                    <div class="theme-preview-box"></div>
                    <div class="theme-preview-box"></div>
                </div>
            </div>
            <div class="theme-info">
                <h4 id="current-theme-name">Loading...</h4>
                <p id="current-theme-description">Loading theme information...</p>
                <span class="theme-badge" id="current-theme-category">-</span>
            </div>
        </div>
    </div>

    <!-- Built-in Themes -->
    <div class="card themes-grid-card">
        <div class="card-header">
            <h3>Built-in Themes</h3>
            <button class="btn btn-secondary" onclick="ThemeAdmin.refreshThemes()">
                <span class="icon">🔄</span> Refresh
            </button>
        </div>

        <div class="themes-grid" id="themes-grid">
            <!-- Themes will be loaded here dynamically -->
        </div>
    </div>

    <!-- Import/Export Section -->
    <div class="card import-export-card">
        <h3>Import / Export Themes</h3>
        
        <div class="import-export-container">
            <!-- Export Current Theme -->
            <div class="export-section">
                <h4>📤 Export Current Theme</h4>
                <p>Download the current theme as JSON file</p>
                <button class="btn btn-primary" onclick="ThemeAdmin.exportTheme()">
                    <span class="icon">💾</span> Export Theme
                </button>
            </div>

            <!-- Import Theme -->
            <div class="import-section">
                <h4>📥 Import Theme</h4>
                <p>Upload a JSON theme file</p>
                <input type="file" id="theme-import-input" accept=".json" style="display:none;">
                <button class="btn btn-primary" onclick="document.getElementById('theme-import-input').click()">
                    <span class="icon">📁</span> Choose File
                </button>
                <div id="import-status" class="import-status"></div>
            </div>
        </div>
    </div>

    <!-- Custom Themes (from database) -->
    <?php if (!empty($customThemes)): ?>
    <div class="card custom-themes-card">
        <h3>Custom Themes</h3>
        <div class="themes-list">
            <?php foreach ($customThemes as $theme): ?>
            <div class="theme-item custom-theme-item" data-theme-id="<?php echo $theme['id']; ?>">
                <div class="theme-item-info">
                    <h4><?php echo htmlspecialchars($theme['name']); ?></h4>
                    <p><?php echo htmlspecialchars($theme['description'] ?? ''); ?></p>
                    <span class="theme-date">Created: <?php echo date('M d, Y', strtotime($theme['created_at'])); ?></span>
                </div>
                <div class="theme-item-actions">
                    <button class="btn btn-sm btn-primary" onclick="ThemeAdmin.applyCustomTheme(<?php echo $theme['id']; ?>)">
                        Apply
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="ThemeAdmin.editCustomTheme(<?php echo $theme['id']; ?>)">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="ThemeAdmin.deleteCustomTheme(<?php echo $theme['id']; ?>)">
                        Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Theme Customizer (Advanced) -->
    <div class="card theme-customizer-card collapsed">
        <div class="card-header" onclick="this.parentElement.classList.toggle('collapsed')">
            <h3>🎨 Advanced Theme Customizer</h3>
            <span class="toggle-icon">▼</span>
        </div>
        
        <div class="card-body">
            <p class="text-muted">Customize individual colors and create your own theme</p>
            
            <div class="color-customizer">
                <div class="color-group">
                    <h4>Primary Colors</h4>
                    <div class="color-inputs">
                        <div class="color-input-item">
                            <label>Primary:</label>
                            <input type="color" id="color-primary" value="#6b46c1">
                            <input type="text" id="color-primary-text" value="#6b46c1">
                        </div>
                        <div class="color-input-item">
                            <label>Primary Hover:</label>
                            <input type="color" id="color-primary-hover" value="#7c3aed">
                            <input type="text" id="color-primary-hover-text" value="#7c3aed">
                        </div>
                        <div class="color-input-item">
                            <label>Secondary:</label>
                            <input type="color" id="color-secondary" value="#10b981">
                            <input type="text" id="color-secondary-text" value="#10b981">
                        </div>
                        <div class="color-input-item">
                            <label>Accent:</label>
                            <input type="color" id="color-accent" value="#ec4899">
                            <input type="text" id="color-accent-text" value="#ec4899">
                        </div>
                    </div>
                </div>

                <div class="color-group">
                    <h4>Background Colors</h4>
                    <div class="color-inputs">
                        <div class="color-input-item">
                            <label>Background:</label>
                            <input type="color" id="color-bg-primary" value="#ffffff">
                            <input type="text" id="color-bg-primary-text" value="#ffffff">
                        </div>
                        <div class="color-input-item">
                            <label>Background Secondary:</label>
                            <input type="color" id="color-bg-secondary" value="#f5f5f5">
                            <input type="text" id="color-bg-secondary-text" value="#f5f5f5">
                        </div>
                    </div>
                </div>

                <div class="color-group">
                    <h4>Text Colors</h4>
                    <div class="color-inputs">
                        <div class="color-input-item">
                            <label>Text Primary:</label>
                            <input type="color" id="color-text-primary" value="#1b1430">
                            <input type="text" id="color-text-primary-text" value="#1b1430">
                        </div>
                        <div class="color-input-item">
                            <label>Text Secondary:</label>
                            <input type="color" id="color-text-secondary" value="#52456d">
                            <input type="text" id="color-text-secondary-text" value="#52456d">
                        </div>
                    </div>
                </div>

                <div class="customizer-actions">
                    <button class="btn btn-primary" onclick="ThemeAdmin.applyCustomColors()">
                        <span class="icon">✨</span> Apply Custom Colors
                    </button>
                    <button class="btn btn-success" onclick="ThemeAdmin.saveCustomTheme()">
                        <span class="icon">💾</span> Save as New Theme
                    </button>
                    <button class="btn btn-secondary" onclick="ThemeAdmin.resetCustomizer()">
                        <span class="icon">🔄</span> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.theme-manager-section {
    padding: 20px;
}

.section-header {
    margin-bottom: 30px;
}

.section-header h2 {
    font-size: 28px;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.section-header p {
    color: var(--text-secondary);
    font-size: 14px;
}

.card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px var(--shadow-sm);
}

.card h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: var(--text-primary);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.current-theme-display {
    display: flex;
    gap: 24px;
    align-items: center;
}

.theme-preview {
    width: 200px;
    height: 120px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg-primary);
}

.theme-preview-header {
    height: 30%;
    background: var(--primary);
}

.theme-preview-body {
    height: 70%;
    padding: 10px;
    display: flex;
    gap: 8px;
    background: var(--bg-secondary);
}

.theme-preview-box {
    flex: 1;
    background: var(--bg-primary);
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.theme-info h4 {
    font-size: 18px;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.theme-info p {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 10px;
}

.theme-badge {
    display: inline-block;
    padding: 4px 12px;
    background: var(--primary);
    color: white;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.theme-card {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--bg-primary);
}

.theme-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px var(--shadow-md);
    transform: translateY(-2px);
}

.theme-card.active {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.2);
}

.theme-card-preview {
    height: 100px;
    position: relative;
}

.theme-card-info {
    padding: 16px;
}

.theme-card-info h4 {
    font-size: 16px;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.theme-card-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 10px;
}

.import-export-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.export-section, .import-section {
    padding: 20px;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    text-align: center;
}

.export-section h4, .import-section h4 {
    font-size: 16px;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.export-section p, .import-section p {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 16px;
}

.import-status {
    margin-top: 12px;
    padding: 8px;
    border-radius: 4px;
    font-size: 13px;
}

.import-status.success {
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid var(--success-border);
}

.import-status.error {
    background: var(--danger-bg);
    color: var(--danger-text);
    border: 1px solid var(--danger-border);
}

.themes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.theme-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-secondary);
}

.theme-item-info h4 {
    font-size: 16px;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.theme-item-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.theme-date {
    font-size: 12px;
    color: var(--text-muted);
}

.theme-item-actions {
    display: flex;
    gap: 8px;
}

.card.collapsed .card-body {
    display: none;
}

.card.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.toggle-icon {
    transition: transform 0.3s ease;
    display: inline-block;
}

.color-customizer {
    max-width: 800px;
}

.color-group {
    margin-bottom: 24px;
}

.color-group h4 {
    font-size: 16px;
    margin-bottom: 12px;
    color: var(--text-primary);
}

.color-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.color-input-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.color-input-item label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
}

.color-input-item input[type="color"] {
    width: 100%;
    height: 40px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    cursor: pointer;
}

.color-input-item input[type="text"] {
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 13px;
    font-family: monospace;
}

.customizer-actions {
    margin-top: 24px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--bg-hover);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    opacity: 0.9;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    opacity: 0.9;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.text-muted {
    color: var(--text-muted);
    font-size: 14px;
}
</style>

<script src="../assets/js/theme-manager.js"></script>
<script src="assets/js/theme-admin.js"></script>
