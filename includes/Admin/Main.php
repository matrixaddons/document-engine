<?php

namespace MatrixAddons\DocumentEngine\Admin;
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
     * Main Main Instance.
     *
     * Ensures only one instance of Yatra_Admin is loaded or can be loaded.
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


        add_action('admin_menu', array($this, 'admin_menu'));

        add_filter('plugin_action_links_' . plugin_basename(DOCUMENT_ENGINE_FILE), array($this, 'setting_link'));


    }

    public function setting_link($links)
    {

        $settings_link = '<a href="admin.php?page=document-engine-settings">' . __('Settings', 'document-engine') . '</a>';
        array_push($links, $settings_link);
        return $links;

    }

    function admin_menu()
    {

        $settings_page = add_menu_page('Documents Engine', 'Documents', 'manage_options', 'document-engine-settings', array($this, 'settings'), DOCUMENT_ENGINE_ASSETS_URI . 'images/menu-icon.svg', 25);

        add_action('load-' . $settings_page, array($this, 'settings_page_init'));

        if(!defined('DOCUMENT_ENGINE_PRO_FILE')) {
            add_submenu_page(
                'document-engine-settings',
                esc_html__('Upgrade to Pro', 'document-engine'),
                '<span style="color:#e27730">' . esc_html__('Upgrade to Pro', 'document-engine') . '</span>',
                'manage_options',
                esc_url('https://matrixaddons.com/downloads/document-engine-pro/?utm_campaign=freeplugin&utm_medium=admin-menu&utm_source=WordPress&utm_content=Upgrade+to+Pro'),
                '',
                35
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
        $current_tab = empty($_GET['tab']) ? 'pdf_downloads' : sanitize_title(wp_unslash($_GET['tab'])); // WPCS: input var okay, CSRF ok.
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
    }


}
