<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Document admin: list columns, file facts, Media Library → Documents, classic editor box.
 */
class Documents
{
    /**
     * File type and size must be recorded however a document is saved: the block editor (REST),
     * WP-CLI, imports and front-end forms are not admin requests, so these hooks load everywhere.
     */
    public static function init_sync()
    {
        $type = PostType::POST_TYPE;
        add_action("save_post_{$type}", array(__CLASS__, 'save_post'), 20, 2);
        add_action("rest_after_insert_{$type}", array(__CLASS__, 'after_rest_insert'));
    }

    /**
     * Records the missing file type and size of documents saved before 2.0.6 (the block editor skipped it).
     */
    public static function repair_file_meta()
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} f ON f.post_id = p.ID AND f.meta_key = %s AND f.meta_value > 0
             LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = %s
             WHERE p.post_type = %s AND (t.meta_value IS NULL OR t.meta_value = '') LIMIT 500",
            Document::META_FILE_ID,
            Document::META_FILE_TYPE,
            PostType::POST_TYPE
        ));
        foreach ($ids as $id) {
            $document = Document::get((int)$id);
            if ($document) {
                $document->sync_file_meta();
            }
        }
        if ($ids) {
            PostType::flush_library_cache();
        }
        return count($ids);
    }

    public static function init()
    {
        $type = PostType::POST_TYPE;

        add_filter("manage_{$type}_posts_columns", array(__CLASS__, 'columns'));
        add_action("manage_{$type}_posts_custom_column", array(__CLASS__, 'column'), 10, 2);
        add_filter("manage_edit-{$type}_sortable_columns", array(__CLASS__, 'sortable'));
        add_action('pre_get_posts', array(__CLASS__, 'sort_by_downloads'));

        add_action('add_meta_boxes', array(__CLASS__, 'meta_box'));

        add_filter('bulk_actions-upload', array(__CLASS__, 'media_bulk_action'));
        add_filter('handle_bulk_actions-upload', array(__CLASS__, 'handle_media_bulk'), 10, 3);
        add_filter('media_row_actions', array(__CLASS__, 'media_row_action'), 10, 2);
        add_action('admin_notices', array(__CLASS__, 'notices'));
        add_filter('post_row_actions', array(__CLASS__, 'row_actions'), 10, 2);

        add_filter('enter_title_here', array(__CLASS__, 'title_placeholder'), 10, 2);
        add_filter('default_hidden_columns', array(__CLASS__, 'hidden_columns'), 10, 2);
        add_action('restrict_manage_posts', array(__CLASS__, 'filters'), 10, 2);
        add_action('pre_get_posts', array(__CLASS__, 'apply_filters_to_list'));
        add_filter('list_table_primary_column', array(__CLASS__, 'primary_column'), 10, 2);
    }

    public static function primary_column($column, $screen)
    {
        return $screen === 'edit-' . PostType::POST_TYPE ? 'title' : $column;
    }

    /**
     * Category and file type filters above the documents list.
     */
    public static function filters($post_type, $which)
    {
        if ($post_type !== PostType::POST_TYPE || $which !== 'top') {
            return;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- list filters.
        $category = isset($_GET[PostType::CATEGORY]) ? sanitize_title(wp_unslash($_GET[PostType::CATEGORY])) : '';
        $type = isset($_GET['dengine_type']) ? sanitize_key(wp_unslash($_GET['dengine_type'])) : '';
        // phpcs:enable
        if (wp_count_terms(array('taxonomy' => PostType::CATEGORY, 'hide_empty' => false))) {
            echo '<label class="screen-reader-text" for="dengine-category-filter">' . esc_html__('Filter by category', 'document-engine') . '</label>';
            wp_dropdown_categories(array(
                'id' => 'dengine-category-filter',
                'taxonomy' => PostType::CATEGORY,
                'name' => PostType::CATEGORY,
                'value_field' => 'slug',
                'show_option_all' => __('All categories', 'document-engine'),
                'hide_empty' => false,
                'hierarchical' => true,
                'selected' => $category,
                'orderby' => 'name',
            ));
        }
        echo '<label class="screen-reader-text" for="dengine-type-filter">' . esc_html__('Filter by file type', 'document-engine') . '</label>';
        echo '<select name="dengine_type" id="dengine-type-filter"><option value="">' . esc_html__('All file types', 'document-engine') . '</option>';
        foreach (document_engine_file_type_groups_labels() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($type, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        do_action('document_engine_admin_list_filters');
    }

    public static function apply_filters_to_list($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== PostType::POST_TYPE) {
            return;
        }
        $type = isset($_GET['dengine_type']) ? sanitize_key(wp_unslash($_GET['dengine_type'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($type === '' || !array_key_exists($type, document_engine_file_type_groups_labels())) {
            return;
        }
        $meta_query = (array)$query->get('meta_query');
        if ($type === 'other') {
            $known = array();
            foreach (array_keys(document_engine_file_type_groups_labels()) as $group) {
                if ($group !== 'other') {
                    $known = array_merge($known, \MatrixAddons\DocumentEngine\Library\Query::extensions_for_group($group));
                }
            }
            $meta_query[] = array('key' => Document::META_FILE_TYPE, 'value' => $known, 'compare' => 'NOT IN');
        } else {
            $meta_query[] = array('key' => Document::META_FILE_TYPE, 'value' => \MatrixAddons\DocumentEngine\Library\Query::extensions_for_group($type), 'compare' => 'IN');
        }
        $query->set('meta_query', $meta_query); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }

    public static function columns($columns)
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['dengine_file'] = __('File', 'document-engine');
            }
        }
        $date = isset($new['date']) ? $new['date'] : null;
        unset($new['date']);
        $new['dengine_downloads'] = __('Activity', 'document-engine');
        if (isset($new['taxonomy-' . PostType::CATEGORY])) {
            $new['taxonomy-' . PostType::CATEGORY] = __('Category', 'document-engine');
        }
        if (isset($new['taxonomy-' . PostType::TAG])) {
            $new['taxonomy-' . PostType::TAG] = __('Tags', 'document-engine');
        }
        $new['dengine_updated'] = __('Updated', 'document-engine');
        if ($date) {
            $new['date'] = $date;
        }
        $new = apply_filters('document_engine_admin_columns', $new);
        // Dates always sit at the end.
        foreach (array('dengine_updated', 'date') as $key) {
            if (isset($new[$key])) {
                $label = $new[$key];
                unset($new[$key]);
                $new[$key] = $label;
            }
        }
        return $new;
    }

    /**
     * Keep the list readable by default; everything stays available under Screen Options.
     */
    public static function hidden_columns($hidden, $screen)
    {
        if ($screen && $screen->id === 'edit-' . PostType::POST_TYPE) {
            $hidden = array_merge($hidden, array('author', 'taxonomy-' . PostType::TAG, 'dengine_dates', 'date'));
        }
        return $hidden;
    }

    public static function column($column, $post_id)
    {
        $document = Document::get($post_id);
        if (!$document) {
            return;
        }
        if ($column === 'dengine_file') {
            if (!$document->has_file()) {
                echo '<span class="dengine-a-pill dengine-a-pill--danger">' . esc_html__('No file', 'document-engine') . '</span>';
            } else {
                $meta = $document->is_external()
                    ? (string)wp_parse_url($document->get_external_url(), PHP_URL_HOST)
                    : trim($document->get_type_label() . ' · ' . $document->get_size_label(), ' ·');
                echo '<span class="dengine-list-file">' . document_engine_file_icon($document->get_extension(), 'dengine-icon--inline') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    . '<span class="dengine-list-file__meta">' . esc_html($meta) . '</span></span>';
            }
        } elseif ($column === 'dengine_downloads') {
            echo '<span class="dengine-list-num">' . wp_kses(sprintf(
                /* translators: %s: number of downloads */
                _n('%s download', '%s downloads', $document->get_download_count(), 'document-engine'),
                '<strong>' . esc_html(number_format_i18n($document->get_download_count())) . '</strong>'
            ), array('strong' => array())) . '</span>';
        } elseif ($column === 'dengine_updated') {
            $post = $document->get_post();
            $states = array('publish' => __('Published', 'document-engine'), 'future' => __('Scheduled', 'document-engine'), 'draft' => __('Draft', 'document-engine'), 'pending' => __('Pending review', 'document-engine'), 'private' => __('Private', 'document-engine'));
            echo '<span class="dengine-list-date" title="' . esc_attr(get_the_modified_date(get_option('date_format') . ' ' . get_option('time_format'), $post)) . '">' . esc_html(get_the_modified_date(apply_filters('document_engine_short_date_format', 'M j, Y'), $post)) . '</span>'
                . '<span class="dengine-list-sub">' . esc_html(isset($states[$post->post_status]) ? $states[$post->post_status] : $post->post_status) . '</span>';
        }
        do_action('document_engine_admin_column', $column, $document);
    }

    public static function sortable($columns)
    {
        $columns['dengine_downloads'] = 'dengine_downloads';
        $columns['dengine_updated'] = array('modified', true);
        return $columns;
    }

    public static function sort_by_downloads($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('orderby') !== 'dengine_downloads') {
            return;
        }
        $query->set('meta_query', array(
            'relation' => 'OR',
            'dengine_downloads' => array('key' => Document::META_DOWNLOADS, 'compare' => 'EXISTS', 'type' => 'NUMERIC'),
            array('key' => Document::META_DOWNLOADS, 'compare' => 'NOT EXISTS'),
        ));
        $query->set('orderby', 'dengine_downloads');
    }

    public static function row_actions($actions, $post)
    {
        if ($post->post_type !== PostType::POST_TYPE) {
            return $actions;
        }
        $document = Document::get($post);
        if ($document && $document->has_file()) {
            $actions['dengine_download'] = '<a href="' . esc_url($document->get_download_url()) . '">' . esc_html__('Download', 'document-engine') . '</a>';
        }
        $actions['dengine_shortcode'] = '<span class="dengine-shortcode" title="' . esc_attr__('Shortcode', 'document-engine') . '"><code>[document_engine_document id="' . absint($post->ID) . '"]</code></span>';
        return $actions;
    }

    public static function title_placeholder($text, $post)
    {
        return $post->post_type === PostType::POST_TYPE ? __('Document title (filled in from the file name if left empty)', 'document-engine') : $text;
    }

    /**
     * Keeps file type/size and the download counter row current, and names untitled documents after their file.
     */
    public static function save_post($post_id, $post)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Classic editor meta box.
        if (isset($_POST['dengine_document_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dengine_document_nonce'])), 'dengine_document_save') && current_user_can('edit_post', $post_id)) {
            update_post_meta($post_id, Document::META_FILE_ID, isset($_POST['dengine_file_id']) ? absint($_POST['dengine_file_id']) : 0);
            update_post_meta($post_id, Document::META_FILE_URL, isset($_POST['dengine_file_url']) ? esc_url_raw(wp_unslash($_POST['dengine_file_url'])) : '');
            $behavior = isset($_POST['dengine_link_behavior']) ? sanitize_key($_POST['dengine_link_behavior']) : '';
            update_post_meta($post_id, Document::META_BEHAVIOR, in_array($behavior, array('download', 'inline'), true) ? $behavior : '');
        }

        self::sync($post_id);
    }

    public static function after_rest_insert($post)
    {
        self::sync($post->ID);
    }

    private static function sync($post_id)
    {
        $document = Document::get($post_id);
        if (!$document) {
            return;
        }
        $document->sync_file_meta();

        if (get_post_meta($post_id, Document::META_DOWNLOADS, true) === '') {
            add_post_meta($post_id, Document::META_DOWNLOADS, 0, true);
        }

        $post = get_post($post_id);
        if ($post && trim($post->post_title) === '' && $document->get_file_id() > 0) {
            $title = self::title_from_file((string)get_attached_file($document->get_file_id()));
            if ($title !== '') {
                remove_action('save_post_' . PostType::POST_TYPE, array(__CLASS__, 'save_post'), 20);
                wp_update_post(array('ID' => $post_id, 'post_title' => $title));
                add_action('save_post_' . PostType::POST_TYPE, array(__CLASS__, 'save_post'), 20, 2);
            }
        }

        do_action('document_engine_save_document', $document);
    }

    public static function title_from_file($path)
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $name = preg_replace('/[-_]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim((string)$name));
        return $name === '' ? '' : ucfirst($name);
    }

    public static function meta_box()
    {
        add_meta_box(
            'dengine-document-file',
            __('Document file', 'document-engine'),
            array(__CLASS__, 'render_meta_box'),
            PostType::POST_TYPE,
            'normal',
            'high',
            array('__back_compat_meta_box' => true)
        );
    }

    public static function render_meta_box($post)
    {
        $document = Document::get($post);
        wp_enqueue_media();
        wp_nonce_field('dengine_document_save', 'dengine_document_nonce');
        $file_id = $document ? $document->get_file_id() : 0;
        $behavior = (string)get_post_meta($post->ID, Document::META_BEHAVIOR, true);
        ?>
        <p>
            <input type="hidden" id="dengine_file_id" name="dengine_file_id" value="<?php echo esc_attr($file_id); ?>">
            <strong id="dengine_file_name"><?php echo $file_id ? esc_html(wp_basename((string)get_attached_file($file_id))) : esc_html__('No file selected', 'document-engine'); ?></strong>
            <button type="button" class="button" id="dengine_choose_file"><?php esc_html_e('Upload or choose file', 'document-engine'); ?></button>
        </p>
        <p>
            <label for="dengine_file_url"><?php esc_html_e('…or link to a file elsewhere', 'document-engine'); ?></label><br>
            <input type="url" class="widefat" id="dengine_file_url" name="dengine_file_url" value="<?php echo esc_attr($document ? $document->get_external_url() : ''); ?>" placeholder="https://">
        </p>
        <p>
            <label for="dengine_link_behavior"><?php esc_html_e('When visitors click Download', 'document-engine'); ?></label><br>
            <select id="dengine_link_behavior" name="dengine_link_behavior">
                <option value=""><?php esc_html_e('Site default', 'document-engine'); ?></option>
                <option value="download" <?php selected($behavior, 'download'); ?>><?php esc_html_e('Download the file', 'document-engine'); ?></option>
                <option value="inline" <?php selected($behavior, 'inline'); ?>><?php esc_html_e('Open in the browser', 'document-engine'); ?></option>
            </select>
        </p>
        <script>
            jQuery(function ($) {
                var frame;
                $('#dengine_choose_file').on('click', function (e) {
                    e.preventDefault();
                    frame = frame || wp.media({title: <?php echo wp_json_encode(__('Choose document file', 'document-engine')); ?>, multiple: false});
                    frame.off('select').on('select', function () {
                        var file = frame.state().get('selection').first().toJSON();
                        $('#dengine_file_id').val(file.id);
                        $('#dengine_file_name').text(file.filename);
                        if (!$('#title').val()) {
                            $('#title').val(file.title).trigger('input');
                            $('#title-prompt-text').addClass('screen-reader-text');
                        }
                    });
                    frame.open();
                });
            });
        </script>
        <?php
    }

    public static function media_bulk_action($actions)
    {
        if (current_user_can('publish_dengine_documents')) {
            $actions['dengine_create_documents'] = __('Create documents', 'document-engine');
        }
        return $actions;
    }

    public static function media_row_action($actions, $post)
    {
        if (current_user_can('publish_dengine_documents')) {
            $actions['dengine_create_document'] = '<a href="' . esc_url(admin_url('post-new.php?post_type=' . PostType::POST_TYPE . '&dengine_file=' . absint($post->ID))) . '">' . esc_html__('Create document', 'document-engine') . '</a>';
        }
        return $actions;
    }

    public static function handle_media_bulk($redirect, $action, $ids)
    {
        if ($action !== 'dengine_create_documents' || !current_user_can('publish_dengine_documents')) {
            return $redirect;
        }
        $created = 0;
        foreach (array_map('absint', (array)$ids) as $attachment_id) {
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                continue;
            }
            $post_id = self::create_from_attachment($attachment_id);
            if ($post_id) {
                $created++;
            }
        }
        return add_query_arg('dengine_created', $created, $redirect);
    }

    /**
     * Creates a published document for a Media Library file.
     *
     * @return int New document ID or 0.
     */
    public static function create_from_attachment($attachment_id, $args = array())
    {
        $attachment = get_post($attachment_id);
        if (!$attachment) {
            return 0;
        }
        $file = (string)get_attached_file($attachment_id);
        // Media Library titles default to the raw file name; tidy those up.
        $title = $attachment->post_title;
        if ($title === '' || sanitize_title($title) === sanitize_title(pathinfo($file, PATHINFO_FILENAME))) {
            $title = self::title_from_file($file);
        }
        $post_id = wp_insert_post(array_merge(array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $attachment->post_excerpt,
            'post_content' => $attachment->post_content,
            'meta_input' => array(
                Document::META_FILE_ID => $attachment_id,
                Document::META_DOWNLOADS => 0,
            ),
        ), $args), true);

        if (is_wp_error($post_id)) {
            return 0;
        }
        $document = Document::get($post_id);
        if ($document) {
            $document->sync_file_meta();
            do_action('document_engine_document_created_from_attachment', $document, $attachment_id);
        }
        return (int)$post_id;
    }

    public static function notices()
    {
        if (isset($_GET['dengine_created'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $count = absint($_GET['dengine_created']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(
                /* translators: %d: number of documents */
                _n('%d document created.', '%d documents created.', $count, 'document-engine'),
                $count
            )) . ' <a href="' . esc_url(admin_url('edit.php?post_type=' . PostType::POST_TYPE)) . '">' . esc_html__('View documents', 'document-engine') . '</a></p></div>';
        }
    }
}
