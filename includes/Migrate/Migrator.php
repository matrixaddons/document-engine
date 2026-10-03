<?php

namespace MatrixAddons\DocumentEngine\Migrate;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Copies documents from another plugin into Document Engine. Read-only on the source:
 * nothing of the old plugin is changed, so it can stay installed until the owner is happy.
 * Safe to run twice: items already moved are skipped.
 */
class Migrator
{
    const META_FROM = '_dengine_migrated_from';
    const OPTION_TERMS = 'document_engine_migrate_terms';
    const OPTION_DONE = 'document_engine_migrated_sources';

    /**
     * What a migration will do, before anything is written.
     */
    public static function preview($key)
    {
        global $wpdb;
        $source = Sources::get($key);
        $total = Sources::count($key);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $done = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s AND p.post_type = %s",
            self::META_FROM,
            $wpdb->esc_like($key . ':') . '%',
            PostType::POST_TYPE
        ));

        $cache_key = 'dengine_migrate_preview_' . $key;
        $details = get_transient($cache_key);
        if (!is_array($details)) {
            $details = array('restricted' => 0, 'locked' => 0, 'passwords' => 0, 'multi' => 0, 'checked' => 0);
            $after = 0;
            // Sample up to 300 items for the details; the totals are exact.
            while ($details['checked'] < 300 && ($ids = Sources::ids($key, $after, 100))) {
                _prime_post_caches($ids, false, true);
                foreach ($ids as $id) {
                    $item = Sources::item($key, $id);
                    $after = $id;
                    $details['checked']++;
                    if (!$item) {
                        continue;
                    }
                    $details['restricted'] += $item['access'] ? 1 : 0;
                    $details['locked'] += !empty($item['locked']) ? 1 : 0;
                    $details['passwords'] += $item['password'] !== '' ? 1 : 0;
                    $details['multi'] += $item['extra_files'] > 0 ? 1 : 0;
                }
            }
            set_transient($cache_key, $details, 10 * MINUTE_IN_SECONDS);
        }
        $restricted = $details['restricted'];
        $passwords = $details['passwords'];
        $multi = $details['multi'];
        $checked = $details['checked'];

        return array(
            'label' => $source ? $source['label'] : $key,
            'total' => $total,
            'done' => $done,
            'remaining' => max(0, $total - $done),
            'restricted' => $restricted,
            'passwords' => $passwords,
            'multi' => $multi,
            'locked' => $details['locked'],
            'sampled' => $checked < $total,
            'active' => self::source_active($key),
        );
    }

    public static function source_active($key)
    {
        $source = Sources::get($key);
        if (!$source) {
            return false;
        }
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach ((array)$source['plugin'] as $file) {
            if (is_plugin_active($file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Moves one batch. Returns counts, a log and the cursor for the next call.
     *
     * @param array $options skip_restricted (bool)
     */
    public static function run_batch($key, $after_id, $options = array(), $limit = 10)
    {
        $result = array('imported' => 0, 'skipped' => 0, 'failed' => 0, 'log' => array(), 'next' => (int)$after_id, 'done' => false);
        if (!Sources::get($key)) {
            $result['done'] = true;
            return $result;
        }

        // One batch at a time per source (two tabs or a double click must not create duplicates).
        $lock = 'document_engine_migrate_lock_' . $key;
        if (!add_option($lock, time(), '', false)) {
            if ((int)get_option($lock) > time() - 5 * MINUTE_IN_SECONDS) {
                $result['busy'] = true;
                return $result;
            }
            update_option($lock, time(), false);
        }
        if (!defined('DOCUMENT_ENGINE_IMPORTING')) {
            define('DOCUMENT_ENGINE_IMPORTING', true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }
        wp_defer_term_counting(true);

        $ids = Sources::ids($key, $after_id, $limit);
        foreach ($ids as $id) {
            $result['next'] = $id;
            $item = Sources::item($key, $id);
            if (!$item) {
                continue;
            }
            $outcome = self::import_item($key, $item, $options);
            $result[$outcome['status']]++;
            if ($outcome['message'] !== '') {
                $result['log'][] = array('status' => $outcome['status'], 'title' => $item['title'] !== '' ? $item['title'] : '#' . $id, 'message' => $outcome['message'], 'edit' => $outcome['edit']);
            }
        }

        wp_defer_term_counting(false);
        delete_option($lock);
        delete_transient('dengine_migrate_preview_' . $key);

        if (count($ids) < $limit) {
            $result['done'] = true;
            $done = (array)get_option(self::OPTION_DONE, array());
            $done[$key] = time();
            update_option(self::OPTION_DONE, $done, false);
        }
        return $result;
    }

    public static function existing($key, $source_id)
    {
        $found = get_posts(array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => array_merge(array_keys(get_post_stati()), array('trash')),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => self::META_FROM, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => $key . ':' . (int)$source_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- exact lookup; filters (e.g. access rules) must not hide rows.
        ));
        return $found ? (int)$found[0] : 0;
    }

    /**
     * @return array status (imported|skipped|failed), message, edit (URL)
     */
    public static function import_item($key, $item, $options = array())
    {
        $existing = self::existing($key, $item['id']);
        if ($existing) {
            return array('status' => 'skipped', 'message' => '', 'edit' => '');
        }
        if ($item['access'] && !empty($options['skip_restricted'])) {
            return array('status' => 'skipped', 'message' => __('Skipped: only for members of the old site.', 'document-engine'), 'edit' => '');
        }

        $notes = array();
        $locked = !empty($item['locked']);
        $can_protect = has_filter('document_engine_migrate_access') && has_filter('document_engine_migrate_private_dir');
        // A members-only or locked (paid, email, captcha…) file must never land in the public Media Library.
        // With an add-on that has private storage it is copied there; otherwise it is left where it is.
        $mode = 'public';
        if ($item['access'] || $locked) {
            $mode = $item['access'] && !$locked && $can_protect ? 'private' : 'none';
        }
        $file = self::resolve_file($item, $notes, $mode);
        if ($locked) {
            // The lock note below says it all.
            $notes = array();
        }
        if ($file === null && !$notes && !$locked) {
            $notes[] = __('No file was found, so it was imported without one.', 'document-engine');
        }
        if ($item['extra_files'] > 0) {
            /* translators: %d: number of files */
            $notes[] = sprintf(_n('%d more file was not imported; only the main file was.', '%d more files were not imported; only the main file was.', $item['extra_files'], 'document-engine'), $item['extra_files']);
        }

        // Start as a draft so nothing is public until access rules and the file are in place.
        $post_id = wp_insert_post(wp_slash(array(
            'post_type' => PostType::POST_TYPE,
            'post_status' => 'draft',
            // Titles from another plugin's data are plain text (migrations run as an admin, who may post HTML).
            'post_title' => sanitize_text_field(wp_strip_all_tags((string)$item['title'])),
            'post_content' => $item['content'],
            'post_excerpt' => $item['excerpt'],
            'post_author' => $item['author'] ?: get_current_user_id(),
            'post_date' => $item['date'],
            'post_date_gmt' => $item['date_gmt'],
            'post_password' => $item['password'],
            'post_name' => isset($item['slug']) ? $item['slug'] : '',
            'menu_order' => $item['menu_order'],
            'meta_input' => array(
                self::META_FROM => $key . ':' . (int)$item['id'],
                Document::META_DOWNLOADS => max(0, (int)$item['downloads']),
            ),
        )), true);

        if (is_wp_error($post_id)) {
            return array('status' => 'failed', 'message' => $post_id->get_error_message(), 'edit' => '');
        }

        if ($file && isset($file['attachment_id'])) {
            update_post_meta($post_id, Document::META_FILE_ID, (int)$file['attachment_id']);
        } elseif ($file && isset($file['url'])) {
            update_post_meta($post_id, Document::META_FILE_URL, esc_url_raw($file['url']));
        }
        if ($item['thumbnail_id'] && get_post_type($item['thumbnail_id']) === 'attachment') {
            set_post_thumbnail($post_id, $item['thumbnail_id']);
        }

        $source = Sources::get($key);
        $categories = self::map_terms($key, $item['categories'], $source['category'], PostType::CATEGORY);
        if ($categories) {
            wp_set_object_terms($post_id, $categories, PostType::CATEGORY);
        }
        $tags = self::map_terms($key, $item['tags'], $source['tag'], PostType::TAG);
        if ($tags) {
            wp_set_object_terms($post_id, $tags, PostType::TAG);
        }

        $document = Document::get($post_id);
        if ($document) {
            $document->sync_file_meta();
        }

        $status = in_array($item['status'], array('publish', 'private', 'pending', 'future'), true) ? $item['status'] : 'draft';
        $visible = array('publish', 'future');
        if ($item['access']) {
            /**
             * Add-ons that enforce access rules apply the old restriction and return true.
             * Without one, restricted items stay drafts so they are never made public by accident.
             */
            $applied = (bool)apply_filters('document_engine_migrate_access', false, $post_id, $item['access'], $key);
            if (!$applied && in_array($status, $visible, true)) {
                $status = 'draft';
                $notes[] = __('Kept as a draft: it was for members only on the old plugin.', 'document-engine');
            }
        }
        if (!$locked && !empty($item['file']) && !$file && in_array($status, $visible, true)) {
            $status = 'draft';
            $notes[] = __('Kept as a draft because its file could not be moved.', 'document-engine');
        }
        if ($locked && in_array($status, $visible, true)) {
            $status = 'draft';
            $notes[] = sprintf(
                /* translators: %s: list of locks such as "paid, email" */
                __('Kept as a draft without its file: on the old plugin it had a lock (%s). Set who may open it, attach the file and publish.', 'document-engine'),
                implode(', ', (array)$item['locked'])
            );
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => $status,
            'post_date' => $item['date'],
            'post_date_gmt' => $item['date_gmt'],
        ));

        // Keep the original "last updated" date so migrated items don't flood "Recently updated" lists.
        if (!empty($item['modified']) && !empty($item['modified_gmt'])) {
            global $wpdb;
            $wpdb->update($wpdb->posts, array('post_modified' => $item['modified'], 'post_modified_gmt' => $item['modified_gmt']), array('ID' => $post_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            clean_post_cache($post_id);
        }

        if ($document) {
            // Lets add-ons react as if the document was saved in the editor (e.g. move files to protected storage).
            do_action('document_engine_save_document', Document::get($post_id));
        }
        do_action('document_engine_migrated_document', $post_id, $item, $key);

        return array('status' => 'imported', 'message' => implode(' ', $notes), 'edit' => $notes ? (string)get_edit_post_link($post_id, 'raw') : '');
    }

    /**
     * Finds or creates the matching category/tag, keeping parents, and remembers the mapping.
     *
     * @return int[]
     */
    public static function map_terms($key, $source_ids, $source_taxonomy, $taxonomy)
    {
        $map = (array)get_option(self::OPTION_TERMS, array());
        $out = array();
        foreach ($source_ids as $source_id) {
            $id = self::map_term($key, (int)$source_id, $source_taxonomy, $taxonomy, $map, 0);
            if ($id) {
                $out[] = $id;
            }
        }
        update_option(self::OPTION_TERMS, $map, false);
        return array_values(array_unique($out));
    }

    private static function map_term($key, $source_id, $source_taxonomy, $taxonomy, &$map, $depth)
    {
        $map_key = $key . ':' . $source_taxonomy . ':' . $source_id;
        if (isset($map[$map_key]) && term_exists((int)$map[$map_key], $taxonomy)) {
            return (int)$map[$map_key];
        }
        $term = Sources::term($source_id, $source_taxonomy);
        if (!$term || $depth > 10) {
            return 0;
        }
        $parent = 0;
        if ((int)$term->parent > 0 && is_taxonomy_hierarchical($taxonomy)) {
            $parent = self::map_term($key, (int)$term->parent, $source_taxonomy, $taxonomy, $map, $depth + 1);
        }

        $found = term_exists($term->name, $taxonomy, $parent ?: null);
        if (is_array($found)) {
            $id = (int)$found['term_id'];
        } else {
            $created = wp_insert_term($term->name, $taxonomy, array(
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $parent,
            ));
            if (is_wp_error($created) && $created->get_error_code() === 'duplicate_term_slug') {
                $created = wp_insert_term($term->name, $taxonomy, array('description' => $term->description, 'parent' => $parent));
            }
            if (is_wp_error($created)) {
                return 0;
            }
            $id = (int)$created['term_id'];
        }
        $map[$map_key] = $id;
        return $id;
    }

    /**
     * Works out where the file lives: an existing attachment, a file on this server
     * (copied into the Media Library), or an external link.
     *
     * @return array|null attachment_id or url
     */
    public static function resolve_file($item, &$notes, $mode = 'public')
    {
        $file = $item['file'];
        $uploads = wp_upload_dir(null, false);
        if (!empty($file['attachment_id'])) {
            $attachment_id = (int)$file['attachment_id'];
            if (get_post_type($attachment_id) !== 'attachment') {
                return null;
            }
            return self::reuse($attachment_id, $item, $notes, $mode);
        }
        if (empty($file['ref'])) {
            return null;
        }
        $ref = trim((string)$file['ref']);

        if (preg_match('#^https?://#i', $ref) || strpos($ref, '//') === 0) {
            $attachment_id = attachment_url_to_postid($ref);
            if ($attachment_id) {
                return self::reuse($attachment_id, $item, $notes, $mode);
            }
            $path = self::local_path_for_url($ref, $uploads);
            if ($path === '') {
                return array('url' => $ref);
            }
        } else {
            $path = '';
            $candidates = array($ref);
            foreach (isset($file['base']) ? (array)$file['base'] : array() as $base) {
                $candidates[] = $base . ltrim($ref, '/\\');
            }
            // Paths saved on another server: keep the part after wp-content.
            if (($pos = strpos($ref, 'wp-content/')) !== false) {
                $candidates[] = trailingslashit(WP_CONTENT_DIR) . substr($ref, $pos + strlen('wp-content/'));
            }
            foreach ($candidates as $candidate) {
                if (@is_file($candidate)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                    $path = $candidate;
                    break;
                }
            }
            if ($path === '') {
                return null;
            }
        }

        $attachment_id = self::attachment_for_path($path, $uploads);
        if ($attachment_id) {
            return self::reuse($attachment_id, $item, $notes, $mode);
        }
        if ($mode === 'none') {
            $notes[] = __('Its file was left in the old plugin\'s private folder, because the Media Library is public. Attach it once the document\'s access is set.', 'document-engine');
            return null;
        }
        if ($mode === 'public') {
            $attachment_id = self::copied_before($path);
            if ($attachment_id) {
                return array('attachment_id' => $attachment_id);
            }
        }
        $roots = isset($file['roots']) ? (array)$file['roots'] : array();
        $attachment_id = self::copy_to_media($path, $item['author'], $mode === 'private', $roots);
        if (is_wp_error($attachment_id)) {
            $notes[] = $attachment_id->get_error_message();
            return null;
        }
        return array('attachment_id' => $attachment_id);
    }

    /**
     * Uses an existing Media Library file. A restricted document gets its own private copy,
     * so protecting it never moves a file that other pages still show.
     */
    private static function reuse($attachment_id, $item, &$notes, $mode)
    {
        if ($mode === 'public') {
            return array('attachment_id' => (int)$attachment_id);
        }
        $path = (string)get_attached_file($attachment_id);
        if ($mode === 'none' || $path === '' || !file_exists($path)) {
            $notes[] = __('Its file was not attached, because it is shared with public pages. Attach a copy once the document\'s access is set.', 'document-engine');
            return null;
        }
        $copy = self::copy_to_media($path, $item['author'], true);
        if (is_wp_error($copy)) {
            $notes[] = $copy->get_error_message();
            return null;
        }
        return array('attachment_id' => $copy);
    }

    /**
     * Folders files may be copied from: this site's uploads plus the old plugin's own folders,
     * never other plugins' private folders (shop downloads, form uploads, other network sites).
     */
    public static function source_allowed($real, $extra_roots = array())
    {
        $uploads = wp_upload_dir(null, false);
        $path = wp_normalize_path($real);
        $roots = array_merge(array($uploads['basedir']), (array)$extra_roots);
        $inside = false;
        foreach ($roots as $root) {
            $root = realpath((string)$root);
            if ($root && strpos($path, trailingslashit(wp_normalize_path($root))) === 0) {
                $inside = true;
                break;
            }
        }
        if (!$inside) {
            return false;
        }
        $relative = ltrim(substr($path, strlen(trailingslashit(wp_normalize_path($uploads['basedir'])))), '/');
        $blocked = array('woocommerce_uploads/', 'edd/', 'wc-logs/', 'wpforms/', 'gravity_forms/', 'wpcf7_uploads/', 'document-engine-private-', 'document-engine/');
        if (is_multisite() && is_main_site()) {
            $blocked[] = 'sites/';
        }
        foreach ((array)apply_filters('document_engine_migrate_blocked_folders', $blocked) as $prefix) {
            if (strpos($relative, $prefix) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Document, image, audio, video and archive types only — never web pages, scripts or SVG.
     */
    public static function allowed_extensions()
    {
        return apply_filters('document_engine_migrate_extensions', array(
            'pdf', 'doc', 'docx', 'dot', 'dotx', 'xls', 'xlsx', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'csv', 'tsv',
            'key', 'pages', 'numbers', 'epub', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff', 'mp3', 'm4a', 'wav', 'ogg', 'mp4', 'm4v', 'mov', 'webm', 'zip', 'gz', '7z', 'rar',
        ));
    }

    private static function local_path_for_url($url, $uploads)
    {
        $url = set_url_scheme($url, 'http');
        $candidates = array(
            set_url_scheme($uploads['baseurl'], 'http') => $uploads['basedir'],
            set_url_scheme(content_url(), 'http') => WP_CONTENT_DIR,
            set_url_scheme(site_url(), 'http') => untrailingslashit(ABSPATH),
        );
        foreach ($candidates as $base_url => $base_dir) {
            if (strpos($url, trailingslashit($base_url)) === 0) {
                $path = trailingslashit($base_dir) . rawurldecode(substr(strtok($url, '?'), strlen(trailingslashit($base_url))));
                return @is_file($path) ? $path : ''; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
        }
        return '';
    }

    /**
     * Two items pointing at the same file share one Media Library copy.
     */
    private static function copied_before($path)
    {
        $real = realpath($path);
        if (!$real) {
            return 0;
        }
        $found = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_dengine_migrated_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => wp_normalize_path($real), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- exact lookup; filters (e.g. access rules) must not hide rows.
        ));
        return $found ? (int)$found[0] : 0;
    }

    private static function attachment_for_path($path, $uploads)
    {
        $base = trailingslashit(wp_normalize_path($uploads['basedir']));
        $path = wp_normalize_path($path);
        if (strpos($path, $base) !== 0) {
            return 0;
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            substr($path, strlen($base))
        ));
    }

    /**
     * Copies a file that lives on this server into the Media Library.
     * Only files inside the WordPress install with an allowed file type are copied.
     *
     * @return int|\WP_Error
     */
    public static function copy_to_media($path, $author_id, $private = false, $extra_roots = array())
    {
        $real = realpath($path);
        $name = wp_basename((string)$real);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $check = $real ? wp_check_filetype_and_ext($real, $name) : array('type' => false);
        $type = array('type' => $check['type'] ?: wp_check_filetype($name)['type']);
        if (!$real || !is_file($real) || !self::source_allowed($real, $extra_roots) || !in_array($ext, self::allowed_extensions(), true) || !$type['type']) {
            /* translators: %s: file name */
            return new \WP_Error('dengine_migrate_file', sprintf(__('The file %s was not copied: only document, image, audio, video and archive files from this site\'s uploads folder are moved.', 'document-engine'), $name ?: basename((string)$path)));
        }
        $size = (int)filesize($real);
        if (is_multisite() && function_exists('get_upload_space_available') && !get_site_option('upload_space_check_disabled') && $size > get_upload_space_available()) {
            /* translators: %s: file name */
            return new \WP_Error('dengine_migrate_quota', sprintf(__('The file %s was not copied: this site has no storage space left.', 'document-engine'), $name));
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new \WP_Error('dengine_migrate_upload', $uploads['error']);
        }
        $dir = $uploads['path'];
        if ($private) {
            /**
             * Private folder (outside public reach) for members-only files. Add-ons with protected storage return one.
             */
            $dir = (string)apply_filters('document_engine_migrate_private_dir', '');
            if ($dir === '' || !wp_mkdir_p($dir)) {
                /* translators: %s: file name */
                return new \WP_Error('dengine_migrate_private', sprintf(__('The file %s was not copied: there is no private storage for members-only files.', 'document-engine'), $name));
            }
        }
        $filename = wp_unique_filename($dir, $name);
        $target = trailingslashit($dir) . $filename;
        if (!@copy($real, $target)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            /* translators: %s: file name */
            return new \WP_Error('dengine_migrate_copy', sprintf(__('The file %s could not be copied to the Media Library.', 'document-engine'), $name));
        }
        $stat = @stat(dirname($target)); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ($stat) {
            @chmod($target, $stat['mode'] & 0000666); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        }

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $type['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $name),
            'post_status' => 'inherit',
            'post_author' => $author_id ?: get_current_user_id(),
            'guid' => trailingslashit($uploads['url']) . $filename,
        ), $target, 0, true);
        if (is_wp_error($attachment_id)) {
            wp_delete_file($target);
            return $attachment_id;
        }

        if ($private) {
            do_action('document_engine_migrate_private_file', (int)$attachment_id, $target);
        } else {
            update_post_meta($attachment_id, '_dengine_migrated_file', wp_normalize_path($real));
        }

        if (!$private && strpos($type['type'], 'image/') === 0) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target));
        } else {
            // Skip PDF preview generation during bulk moves; it is slow and not needed for documents.
            wp_update_attachment_metadata($attachment_id, array('filesize' => (int)filesize($target)));
        }
        return (int)$attachment_id;
    }
}
