<?php
/**
 * User Profile Dropdown Component
 * Professional dropdown menu with wishlist, orders, settings
 */

// Ensure we have session data
$is_logged_in = isset($_SESSION['customer_id']) && isset($_SESSION['customer_user']);
if (!$is_logged_in) {
    return; // Don't display if not logged in
}

$user_name = $_SESSION['customer_user'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'customer';

// Get customer data
$customer = null;
$wishlist_count = 0;
$orders_count = 0;

if (isset($_SESSION['customer_id'])) {
    try {
        $customer = db_table('customers')->find('id', $_SESSION['customer_id']);
        
        // Get wishlist count
        $wishlist_items = db_table('wishlist')->findAll('customer_id', $_SESSION['customer_id']);
        $wishlist_count = count($wishlist_items);
        
        // Get orders count
        $orders = db_table('orders')->findAll('customer_id', $_SESSION['customer_id']);
        $orders_count = count($orders);
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
    <button class="profile-trigger" onclick="toggleProfileDropdown()" aria-label="User menu" aria-expanded="false">
        <?php if (!empty($profile_picture)): ?>
            <img src="/<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="profile-trigger-pic">
        <?php else: ?>
            <div class="profile-trigger-placeholder"><?php echo $initials; ?></div>
        <?php endif; ?>
        <span class="profile-trigger-name"><?php echo htmlspecialchars($user_name); ?></span>
        <svg class="profile-trigger-arrow" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M4.5 5.5L8 9L11.5 5.5L12.5 6.5L8 11L3.5 6.5L4.5 5.5Z"/>
        </svg>
    </button>
    
    <div class="profile-dropdown-menu">
        <div class="profile-dropdown-header">
            <?php if (!empty($profile_picture)): ?>
                <img src="/<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="profile-dropdown-pic">
            <?php else: ?>
                <div class="profile-dropdown-placeholder"><?php echo $initials; ?></div>
            <?php endif; ?>
            <div class="profile-dropdown-header-info">
                <div class="profile-dropdown-header-name"><?php echo htmlspecialchars($user_name); ?></div>
                <?php if (!empty($email)): ?>
                    <div class="profile-dropdown-header-email"><?php echo htmlspecialchars($email); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-dropdown-items">
            <a href="/profile.php" class="profile-dropdown-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span><?php echo __('profile.my_profile'); ?></span>
            </a>
            
            <a href="/wishlist.php" class="profile-dropdown-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span><?php echo __('profile.wishlist'); ?></span>
                <?php if ($wishlist_count > 0): ?>
                    <span class="profile-dropdown-badge"><?php echo $wishlist_count; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="/profile.php?tab=orders" class="profile-dropdown-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span><?php echo __('profile.orders'); ?></span>
                <?php if ($orders_count > 0): ?>
                    <span class="profile-dropdown-badge"><?php echo $orders_count; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="/profile.php?tab=settings" class="profile-dropdown-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m-9-9h6m6 0h6"></path>
                </svg>
                <span><?php echo __('profile.settings'); ?></span>
            </a>
            
            <?php if ($user_role === 'admin'): ?>
                <div class="profile-dropdown-divider"></div>
                <a href="/admin/index.php" class="profile-dropdown-item admin">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span><?php echo __('admin_panel'); ?></span>
                </a>
            <?php endif; ?>
            
            <div class="profile-dropdown-divider"></div>
            
            <a href="/auth.php?logout=1" class="profile-dropdown-item logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span><?php echo __('logout'); ?></span>
            </a>
        </div>
    </div>
</div>

<script>
// Toggle profile dropdown
function toggleProfileDropdown() {
    const dropdown = document.getElementById('userProfileDropdown');
    const trigger = dropdown.querySelector('.profile-trigger');
    const isOpen = dropdown.classList.toggle('open');
    trigger.setAttribute('aria-expanded', isOpen);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userProfileDropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('open');
        const trigger = dropdown.querySelector('.profile-trigger');
        trigger.setAttribute('aria-expanded', 'false');
    }
});

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('userProfileDropdown');
        if (dropdown) {
            dropdown.classList.remove('open');
            const trigger = dropdown.querySelector('.profile-trigger');
            trigger.setAttribute('aria-expanded', 'false');
        }
    }
});
</script>
