<?php

namespace MatrixAddons\DocumentEngine;

use MatrixAddons\DocumentEngine\Install\Upgrader;
use MatrixAddons\DocumentEngine\Library\Library;
use MatrixAddons\DocumentEngine\Library\Lists;
use MatrixAddons\DocumentEngine\Viewer\Viewer;

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
        foreach ($categories as $category) {
            if (isset($category['slug']) && $category['slug'] === 'document-engine') {
                return $categories;
            }
        }
        array_push(
            $categories,
            array(
                'slug' => 'document-engine',
                'title' => DOCUMENT_ENGINE_BRAND,
            )
        );
        return $categories;
    }

    public function register_block()
    {
        // Titles, descriptions and icons live in assets/blocks/*/block.json (also read by WordPress.org);
        // attributes and rendering stay here so existing content is unchanged.
        // 1.x block: kept with identical attributes so existing content keeps rendering.
        register_block_type(
            DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/pdf',
            array(
                'api_version' => 3,

                'editor_script' => 'document-engine-pdf-block',

                'editor_style' => 'document-engine-blocks-editor',

                'attributes' => $this->attributes(),

                'render_callback' => 'document_engine_pdf_view_callback',

            )
        );

        register_block_type(DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/viewer', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'source' => array('type' => 'string', 'default' => 'media'),
                'documentId' => array('type' => 'number', 'default' => 0),
                'fileId' => array('type' => 'number', 'default' => 0),
                'url' => array('type' => 'string', 'default' => ''),
                'height' => array('type' => 'string', 'default' => ''),
                'zoom' => array('type' => 'string', 'default' => ''),
                'page' => array('type' => 'number', 'default' => 1),
                'toolbar' => array('type' => 'string', 'default' => ''),
                'download' => array('type' => 'string', 'default' => ''),
                'print' => array('type' => 'string', 'default' => ''),
                'fullscreen' => array('type' => 'string', 'default' => ''),
                'align' => array('type' => 'string', 'default' => ''),
                'className' => array('type' => 'string', 'default' => ''),
            ),
            'supports' => array('align' => array('wide', 'full'), 'html' => false),
            'render_callback' => array($this, 'render_viewer'),
        ));

        register_block_type(DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/library', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'libraryId' => array('type' => 'string', 'default' => ''),
                'layout' => array('type' => 'string', 'default' => get_option('document_engine_library_layout', 'table')),
                'categories' => array('type' => 'array', 'default' => array(), 'items' => array('type' => 'string')),
                'tags' => array('type' => 'array', 'default' => array(), 'items' => array('type' => 'string')),
                'fileTypes' => array('type' => 'array', 'default' => array(), 'items' => array('type' => 'string')),
                'perPage' => array('type' => 'number', 'default' => absint(get_option('document_engine_library_per_page', 20)) ?: 20),
                'orderby' => array('type' => 'string', 'default' => 'date'),
                'order' => array('type' => 'string', 'default' => 'desc'),
                'columns' => array('type' => 'array', 'default' => array('title', 'category', 'type', 'size', 'date', 'actions'), 'items' => array('type' => 'string')),
                'gridColumns' => array('type' => 'number', 'default' => 3),
                'search' => array('type' => 'boolean', 'default' => true),
                'filters' => array('type' => 'array', 'default' => array('category', 'type'), 'items' => array('type' => 'string')),
                'showThumbnails' => array('type' => 'boolean', 'default' => true),
                'showExcerpt' => array('type' => 'boolean', 'default' => true),
                'linkTo' => array('type' => 'string', 'default' => 'document'),
                'pagination' => array('type' => 'boolean', 'default' => true),
                'paginationStyle' => array('type' => 'string', 'default' => 'numbers'),
                'sortable' => array('type' => 'boolean', 'default' => true),
                'openFolders' => array('type' => 'boolean', 'default' => false),
                'multiFilters' => array('type' => 'boolean', 'default' => false),
                'align' => array('type' => 'string', 'default' => ''),
                'className' => array('type' => 'string', 'default' => ''),
            ),
            'supports' => array('align' => array('wide', 'full'), 'html' => false),
            'render_callback' => array($this, 'render_library'),
        ));

        register_block_type(DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/download', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'documentId' => array('type' => 'number', 'default' => 0),
                'variant' => array('type' => 'string', 'default' => 'card'),
                'label' => array('type' => 'string', 'default' => ''),
                'showMeta' => array('type' => 'boolean', 'default' => true),
                'className' => array('type' => 'string', 'default' => ''),
            ),
            'render_callback' => array(Library::class, 'render_download'),
        ));

        register_block_type(DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/documents', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'mode' => array('type' => 'string', 'default' => 'recent'),
                'count' => array('type' => 'number', 'default' => 5),
                'categories' => array('type' => 'array', 'default' => array(), 'items' => array('type' => 'string')),
                'title' => array('type' => 'string', 'default' => ''),
                'showMeta' => array('type' => 'boolean', 'default' => true),
                'documentId' => array('type' => 'number', 'default' => 0),
                'className' => array('type' => 'string', 'default' => ''),
            ),
            'supports' => array('html' => false),
            'render_callback' => array(Lists::class, 'render'),
        ));

        register_block_type(DOCUMENT_ENGINE_ABSPATH . 'assets/blocks/pdf-button', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'text' => array('type' => 'string', 'default' => ''),
                'alignment' => array('type' => 'string', 'default' => ''),
            ),
            'render_callback' => array($this, 'render_pdf_button'),
        ));
    }

    public function render_viewer($attributes)
    {
        $source = isset($attributes['source']) ? $attributes['source'] : 'media';
        $tri = function ($value) {
            return $value === 'yes' ? true : ($value === 'no' ? false : null);
        };
        return Viewer::render(array(
            'documentId' => $source === 'document' ? absint($attributes['documentId']) : 0,
            'fileId' => $source === 'media' ? absint($attributes['fileId']) : 0,
            'url' => $source === 'url' ? (string)$attributes['url'] : '',
            'height' => (string)$attributes['height'],
            'zoom' => (string)$attributes['zoom'],
            'page' => absint($attributes['page']),
            'toolbar' => $tri($attributes['toolbar']),
            'download' => $tri($attributes['download']),
            'print' => $tri($attributes['print']),
            'fullscreen' => $tri($attributes['fullscreen']),
            'align' => (string)$attributes['align'],
            'className' => (string)$attributes['className'],
        ));
    }

    public function render_library($attributes)
    {
        $atts = array(
            'layout' => $attributes['layout'],
            'categories' => $attributes['categories'],
            'tags' => $attributes['tags'],
            'file_types' => $attributes['fileTypes'],
            'per_page' => $attributes['perPage'],
            'orderby' => $attributes['orderby'],
            'order' => $attributes['order'],
            'columns' => $attributes['columns'],
            'grid_columns' => $attributes['gridColumns'],
            'search' => $attributes['search'],
            'filters' => $attributes['filters'],
            'show_thumbnails' => $attributes['showThumbnails'],
            'show_excerpt' => $attributes['showExcerpt'],
            'link_to' => $attributes['linkTo'],
            'pagination' => $attributes['pagination'],
            'pagination_style' => $attributes['paginationStyle'],
            'sortable' => $attributes['sortable'],
            'open_folders' => $attributes['openFolders'],
            'multi_filters' => !empty($attributes['multiFilters']),
            'class' => trim(($attributes['align'] ? 'align' . $attributes['align'] : '') . ' ' . $attributes['className']),
        );
        if ($attributes['libraryId'] !== '') {
            $atts['id'] = $attributes['libraryId'];
        }
        return Library::render($atts);
    }

    public function render_pdf_button($attributes)
    {
        if (!is_singular()) {
            return '';
        }
        $atts = array();
        if (!empty($attributes['text'])) {
            $atts['text'] = $attributes['text'];
        }
        if (!empty($attributes['alignment'])) {
            $atts['alignment'] = $attributes['alignment'];
        }
        return Shortcodes::pdf_button($atts);
    }

    /**
     * Whether a 1.x PDF block should use the built-in viewer instead of Google Docs.
     */
    public static function legacy_uses_builtin_viewer()
    {
        $default = Upgrader::is_legacy_site() ? 'no' : 'yes';
        return get_option('document_engine_viewer_legacy_builtin', $default) === 'yes';
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
