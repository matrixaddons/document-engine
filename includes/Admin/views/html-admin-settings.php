<?php
/**
 * Admin View: Settings
 *
 * @package AgencyEcommerceAddons
 */

if (!defined('ABSPATH')) {
    exit;
}


$tab_exists = isset($tabs[$current_tab]) || has_action('document_engine_sections_' . $current_tab) || has_action('document_engine_settings_' . $current_tab) || has_action('document_engine_settings_tabs_' . $current_tab);
$current_tab_label = isset($tabs[$current_tab]) ? $tabs[$current_tab] : '';

if (!$tab_exists) {
    wp_safe_redirect(admin_url('admin.php?page=document-engine-settings'));
    exit;
}
?>
<div class="wrap document-engine-admin-setting-page-wrap">
    <h1 class="screen-reader-text"><?php echo esc_html($current_tab_label); ?></h1>
    <form method="<?php echo esc_attr(apply_filters('document_engine_settings_form_method_tab_' . $current_tab, 'post')); ?>"
          id="mainform" action="" enctype="multipart/form-data">
        <nav class="nav-tab-wrapper document-engine-nav-tab-wrapper">
            <?php

            foreach ($tabs as $slug => $label) {
                echo '<a href="' . esc_html(admin_url('admin.php?page=document-engine-settings&tab=' . esc_attr($slug))) . '" class="nav-tab ' . ($current_tab === $slug ? 'nav-tab-active' : '') . '">' . esc_html($label) . '</a>';
            }

            do_action('document_engine_settings_tabs');

            ?>
        </nav>

        <?php
        do_action('document_engine_sections_' . $current_tab);

        self::show_messages();

        do_action('document_engine_settings_' . $current_tab);
        do_action('document_engine_settings_tabs_' . $current_tab);
        ?>
        <p class="submit">
            <?php if (empty($GLOBALS['hide_save_button'])) : ?>
                <button name="save" class="button-primary document-engine-save-button" type="submit"
                        value="<?php esc_attr_e('Save changes', 'document-engine'); ?>"><?php esc_html_e('Save changes', 'document-engine'); ?></button>
            <?php endif; ?>
            <?php wp_nonce_field('document-engine-settings'); ?>
        </p>
    </form>
</div>
