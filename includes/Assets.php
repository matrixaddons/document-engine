<?php

namespace MatrixAddons\DocumentEngine;

class Assets
{
    public static function init()
    {
        $self = new self();
        add_action('init', [$self, 'register_assets']);
    }

    public function register_assets()
    {
        wp_register_style(
            'document-engine-font-awesome', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'vendor/font-awesome/css/fontawesome.min.css',
            array(),
            DOCUMENT_ENGINE_VERSION
        );

        wp_register_style(
            'document-engine-frontend', // Handle.
            DOCUMENT_ENGINE_ASSETS_URI . 'css/frontend.css',
            array('document-engine-font-awesome'),
            DOCUMENT_ENGINE_VERSION
        );

        $pdf_block_dependencies = include_once DOCUMENT_ENGINE_ASSETS_DIR_PATH . 'build/blocks.min.asset.php';

        $js_dependencies = $pdf_block_dependencies['dependencies'] ?? array();
        $block_version = $pdf_block_dependencies['version'] ?? DOCUMENT_ENGINE_VERSION;

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
            $js_dependencies,
            $block_version
        );
        wp_localize_script('document-engine-pdf-block', 'DocumentEnginePDFViewer', $localize_data);
        wp_enqueue_style('document-engine-frontend');


    }
}
