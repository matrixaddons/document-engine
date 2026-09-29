<?php

namespace MatrixAddons\DocumentEngine\Library;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Renders document libraries (block `document-engine/library`, shortcode `[document_engine_library]`).
 *
 * Output is complete server-side HTML (search and pagination work without JavaScript);
 * library.js upgrades it to in-place updates through the REST route.
 */
class Library
{
    private static $instances = array();

    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_shortcodes'));
    }

    public static function register_shortcodes()
    {
        add_shortcode('document_engine_library', array(__CLASS__, 'shortcode'));
        add_shortcode('document_engine_document', array(__CLASS__, 'document_shortcode'));
    }

    public static function shortcode($atts)
    {
        return self::render(is_array($atts) ? $atts : array());
    }

    /**
     * [document_engine_document id="12" style="card|button" label="Download"]
     */
    public static function document_shortcode($atts)
    {
        $atts = shortcode_atts(array('id' => 0, 'style' => 'card', 'label' => '', 'show_meta' => 'yes'), $atts);
        return self::render_download(array(
            'documentId' => absint($atts['id']),
            'variant' => $atts['style'],
            'label' => $atts['label'],
            'showMeta' => $atts['show_meta'] === 'yes',
        ));
    }

    /**
     * Full library markup.
     */
    public static function render($atts)
    {
        // Give repeated libraries on one page their own URL parameters.
        $requested_id = isset($atts['id']) ? (string)$atts['id'] : '';
        $settings = Query::normalize($atts);
        if ($requested_id === '') {
            $n = count(self::$instances) + 1;
            $settings['id'] = $n > 1 ? 'dl' . $n : 'dl';
        }
        self::$instances[] = $settings['id'];

        $state = Query::state($settings);

        document_engine_enqueue_frontend(array('document-engine-library'));

        $classes = array('dengine-library', 'dengine-library--' . $settings['layout']);
        if ($settings['class'] !== '') {
            $classes[] = $settings['class'];
        }

        $config = array(
            'id' => $settings['id'],
            'atts' => self::public_settings($settings),
        );

        ob_start();
        ?>
        <div id="dengine-library-<?php echo esc_attr($settings['id']); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-dengine-library="<?php echo esc_attr(wp_json_encode($config)); ?>">
            <?php
            self::render_controls($settings, $state);
            ?>
            <div class="dengine-library__results" aria-live="polite" aria-busy="false">
                <?php echo self::render_results($settings, $state); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <?php
        return apply_filters('document_engine_library_html', ob_get_clean(), $settings, $state);
    }

    /**
     * The settings a browser needs to ask for the next page (no internal values).
     */
    public static function public_settings($settings)
    {
        $public = $settings;
        foreach (array('categories', 'tags', 'include', 'exclude', 'file_types', 'columns', 'filters') as $key) {
            $public[$key] = implode(',', $settings[$key]);
        }
        return $public;
    }

    public static function render_controls($settings, $state)
    {
        $filters = $settings['filters'];
        if (!$settings['search'] && empty($filters)) {
            return;
        }
        $p = $settings['id'] . '_';
        $uid = 'dengine-' . $settings['id'];
        ?>
        <form class="dengine-library__controls" method="get" role="search" action="<?php echo esc_url(self::base_url()); ?>#dengine-library-<?php echo esc_attr($settings['id']); ?>">
            <?php
            // Keep other query args (e.g. page_id on plain permalinks).
            foreach ($_GET as $key => $value) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if (is_string($value) && strpos((string)$key, $p) !== 0) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(sanitize_text_field(wp_unslash($value))) . '">';
                }
            }
            if ($settings['search']) : ?>
                <div class="dengine-field dengine-field--search">
                    <label class="screen-reader-text" for="<?php echo esc_attr($uid); ?>-s"><?php esc_html_e('Search documents', 'document-engine'); ?></label>
                    <?php echo document_engine_ui_icon('search'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <input type="search" id="<?php echo esc_attr($uid); ?>-s" name="<?php echo esc_attr($p); ?>s" value="<?php echo esc_attr($state['s']); ?>" placeholder="<?php esc_attr_e('Search documents…', 'document-engine'); ?>">
                </div>
            <?php endif;

            // Category, tag and file type: a dropdown, or checkboxes when visitors may pick several.
            $choice = function ($key, $label, $all_label, $options) use ($settings, $state, $uid, $p) {
                if ($settings['multi_filters']) {
                    self::multi($uid . '-' . $key, $p . $key, $label, $options, Query::values($state[$key]));
                } else {
                    $values = Query::values($state[$key]);
                    self::select($uid . '-' . $key, $p . $key, $label, $all_label, $options, count($values) === 1 ? $values[0] : '');
                }
            };
            if (in_array('category', $filters, true)) {
                $terms = self::category_options($settings);
                if ($terms) {
                    $choice('cat', __('Category', 'document-engine'), __('All categories', 'document-engine'), $terms);
                }
            }
            if (in_array('tag', $filters, true)) {
                $tags = get_terms(array('taxonomy' => PostType::TAG, 'hide_empty' => true, 'slug' => $settings['tags'] ?: '', 'number' => 200));
                $options = array();
                foreach (is_array($tags) ? $tags : array() as $tag) {
                    $options[$tag->slug] = $tag->name . ' (' . number_format_i18n($tag->count) . ')';
                }
                if ($options) {
                    $choice('tag', __('Tag', 'document-engine'), __('All tags', 'document-engine'), $options);
                }
            }
            if (in_array('type', $filters, true)) {
                $labels = document_engine_file_type_groups_labels();
                if (!empty($settings['file_types'])) {
                    $labels = array_intersect_key($labels, array_flip($settings['file_types']));
                }
                if (count($labels) > 1) {
                    $choice('type', __('File type', 'document-engine'), __('All file types', 'document-engine'), $labels);
                }
            }
            if (in_array('year', $filters, true)) {
                $years = self::year_options($settings);
                if (count($years) > 1 || $state['year'] !== '') {
                    self::select($uid . '-year', $p . 'year', __('Year', 'document-engine'), __('All years', 'document-engine'), $years, $state['year']);
                }
            }
            if (in_array('author', $filters, true)) {
                $authors = self::author_options();
                if (count($authors) > 1 || $state['author'] !== '') {
                    self::select($uid . '-author', $p . 'author', __('Author', 'document-engine'), __('All authors', 'document-engine'), $authors, $state['author']);
                }
            }
            /**
             * Add-on filters (e.g. Pro custom fields) print their fields here.
             */
            do_action('document_engine_library_controls', $settings, $state, $uid, $p);
            if (in_array('sort', $filters, true)) {
                self::select($uid . '-sort', $p . 'sort', __('Sort by', 'document-engine'), __('Default order', 'document-engine'), Query::sort_options(), $state['sort']);
            }
            ?>
            <button type="submit" class="dengine-button dengine-library__submit"><?php esc_html_e('Search', 'document-engine'); ?></button>
        </form>
        <?php
    }

    /**
     * Checkbox picker in a disclosure: several values within one filter.
     * Without JavaScript the form sends name[]=a&name[]=b, which Query::state() understands too.
     */
    public static function multi($id, $name, $label, $options, $current)
    {
        $count = count(array_intersect(array_map('strval', array_keys($options)), $current));
        echo '<details class="dengine-field dengine-multi" data-dengine-multi>';
        echo '<summary class="dengine-multi__toggle" id="' . esc_attr($id) . '"><span class="dengine-multi__label">' . esc_html($label) . '</span>'
            . '<span class="dengine-multi__count"' . ($count ? '' : ' hidden') . '>' . esc_html(number_format_i18n($count)) . '</span></summary>';
        echo '<div class="dengine-multi__panel" role="group" aria-labelledby="' . esc_attr($id) . '">';
        foreach ($options as $value => $text) {
            echo '<label class="dengine-multi__option"><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($value) . '"' . checked(in_array((string)$value, $current, true), true, false) . '> <span>' . esc_html($text) . '</span></label>';
        }
        echo '</div></details>';
    }

    public static function select($id, $name, $label, $all_label, $options, $current)
    {
        echo '<div class="dengine-field"><label class="screen-reader-text" for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
        if ($all_label !== '') {
            echo '<option value="">' . esc_html($all_label) . '</option>';
        }
        foreach ($options as $value => $text) {
            echo '<option value="' . esc_attr($value) . '"' . selected($current, (string)$value, false) . '>' . esc_html($text) . '</option>';
        }
        echo '</select></div>';
    }

    /**
     * Years that have published documents, newest first (cached until a document changes).
     */
    private static function year_options($settings)
    {
        $years = get_transient('dengine_library_years');
        if (!is_array($years)) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $years = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY 1 DESC", PostType::POST_TYPE)));
            set_transient('dengine_library_years', $years, DAY_IN_SECONDS);
        }
        $options = array();
        foreach ($years as $year) {
            $options[(string)$year] = (string)$year;
        }
        return $options;
    }

    private static function author_options()
    {
        $users = get_users(array('has_published_posts' => array(PostType::POST_TYPE), 'fields' => array('ID', 'display_name'), 'orderby' => 'display_name', 'number' => 100));
        $options = array();
        foreach ($users as $user) {
            $options[(string)$user->ID] = $user->display_name;
        }
        return $options;
    }

    /**
     * Category dropdown options, indented by depth and limited to the library's own categories.
     */
    private static function category_options($settings)
    {
        $args = array('taxonomy' => PostType::CATEGORY, 'hide_empty' => true, 'orderby' => 'name');
        $roots = array();
        if (!empty($settings['categories'])) {
            foreach ($settings['categories'] as $slug) {
                $term = get_term_by('slug', $slug, PostType::CATEGORY);
                if ($term) {
                    $roots[] = $term;
                }
            }
        }
        $options = array();
        $walk = function ($parent, $depth) use (&$walk, &$options, $args) {
            $terms = get_terms(array_merge($args, array('parent' => $parent)));
            foreach (is_array($terms) ? $terms : array() as $term) {
                $options[$term->slug] = str_repeat('— ', $depth) . $term->name . ' (' . number_format_i18n($term->count) . ')';
                $walk($term->term_id, $depth + 1);
            }
        };
        if ($roots) {
            foreach ($roots as $root) {
                $options[$root->slug] = $root->name . ' (' . number_format_i18n($root->count) . ')';
                $walk($root->term_id, 1);
            }
        } else {
            $walk(0, 0);
        }
        return $options;
    }

    /**
     * Result area: layout plus pagination. Also returned by the REST route.
     */
    public static function render_results($settings, $state)
    {
        ob_start();

        $layout = $settings['layout'];
        // Searching inside a folder view shows a flat result list instead.
        if ($layout === 'folders' && Query::is_filtered($state)) {
            $layout = 'table';
        }

        if ($layout === 'folders') {
            self::render_folders($settings);
            return ob_get_clean();
        }

        $query = Query::run($settings, $state);

        if ($state['s'] !== '' && $state['page'] === 1) {
            /**
             * A visitor searched a library (add-ons use it for search analytics).
             */
            // Context: "live" for search-as-you-type requests, "page" for a full page load. Listeners should throttle.
            do_action('document_engine_library_searched', $state['s'], (int)$query->found_posts, $settings, $state, defined('REST_REQUEST') && REST_REQUEST ? 'live' : 'page');
        }

        if (!$query->have_posts()) {
            document_engine_get_template('library/empty.php', array('settings' => $settings, 'state' => $state));
            return ob_get_clean();
        }

        $documents = array_values(array_filter(array_map(array(Document::class, 'get'), $query->posts)));

        self::render_summary($settings, $state, (int)$query->found_posts);

        document_engine_get_template('library/' . ($layout === 'grid' ? 'grid' : 'table') . '.php', array(
            'settings' => $settings,
            'state' => $state,
            'documents' => $documents,
            'query' => $query,
        ));

        if ($settings['pagination'] && $query->max_num_pages > 1) {
            self::render_pagination($settings, $state, (int)$query->max_num_pages, (int)$query->found_posts);
        }

        return ob_get_clean();
    }

    /**
     * "12 documents" plus removable chips for the active search and filters.
     */
    private static function render_summary($settings, $state, $total)
    {
        $chips = array();
        if ($state['s'] !== '') {
            /* translators: %s: search terms */
            $chips[] = array(sprintf(__('“%s”', 'document-engine'), $state['s']), array_merge($state, array('s' => '', 'page' => 1)));
        }
        // One chip per picked value; removing it keeps the others.
        $without = function ($key, $value) use ($state) {
            return array_merge($state, array($key => implode(',', array_diff(Query::values($state[$key]), array($value))), 'page' => 1));
        };
        foreach (Query::values($state['cat']) as $slug) {
            $term = get_term_by('slug', $slug, PostType::CATEGORY);
            $chips[] = array($term ? $term->name : $slug, $without('cat', $slug));
        }
        foreach (Query::values($state['tag']) as $slug) {
            $term = get_term_by('slug', $slug, PostType::TAG);
            $chips[] = array('#' . ($term ? $term->name : $slug), $without('tag', $slug));
        }
        $labels = document_engine_file_type_groups_labels();
        foreach (Query::values($state['type']) as $group) {
            $chips[] = array(isset($labels[$group]) ? $labels[$group] : $group, $without('type', $group));
        }
        if (!empty($state['year'])) {
            $chips[] = array($state['year'], array_merge($state, array('year' => '', 'page' => 1)));
        }
        if (!empty($state['author'])) {
            $user = get_userdata((int)$state['author']);
            $chips[] = array($user ? $user->display_name : '#' . $state['author'], array_merge($state, array('author' => '', 'page' => 1)));
        }
        /**
         * Chips for add-on filters: array(label, state after removing it).
         */
        $chips = apply_filters('document_engine_library_chips', $chips, $settings, $state);
        if (!$chips && !$settings['search'] && empty($settings['filters'])) {
            return;
        }
        echo '<div class="dengine-summary"><span class="dengine-summary__count">' . esc_html(sprintf(
            /* translators: %s: number of documents */
            _n('%s document', '%s documents', $total, 'document-engine'),
            number_format_i18n($total)
        )) . '</span>';
        foreach ($chips as $chip) {
            echo '<a class="dengine-filter-chip" data-dengine-nav href="' . esc_url(self::url_for($settings, $chip[1])) . '">' . esc_html($chip[0]) . '<span aria-hidden="true">×</span><span class="screen-reader-text">' . esc_html__('Remove filter', 'document-engine') . '</span></a>';
        }
        if (count($chips) > 1) {
            echo '<a class="dengine-summary__clear" data-dengine-nav href="' . esc_url(self::url_for($settings, array())) . '">' . esc_html__('Clear all', 'document-engine') . '</a>';
        }
        echo '</div>';
    }

    private static function render_folders($settings)
    {
        $parent_terms = array();
        if (!empty($settings['categories'])) {
            foreach ($settings['categories'] as $slug) {
                $term = get_term_by('slug', $slug, PostType::CATEGORY);
                if ($term) {
                    $parent_terms[] = $term;
                }
            }
        } else {
            $parent_terms = get_terms(array('taxonomy' => PostType::CATEGORY, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name'));
            $parent_terms = is_array($parent_terms) ? $parent_terms : array();
        }

        echo '<div class="dengine-folders">';
        foreach ($parent_terms as $term) {
            self::render_folder($term, $settings, 0);
        }

        if (empty($settings['categories'])) {
            // Documents without a category.
            $query = Query::run($settings, Query::state($settings, array()), array(
                'posts_per_page' => $settings['folder_limit'],
                'paged' => 1,
                'tax_query' => array(array('taxonomy' => PostType::CATEGORY, 'operator' => 'NOT EXISTS')), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            ));
            if ($query->have_posts()) {
                echo '<details class="dengine-folder dengine-folder--other"' . ($settings['open_folders'] ? ' open' : '') . '><summary class="dengine-folder__summary">' . document_engine_ui_icon('folder') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    . '<span class="dengine-folder__name">' . esc_html__('Other documents', 'document-engine') . '</span>'
                    . '<span class="dengine-folder__count">' . esc_html(number_format_i18n($query->found_posts)) . '</span></summary><div class="dengine-folder__body">';
                self::folder_list(array_filter(array_map(array(Document::class, 'get'), $query->posts)), $settings);
                echo '</div></details>';
            }
        }
        echo '</div>';
    }

    private static function render_folder($term, $settings, $depth)
    {
        $children = get_terms(array('taxonomy' => PostType::CATEGORY, 'parent' => $term->term_id, 'hide_empty' => false, 'orderby' => 'name'));
        $children = is_array($children) ? $children : array();

        $query = Query::run($settings, Query::state($settings, array()), array(
            'posts_per_page' => $settings['folder_limit'],
            'paged' => 1,
            'tax_query' => array(array('taxonomy' => PostType::CATEGORY, 'field' => 'term_id', 'terms' => array($term->term_id), 'include_children' => false)), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        ));

        if (!$query->have_posts() && empty($children) && !apply_filters('document_engine_show_empty_folders', false)) {
            return;
        }
        $count = self::folder_count($term);
        $open = $settings['open_folders'] ? ' open' : '';
        echo '<details class="dengine-folder"' . $open . '><summary class="dengine-folder__summary">' . document_engine_ui_icon('folder') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            . '<span class="dengine-folder__name">' . esc_html($term->name) . '</span>'
            . '<span class="dengine-folder__count">' . esc_html(number_format_i18n($count)) . '</span></summary>'
            . '<div class="dengine-folder__body">';

        foreach ($children as $child) {
            self::render_folder($child, $settings, $depth + 1);
        }
        if ($query->have_posts()) {
            self::folder_list(array_filter(array_map(array(Document::class, 'get'), $query->posts)), $settings);
            if ($query->found_posts > count($query->posts)) {
                echo '<p class="dengine-folder__more"><a href="' . esc_url(self::url_for($settings, array('cat' => $term->slug))) . '">'
                    /* translators: %d: number of documents */
                    . esc_html(sprintf(__('Show all %d documents', 'document-engine'), $query->found_posts)) . '</a></p>';
            }
        }
        echo '</div></details>';
    }

    /**
     * Documents in a folder including its subfolders (term counts only cover direct items).
     */
    private static function folder_count($term)
    {
        $count = (int)$term->count;
        $children = get_term_children($term->term_id, PostType::CATEGORY);
        if (!is_wp_error($children) && $children) {
            $query = new \WP_Query(array(
                'post_type' => PostType::POST_TYPE,
                'post_status' => 'publish',
                'fields' => 'ids',
                'posts_per_page' => 1,
                'tax_query' => array(array('taxonomy' => PostType::CATEGORY, 'field' => 'term_id', 'terms' => array($term->term_id), 'include_children' => true)), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            ));
            $count = (int)$query->found_posts;
        }
        return $count;
    }

    private static function folder_list($documents, $settings)
    {
        document_engine_get_template('library/folder-list.php', array('settings' => $settings, 'documents' => $documents));
    }

    private static function render_pagination($settings, $state, $pages, $total)
    {
        $current = min($state['page'], $pages);
        echo '<nav class="dengine-pagination" aria-label="' . esc_attr__('Documents pages', 'document-engine') . '">';
        echo '<span class="dengine-pagination__summary">'
            /* translators: 1: current page, 2: total pages, 3: number of documents */
            . esc_html(sprintf(__('Page %1$d of %2$d · %3$s documents', 'document-engine'), $current, $pages, number_format_i18n($total))) . '</span><span class="dengine-pagination__links">';

        $range = array_unique(array_filter(array(1, $current - 1, $current, $current + 1, $pages), function ($n) use ($pages) {
            return $n >= 1 && $n <= $pages;
        }));
        sort($range);

        if ($current > 1) {
            echo '<a class="dengine-pagination__link" data-page="' . esc_attr($current - 1) . '" href="' . esc_url(self::url_for($settings, array_merge($state, array('page' => $current - 1)))) . '" rel="prev">' . esc_html__('Previous', 'document-engine') . '</a>';
        }
        $last = 0;
        foreach ($range as $n) {
            if ($last && $n > $last + 1) {
                echo '<span class="dengine-pagination__gap">…</span>';
            }
            if ($n === $current) {
                echo '<span class="dengine-pagination__link is-current" aria-current="page">' . esc_html(number_format_i18n($n)) . '</span>';
            } else {
                echo '<a class="dengine-pagination__link" data-page="' . esc_attr($n) . '" href="' . esc_url(self::url_for($settings, array_merge($state, array('page' => $n)))) . '">' . esc_html(number_format_i18n($n)) . '</a>';
            }
            $last = $n;
        }
        if ($current < $pages) {
            echo '<a class="dengine-pagination__link" data-page="' . esc_attr($current + 1) . '" href="' . esc_url(self::url_for($settings, array_merge($state, array('page' => $current + 1)))) . '" rel="next">' . esc_html__('Next', 'document-engine') . '</a>';
        }
        echo '</span></nav>';
    }

    /**
     * Page URL (without other library params) used as the form action and link base.
     */
    public static function base_url()
    {
        $base = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        if (defined('REST_REQUEST') && REST_REQUEST && !empty($_REQUEST['_page'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $base = esc_url_raw(wp_unslash($_REQUEST['_page'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        $path = (string)(wp_parse_url($base, PHP_URL_PATH) ?: '/');
        $home_path = (string)wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($home_path !== '/' && strpos($path, $home_path) === 0) {
            $path = '/' . ltrim(substr($path, strlen($home_path)), '/');
        }
        return home_url($path);
    }

    /**
     * Link to this page with the given library state.
     */
    public static function url_for($settings, $state)
    {
        $p = $settings['id'] . '_';
        $url = self::current_url_without($p);
        $map = Query::state_params();
        $args = array();
        foreach ($map as $key => $param) {
            if (!empty($state[$key]) && !($key === 'page' && (int)$state[$key] === 1)) {
                $args[$p . $param] = $state[$key];
            }
        }
        return add_query_arg($args, $url) . '#dengine-library-' . $settings['id'];
    }

    private static function current_url_without($prefix)
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        if (defined('REST_REQUEST') && REST_REQUEST && !empty($_REQUEST['_page'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $uri = esc_url_raw(wp_unslash($_REQUEST['_page'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        $parts = wp_parse_url($uri);
        $query = array();
        if (!empty($parts['query'])) {
            wp_parse_str($parts['query'], $query);
        }
        foreach ($query as $key => $value) {
            // Drop this library's own params, and nested/array values we can't safely echo back.
            if (strpos((string)$key, $prefix) === 0 || !is_scalar($value)) {
                unset($query[$key]);
            }
        }
        $path = isset($parts['path']) ? $parts['path'] : '/';
        $home_path = (string)wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($home_path !== '/' && strpos($path, $home_path) === 0) {
            $path = '/' . ltrim(substr($path, strlen($home_path)), '/');
        }
        return add_query_arg(array_map('rawurlencode', $query), home_url($path));
    }

    /**
     * Where a document title should link.
     */
    public static function title_url(Document $document, $settings)
    {
        if ($settings['link_to'] === 'none') {
            return '';
        }
        if ($settings['link_to'] === 'file' || get_option('document_engine_single_pages', 'yes') !== 'yes') {
            return $document->get_download_url();
        }
        return get_permalink($document->get_post());
    }

    /**
     * Markup for one table cell or grid meta value.
     */
    public static function cell($column, Document $document, $settings)
    {
        $html = '';
        switch ($column) {
            case 'thumbnail':
                $html = '<span class="dengine-thumb">' . $document->get_thumbnail_html('thumbnail') . '</span>';
                break;
            case 'title':
                $url = self::title_url($document, $settings);
                $title = esc_html($document->get_title());
                $icon = document_engine_file_icon($document->get_extension(), 'dengine-icon--inline');
                $html = '<span class="dengine-title">' . $icon . '<span class="dengine-title__text">'
                    . ($url ? '<a class="dengine-title__link" href="' . esc_url($url) . '">' . $title . '</a>' : '<span class="dengine-title__link">' . $title . '</span>');
                if (!FileServer::can_access($document, null, 'list')) {
                    $html .= ' <span class="dengine-pill dengine-pill--locked">' . document_engine_ui_icon('lock') . esc_html__('Members only', 'document-engine') . '</span>';
                }
                $meta = array_filter(array($document->get_type_label(), $document->get_size_label(), self::short_date($document)));
                $html .= '<span class="dengine-title__meta">' . esc_html(implode(' · ', $meta)) . '</span></span></span>';
                break;
            case 'excerpt':
                $html = esc_html(wp_trim_words(get_the_excerpt($document->get_post()), 20));
                break;
            case 'category':
            case 'tag':
                $terms = get_the_terms($document->get_post(), $column === 'category' ? PostType::CATEGORY : PostType::TAG);
                $links = array();
                foreach (is_array($terms) ? $terms : array() as $term) {
                    $links[] = '<a class="dengine-chip" href="' . esc_url(self::url_for($settings, array($column === 'category' ? 'cat' : 'tag' => $term->slug))) . '" data-filter="' . esc_attr($column === 'category' ? 'cat' : 'tag') . '" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</a>';
                }
                $html = implode(' ', $links);
                break;
            case 'type':
                $html = '<span class="dengine-type dengine-type--' . esc_attr(document_engine_file_type_group($document->get_extension())) . '">' . esc_html($document->get_type_label()) . '</span>';
                break;
            case 'size':
                $html = esc_html($document->get_size_label());
                break;
            case 'date':
                $html = '<time datetime="' . esc_attr(get_the_date('c', $document->get_post())) . '">' . esc_html(self::short_date($document)) . '</time>';
                break;
            case 'modified':
                $html = '<time datetime="' . esc_attr(get_the_modified_date('c', $document->get_post())) . '">' . esc_html(self::short_date($document, true)) . '</time>';
                break;
            case 'author':
                $html = esc_html(get_the_author_meta('display_name', $document->get_post()->post_author));
                break;
            case 'downloads':
                $html = esc_html(number_format_i18n($document->get_download_count()));
                break;
            case 'actions':
                $html = self::actions($document);
                break;
        }
        return apply_filters('document_engine_library_cell', $html, $column, $document, $settings);
    }

    /**
     * View / download buttons for a document.
     */
    public static function actions(Document $document, $labels = false, $show_view = true)
    {
        if (!$document->has_file()) {
            return '';
        }
        $title = $document->get_title();
        $out = '<span class="dengine-actions">';
        $popup = get_option('document_engine_library_preview', 'page') === 'popup' && self::previewable($document);
        if ($show_view && ($document->is_pdf() || $popup) && get_option('document_engine_library_view_button', 'yes') === 'yes') {
            $view_url = get_option('document_engine_single_pages', 'yes') === 'yes' ? get_permalink($document->get_post()) : $document->get_download_url(array('inline' => 1));
            if ($popup) {
                wp_enqueue_script('document-engine-viewer');
            }
            $out .= '<a class="dengine-button dengine-button--ghost dengine-button--sm" href="' . esc_url($view_url) . '"' . ($popup ? ' data-dengine-preview="' . esc_attr($document->get_id()) . '"' : '') . ' aria-label="' . esc_attr(sprintf(
                /* translators: %s: document title */
                __('View %s', 'document-engine'),
                $title
            )) . '">' . document_engine_ui_icon('view') . '<span class="dengine-button__label">' . esc_html__('View', 'document-engine') . '</span></a>';
        }
        $external = $document->is_external();
        $label = $external ? __('Open', 'document-engine') : __('Download', 'document-engine');
        $out .= '<a class="dengine-button dengine-button--sm" href="' . esc_url($document->get_download_url()) . '"' . ($document->get_behavior() === 'inline' || $external ? ' target="_blank" rel="noopener"' : '') . ' data-dengine-download="' . esc_attr($document->get_id()) . '" aria-label="' . esc_attr(sprintf(
            /* translators: 1: Download/Open, 2: document title */
            __('%1$s %2$s', 'document-engine'),
            $label,
            $title
        )) . '">'
            . document_engine_ui_icon($external ? 'external' : 'download')
            . '<span class="dengine-button__label">' . esc_html($label) . '</span></a>';
        $out .= '</span>';
        return apply_filters('document_engine_document_actions', $out, $document);
    }

    /**
     * Files the preview popup can show: PDFs in the viewer, images, audio and video natively.
     */
    public static function previewable(Document $document)
    {
        if (!$document->has_file() || ($document->is_external() && !$document->is_pdf())) {
            return false;
        }
        return in_array(document_engine_file_type_group($document->get_extension()), array('pdf', 'image', 'audio', 'video'), true)
            && !in_array($document->get_extension(), array('psd', 'ai', 'eps', 'tif', 'tiff', 'avi', 'mkv', 'flac'), true);
    }

    /**
     * Compact date for tables and cards (filter to use the site's own format).
     */
    public static function short_date(Document $document, $modified = false)
    {
        $format = apply_filters('document_engine_short_date_format', 'M j, Y');
        return $modified ? get_the_modified_date($format, $document->get_post()) : get_the_date($format, $document->get_post());
    }

    /**
     * Download card/button for one document (block `document-engine/download`).
     */
    public static function render_download($attributes)
    {
        $document = Document::get(absint(isset($attributes['documentId']) ? $attributes['documentId'] : 0));
        if (!$document || ($document->get_post()->post_status !== 'publish' && !current_user_can('read_post', $document->get_id()))) {
            return current_user_can('edit_dengine_documents') ? '<p class="dengine-notice">' . esc_html__('Choose a published document for this download block.', 'document-engine') . '</p>' : '';
        }
        document_engine_enqueue_frontend();

        ob_start();
        document_engine_get_template('document/card.php', array(
            'document' => $document,
            // "variant" (2.0); a plain-string "style" is accepted from early 2.0 builds.
            'style' => (isset($attributes['variant']) ? $attributes['variant'] : (isset($attributes['style']) && is_string($attributes['style']) ? $attributes['style'] : 'card')) === 'button' ? 'button' : 'card',
            'label' => !empty($attributes['label']) ? $attributes['label'] : __('Download', 'document-engine'),
            'show_meta' => !isset($attributes['showMeta']) || (bool)$attributes['showMeta'],
        ));
        return ob_get_clean();
    }
}
