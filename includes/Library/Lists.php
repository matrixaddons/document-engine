<?php

namespace MatrixAddons\DocumentEngine\Library;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Short document lists: recent, recently updated, popular and related
 * (block `document-engine/documents`, shortcode `[document_engine_documents]`).
 */
class Lists
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_shortcode'));
        add_filter('the_content', array(__CLASS__, 'append_related'), 25);
    }

    public static function register_shortcode()
    {
        add_shortcode('document_engine_documents', array(__CLASS__, 'shortcode'));
    }

    public static function modes()
    {
        return apply_filters('document_engine_list_modes', array(
            'recent' => __('Newest documents', 'document-engine'),
            'updated' => __('Recently updated', 'document-engine'),
            'popular' => __('Most downloaded', 'document-engine'),
            'related' => __('Related documents', 'document-engine'),
        ));
    }

    /**
     * [document_engine_documents mode="popular" count="5" category="minutes" title="Popular" show_meta="yes" document="12"]
     */
    public static function shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'mode' => 'recent',
            'count' => 5,
            'category' => '',
            'title' => '',
            'show_meta' => 'yes',
            'document' => 0,
        ), $atts, 'document_engine_documents');

        return self::render(array(
            'mode' => $atts['mode'],
            'count' => $atts['count'],
            'categories' => array_filter(array_map('trim', explode(',', (string)$atts['category']))),
            'title' => $atts['title'],
            'showMeta' => $atts['show_meta'] !== 'no',
            'documentId' => $atts['document'],
        ));
    }

    /**
     * @param array $args mode, count, categories, title, showMeta, documentId, className
     */
    public static function render($args)
    {
        $args = wp_parse_args($args, array(
            'mode' => 'recent',
            'count' => 5,
            'categories' => array(),
            'title' => '',
            'showMeta' => true,
            'documentId' => 0,
            'className' => '',
        ));
        $mode = array_key_exists($args['mode'], self::modes()) ? $args['mode'] : 'recent';
        $count = max(1, min(20, absint($args['count']) ?: 5));

        $documents = self::documents($mode, $count, array_values(array_filter(array_map('sanitize_title', (array)$args['categories']), 'strlen')), absint($args['documentId']));
        if (!$documents) {
            // Editors see why an empty list renders nothing; visitors see nothing.
            if ($mode !== 'related' && current_user_can('edit_dengine_documents') && (defined('REST_REQUEST') && REST_REQUEST)) {
                return '<p class="dengine-notice">' . esc_html__('No documents to list yet.', 'document-engine') . '</p>';
            }
            return '';
        }

        document_engine_enqueue_frontend();

        $classes = trim('dengine-list dengine-list--' . $mode . ' ' . implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', (string)$args['className']))));
        $title = (string)$args['title'];

        ob_start();
        ?>
        <section class="<?php echo esc_attr($classes); ?>"<?php echo $title !== '' ? ' aria-label="' . esc_attr($title) . '"' : ''; ?>>
            <?php if ($title !== '') : ?>
                <h2 class="dengine-list__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <ul class="dengine-list__items">
                <?php foreach ($documents as $document) :
                    $meta = array();
                    if ($args['showMeta']) {
                        $meta = array_filter(array(
                            $document->get_type_label(),
                            $document->get_size_label(),
                            $mode === 'popular' && $document->get_download_count() > 0
                                /* translators: %s: number of downloads */
                                ? sprintf(_n('%s download', '%s downloads', $document->get_download_count(), 'document-engine'), number_format_i18n($document->get_download_count()))
                                : Library::short_date($document, $mode === 'updated'),
                        ));
                    }
                    $url = get_option('document_engine_single_pages', 'yes') === 'yes' ? get_permalink($document->get_post()) : $document->get_download_url();
                    ?>
                    <li class="dengine-list__item">
                        <?php echo document_engine_file_icon($document->get_extension(), 'dengine-icon--inline'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span class="dengine-list__body">
                            <a class="dengine-list__link" href="<?php echo esc_url($url); ?>"><?php echo esc_html($document->get_title()); ?></a>
                            <?php if ($meta) : ?>
                                <span class="dengine-list__meta"><?php echo esc_html(implode(' · ', $meta)); ?></span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
        return apply_filters('document_engine_list_html', ob_get_clean(), $documents, $args);
    }

    /**
     * @return Document[]
     */
    public static function documents($mode, $count, $categories = array(), $document_id = 0)
    {
        $query = array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'has_password' => false,
            // Let multilingual plugins (WPML, Polylang) limit the list to the current language.
            'suppress_filters' => false,
        );
        if ($categories) {
            $query['tax_query'] = array(array('taxonomy' => PostType::CATEGORY, 'field' => 'slug', 'terms' => $categories, 'include_children' => true)); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        }

        switch ($mode) {
            case 'updated':
                $query['orderby'] = array('modified' => 'DESC', 'title' => 'ASC');
                break;
            case 'popular':
                /**
                 * Add-ons with real analytics can return ranked document IDs (e.g. downloads in the last 30 days).
                 */
                $ids = apply_filters('document_engine_popular_document_ids', null, $count, $categories);
                if (is_array($ids) && $ids) {
                    $query['post__in'] = array_map('absint', $ids);
                    $query['orderby'] = 'post__in';
                } else {
                    $query['meta_query'] = array(array('key' => Document::META_DOWNLOADS, 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    $query['meta_key'] = Document::META_DOWNLOADS; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $query['orderby'] = array('meta_value_num' => 'DESC', 'title' => 'ASC');
                }
                break;
            case 'related':
                $document_id = $document_id ?: (is_singular(PostType::POST_TYPE) ? get_queried_object_id() : 0);
                if (!$document_id || get_post_type($document_id) !== PostType::POST_TYPE) {
                    return array();
                }
                $tax = array('relation' => 'OR');
                foreach (array(PostType::CATEGORY, PostType::TAG) as $taxonomy) {
                    $terms = wp_get_post_terms($document_id, $taxonomy, array('fields' => 'ids'));
                    if (!is_wp_error($terms) && $terms) {
                        $tax[] = array('taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $terms);
                    }
                }
                if (count($tax) === 1) {
                    return array();
                }
                $query['tax_query'] = isset($query['tax_query']) ? array('relation' => 'AND', $query['tax_query'], $tax) : $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                $query['post__not_in'] = array($document_id);
                $query['orderby'] = array('modified' => 'DESC');
                break;
            default:
                $query['orderby'] = array('date' => 'DESC', 'title' => 'ASC');
        }

        // Fetch a few extra so hidden documents do not shorten the list.
        $query['posts_per_page'] = $count + 5;
        $posts = get_posts(apply_filters('document_engine_list_query_args', $query, $mode));

        $documents = array();
        foreach ($posts as $post) {
            $document = Document::get($post);
            if ($document && FileServer::can_access($document, null, 'list')) {
                $documents[] = $document;
            }
            if (count($documents) >= $count) {
                break;
            }
        }
        return $documents;
    }

    /**
     * Optional "Related documents" list under each document page (off by default).
     */
    public static function append_related($content)
    {
        static $rendered = array();
        // SEO and social plugins run the_content in <head>; don't spend the one render there.
        if (get_option('document_engine_single_related', 'no') !== 'yes' || !is_singular(PostType::POST_TYPE) || doing_filter('get_the_excerpt') || doing_filter('wp_trim_excerpt') || doing_action('wp_head')) {
            return $content;
        }
        // Block themes render post content outside "the loop", so match the queried document instead.
        $id = (int)get_queried_object_id();
        if ((int)get_the_ID() !== $id || isset($rendered[$id]) || post_password_required($id)) {
            return $content;
        }
        $rendered[$id] = true;
        $document = Document::get($id);
        if (!$document || !FileServer::can_access($document, null, 'view')) {
            return $content;
        }
        return $content . self::render(array(
            'mode' => 'related',
            'count' => 4,
            'documentId' => $id,
            'title' => __('Related documents', 'document-engine'),
        ));
    }
}
