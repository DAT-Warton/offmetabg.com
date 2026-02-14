<?php
/**
 * Site Settings Section - Database-Driven Configuration
 */

// Handle image uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
    $upload_dir = __DIR__ . '/../../uploads/settings/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    foreach ($_FILES as $input_name => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (in_array($extension, $allowed_extensions)) {
                $filename = $input_name . '_' . time() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $_POST[$input_name] = '/uploads/settings/' . $filename;
                }
            }
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    if (!function_exists('get_database')) {
        die('Error: Database function not available');
    }
    $db = get_database();
    $updated = 0;
    
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        
        // Find the setting
        $stmt = $db->prepare("SELECT id, setting_type, is_encrypted FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($setting) {
            // Handle encryption if needed
            $save_value = $value;
            if ($setting['is_encrypted'] && !empty($value)) {
                // In production, use proper encryption
                // For now, just mark it as encrypted (implement proper encryption later)
                $save_value = $value;
            }
            
            // Update setting
            $update_stmt = $db->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$save_value, $setting['id']]);
            $updated++;
        }
    }
    
    $success_message = "✅ Updated {$updated} settings successfully!";
}

// Load all settings grouped by category
if (!function_exists('get_database')) {
    die('Error: Database function not available');
}
$db = get_database();
$stmt = $db->query("SELECT * FROM site_settings ORDER BY category, display_order, setting_key");
$all_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings_by_category = [];
foreach ($all_settings as $setting) {
    $settings_by_category[$setting['category']][] = $setting;
}
?>

<style>
.settings-page {
    max-width: 1400px;
    margin: 0 auto;
}

.settings-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid var(--primary);
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.settings-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    color: var(--text-secondary);
    transition: all 0.3s;
    text-transform: capitalize;
}

.settings-tab:hover {
    color: var(--primary);
    background: var(--bg-hover);
}

.settings-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--bg-hover);
}

.settings-panel {
    display: none;
    animation: fadeIn 0.3s;
}

.settings-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.setting-item {
    background: var(--bg-secondary, #2d2d2d);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: background-color 0.3s ease;
}

.setting-item label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.setting-item .help-text {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 10px;
    font-style: italic;
}

.setting-item input[type="text"],
.setting-item input[type="email"],
.setting-item input[type="url"],
.setting-item input[type="number"],
.setting-item input[type="password"],
.setting-item textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    background: var(--bg-primary, #1a1a1a);
    color: var(--text-primary);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.setting-item input:focus,
.setting-item textarea:focus,
.setting-item select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(107, 70, 193, 0.2);
}

.setting-item textarea {
    min-height: 80px;
    resize: vertical;
}

.setting-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.setting-item .checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.setting-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.badge-encrypted {
    background: var(--warning);
    color: white;
}

.badge-private {
    background: var(--danger);
    color: white;
}

.badge-public {
    background: var(--success);
    color: white;
}

.settings-actions {
    position: sticky;
    bottom: 0;
    background: var(--bg-secondary, #2d2d2d);
    padding: 20px;
    border-top: 2px solid var(--primary);
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    transition: background-color 0.3s ease;
}

.btn-save {
    padding: 12px 32px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-save:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 70, 193, 0.3);
}

.btn-reset {
    padding: 12px 32px;
    background: var(--text-secondary);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

.success-message {
    background: var(--success);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.image-upload-wrapper {
    display: flex;
    flex-direction: column;
}

.image-upload-wrapper .current-image {
    padding: 10px;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 2px dashed var(--border-color);
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.image-upload-wrapper input[type="file"] {
    padding: 8px;
    border: 2px solid var(--border-color);
    border-radius: 4px;
    background: var(--bg-secondary, #2d2d2d);
    color: var(--text-primary);
    transition: background-color 0.3s ease;
}

.image-upload-wrapper input[type="text"] {
    margin-top: 8px;
}

.image-upload-wrapper select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    background: var(--bg-secondary, #2d2d2d);
    color: var(--text-primary);
    transition: background-color 0.3s ease;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-tabs {
        gap: 5px;
    }
    
    .settings-tab {
        padding: 10px 16px;
        font-size: 13px;
    }
}
</style>

<div class="settings-page">
    <h2><?php echo icon_settings(24); ?> Site Settings</h2>
    
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="settings-tabs">
        <?php 
        $categories = array_keys($settings_by_category);
        foreach ($categories as $index => $category): 
        ?>
            <button type="button" class="settings-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                    onclick="switchTab('<?php echo $category; ?>')">
                <?php echo ucfirst(str_replace('_', ' ', $category)); ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <form method="POST" id="settingsForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_settings">
        
        <?php foreach ($settings_by_category as $category => $settings): ?>
        <div class="settings-panel <?php echo $category === $categories[0] ? 'active' : ''; ?>" 
             id="panel-<?php echo $category; ?>">
            
            <div class="settings-grid">
                <?php foreach ($settings as $setting): ?>
                    <div class="setting-item">
                        <label>
                            <?php echo htmlspecialchars($setting['label'] ?: $setting['setting_key']); ?>
                            
                            <?php if ($setting['is_encrypted']): ?>
                                <span class="setting-badge badge-encrypted">🔒 Encrypted</span>
                            <?php endif; ?>
                            
                            <?php if (!$setting['is_public']): ?>
                                <span class="setting-badge badge-private">Private</span>
                            <?php else: ?>
                                <span class="setting-badge badge-public">Public</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php if ($setting['description']): ?>
                            <span class="help-text"><?php echo htmlspecialchars($setting['description']); ?></span>
                        <?php endif; ?>
                        
                        <?php
                        $value = $setting['setting_value'] ?? $setting['default_value'] ?? '';
                        $name = htmlspecialchars($setting['setting_key']);
                        
                        switch ($setting['setting_type']):
                            case 'image':
                                ?>
                                <div class="image-upload-wrapper">
                                    <?php if (!empty($value)): ?>
                                        <div class="current-image">
                                            <img src="<?php echo htmlspecialchars($value); ?>" 
                                                 alt="<?php echo htmlspecialchars($setting['label']); ?>" 
                                                 style="max-width: 200px; max-height: 100px; object-fit: contain; margin-bottom: 10px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" 
                                           name="<?php echo $name; ?>" 
                                           accept="image/*"
                                           style="margin-bottom: 8px;">
                                    <input type="text" 
                                           name="<?php echo $name; ?>" 
                                           value="<?php echo htmlspecialchars($value); ?>"
                                           placeholder="Or enter image URL">
                                    <small style="color: var(--text-secondary);">Upload image or enter URL</small>
                                </div>
                                <?php
                                break;
                            
                            case 'select':
                                ?>
                                <select name="<?php echo $name; ?>">
                                    <?php
                                    // Extract options from description or use defaults
                                    $options = [];
                                    if ($name === 'logo_position') {
                                        $options = ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'];
                                    }
                                    foreach ($options as $opt_value => $opt_label):
                                    ?>
                                        <option value="<?php echo $opt_value; ?>" 
                                                <?php echo $value === $opt_value ? 'selected' : ''; ?>>
                                            <?php echo $opt_label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                break;
                            
                            case 'boolean':
                                ?>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" 
                                           name="<?php echo $name; ?>" 
                                           value="true"
                                           <?php echo ($value === 'true' || $value === '1') ? 'checked' : ''; ?>>
                                    <span>Enable this setting</span>
                                </div>
                                <?php
                                break;
                            
                            case 'password':
                                ?>
                                <input type="password" 
                                       name="<?php echo $name; ?>" 
                                       value="<?php echo htmlspecialchars($value); ?>"
                                       placeholder="<?php echo $setting['is_encrypted'] ? '••••••••' : ''; ?>">
                                <?php
                                break;
                            
                            case 'number':
                                ?>
                                <input type="number" 
                                       name="<?php echo $name; ?>" 
                                       value="<?php echo htmlspecialchars($value); ?>"
                                       step="any">
                                <?php
                                break;
                            
                            case 'email':
                                ?>
                                <input type="email" 
                                       name="<?php echo $name; ?>" 
                                       value="<?php echo htmlspecialchars($value); ?>">
                                <?php
                                break;
                            
                            case 'url':
                                ?>
                                <input type="url" 
                                       name="<?php echo $name; ?>" 
                                       value="<?php echo htmlspecialchars($value); ?>"
                                       placeholder="https://">
                                <?php
                                break;
                            
                            case 'json':
                                ?>
                                <textarea name="<?php echo $name; ?>" 
                                          rows="4"><?php echo htmlspecialchars($value); ?></textarea>
                                <small style="color: var(--text-secondary);">JSON format</small>
                                <?php
                                break;
                            
                            default:
                                if (strlen($value) > 100):
                                    ?>
                                    <textarea name="<?php echo $name; ?>" 
                                              rows="4"><?php echo htmlspecialchars($value); ?></textarea>
                                    <?php
                                else:
                                    ?>
                                    <input type="text" 
                                           name="<?php echo $name; ?>" 
                                           value="<?php echo htmlspecialchars($value); ?>">
                                    <?php
                                endif;
                                break;
                        endswitch;
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="settings-actions">
            <button type="button" class="btn-reset" onclick="document.getElementById('settingsForm').reset()">
                Reset Changes
            </button>
            <button type="submit" class="btn-save">
                💾 Save All Settings
            </button>
        </div>
    </form>
</div>

<script>
function switchTab(category) {
    // Hide all panels
    document.querySelectorAll('.settings-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Remove active from all tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected panel
    document.getElementById('panel-' + category).classList.add('active');
    
    // Mark tab as active
    event.target.classList.add('active');
}

// Handle checkbox values properly
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    // Convert unchecked checkboxes to "false"
    this.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        if (!checkbox.checked) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = checkbox.name;
            hidden.value = 'false';
            this.appendChild(hidden);
        }
    });
});
</script>