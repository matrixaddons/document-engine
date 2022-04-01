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


    }

    function admin_menu()
    {
        $settings_page = add_menu_page('Document Engine', 'Document Engine', 'manage_options', 'document-engine-settings', array($this, 'settings'));

        add_action('load-' . $settings_page, array($this, 'settings_page_init'));

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
        $current_tab = empty($_GET['tab']) ? 'pdf' : sanitize_title(wp_unslash($_GET['tab'])); // WPCS: input var okay, CSRF ok.
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


    }


}
