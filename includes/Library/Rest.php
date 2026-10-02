<?php

namespace MatrixAddons\DocumentEngine\Library;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * REST routes (namespace document-engine/v1).
 *
 * GET /library    Rendered library results for a settings + state pair (used by library.js).
 * GET /documents  Document search for the block editor pickers.
 */
class Rest
{
    const NS = 'document-engine/v1';

    public static function init()
    {
        add_action('rest_api_init', array(__CLASS__, 'routes'));
    }

    public static function routes()
    {
        register_rest_route(self::NS, '/library', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'library'),
            'permission_callback' => '__return_true',
            'args' => array(
                'atts' => array('type' => 'string', 'required' => true),
            ),
        ));

        register_rest_route(self::NS, '/preview/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'preview'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NS, '/documents', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'documents'),
            'permission_callback' => function () {
                return current_user_can('edit_dengine_documents');
            },
            'args' => array(
                'search' => array('type' => 'string', 'default' => ''),
                'include' => array('type' => 'array', 'items' => array('type' => 'integer'), 'default' => array()),
                'type' => array('type' => 'string', 'default' => ''),
            ),
        ));
    }

    /**
     * Markup for the library's preview popup. Access is checked like any other view.
     */
    public static function preview(\WP_REST_Request $request)
    {
        $document = \MatrixAddons\DocumentEngine\Documents\Document::get((int)$request['id']);
        if (!$document || $document->get_post()->post_status !== 'publish' || post_password_required($document->get_post()) || !Library::previewable($document)) {
            return new \WP_Error('document_engine_not_found', __('This document cannot be previewed.', 'document-engine'), array('status' => 404));
        }
        if (!\MatrixAddons\DocumentEngine\Documents\FileServer::can_access($document, null, 'view')) {
            return new \WP_Error('document_engine_forbidden', __('You do not have access to this document.', 'document-engine'), array('status' => 403));
        }
        $group = document_engine_file_type_group($document->get_extension());
        $src = $document->get_download_url(array('view' => 1));
        $title = $document->get_title();
        $office = document_engine_office_embed_url($document);
        if ($office !== '') {
            $html = '<iframe class="dengine-preview__media dengine-office" src="' . esc_url($office) . '" title="' . esc_attr($title) . '" loading="lazy" referrerpolicy="no-referrer"></iframe>';
        } elseif ($group === 'pdf') {
            $html = \MatrixAddons\DocumentEngine\Viewer\Viewer::render(array('documentId' => $document->get_id(), 'height' => '78vh'));
        } elseif ($group === 'image') {
            $html = '<img class="dengine-preview__media" src="' . esc_url($src) . '" alt="' . esc_attr($title) . '">';
        } elseif ($group === 'audio') {
            $html = '<audio class="dengine-preview__media" controls preload="metadata" src="' . esc_url($src) . '"></audio>';
        } else {
            $html = '<video class="dengine-preview__media" controls preload="metadata" playsinline src="' . esc_url($src) . '"></video>';
        }
        $response = rest_ensure_response(array(
            'html' => $html,
            'title' => $title,
            'url' => get_option('document_engine_single_pages', 'yes') === 'yes' ? get_permalink($document->get_post()) : '',
            'download' => $document->get_download_url(),
        ));
        $response->header('Cache-Control', 'no-store');
        return $response;
    }

    public static function library(\WP_REST_Request $request)
    {
        $atts = json_decode((string)$request->get_param('atts'), true);
        if (!is_array($atts)) {
            return new \WP_Error('document_engine_bad_request', __('Invalid library settings.', 'document-engine'), array('status' => 400));
        }

        $settings = Query::normalize($atts);
        $state = Query::state($settings, $request->get_query_params());
        $html = Library::render_results($settings, $state);

        $response = rest_ensure_response(array(
            'html' => $html,
            'url' => Library::url_for($settings, $state),
            'filtered' => Query::is_filtered($state),
        ));
        $response->header('Cache-Control', 'no-store');
        return $response;
    }

    public static function documents(\WP_REST_Request $request)
    {
        $args = array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => array('publish', 'private', 'draft', 'future'),
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        );
        if ($request->get_param('search') !== '') {
            $args['s'] = sanitize_text_field($request->get_param('search'));
        }
        $include = array_filter(array_map('absint', (array)$request->get_param('include')));
        if ($include) {
            $args['post__in'] = $include;
            $args['orderby'] = 'post__in';
        }
        if ($request->get_param('type') === 'pdf') {
            $args['meta_query'] = array(array('key' => Document::META_FILE_TYPE, 'value' => 'pdf')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        }

        $out = array();
        foreach ((new \WP_Query($args))->posts as $post) {
            $document = Document::get($post);
            if (!$document || !current_user_can('read_post', $post->ID)) {
                continue;
            }
            $out[] = array(
                'id' => $document->get_id(),
                'title' => html_entity_decode($document->get_title(), ENT_QUOTES, 'UTF-8'),
                'status' => $post->post_status,
                'type' => $document->get_extension(),
                'size' => $document->get_size_label(),
                'isPdf' => $document->is_pdf(),
                'fileUrl' => FileServer::can_access($document, null, 'view') ? $document->get_download_url(array('view' => 1)) : '',
            );
        }
        return rest_ensure_response($out);
    }
}
