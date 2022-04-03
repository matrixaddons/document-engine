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
        wp_enqueue_style('document-engine-frontend');


    }
}
