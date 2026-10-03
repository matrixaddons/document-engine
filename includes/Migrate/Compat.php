<?php

namespace MatrixAddons\DocumentEngine\Migrate;

use MatrixAddons\DocumentEngine\Library\Library;

defined('ABSPATH') || exit;

/**
 * After a migration, pages still using the old plugin's shortcodes keep working
 * once that plugin is deactivated: they render the matching Document Engine output.
 */
class Compat
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register'), 99);
        add_action('template_redirect', array(__CLASS__, 'redirect_old_links'), 0);
    }

    /**
     * Old download links (e.g. /download/12/, ?download=12, ?wpdmdl=12) and old document pages
     * keep working after the old plugin is deactivated: they redirect to the moved document.
     */
    public static function redirect_old_links()
    {
        $done = (array)get_option(Migrator::OPTION_DONE, array());
        if (!$done) {
            return;
        }
        $target = 0;
        $download = false;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only redirect lookup.
        if (isset($done['wpdm'], $_GET['wpdmdl']) && !Migrator::source_active('wpdm')) {
            $target = Migrator::existing('wpdm', absint($_GET['wpdmdl']));
            $download = true;
        } elseif (isset($done['sdm'], $_GET['smd_process_download'], $_GET['download_id']) && !Migrator::source_active('sdm')) {
            $target = Migrator::existing('sdm', absint($_GET['download_id']));
            $download = true;
        } elseif (isset($done['dlm'], $_GET['download']) && !Migrator::source_active('dlm') && is_numeric($_GET['download'])) {
            $target = Migrator::existing('dlm', absint($_GET['download']));
            $download = true;
        }
        // phpcs:enable
        if (!$target && is_404()) {
            $path = trim((string)wp_parse_url(isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '', PHP_URL_PATH), '/');
            $home = trim((string)wp_parse_url(home_url(), PHP_URL_PATH), '/');
            if ($home !== '' && strpos($path, $home . '/') === 0) {
                $path = substr($path, strlen($home) + 1);
            }
            $prefixes = apply_filters('document_engine_migrate_old_prefixes', array(
                'download' => array('dlm', 'wpdm'),
                'document' => array('barn2'),
                'dlp_document' => array('barn2'),
                'sdm_downloads' => array('sdm'),
            ));
            if (preg_match('#^([a-z0-9_-]+)/([^/]+)/?$#i', $path, $m) && isset($prefixes[$m[1]])) {
                foreach ((array)$prefixes[$m[1]] as $key) {
                    if (!isset($done[$key]) || Migrator::source_active($key)) {
                        continue;
                    }
                    $target = is_numeric($m[2]) ? Migrator::existing($key, (int)$m[2]) : self::by_slug($key, sanitize_title($m[2]));
                    if ($target) {
                        // DLM's /download/… is a file link; the others are pages.
                        $download = $key === 'dlm';
                        break;
                    }
                }
            }
        }
        if (!$target || get_post_status($target) !== 'publish') {
            return;
        }
        $document = \MatrixAddons\DocumentEngine\Documents\Document::get($target);
        if (!$document) {
            return;
        }
        wp_safe_redirect($download && $document->has_file() ? $document->get_download_url() : get_permalink($target), 301);
        exit;
    }

    private static function by_slug($key, $slug)
    {
        $found = get_posts(array(
            'post_type' => \MatrixAddons\DocumentEngine\Documents\PostType::POST_TYPE,
            'post_status' => 'publish',
            'name' => $slug,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(array('key' => Migrator::META_FROM, 'value' => $key . ':', 'compare' => 'LIKE')), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        ));
        return $found ? (int)$found[0] : 0;
    }

    public static function register()
    {
        $done = (array)get_option(Migrator::OPTION_DONE, array());
        if (!$done || get_option('document_engine_migrate_shortcodes', 'yes') !== 'yes') {
            return;
        }
        $map = array(
            'barn2' => array('doc_library' => 'library'),
            'dlm' => array('download' => 'download', 'downloads' => 'library'),
            'wpdm' => array('wpdm_package' => 'download', 'wpdm_packages' => 'library', 'wpdm_all_packages' => 'library'),
            'sfl' => array('eeSFL' => 'library'),
            'sdm' => array('sdm_download' => 'download', 'sdm_download_link' => 'download', 'sdm_show_all_dl' => 'library', 'sdm_show_dl_from_category' => 'library', 'sdm_latest_downloads' => 'library', 'sdm_popular_downloads' => 'popular', 'sdm_search_form' => 'search', 'sdm_download_counter' => 'counter'),
        );
        foreach ($map as $key => $shortcodes) {
            if (!isset($done[$key])) {
                continue;
            }
            foreach ($shortcodes as $tag => $kind) {
                // Never take over a shortcode the old plugin (still active) owns.
                if (shortcode_exists($tag)) {
                    continue;
                }
                add_shortcode($tag, function ($atts) use ($key, $kind) {
                    if ($kind === 'popular') {
                        $atts = array_merge(is_array($atts) ? $atts : array(), array('orderby' => 'downloads', 'order' => 'desc'));
                        return self::library($atts, $key);
                    }
                    if ($kind === 'search') {
                        return \MatrixAddons\DocumentEngine\Library\SearchBox::render(array());
                    }
                    if ($kind === 'counter') {
                        $document_id = isset($atts['id']) ? Migrator::existing($key, absint($atts['id'])) : 0;
                        $document = $document_id ? \MatrixAddons\DocumentEngine\Documents\Document::get($document_id) : null;
                        return $document ? esc_html(number_format_i18n($document->get_download_count())) : '';
                    }
                    return $kind === 'library' ? self::library($atts, $key) : self::download($key, $atts);
                });
            }
        }
    }

    private static function download($key, $atts)
    {
        $atts = is_array($atts) ? $atts : array();
        $source_id = isset($atts['id']) ? absint($atts['id']) : 0;
        $document_id = $source_id ? Migrator::existing($key, $source_id) : 0;
        if (!$document_id) {
            return '';
        }
        return Library::render_download(array('documentId' => $document_id, 'variant' => 'card', 'showMeta' => true));
    }

    private static function library($atts, $key = '')
    {
        $atts = is_array($atts) ? $atts : array();
        $args = array();
        // Simple File List: [eeSFL showfolder="Folder/Sub"] shows one folder (now a category).
        if ($key === 'sfl' && !empty($atts['showfolder'])) {
            $map = (array)get_option(Migrator::OPTION_TERMS, array());
            $map_key = 'sfl:sfl_folder:' . Sources::sfl_folder_id((string)$atts['showfolder']);
            $term = isset($map[$map_key]) ? get_term((int)$map[$map_key], \MatrixAddons\DocumentEngine\Documents\PostType::CATEGORY) : null;
            $args['categories'] = $term && !is_wp_error($term) ? $term->slug : '__none__';
        }
        foreach (array('doc_category', 'category', 'categories', 'category_slug', 'category_id') as $name) {
            if (!empty($atts[$name])) {
                $args['categories'] = implode(',', self::map_categories($key, preg_split('/[,|+]/', (string)$atts[$name])));
                break;
            }
        }
        foreach (array('rows_per_page', 'per_page', 'number', 'items_per_page') as $name) {
            if (!empty($atts[$name])) {
                $args['per_page'] = absint($atts[$name]);
                break;
            }
        }
        foreach (array('include', 'exclude') as $name) {
            if (!empty($atts[$name])) {
                $ids = array();
                foreach (preg_split('/[,|]/', (string)$atts[$name]) as $source_id) {
                    $id = $key ? Migrator::existing($key, absint($source_id)) : 0;
                    if ($id) {
                        $ids[] = $id;
                    }
                }
                if ($ids) {
                    $args[$name] = implode(',', $ids);
                } elseif ($name === 'include') {
                    // An include list with no moved documents must show nothing, not everything.
                    $args['categories'] = '__none__';
                }
            }
        }
        if (!empty($atts['orderby'])) {
            $orderby = strtolower((string)$atts['orderby']);
            $map = array('download_count' => 'downloads', 'downloads' => 'downloads', 'title' => 'title', 'date' => 'date', 'modified' => 'modified', 'menu_order' => 'menu_order');
            if (isset($map[$orderby])) {
                $args['orderby'] = $map[$orderby];
            }
            if (!empty($atts['order'])) {
                $args['order'] = strtolower((string)$atts['order']) === 'asc' ? 'asc' : 'desc';
            }
        }
        return Library::render($args);
    }

    /**
     * Old shortcodes may name categories by ID or slug; turn both into the moved categories' slugs.
     */
    private static function map_categories($key, $values)
    {
        $map = (array)get_option(Migrator::OPTION_TERMS, array());
        $source = $key ? Sources::get($key) : null;
        $slugs = array();
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }
            $term_id = 0;
            if ($source) {
                $source_id = is_numeric($value) ? (int)$value : 0;
                if (!$source_id) {
                    $old = get_term_by('slug', sanitize_title($value), $source['category']);
                    if (!$old) {
                        global $wpdb;
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                        $source_id = (int)$wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE t.slug = %s AND tt.taxonomy = %s", sanitize_title($value), $source['category']));
                    } else {
                        $source_id = (int)$old->term_id;
                    }
                }
                $map_key = $key . ':' . $source['category'] . ':' . $source_id;
                $term_id = isset($map[$map_key]) ? (int)$map[$map_key] : 0;
            }
            $term = $term_id ? get_term($term_id) : get_term_by('slug', sanitize_title($value), \MatrixAddons\DocumentEngine\Documents\PostType::CATEGORY);
            if ($term && !is_wp_error($term)) {
                $slugs[] = $term->slug;
            }
        }
        return $slugs ?: array('__none__');
    }
}
