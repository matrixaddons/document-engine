<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Pro previews for free users: shown where the features would live, never as nag notices.
 * Everything here switches off when Document Engine Pro is active.
 */
class Upsell
{
    const SLUG = 'dengine-pro-feature';

    public static function init()
    {
        if (defined('DOCUMENT_ENGINE_PRO_FILE')) {
            return;
        }
        add_action('admin_menu', array(__CLASS__, 'menu'), 30);
        add_action('admin_menu', array(__CLASS__, 'preview_tabs'), 31);
        add_filter('document_engine_menu_previews', array(__CLASS__, 'preview_slugs'));
        add_filter('document_engine_settings_tabs_array', array(__CLASS__, 'settings_tab'), 80);
        add_action('document_engine_settings_pro_preview', array(__CLASS__, 'settings_tab_body'));
        add_filter('document_engine_settings_tab_has_form', function ($has, $tab) {
            return $tab === 'pro_preview' ? false : $has;
        }, 10, 2);
        add_filter('document_engine_settings_tab_icons', function ($icons) {
            $icons['pro_preview'] = 'shield';
            return $icons;
        });
        add_filter('document_engine_settings_tab_descriptions', function ($d) {
            $d['pro_preview'] = __('Decide who can open each document and keep files private. Available in Pro.', 'document-engine');
            return $d;
        });
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'editor_data'), 20);
    }

    /**
     * Feature previews (also used for the pages). Keyed by slug.
     */
    public static function features()
    {
        return apply_filters('document_engine_pro_previews', array(
            'access' => array(
                'icon' => 'shield',
                'title' => __('Access control & private files', 'document-engine'),
                'lead' => __('Decide who can open every document — by role, by person, or for a whole category — and keep the files out of public reach.', 'document-engine'),
                'points' => array(
                    __('Restrict documents or entire categories to logged-in users, roles or named people', 'document-engine'),
                    __('Protected storage: files move out of the public uploads folder', 'document-engine'),
                    __('Expiring share links for people without an account', 'document-engine'),
                    __('Secure viewer with per-reader watermarks; no download, print or copy', 'document-engine'),
                ),
            ),
            'activity' => array(
                'icon' => 'activity',
                'title' => __('Activity & analytics', 'document-engine'),
                'lead' => __('See who opened and downloaded what, and when — with a full audit log you can export.', 'document-engine'),
                'points' => array(
                    __('Views, downloads, people and blocked requests over time', 'document-engine'),
                    __('Per-document history and top documents', 'document-engine'),
                    __('Filterable audit log with CSV export and retention settings', 'document-engine'),
                    __('Privacy-friendly: no IP addresses stored', 'document-engine'),
                ),
            ),
            'leads' => array(
                'icon' => 'users',
                'title' => __('Email gate & leads', 'document-engine'),
                'lead' => __('Ask for a name and email before a download, with consent — and send leads to your CRM.', 'document-engine'),
                'points' => array(
                    __('Per document or site-wide', 'document-engine'),
                    __('Consent checkbox and spam protection built in', 'document-engine'),
                    __('Webhook to Zapier, Make or your CRM; CSV export', 'document-engine'),
                    __('Returning visitors are remembered', 'document-engine'),
                ),
            ),
            'import' => array(
                'icon' => 'upload',
                'title' => __('Bulk import, versions & more', 'document-engine'),
                'lead' => __('Move a whole archive in at once and keep every document current.', 'document-engine'),
                'points' => array(
                    __('Drag in hundreds of files or import a spreadsheet', 'document-engine'),
                    __('Versions: replace a file without changing its link, restore any time', 'document-engine'),
                    __('Review and expiry dates with reminder emails', 'document-engine'),
                    __('Search inside PDF, Word, Excel and PowerPoint files', 'document-engine'),
                ),
            ),
        ));
    }

    public static function url($feature)
    {
        return admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . self::SLUG . '&feature=' . $feature);
    }

    public static function menu()
    {
        // Opened from Pro previews in the editor and settings; not listed in the WordPress menu.
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        $hook = add_submenu_page($parent, __('Document Engine Pro', 'document-engine'), __('Pro feature', 'document-engine'), 'edit_dengine_documents', self::SLUG, array(__CLASS__, 'render'));
        remove_submenu_page($parent, self::SLUG);
        // Pages removed from the menu have no title; give the browser tab one.
        add_action('load-' . $hook, function () {
            $GLOBALS['title'] = __('Document Engine Pro', 'document-engine');
        });
    }

    /**
     * Pro screens a free site would look for inside Reports and Tools (Activity, Import): shown as a
     * "Pro" tab next to the free screens of the same group, never as menu items of their own.
     */
    public static function preview_slugs($slugs = array())
    {
        return array_merge((array)$slugs, array('dengine-activity' => 'activity', 'dengine-import' => 'import'));
    }

    public static function preview_tabs()
    {
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        $caps = array('dengine-activity' => 'edit_others_dengine_documents', 'dengine-import' => 'publish_dengine_documents');
        $features = self::features();
        foreach (self::preview_slugs() as $slug => $feature) {
            if (!isset($features[$feature])) {
                continue;
            }
            add_submenu_page($parent, $features[$feature]['title'], $feature === 'activity' ? __('Activity', 'document-engine') : __('Import', 'document-engine'), $caps[$slug], $slug, function () use ($feature) {
                $features = self::features();
                UI::page_start($features[$feature]['title'], esc_html__('Available in Document Engine Pro.', 'document-engine'));
                self::preview($features[$feature]);
                UI::page_end();
            });
        }
    }

    public static function render()
    {
        $features = self::features();
        $key = isset($_GET['feature']) ? sanitize_key($_GET['feature']) : 'access'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $feature = isset($features[$key]) ? $features[$key] : $features['access'];
        UI::page_start($feature['title'], esc_html__('Available in Document Engine Pro.', 'document-engine'));
        self::preview($feature);
        UI::page_end();
    }

    /**
     * The shared preview block: explanation, benefits, and both paths (buy / install with key).
     */
    public static function preview($feature)
    {
        ?>
        <div class="dengine-a-preview">
            <div class="dengine-a-preview__main">
                <span class="dengine-a-preview__icon"><?php echo UI::icon($feature['icon'], 26); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span class="dengine-a-pill dengine-a-pill--pro"><?php esc_html_e('Pro', 'document-engine'); ?></span>
                <h3><?php echo esc_html($feature['title']); ?></h3>
                <p class="dengine-a-preview__lead"><?php echo esc_html($feature['lead']); ?></p>
                <ul class="dengine-a-preview__points">
                    <?php foreach ($feature['points'] as $point) : ?>
                        <li><?php echo UI::icon('check', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html($point); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="dengine-a-preview__actions">
                    <a class="dengine-a-btn dengine-a-btn--primary" href="<?php echo esc_url(ProPage::store_url('preview-' . sanitize_key($feature['icon']))); ?>" target="_blank" rel="noopener"><?php esc_html_e('See plans and pricing', 'document-engine'); ?><?php echo UI::icon('external', 14); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="dengine-a-btn dengine-a-btn--secondary" href="<?php echo esc_url(ProPage::url()); ?>"><?php esc_html_e('Compare free and Pro', 'document-engine'); ?></a>
                </p>
            </div>
            <aside class="dengine-a-preview__side">
                <?php ProInstaller::form(); ?>
            </aside>
        </div>
        <?php
    }

    public static function settings_tab($tabs)
    {
        $tabs['pro_preview'] = __('Access & security', 'document-engine');
        return $tabs;
    }

    public static function settings_tab_body()
    {
        $features = self::features();
        self::preview($features['access']);
    }

    /**
     * Lets the document editor show a small "Access & security (Pro)" panel.
     */
    public static function editor_data()
    {
        if (!wp_script_is('document-engine-document-editor', 'enqueued')) {
            return;
        }
        wp_add_inline_script('document-engine-document-editor', 'window.DocumentEngineEditor = Object.assign(window.DocumentEngineEditor || {}, ' . wp_json_encode(array(
            'proPreview' => array(
                'title' => __('Access & security', 'document-engine'),
                'text' => __('Control who can open this document and prove who read it.', 'document-engine'),
                'points' => array(
                    __('Members, roles or named people only', 'document-engine'),
                    __('Private file storage and expiring share links', 'document-engine'),
                    __('Watermarked secure viewer', 'document-engine'),
                    __('"I have read and understood" confirmations', 'document-engine'),
                ),
                'button' => __('See what Pro adds', 'document-engine'),
                'url' => self::url('access'),
            ),
        )) . ');', 'before');
    }
}
