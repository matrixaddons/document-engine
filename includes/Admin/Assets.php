<?php

namespace MatrixAddons\DocumentEngine\Admin;

class Assets
{
    public static function init()
    {
        $self = new self();

        add_action('admin_enqueue_scripts', array($self, 'admin_assets'), 10, 1);
    }

    public function admin_assets()
    {
        $screen = get_current_screen();

        $screen_id = $screen->id ?? '';

        if ($screen_id !== 'toplevel_page_document-engine-settings') {
            return;
        }

        wp_enqueue_media();
        
        wp_register_style(
            'document-engine-admin-settings', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'admin/css/settings.css',
            array(),
            DOCUMENT_ENGINE_VERSION
        );

        wp_register_script(
            'document-engine-ace', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'vendor/ace/js/ace.js',
            array(),
            DOCUMENT_ENGINE_VERSION
        );
        wp_register_script(
            'document-engine-admin-settings', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'admin/js/settings.js',
            array('jquery', 'document-engine-ace'),
            DOCUMENT_ENGINE_VERSION
        );
        wp_enqueue_script('document-engine-admin-settings');
        wp_enqueue_style('document-engine-admin-settings');
    }
}
