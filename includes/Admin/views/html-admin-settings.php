<?php
/**
 * Admin View: Settings (header, section navigation, cards, sticky save bar).
 *
 * @var array $tabs tab id => label
 */

use MatrixAddons\DocumentEngine\Admin\Settings;
use MatrixAddons\DocumentEngine\Admin\UI;

if (!defined('ABSPATH')) {
    exit;
}

$tab_exists = isset($tabs[$current_tab]) || has_action('document_engine_sections_' . $current_tab) || has_action('document_engine_settings_' . $current_tab) || has_action('document_engine_settings_tabs_' . $current_tab);
$current_tab_label = isset($tabs[$current_tab]) ? $tabs[$current_tab] : '';

if (!$tab_exists) {
    wp_safe_redirect(admin_url('admin.php?page=document-engine-settings'));
    exit;
}

// Sections per tab, from the tab objects (add-ons extend them through the usual filters).
$pages_by_id = array();
foreach (Settings::get_settings_pages() as $page_object) {
    if (is_object($page_object) && method_exists($page_object, 'get_id') && method_exists($page_object, 'get_sections')) {
        $pages_by_id[$page_object->get_id()] = $page_object;
    }
}
$sections = isset($pages_by_id[$current_tab]) ? (array)$pages_by_id[$current_tab]->get_sections() : array();
$section_label = isset($sections[$current_section]) ? $sections[$current_section] : '';

$icons = apply_filters('document_engine_settings_tab_icons', array(
    'general' => 'documents',
    'viewer' => 'viewer',
    'pdf_downloads' => 'pdf',
    'advanced' => 'advanced',
    'pro' => 'shield',
    'license' => 'key',
));
$descriptions = apply_filters('document_engine_settings_tab_descriptions', array(
    'general' => __('How documents behave across your site: pages, links, search and download counting.', 'document-engine'),
    'viewer' => __('The built-in PDF viewer runs on your own site. Nothing is sent to Google or anyone else.', 'document-engine'),
    'pdf_downloads' => __('Let visitors download posts and pages as branded PDFs.', 'document-engine'),
    'advanced' => __('Performance and data options.', 'document-engine'),
    'pro' => __('Access control, secure viewing, activity log, email gate and workflows.', 'document-engine'),
    'license' => __('Activate your license for one-click updates and support.', 'document-engine'),
));

$has_form = apply_filters('document_engine_settings_tab_has_form', true, $current_tab);
$title = $section_label !== '' && count($sections) > 1 && $section_label !== $current_tab_label ? $current_tab_label . ' · ' . $section_label : $current_tab_label;
$base = admin_url('admin.php?page=document-engine-settings');
?>
<div class="wrap dengine-a dengine-a--settings">
    <h1 class="screen-reader-text"><?php echo esc_html(DOCUMENT_ENGINE_BRAND . ' — ' . $title); ?></h1>
    <?php UI::header(); ?>
    <div class="dengine-a-layout">
        <nav class="dengine-a-nav" aria-label="<?php esc_attr_e('Settings', 'document-engine'); ?>">
            <ul>
                <?php foreach ($tabs as $slug => $label) :
                    $active = $current_tab === $slug;
                    $tab_sections = isset($pages_by_id[$slug]) ? (array)$pages_by_id[$slug]->get_sections() : array(); ?>
                    <li class="<?php echo $active ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', $slug, $base)); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
                            <?php echo UI::icon(isset($icons[$slug]) ? $icons[$slug] : 'documents'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <span><?php echo esc_html($label); ?></span>
                        </a>
                        <?php if ($active && count($tab_sections) > 1) : ?>
                            <ul class="dengine-a-nav__sub">
                                <?php foreach ($tab_sections as $section_id => $section_name) : ?>
                                    <li><a class="<?php echo (string)$current_section === (string)$section_id ? 'is-current' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('tab' => $slug, 'section' => $section_id), $base)); ?>" <?php echo (string)$current_section === (string)$section_id ? 'aria-current="page"' : ''; ?>><?php echo esc_html($section_name); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php do_action('document_engine_settings_tabs'); ?>
            </ul>
        </nav>

        <main class="dengine-a-main">
            <div class="dengine-a-page__head">
                <div>
                    <h2 class="dengine-a-page__title"><?php echo esc_html($title); ?></h2>
                    <?php if (!empty($descriptions[$current_tab])) : ?><p class="dengine-a-page__desc"><?php echo esc_html($descriptions[$current_tab]); ?></p><?php endif; ?>
                </div>
                <div class="dengine-a-page__actions"><?php do_action('document_engine_settings_header_actions', $current_tab, $current_section); ?></div>
            </div>

            <?php Settings::show_messages(); ?>

            <?php if ($has_form) : ?>
            <form method="<?php echo esc_attr(apply_filters('document_engine_settings_form_method_tab_' . $current_tab, 'post')); ?>" id="mainform" action="" enctype="multipart/form-data" class="dengine-a-form" data-dengine-settings>
            <?php endif; ?>

                <?php
                do_action('document_engine_settings_' . $current_tab);
                do_action('document_engine_settings_tabs_' . $current_tab);
                ?>

            <?php if ($has_form) : ?>
                <?php if (empty($GLOBALS['hide_save_button'])) : ?>
                    <div class="dengine-a-savebar">
                        <span class="dengine-a-savebar__status" data-dengine-status aria-live="polite"></span>
                        <button name="save" class="dengine-a-btn dengine-a-btn--primary" type="submit" value="<?php esc_attr_e('Save changes', 'document-engine'); ?>"><?php esc_html_e('Save changes', 'document-engine'); ?></button>
                    </div>
                <?php endif; ?>
                <?php wp_nonce_field('document-engine-settings'); ?>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>
