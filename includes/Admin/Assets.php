<?php

namespace MatrixAddons\DocumentEngine\Admin;

class Assets
{
    public static function init()
    {
        $self = new self();

        add_action('admin_enqueue_scripts', array($self, 'admin_assets'), 10, 1);
    }

    /**
     * Screens that belong to the product (list, editor, settings, add-on pages).
     */
    public static function is_product_screen($screen_id)
    {
        return in_array($screen_id, array('edit-dengine_document', 'dengine_document', 'edit-dengine_category', 'edit-dengine_tag', 'edit-dengine_request'), true)
            || strpos((string)$screen_id, 'dengine_document_page_') === 0
            || $screen_id === 'toplevel_page_document-engine-settings';
    }

    public function admin_assets()
    {
        $this->commands();
        $screen = get_current_screen();

        $screen_id = $screen->id ?? '';

        if (self::is_product_screen($screen_id)) {
            UI::enqueue();
        }

        if (!in_array($screen_id, array('dengine_document_page_document-engine-settings', 'toplevel_page_document-engine-settings'), true)) {
            return;
        }

        wp_enqueue_media();
        $code_editor = wp_enqueue_code_editor(array('type' => 'text/css', 'codemirror' => array('lineNumbers' => true, 'lineWrapping' => true)));

        wp_register_script(
            'document-engine-admin-settings', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'admin/js/settings.js',
            array('jquery'),
            DOCUMENT_ENGINE_VERSION,
            true
        );
        wp_localize_script('document-engine-admin-settings', 'DocumentEngineSettings', array(
            'codeEditor' => $code_editor ?: null,
            'choose' => __('Choose image', 'document-engine'),
            'replace' => __('Replace image', 'document-engine'),
            'unsaved' => __('You have unsaved changes', 'document-engine'),
            'saving' => __('Saving…', 'document-engine'),
            'saved' => __('All changes saved', 'document-engine'),
        ));
        wp_enqueue_script('document-engine-admin-settings');
    }

    /**
     * Command palette entries (Cmd/Ctrl + K) on every admin screen for people who manage documents.
     */
    private function commands()
    {
        if (!current_user_can('edit_dengine_documents') || !wp_script_is('wp-commands', 'registered')) {
            return;
        }
        $file = DOCUMENT_ENGINE_ABSPATH . 'assets/build/commands.asset.php';
        if (!file_exists($file)) {
            return;
        }
        $asset = include $file;
        wp_enqueue_script('document-engine-commands', DOCUMENT_ENGINE_ASSETS_URI . 'build/commands.js', $asset['dependencies'], $asset['version'], true);
        $type = \MatrixAddons\DocumentEngine\Documents\PostType::POST_TYPE;
        wp_localize_script('document-engine-commands', 'DocumentEngineCommands', array(
            'newUrl' => admin_url('post-new.php?post_type=' . $type),
            'listUrl' => admin_url('edit.php?post_type=' . $type),
            'dashboardUrl' => admin_url('edit.php?post_type=' . $type . '&page=dengine-dashboard'),
            'settingsUrl' => admin_url('admin.php?page=document-engine-settings'),
            'postUrl' => admin_url('post.php'),
            'extra' => apply_filters('document_engine_commands', array()),
        ));
        wp_set_script_translations('document-engine-commands', 'document-engine', DOCUMENT_ENGINE_ABSPATH . 'languages');
    }
}
