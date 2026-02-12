<?php
/**
 * Settings Section
 */
?>

<div>
    <h2><?php echo icon_settings(24); ?> <?php echo __('settings.title'); ?></h2>

    <form method="POST">
        <input type="hidden" name="action" value="update_settings">

        <div class="form-group">
            <label><?php echo __('settings.site_title'); ?></label>
            <input type="text" name="site_title" value="<?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?>">
        </div>

        <div class="form-group">
            <label><?php echo __('settings.site_description'); ?></label>
            <textarea name="site_description"><?php echo htmlspecialchars(get_option('site_description', '')); ?></textarea>
        </div>

        <div class="form-group">
            <label>Администраторски имейл</label>
            <input type="email" name="site_email" value="<?php echo htmlspecialchars(get_option('site_email', '')); ?>">
        </div>

        <button type="submit"><?php echo __('save'); ?> <?php echo __('settings.title'); ?></button>
    </form>
</div>
