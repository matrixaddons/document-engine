<?php

namespace MatrixAddons\DocumentEngine;

class Blocks
{
    private static function get_instance()
    {
        return new self();
    }

    public static function init()
    {
        $self = self::get_instance();

        add_filter('block_categories_all', array($self, 'register_category'), 10, 2);

        add_action('init', [$self, 'register_block']);

    }

    public function register_category($categories, $context)
    {
        array_push(
            $categories,
            array(
                'slug' => 'document-engine',
                'title' => __('Document Engine', 'document-engine'),
            )
        );
        return $categories;
    }

    public function register_block()
    {
        register_block_type(
            'document-engine/pdf',
            array(
                'api_version' => 2,

                'editor_script' => 'document-engine-pdf-block',

                'attributes' => $this->attributes(),

                'render_callback' => 'document_engine_pdf_view_callback',

            )
        );
    }

    public function attributes()
    {
        return array(

            'pdf_type' => array(
                'type' => 'string',
                'default' => "url",
            ),
            'pdf_url' => array(
                'type' => 'string',
                'default' => "http://www.pdf995.com/samples/pdf.pdf",
            ),
            'pdf_id' => array(
                'type' => 'number',
                'default' => 0
            ),
            'width_size' => array(
                'type' => 'number',
                'default' => 100,
            ),
            'width_unit' => array(
                'type' => 'string',
                'default' => '%',
            ),
            'height_size' => array(
                'type' => 'number',
                'default' => 1000,
            ),
            'height_unit' => array(
                'type' => 'string',
                'default' => 'px',
            ),

        );

    }
}

