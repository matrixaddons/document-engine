<?php

namespace MatrixAddons\DocumentEngine;

use MatrixAddons\DocumentEngine\Library\Query;

class Assets
{
    public static function init()
    {
        $self = new self();
        add_action('init', [$self, 'register_assets']);
        add_action('enqueue_block_editor_assets', [$self, 'editor_assets']);
    }

    private static function asset($name)
    {
        $file = DOCUMENT_ENGINE_ASSETS_DIR_PATH . 'build/' . $name . '.asset.php';
        $asset = file_exists($file) ? include $file : array();
        return array(
            'dependencies' => isset($asset['dependencies']) ? $asset['dependencies'] : array(),
            'version' => isset($asset['version']) ? $asset['version'] : DOCUMENT_ENGINE_VERSION,
        );
    }

    public function register_assets()
    {
        wp_register_style(
            'document-engine-font-awesome', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'vendor/font-awesome/css/fontawesome.min.css',
            array(),
            DOCUMENT_ENGINE_VERSION
        );

        // 1.x "Download PDF" button styles. Enqueued only where the button renders.
        wp_register_style(
            'document-engine-frontend', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'css/frontend.css',
            array('document-engine-font-awesome'),
            DOCUMENT_ENGINE_VERSION
        );
        wp_style_add_data('document-engine-frontend', 'rtl', 'replace');

        $documents = self::asset('documents');
        wp_register_style('document-engine-documents', DOCUMENT_ENGINE_ASSETS_URI . 'build/documents.css', array(), $documents['version']);
        wp_style_add_data('document-engine-documents', 'rtl', 'replace');

        $viewer = self::asset('viewer');
        wp_register_script('document-engine-viewer', DOCUMENT_ENGINE_ASSETS_URI . 'build/viewer.js', $viewer['dependencies'], $viewer['version'], array('in_footer' => true, 'strategy' => 'defer'));
        $pdfjs = DOCUMENT_ENGINE_ASSETS_URI . 'vendor/pdfjs/';
        wp_localize_script('document-engine-viewer', 'DocumentEngineViewer', apply_filters('document_engine_viewer_script_data', array(
            'lib' => $pdfjs . 'pdf.min.js?ver=' . rawurlencode($viewer['version']),
            'worker' => $pdfjs . 'pdf.worker.min.js?ver=' . rawurlencode($viewer['version']),
            'cMapUrl' => $pdfjs . 'cmaps/',
            'standardFontDataUrl' => $pdfjs . 'standard_fonts/',
            'wasmUrl' => $pdfjs . 'wasm/',
            'i18n' => array(
                'password' => __('This PDF is password protected. Enter the password to open it.', 'document-engine'),
                'wrongPassword' => __('That password is not correct. Please try again.', 'document-engine'),
                /* translators: 1: page number, 2: number of pages */
                'pageOf' => __('Page %1$d of %2$d', 'document-engine'),
                /* translators: 1: current match, 2: number of matches */
                'matchOf' => __('%1$d of %2$d', 'document-engine'),
                'noMatches' => __('No matches', 'document-engine'),
                'searching' => __('Searching…', 'document-engine'),
                /* translators: %d: page number */
                'pageN' => __('Page %d', 'document-engine'),
            ),
        )));

        $library = self::asset('library');
        wp_register_script('document-engine-library', DOCUMENT_ENGINE_ASSETS_URI . 'build/library.js', $library['dependencies'], $library['version'], array('in_footer' => true, 'strategy' => 'defer'));
        wp_localize_script('document-engine-library', 'DocumentEngineLibrary', array(
            'rest' => rest_url('document-engine/v1/library'),
            // Only logged-in visitors need a nonce (so REST sees who they are); cached public pages stay nonce-free.
            'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'preview' => rest_url('document-engine/v1/preview/'),
            'i18n' => array(
                'reset' => __('Reset', 'document-engine'),
                'close' => __('Close preview', 'document-engine'),
                'openPage' => __('Open document page', 'document-engine'),
                'download' => __('Download', 'document-engine'),
                'loading' => __('Loading preview…', 'document-engine'),
                'failed' => __('This preview could not be loaded.', 'document-engine'),
            ),
        ));

        $pdf_block_dependencies = self::asset('blocks.min');

        $localize_data = array();

        $localize_data['all_pdf_types'] = [
            ['label' => __('URL', 'document-engine'), 'value' => 'url'],
            ['label' => __('File', 'document-engine'), 'value' => 'file']
        ];
        $localize_data['all_units'] = [
            ['label' => __('Pixel', 'document-engine'), 'value' => 'px'],
            ['label' => __('Percentage', 'document-engine'), 'value' => '%']
        ];

        wp_register_script(
            'document-engine-pdf-block', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'build/blocks.min.js',
            $pdf_block_dependencies['dependencies'],
            $pdf_block_dependencies['version']
        );
        wp_localize_script('document-engine-pdf-block', 'DocumentEnginePDFViewer', $localize_data);
        wp_set_script_translations('document-engine-pdf-block', 'document-engine', DOCUMENT_ENGINE_ABSPATH . 'languages');

        wp_register_style('document-engine-blocks-editor', DOCUMENT_ENGINE_ASSETS_URI . 'build/blocks.min.css', array('document-engine-documents'), $pdf_block_dependencies['version']);
        wp_style_add_data('document-engine-blocks-editor', 'rtl', 'replace');
    }

    public function editor_assets()
    {
        wp_localize_script('document-engine-pdf-block', 'DocumentEngineBlocks', array(
            'columns' => Query::columns(),
            'layouts' => Query::layouts(),
            'sortOptions' => Query::sort_options(),
            'filters' => Query::filter_labels(),
            'fileTypes' => document_engine_file_type_groups_labels(),
            'newDocumentUrl' => current_user_can('edit_dengine_documents') ? admin_url('post-new.php?post_type=' . Documents\PostType::POST_TYPE) : '',
            'pdfButtonText' => document_engine_pdf_button_text(),
            'pdfButtonAlignment' => document_engine_pdf_button_alignment(),
            // T9: one muted line in the library's filter settings (administrators, without Pro).
            'proFieldsUrl' => \MatrixAddons\DocumentEngine\Admin\Nudges::can_show('t9-fields', false) ? \MatrixAddons\DocumentEngine\Admin\ProPage::url() : '',
        ));

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === Documents\PostType::POST_TYPE) {
            $editor = self::asset('document-editor');
            wp_enqueue_script('document-engine-document-editor', DOCUMENT_ENGINE_ASSETS_URI . 'build/document-editor.js', $editor['dependencies'], $editor['version'], true);
            wp_enqueue_style('document-engine-document-editor', DOCUMENT_ENGINE_ASSETS_URI . 'build/document-editor.css', array('wp-components'), $editor['version']);
            wp_style_add_data('document-engine-document-editor', 'rtl', 'replace');
            wp_localize_script('document-engine-document-editor', 'DocumentEngineEditor', array(
                'defaultBehavior' => get_option('document_engine_link_behavior', 'download'),
                'isPro' => defined('DOCUMENT_ENGINE_PRO_FILE'),
            ));
            wp_set_script_translations('document-engine-document-editor', 'document-engine', DOCUMENT_ENGINE_ABSPATH . 'languages');
        }
    }
}
