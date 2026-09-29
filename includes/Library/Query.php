<?php

namespace MatrixAddons\DocumentEngine\Library;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Normalises library settings and turns them plus the visitor's filters into a WP_Query.
 */
class Query
{
    /**
     * Settings a library accepts (block attributes and shortcode attributes share these).
     */
    public static function defaults()
    {
        return apply_filters('document_engine_library_defaults', array(
            'id' => 'dl',
            'layout' => get_option('document_engine_library_layout', 'table'),
            'categories' => '',
            'tags' => '',
            'include' => '',
            'exclude' => '',
            'file_types' => '',
            'per_page' => absint(get_option('document_engine_library_per_page', 20)),
            'orderby' => 'date',
            'order' => 'desc',
            'columns' => 'title,category,type,size,date,actions',
            'grid_columns' => 3,
            'search' => true,
            'filters' => 'category,type',
            'show_thumbnails' => true,
            'show_excerpt' => true,
            'link_to' => get_option('document_engine_single_pages', 'yes') === 'yes' ? 'document' : 'file',
            'pagination' => true,
            'folder_limit' => 50,
            'open_folders' => false,
            'multi_filters' => false,
            'class' => '',
        ));
    }

    public static function layouts()
    {
        return apply_filters('document_engine_library_layouts', array(
            'table' => __('Table', 'document-engine'),
            'grid' => __('Grid', 'document-engine'),
            'folders' => __('Folders', 'document-engine'),
        ));
    }

    public static function columns()
    {
        return apply_filters('document_engine_library_columns', array(
            'thumbnail' => __('Thumbnail', 'document-engine'),
            'title' => __('Title', 'document-engine'),
            'excerpt' => __('Description', 'document-engine'),
            'category' => __('Category', 'document-engine'),
            'tag' => __('Tags', 'document-engine'),
            'type' => __('Type', 'document-engine'),
            'size' => __('Size', 'document-engine'),
            'date' => __('Date', 'document-engine'),
            'modified' => __('Updated', 'document-engine'),
            'author' => __('Author', 'document-engine'),
            'downloads' => __('Downloads', 'document-engine'),
            'actions' => __('Download', 'document-engine'),
        ));
    }

    /**
     * Filters a library can show. Add-ons add their own keys (e.g. custom fields) and render them
     * on `document_engine_library_controls`.
     */
    public static function filter_keys()
    {
        return apply_filters('document_engine_library_filter_keys', array('category', 'tag', 'type', 'year', 'author', 'sort'));
    }

    /**
     * Filter names for the block editor.
     */
    public static function filter_labels()
    {
        return array_intersect_key(apply_filters('document_engine_library_filter_labels', array(
            'category' => __('Category', 'document-engine'),
            'tag' => __('Tag', 'document-engine'),
            'type' => __('File type', 'document-engine'),
            'year' => __('Year', 'document-engine'),
            'author' => __('Author', 'document-engine'),
            'sort' => __('Sort order', 'document-engine'),
        )), array_flip(self::filter_keys()));
    }

    public static function sort_options()
    {
        return apply_filters('document_engine_library_sort_options', array(
            'date-desc' => __('Newest first', 'document-engine'),
            'date-asc' => __('Oldest first', 'document-engine'),
            'title-asc' => __('Title A–Z', 'document-engine'),
            'title-desc' => __('Title Z–A', 'document-engine'),
            'modified-desc' => __('Recently updated', 'document-engine'),
            'downloads-desc' => __('Most downloaded', 'document-engine'),
        ));
    }

    /**
     * Cleans raw settings from a block, shortcode or REST request.
     */
    public static function normalize($atts)
    {
        $d = self::defaults();
        $atts = shortcode_atts($d, is_array($atts) ? $atts : array());

        $bool = function ($v) {
            return is_bool($v) ? $v : in_array(strtolower((string)$v), array('1', 'true', 'yes', 'on'), true);
        };
        $list = function ($v) {
            $items = is_array($v) ? $v : explode(',', (string)$v);
            return array_values(array_filter(array_map(function ($i) {
                return sanitize_title(trim((string)$i));
            }, $items), 'strlen'));
        };
        $ids = function ($v) {
            $items = is_array($v) ? $v : explode(',', (string)$v);
            return array_values(array_filter(array_map('absint', $items)));
        };

        $layouts = array_keys(self::layouts());
        $columns = array_keys(self::columns());

        $out = array(
            'id' => preg_replace('/[^a-z0-9]/', '', strtolower((string)$atts['id'])) ?: 'dl',
            'layout' => in_array($atts['layout'], $layouts, true) ? $atts['layout'] : 'table',
            'categories' => $list($atts['categories']),
            'tags' => $list($atts['tags']),
            'include' => $ids($atts['include']),
            'exclude' => $ids($atts['exclude']),
            'file_types' => array_values(array_intersect($list($atts['file_types']), array_keys(document_engine_file_type_groups_labels()))),
            'per_page' => max(1, min(100, absint($atts['per_page']) ?: 20)),
            'orderby' => in_array($atts['orderby'], array('date', 'title', 'modified', 'downloads', 'menu_order', 'rand'), true) ? $atts['orderby'] : 'date',
            'order' => strtolower((string)$atts['order']) === 'asc' ? 'asc' : 'desc',
            'columns' => array_values(array_intersect($list($atts['columns']), $columns)),
            'grid_columns' => max(1, min(6, absint($atts['grid_columns']) ?: 3)),
            'search' => $bool($atts['search']),
            'filters' => array_values(array_intersect($list($atts['filters']), self::filter_keys())),
            'show_thumbnails' => $bool($atts['show_thumbnails']),
            'show_excerpt' => $bool($atts['show_excerpt']),
            'link_to' => in_array($atts['link_to'], array('document', 'file', 'none'), true) ? $atts['link_to'] : 'document',
            'pagination' => $bool($atts['pagination']),
            'folder_limit' => max(1, min(100, absint($atts['folder_limit']) ?: 50)),
            'open_folders' => $bool($atts['open_folders']),
            'multi_filters' => $bool($atts['multi_filters']),
            'class' => implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', (string)$atts['class']))),
        );

        if (empty($out['columns'])) {
            $out['columns'] = array('title', 'type', 'size', 'date', 'actions');
        }

        return apply_filters('document_engine_library_settings', $out, $atts);
    }

    /**
     * Reads the visitor's search/filter/sort/page state for a library from a request array.
     */
    public static function state($settings, $source = null)
    {
        $source = $source === null ? $_GET : $source; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $p = $settings['id'] . '_';

        $get = function ($key) use ($source, $p) {
            return isset($source[$p . $key]) ? sanitize_text_field(wp_unslash((string)$source[$p . $key])) : '';
        };

        // Category, tag and type take several values ("a,b", or a[]=… from a form without JavaScript).
        $many = function ($key, $clean) use ($source, $p) {
            $raw = isset($source[$p . $key]) ? $source[$p . $key] : '';
            $items = is_array($raw) ? $raw : explode(',', (string)$raw);
            $out = array();
            foreach ($items as $item) {
                if (is_scalar($item)) {
                    $item = call_user_func($clean, sanitize_text_field(wp_unslash((string)$item)));
                    if ($item !== '' && !in_array($item, $out, true)) {
                        $out[] = $item;
                    }
                }
            }
            return implode(',', array_slice($out, 0, 20));
        };

        $sort = $get('sort');
        $state = array(
            's' => mb_substr($get('s'), 0, 100),
            'cat' => $many('cat', 'sanitize_title'),
            'tag' => $many('tag', 'sanitize_title'),
            'type' => $many('type', 'sanitize_key'),
            'year' => preg_match('/^(19|20)\d\d$/', $get('year')) ? $get('year') : '',
            'author' => (string)absint($get('author')) === $get('author') ? $get('author') : '',
            'sort' => array_key_exists($sort, self::sort_options()) ? $sort : '',
            'page' => max(1, absint($get('p'))),
        );
        /**
         * Extra state (add-on filters). Keys added here also need `document_engine_library_state_params`.
         */
        return apply_filters('document_engine_library_state', $state, $settings, $source);
    }

    /**
     * State key => URL parameter (without the library prefix).
     */
    public static function state_params()
    {
        return apply_filters('document_engine_library_state_params', array('s' => 's', 'cat' => 'cat', 'tag' => 'tag', 'type' => 'type', 'year' => 'year', 'author' => 'author', 'sort' => 'sort', 'page' => 'p'));
    }

    public static function is_filtered($state)
    {
        $filtered = $state['s'] !== '' || $state['cat'] !== '' || $state['tag'] !== '' || $state['type'] !== '' || !empty($state['year']) || !empty($state['author']);
        return (bool)apply_filters('document_engine_library_is_filtered', $filtered, $state);
    }

    /**
     * @return \WP_Query
     */
    public static function run($settings, $state, $overrides = array())
    {
        $args = array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $settings['per_page'],
            'paged' => $state['page'],
            'ignore_sticky_posts' => true,
            'no_found_rows' => false,
        );

        list($orderby, $order) = array($settings['orderby'], $settings['order']);
        if ($state['sort'] !== '') {
            list($orderby, $order) = explode('-', $state['sort']);
        }
        if ($orderby === 'downloads') {
            // Documents never downloaded have no row yet, so include them via NOT EXISTS.
            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    'dengine_downloads' => array('key' => Document::META_DOWNLOADS, 'compare' => 'EXISTS', 'type' => 'NUMERIC'),
                    array('key' => Document::META_DOWNLOADS, 'compare' => 'NOT EXISTS'),
                ),
            );
            $args['orderby'] = array('dengine_downloads' => strtoupper($order), 'title' => 'ASC');
        } else {
            $args['orderby'] = array($orderby => strtoupper($order));
            if ($orderby !== 'title') {
                $args['orderby']['title'] = 'ASC';
            }
        }

        $tax = array();
        if (!empty($settings['categories'])) {
            $tax[] = array('taxonomy' => PostType::CATEGORY, 'field' => 'slug', 'terms' => $settings['categories'], 'include_children' => true);
        }
        if (!empty($settings['tags'])) {
            $tax[] = array('taxonomy' => PostType::TAG, 'field' => 'slug', 'terms' => $settings['tags']);
        }
        // Several picks within one filter widen it (any of them); different filters narrow each other.
        if ($state['cat'] !== '') {
            $tax[] = array('taxonomy' => PostType::CATEGORY, 'field' => 'slug', 'terms' => self::values($state['cat']), 'include_children' => true);
        }
        if ($state['tag'] !== '') {
            $tax[] = array('taxonomy' => PostType::TAG, 'field' => 'slug', 'terms' => self::values($state['tag']));
        }
        if ($tax) {
            $args['tax_query'] = array_merge(array('relation' => 'AND'), $tax); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        }

        $types = $settings['file_types'];
        if ($state['type'] !== '') {
            $picked = self::values($state['type']);
            $types = empty($types) ? $picked : (array_values(array_intersect($picked, $types)) ?: array('__none__'));
        }
        if (!empty($types)) {
            $extensions = array();
            foreach (array_keys(document_engine_file_type_groups_labels()) as $group) {
                if (in_array($group, $types, true)) {
                    $extensions = array_merge($extensions, self::extensions_for_group($group));
                }
            }
            $meta = array('key' => Document::META_FILE_TYPE, 'value' => $extensions ?: array('__none__'), 'compare' => 'IN');
            if (in_array('other', $types, true)) {
                $meta = array(
                    'relation' => 'OR',
                    $meta,
                    array('key' => Document::META_FILE_TYPE, 'value' => self::all_known_extensions(), 'compare' => 'NOT IN'),
                );
            }
            $args['meta_query'] = isset($args['meta_query']) ? $args['meta_query'] : array('relation' => 'AND'); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $args['meta_query'][] = $meta;
        }

        if (!empty($settings['include'])) {
            $args['post__in'] = $settings['include'];
            if ($state['sort'] === '' && $settings['orderby'] === 'date' && empty($overrides['orderby'])) {
                $args['orderby'] = 'post__in';
            }
        }
        if (!empty($settings['exclude'])) {
            $args['post__not_in'] = $settings['exclude'];
        }
        if ($state['s'] !== '') {
            $args['s'] = $state['s'];
        }
        if (!empty($state['year'])) {
            $args['year'] = (int)$state['year'];
        }
        if (!empty($state['author'])) {
            $args['author'] = (int)$state['author'];
        }

        $args = array_merge($args, $overrides);

        return new \WP_Query(apply_filters('document_engine_library_query_args', $args, $settings, $state));
    }

    /**
     * The values of a multi-value filter ("a,b" → array('a', 'b')).
     */
    public static function values($csv)
    {
        return array_values(array_filter(explode(',', (string)$csv), 'strlen'));
    }

    public static function extensions_for_group($group)
    {
        $all = array('pdf', 'doc', 'docx', 'odt', 'rtf', 'pages', 'txt', 'md', 'xls', 'xlsx', 'ods', 'csv', 'numbers', 'tsv', 'ppt', 'pptx', 'odp', 'key', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'tif', 'tiff', 'psd', 'ai', 'eps', 'mp3', 'wav', 'm4a', 'ogg', 'flac', 'mp4', 'mov', 'webm', 'avi', 'mkv', 'zip', 'rar', '7z', 'gz', 'tar');
        return array_values(array_filter($all, function ($ext) use ($group) {
            return document_engine_file_type_group($ext) === $group;
        }));
    }

    private static function all_known_extensions()
    {
        $all = array();
        foreach (array_keys(document_engine_file_type_groups_labels()) as $group) {
            if ($group !== 'other') {
                $all = array_merge($all, self::extensions_for_group($group));
            }
        }
        return $all;
    }
}
