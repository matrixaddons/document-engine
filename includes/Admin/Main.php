<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

final class Main
{

    /**
     * The single instance of the class.
     *
     * @var Main
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @return Main - Main instance.
     * @since 1.0.0
     * @static
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Main Constructor.
     */
    public function __construct()
    {
        self::$_instance = $this;
        $this->init();
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'admin_menu'), 9);

        add_filter('plugin_action_links_' . plugin_basename(DOCUMENT_ENGINE_FILE), array($this, 'setting_link'));
    }

    public function setting_link($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=document-engine-settings')) . '">' . esc_html__('Settings', 'document-engine') . '</a>';
        array_unshift($links, $settings_link);

        if (!defined('DOCUMENT_ENGINE_PRO_FILE')) {
            $links[] = '<a href="' . esc_url(ProPage::url()) . '" style="color:#1d7a3a;font-weight:600">' . esc_html__('Upgrade to Pro', 'document-engine') . '</a>';
        }
        return $links;
    }

    function admin_menu()
    {
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;

        // Same slug as 1.x, so admin.php?page=document-engine-settings keeps working.
        $settings_page = add_submenu_page(
            $parent,
            /* translators: %s: plugin name */
            sprintf(__('%s Settings', 'document-engine'), DOCUMENT_ENGINE_BRAND),
            __('Settings', 'document-engine'),
            'manage_options',
            'document-engine-settings',
            array($this, 'settings'),
            20
        );

        add_action('load-' . $settings_page, array($this, 'settings_page_init'));

        if (!defined('DOCUMENT_ENGINE_PRO_FILE') || (defined('DOCUMENT_ENGINE_PRO_VERSION') && version_compare(DOCUMENT_ENGINE_PRO_VERSION, '2.0.0', '<'))) {
            add_submenu_page(
                $parent,
                esc_html__('Free vs Pro', 'document-engine'),
                esc_html__('Free vs Pro', 'document-engine'),
                'manage_options',
                ProPage::SLUG,
                array(ProPage::class, 'render'),
                40
            );
        }
    }

    public function settings()
    {
        Settings::output();
    }

    public function settings_page_init()
    {
        global $current_tab, $current_section;

        // Include settings pages.
        Settings::get_settings_pages();

        // Get current tab/section.
        $current_tab = empty($_GET['tab']) ? 'general' : sanitize_title(wp_unslash($_GET['tab'])); // WPCS: input var okay, CSRF ok.
        $current_section = empty($_REQUEST['section']) ? '' : sanitize_title(wp_unslash($_REQUEST['section'])); // WPCS: input var okay, CSRF ok.

        // Save settings if data has been posted.
        if ('' !== $current_section && apply_filters("document_engine_save_settings_{$current_tab}_{$current_section}", !empty($_POST['save']))) { // WPCS: input var okay, CSRF ok.
            Settings::save();
        } elseif ('' === $current_section && apply_filters("document_engine_save_settings_{$current_tab}", !empty($_POST['save']))) { // WPCS: input var okay, CSRF ok.
            Settings::save();
        }

        // Add any posted messages.
        if (!empty($_GET['document_engine_error'])) { // WPCS: input var okay, CSRF ok.
            Settings::add_error(wp_kses_post(wp_unslash($_GET['document_engine_error']))); // WPCS: input var okay, CSRF ok.
        }

        if (!empty($_GET['document_engine_message'])) { // WPCS: input var okay, CSRF ok.
            Settings::add_message(wp_kses_post(wp_unslash($_GET['document_engine_message']))); // WPCS: input var okay, CSRF ok.
        }

        do_action('document_engine_settings_page_init');
    }

    /**
     * Include required core files used in admin.
     */
    public function init()
    {
        Assets::init();
        Documents::init();
        Dashboard::init();
        AppShell::init();
        Upsell::init();
        Docs::init();
        ProInstaller::init();
        Migrate::init();
    }
}
