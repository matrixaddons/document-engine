<?php

namespace MatrixAddons\DocumentEngine\Integrations;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * WordPress Abilities API (6.9+): lets AI assistants and agents (e.g. through the MCP adapter)
 * search and read the document library. Every result respects the asking user's access.
 */
class Abilities
{
    public static function init()
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }
        add_action('wp_abilities_api_categories_init', array(__CLASS__, 'category'));
        add_action('wp_abilities_api_init', array(__CLASS__, 'register'));
    }

    public static function category()
    {
        if (!function_exists('wp_register_ability_category')) {
            return;
        }
        wp_register_ability_category('document-engine', array(
            'label' => DOCUMENT_ENGINE_BRAND,
            'description' => __('Search and read documents in the document library.', 'document-engine'),
        ));
    }

    public static function register()
    {
        $document_schema = array(
            'type' => 'object',
            'properties' => array(
                'id' => array('type' => 'integer'),
                'title' => array('type' => 'string'),
                'url' => array('type' => 'string', 'description' => 'Document page.'),
                'download_url' => array('type' => 'string'),
                'type' => array('type' => 'string', 'description' => 'File extension, e.g. pdf.'),
                'size' => array('type' => 'string'),
                'updated' => array('type' => 'string', 'description' => 'ISO 8601 date.'),
                'summary' => array('type' => 'string'),
                'categories' => array('type' => 'array', 'items' => array('type' => 'string')),
            ),
        );

        wp_register_ability('document-engine/search-documents', array(
            'label' => __('Search documents', 'document-engine'),
            'description' => __('Finds documents in the library by words in their title, description (and file text with Pro), optionally in one category. Only documents the current user may open are returned.', 'document-engine'),
            'category' => 'document-engine',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'query' => array('type' => 'string', 'description' => 'Words to search for.'),
                    'category' => array('type' => 'string', 'description' => 'Optional category slug.'),
                    'limit' => array('type' => 'integer', 'minimum' => 1, 'maximum' => 25, 'default' => 10),
                ),
                'required' => array('query'),
            ),
            'output_schema' => array('type' => 'array', 'items' => $document_schema),
            'execute_callback' => array(__CLASS__, 'search'),
            'permission_callback' => '__return_true',
            'meta' => array('annotations' => array('readonly' => true, 'destructive' => false, 'idempotent' => true), 'public' => true),
        ));

        wp_register_ability('document-engine/get-document', array(
            'label' => __('Get a document', 'document-engine'),
            'description' => __('Returns the details of one document (title, links, file type and size, categories, description), if the current user may open it.', 'document-engine'),
            'category' => 'document-engine',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array('id' => array('type' => 'integer', 'description' => 'Document ID.')),
                'required' => array('id'),
            ),
            'output_schema' => $document_schema,
            'execute_callback' => array(__CLASS__, 'get'),
            'permission_callback' => '__return_true',
            'meta' => array('annotations' => array('readonly' => true, 'destructive' => false, 'idempotent' => true), 'public' => true),
        ));

        do_action('document_engine_register_abilities');
    }

    public static function describe(Document $document)
    {
        $terms = get_the_terms($document->get_post(), PostType::CATEGORY);
        $data = array(
            'id' => $document->get_id(),
            'title' => $document->get_title(),
            'url' => get_permalink($document->get_post()),
            'download_url' => $document->get_download_url(),
            'type' => $document->get_extension(),
            'size' => $document->get_size_label(),
            'updated' => get_post_modified_time('c', true, $document->get_post()),
            'summary' => wp_strip_all_tags(get_the_excerpt($document->get_post())),
            'categories' => is_array($terms) ? wp_list_pluck($terms, 'name') : array(),
        );
        return apply_filters('document_engine_ability_document', $data, $document);
    }

    public static function search($input)
    {
        $input = is_array($input) ? $input : array();
        $args = array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => 'publish',
            's' => sanitize_text_field(isset($input['query']) ? (string)$input['query'] : ''),
            'posts_per_page' => max(1, min(25, isset($input['limit']) ? (int)$input['limit'] : 10)) + 10,
            'has_password' => false,
            'suppress_filters' => false,
        );
        if (!empty($input['category'])) {
            $args['tax_query'] = array(array('taxonomy' => PostType::CATEGORY, 'field' => 'slug', 'terms' => sanitize_title($input['category']), 'include_children' => true)); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        }
        $out = array();
        $limit = max(1, min(25, isset($input['limit']) ? (int)$input['limit'] : 10));
        foreach (get_posts($args) as $post) {
            $document = Document::get($post);
            if ($document && FileServer::can_access($document, null, 'view')) {
                $out[] = self::describe($document);
            }
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    public static function get($input)
    {
        $id = is_array($input) && isset($input['id']) ? absint($input['id']) : 0;
        $document = $id ? Document::get($id) : null;
        if (!$document || $document->get_post()->post_status !== 'publish' || post_password_required($document->get_post()) || !FileServer::can_access($document, null, 'view')) {
            return new \WP_Error('document_engine_not_found', __('Document not found, or you are not allowed to open it.', 'document-engine'));
        }
        return self::describe($document);
    }
}
