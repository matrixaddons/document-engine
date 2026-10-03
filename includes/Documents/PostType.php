<?php

namespace MatrixAddons\DocumentEngine\Documents;

defined('ABSPATH') || exit;

/**
 * The document post type and its taxonomies.
 */
class PostType
{
    const POST_TYPE = 'dengine_document';
    const CATEGORY = 'dengine_category';
    const TAG = 'dengine_tag';

    public static function init()
    {
        add_action('init', array(__CLASS__, 'register'), 5);
        add_action('init', array(__CLASS__, 'register_meta'), 6);
        add_action('init', array(__CLASS__, 'maybe_flush'), 99);
        add_action('rest_api_init', array(__CLASS__, 'register_file_name'));
        add_action('init', array(__CLASS__, 'register_template'), 20);
        add_filter('the_content', array(__CLASS__, 'single_content'), 20);
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'flush_library_cache'));
        add_action('deleted_post', array(__CLASS__, 'flush_library_cache'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_single'));
        add_filter('rest_prepare_' . self::POST_TYPE, array(__CLASS__, 'rest_hide_meta'), 20, 2);
        add_filter('add_post_metadata', array(__CLASS__, 'guard_file_meta'), 10, 4);
        add_filter('update_post_metadata', array(__CLASS__, 'guard_file_meta'), 10, 4);
        add_filter('post_type_link', array(__CLASS__, 'external_permalink'), 10, 2);
        add_filter('the_excerpt', array(__CLASS__, 'search_excerpt'));
        add_filter('get_the_excerpt', array(__CLASS__, 'search_plain_excerpt'), 20, 2);
    }

    /**
     * Block themes: a template for document pages, so they don't use the blog post template (author
     * byline, post categories, "More posts"). The theme's own single-dengine_document template, or
     * one edited in the Site Editor, still wins.
     */
    public static function register_template()
    {
        if (!function_exists('register_block_template') || !apply_filters('document_engine_register_block_template', true)) {
            return;
        }
        register_block_template('document-engine//single-' . self::POST_TYPE, array(
            'title' => __('Single Document', 'document-engine'),
            'description' => __('Displays a single document: its title, details, viewer and download button.', 'document-engine'),
            'post_types' => array(self::POST_TYPE),
            'content' => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
                . '<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} --><main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:var(--wp--preset--spacing--60)">'
                . '<!-- wp:post-title {"level":1} /-->'
                . '<!-- wp:post-content {"layout":{"type":"constrained"}} /-->'
                . '<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} --><div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)">'
                . '<!-- wp:post-navigation-link {"type":"previous","showTitle":true,"arrow":"arrow"} /-->'
                . '<!-- wp:post-navigation-link {"showTitle":true,"arrow":"arrow"} /-->'
                . '</div><!-- /wp:group -->'
                . '</main><!-- /wp:group -->'
                . '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
        ));
    }

    /**
     * The real file name of an attachment, for the document editor: protected files' URLs are the
     * download link, so the name can't be read from them. Only for people who may edit the file.
     */
    public static function register_file_name()
    {
        register_rest_field('attachment', 'dengine_file_name', array(
            'get_callback' => function ($item) {
                $id = isset($item['id']) ? (int)$item['id'] : 0;
                if (!$id || !current_user_can('edit_post', $id)) {
                    return '';
                }
                $file = get_attached_file($id);
                return $file ? wp_basename($file) : '';
            },
            'schema' => array('type' => 'string', 'context' => array('view', 'edit'), 'readonly' => true),
        ));
    }

    public static function register()
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }

        $slug = sanitize_title(get_option('document_engine_documents_slug', 'documents'));
        $slug = $slug !== '' ? $slug : 'documents';

        register_post_type(self::POST_TYPE, apply_filters('document_engine_post_type_args', array(
            'labels' => array(
                'name' => __('Documents', 'document-engine'),
                'singular_name' => __('Document', 'document-engine'),
                'menu_name' => __('Documents', 'document-engine'),
                'all_items' => __('All Documents', 'document-engine'),
                'add_new' => __('Add New', 'document-engine'),
                'add_new_item' => __('Add New Document', 'document-engine'),
                'edit_item' => __('Edit Document', 'document-engine'),
                'new_item' => __('New Document', 'document-engine'),
                'view_item' => __('View Document', 'document-engine'),
                'view_items' => __('View Documents', 'document-engine'),
                'search_items' => __('Search Documents', 'document-engine'),
                'not_found' => __('No documents found.', 'document-engine'),
                'not_found_in_trash' => __('No documents found in Trash.', 'document-engine'),
                'featured_image' => __('Thumbnail', 'document-engine'),
                'set_featured_image' => __('Set thumbnail', 'document-engine'),
                'remove_featured_image' => __('Remove thumbnail', 'document-engine'),
                'use_featured_image' => __('Use as thumbnail', 'document-engine'),
                'item_published' => __('Document published.', 'document-engine'),
                'item_updated' => __('Document updated.', 'document-engine'),
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 25,
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M5 1h7l5 5v11.5A1.5 1.5 0 0 1 15.5 19h-10A1.5 1.5 0 0 1 4 17.5v-15A1.5 1.5 0 0 1 5.5 1H5zm7 1.5V6h3.5L12 2.5zM6.5 9v1.5h7V9h-7zm0 3v1.5h7V12h-7zm0 3v1.5h4.5V15H6.5z"/></svg>'),
            'exclude_from_search' => get_option('document_engine_documents_in_search', 'yes') !== 'yes',
            'has_archive' => false,
            'hierarchical' => false,
            'rewrite' => array('slug' => $slug, 'with_front' => false),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields', 'revisions'),
            'taxonomies' => array(self::CATEGORY, self::TAG),
            'capability_type' => array('dengine_document', 'dengine_documents'),
            'map_meta_cap' => true,
            'template' => array(array('core/paragraph', array('placeholder' => __('Optional description shown on the document page and in library grids…', 'document-engine')))),
        )));

        register_taxonomy(self::CATEGORY, self::POST_TYPE, apply_filters('document_engine_category_args', array(
            'labels' => array(
                'name' => __('Document Categories', 'document-engine'),
                'singular_name' => __('Document Category', 'document-engine'),
                'menu_name' => __('Categories', 'document-engine'),
                'all_items' => __('All Categories', 'document-engine'),
                'edit_item' => __('Edit Category', 'document-engine'),
                'add_new_item' => __('Add New Category', 'document-engine'),
                'parent_item' => __('Parent Category', 'document-engine'),
                'search_items' => __('Search Categories', 'document-engine'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'capabilities' => array('manage_terms' => 'manage_dengine_categories', 'edit_terms' => 'manage_dengine_categories', 'delete_terms' => 'manage_dengine_categories', 'assign_terms' => 'edit_dengine_documents'),
            'rewrite' => array('slug' => $slug . '/category', 'with_front' => false, 'hierarchical' => true),
        )));

        register_taxonomy(self::TAG, self::POST_TYPE, apply_filters('document_engine_tag_args', array(
            'labels' => array(
                'name' => __('Document Tags', 'document-engine'),
                'singular_name' => __('Document Tag', 'document-engine'),
                'menu_name' => __('Tags', 'document-engine'),
                'all_items' => __('All Tags', 'document-engine'),
                'edit_item' => __('Edit Tag', 'document-engine'),
                'add_new_item' => __('Add New Tag', 'document-engine'),
                'search_items' => __('Search Tags', 'document-engine'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'capabilities' => array('manage_terms' => 'manage_dengine_categories', 'edit_terms' => 'manage_dengine_categories', 'delete_terms' => 'manage_dengine_categories', 'assign_terms' => 'edit_dengine_documents'),
            'rewrite' => array('slug' => $slug . '/tag', 'with_front' => false),
        )));
    }

    public static function register_meta()
    {
        $auth = function ($allowed, $meta_key, $post_id) {
            return current_user_can('edit_post', $post_id);
        };

        $meta = array(
            Document::META_FILE_ID => array('type' => 'integer', 'default' => 0),
            Document::META_FILE_URL => array('type' => 'string', 'default' => ''),
            Document::META_BEHAVIOR => array('type' => 'string', 'default' => ''),
            Document::META_FILE_TYPE => array('type' => 'string', 'default' => ''),
            Document::META_FILE_SIZE => array('type' => 'integer', 'default' => 0),
            Document::META_DOWNLOADS => array('type' => 'integer', 'default' => 0),
        );

        foreach ($meta as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, array(
                'type' => $args['type'],
                'default' => $args['default'],
                'single' => true,
                'show_in_rest' => $key !== Document::META_DOWNLOADS,
                'auth_callback' => $auth,
                'sanitize_callback' => $args['type'] === 'integer' ? 'absint' : ($key === Document::META_FILE_URL ? 'esc_url_raw' : 'sanitize_key'),
            ));
        }
    }

    /**
     * File locations and access settings are only shown to people who can edit the document.
     */
    public static function rest_hide_meta($response, $post)
    {
        if (current_user_can('edit_post', $post->ID)) {
            return $response;
        }
        $data = $response->get_data();
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach (array_keys($data['meta']) as $key) {
                if (strpos($key, '_dengine_') === 0) {
                    unset($data['meta'][$key]);
                }
            }
            $response->set_data($data);
        }
        return $response;
    }

    /**
     * A document may only point at a Media Library file the current user may edit
     * (stops authors attaching someone else's private file to their own document).
     */
    public static function guard_file_meta($check, $object_id, $meta_key, $meta_value)
    {
        if ($meta_key !== Document::META_FILE_ID || !is_user_logged_in() || wp_doing_cron()) {
            return $check;
        }
        $value = absint($meta_value);
        if ($value < 1 || get_post_type($object_id) !== self::POST_TYPE) {
            return $check;
        }
        $allowed = get_post_type($value) === 'attachment' && current_user_can('edit_post', $value);
        $allowed = (bool)apply_filters('document_engine_can_use_attachment', $allowed, $value, $object_id);
        return $allowed ? $check : false;
    }

    /**
     * Document pages always need the styles (loaded in the head, not late in the footer).
     */
    public static function enqueue_single()
    {
        if (is_singular(self::POST_TYPE)) {
            wp_enqueue_style('document-engine-documents');
        }
    }

    public static function maybe_flush()
    {
        if (get_option('document_engine_flush_rewrite') === 'yes' || get_option('document_engine_queue_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules(false);
            delete_option('document_engine_flush_rewrite');
            delete_option('document_engine_queue_flush_rewrite_rules');
        }
    }

    /**
     * Adds the document card (viewer / download) to single document pages.
     */
    public static function flush_library_cache()
    {
        delete_transient('dengine_library_years');
        delete_transient('dengine_library_types');
    }

    public static function single_content($content)
    {
        // Block themes render post content outside "the loop", so match the queried document instead.
        static $rendered = array();
        $id = (int)get_the_ID();
        if (!is_singular(self::POST_TYPE) || $id !== (int)get_queried_object_id() || isset($rendered[$id]) || doing_filter('get_the_excerpt') || doing_filter('wp_trim_excerpt') || doing_action('wp_head')) {
            return $content;
        }
        $rendered[$id] = true;
        $document = Document::get(get_the_ID());

        if (!$document) {
            return $content;
        }

        ob_start();
        document_engine_get_template('document/single.php', array('document' => $document));
        $card = ob_get_clean();

        return apply_filters('document_engine_single_content', $card . $content, $card, $content, $document);
    }

    /**
     * Documents without single pages link straight to the file.
     */
    public static function external_permalink($url, $post)
    {
        if ($post->post_type !== self::POST_TYPE || is_admin() || get_option('document_engine_single_pages', 'yes') === 'yes') {
            return $url;
        }
        $document = Document::get($post);
        return $document ? $document->get_download_url() : $url;
    }

    /**
     * Themes that print get_the_excerpt() get the file facts appended in search results.
     */
    public static function search_plain_excerpt($excerpt, $post = null)
    {
        $post = get_post($post);
        if (!$post || !is_search() || is_admin() || $post->post_type !== self::POST_TYPE || doing_filter('the_excerpt')) {
            return $excerpt;
        }
        $document = Document::get($post);
        if (!$document) {
            return $excerpt;
        }
        $meta = trim($document->get_type_label() . ' · ' . $document->get_size_label(), ' ·');
        return trim($excerpt) . ($meta ? ' — ' . $meta : '');
    }

    /**
     * Search results show the file type and size under the excerpt.
     */
    public static function search_excerpt($excerpt)
    {
        if (!is_search() || get_post_type() !== self::POST_TYPE || strpos($excerpt, ' — ') !== false) {
            return $excerpt;
        }
        $document = Document::get(get_the_ID());
        if (!$document) {
            return $excerpt;
        }
        $meta = trim($document->get_type_label() . ' · ' . $document->get_size_label(), ' ·');
        return $excerpt . ($meta ? '<p class="dengine-search-meta">' . esc_html($meta) . '</p>' : '');
    }
}
