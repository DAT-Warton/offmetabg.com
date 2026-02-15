<?php
/**
 * User Profile Dropdown Component
 * Displays user profile with dropdown menu for profile, wishlist, orders, logout
 */

// Ensure we have session data
$is_logged_in = isset($_SESSION['customer_id']) && isset($_SESSION['customer_user']);
if (!$is_logged_in) {
    return; // Don't display if not logged in
}

$user_name = $_SESSION['customer_user'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'customer';

// Get customer data for profile picture
$customer = null;
if (isset($_SESSION['customer_id'])) {
    try {
        $customer = db_table('customers')->find('id', $_SESSION['customer_id']);
    } catch (Exception $e) {
        // Database might not be available
    }
}

$profile_picture = $customer['profile_picture'] ?? null;
$email = $customer['email'] ?? '';

// Get initials for placeholder
$initials = strtoupper(substr($user_name, 0, 1));
?>
<div class="user-profile-dropdown" id="userProfileDropdown">
    <div class="profile-trigger" onclick="toggleProfileDropdown()">
        <?php if (!empty($profile_picture)): ?>
            <img src="/<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="profile-trigger-pic">
        <?php else: ?>
            <div class="profile-trigger-placeholder"><?php echo $initials; ?></div>
        <?php endif; ?>
        <span class="profile-trigger-name"><?php echo htmlspecialchars($user_name); ?></span>
        <svg class="profile-trigger-arrow" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M4.5 5.5L8 9L11.5 5.5L12.5 6.5L8 11L3.5 6.5L4.5 5.5Z"/>
        </svg>
    </div>
    
    <div class="profile-dropdown-menu">
        <div class="profile-dropdown-header">
            <div class="profile-dropdown-header-name"><?php echo htmlspecialchars($user_name); ?></div>
            <?php if (!empty($email)): ?>
                <div class="profile-dropdown-header-email"><?php echo htmlspecialchars($email); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="profile-dropdown-items">
            <a href="/profile.php" class="profile-dropdown-item">
                <?php echo icon_user(18); ?>
                <span><?php echo __('profile.my_profile'); ?></span>
            </a>
            
            <a href="/profile.php?tab=wishlist" class="profile-dropdown-item">
                <?php echo icon_heart(18); ?>
                <span><?php echo __('profile.wishlist'); ?></span>
            </a>
            
            <a href="/profile.php?tab=orders" class="profile-dropdown-item">
                <?php echo icon_shopping_bag(18); ?>
                <span><?php echo __('profile.orders'); ?></span>
            </a>
            
            <a href="/profile.php?tab=security" class="profile-dropdown-item">
                <?php echo icon_lock(18); ?>
                <span><?php echo __('profile.security'); ?></span>
            </a>
            
            <?php if ($user_role === 'admin'): ?>
                <div class="profile-dropdown-divider"></div>
                <a href="/admin/index.php" class="profile-dropdown-item">
                    <?php echo icon_settings(18); ?>
                    <span><?php echo __('admin_panel'); ?></span>
                </a>
            <?php endif; ?>
            
            <div class="profile-dropdown-divider"></div>
            
            <a href="/auth.php?logout=1" class="profile-dropdown-item logout">
                <?php echo icon_log_out(18); ?>
                <span><?php echo __('logout'); ?></span>
            </a>
        </div>
    </div>
</div>

<script>
// Toggle profile dropdown
function toggleProfileDropdown() {
    const dropdown = document.getElementById('userProfileDropdown');
    dropdown.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userProfileDropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('open');
    }
});

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('userProfileDropdown');
        if (dropdown) {
            dropdown.classList.remove('open');
        }
    }
});
</script>
