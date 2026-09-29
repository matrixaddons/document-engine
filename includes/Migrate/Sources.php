<?php

namespace MatrixAddons\DocumentEngine\Migrate;

defined('ABSPATH') || exit;

/**
 * Reads documents stored by other document plugins straight from the database,
 * so a migration works even after the old plugin has been deactivated.
 *
 * Every source returns items in one shape:
 * - id, title, content, excerpt, status, date, date_gmt, modified, modified_gmt, author, menu_order, thumbnail_id
 * - categories: source term IDs, tags: source term IDs
 * - file: array('attachment_id' => int) | array('ref' => url-or-path) | array()
 * - extra_files: number of extra files the source item holds (only the first one is imported)
 * - downloads: int
 * - version: string
 * - password: string
 * - access: array('mode' => 'logged_in'|'roles', 'roles' => string[]) or array()
 */
class Sources
{
    public static function all()
    {
        return apply_filters('document_engine_migrate_sources', array(
            'barn2' => array(
                'label' => 'Document Library (Barn2)',
                'description' => __('Document Library Lite and Document Library Pro by Barn2.', 'document-engine'),
                'post_type' => 'dlp_document',
                'category' => 'doc_categories',
                'tag' => 'doc_tags',
                'plugin' => array('document-library-lite/document-library-lite.php', 'document-library-pro/document-library-pro.php'),
            ),
            'dlm' => array(
                'label' => 'Download Monitor',
                'description' => __('Downloads, their newest version, categories, tags and download counts.', 'document-engine'),
                'post_type' => 'dlm_download',
                'category' => 'dlm_download_category',
                'tag' => 'dlm_download_tag',
                'plugin' => array('download-monitor/download-monitor.php'),
            ),
            'wpdm' => array(
                'label' => 'WordPress Download Manager',
                'description' => __('Packages, their main file, categories, tags, passwords and role limits.', 'document-engine'),
                'post_type' => 'wpdmpro',
                'category' => 'wpdmcategory',
                'tag' => 'wpdmtag',
                'plugin' => array('download-manager/download-manager.php'),
            ),
        ));
    }

    public static function get($key)
    {
        $all = self::all();
        return isset($all[$key]) ? $all[$key] : null;
    }

    /**
     * Statuses worth moving; trash and auto-drafts stay behind.
     */
    public static function statuses()
    {
        return array('publish', 'private', 'draft', 'pending', 'future');
    }

    public static function count($key)
    {
        global $wpdb;
        $source = self::get($key);
        if (!$source) {
            return 0;
        }
        $in = implode(',', array_fill(0, count(self::statuses()), '%s'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($in)", array_merge(array($source['post_type']), self::statuses())));
    }

    /**
     * Source IDs after a cursor, oldest first.
     */
    public static function ids($key, $after_id, $limit)
    {
        global $wpdb;
        $source = self::get($key);
        if (!$source) {
            return array();
        }
        $in = implode(',', array_fill(0, count(self::statuses()), '%s'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($in) AND ID > %d ORDER BY ID ASC LIMIT %d",
            array_merge(array($source['post_type']), self::statuses(), array((int)$after_id, (int)$limit))
        )));
    }

    /**
     * Term IDs of one post in a taxonomy that may no longer be registered.
     */
    public static function object_terms($post_id, $taxonomy)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT tt.term_id FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tr.object_id = %d AND tt.taxonomy = %s",
            $post_id,
            $taxonomy
        )));
    }

    /**
     * @return object|null term_id, name, slug, description, parent
     */
    public static function term($term_id, $taxonomy)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.term_id, t.name, t.slug, tt.description, tt.parent FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE t.term_id = %d AND tt.taxonomy = %s",
            $term_id,
            $taxonomy
        ));
    }

    public static function item($key, $post_id)
    {
        $source = self::get($key);
        $post = get_post($post_id);
        if (!$source || !$post || $post->post_type !== $source['post_type']) {
            return null;
        }

        $item = array(
            'id' => (int)$post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'date' => $post->post_date,
            'date_gmt' => $post->post_date_gmt,
            'modified' => $post->post_modified,
            'modified_gmt' => $post->post_modified_gmt,
            'author' => (int)$post->post_author,
            'menu_order' => (int)$post->menu_order,
            'thumbnail_id' => (int)get_post_meta($post->ID, '_thumbnail_id', true),
            'categories' => self::object_terms($post->ID, $source['category']),
            'tags' => self::object_terms($post->ID, $source['tag']),
            'file' => array(),
            'extra_files' => 0,
            'downloads' => 0,
            'version' => '',
            'password' => $post->post_password,
            'access' => array(),
            'locked' => array(),
            'slug' => $post->post_name,
        );

        $method = 'read_' . $key;
        if (method_exists(__CLASS__, $method)) {
            $item = self::$method($post, $item);
        }
        return apply_filters('document_engine_migrate_item', $item, $key, $post);
    }

    private static function read_barn2($post, $item)
    {
        $type = (string)get_post_meta($post->ID, '_dlp_document_link_type', true);
        if ($type === 'file') {
            $item['file'] = array('attachment_id' => (int)get_post_meta($post->ID, '_dlp_attached_file_id', true));
        } elseif ($type === 'url') {
            // Pro stores the link in one of these keys depending on its version.
            foreach (array('_dlp_direct_link_url', '_dlp_document_link_url', '_dlp_document_url') as $meta_key) {
                $url = (string)get_post_meta($post->ID, $meta_key, true);
                if ($url !== '') {
                    $item['file'] = array('ref' => $url);
                    break;
                }
            }
        }
        foreach (array('_dlp_download_count', '_dlp_downloads') as $meta_key) {
            $count = (int)get_post_meta($post->ID, $meta_key, true);
            if ($count > 0) {
                $item['downloads'] = $count;
                break;
            }
        }
        return $item;
    }

    private static function read_dlm($post, $item)
    {
        global $wpdb;

        // The newest version is the published one with the lowest menu order.
        $version = get_posts(array(
            'post_type' => 'dlm_download_version',
            'post_parent' => $post->ID,
            'post_status' => 'publish',
            'orderby' => array('menu_order' => 'ASC', 'ID' => 'DESC'),
            'posts_per_page' => 1,
            'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- reading another plugin's data as stored.
        ));
        if ($version) {
            $files = get_post_meta($version[0]->ID, '_files', true);
            if (is_string($files) && $files !== '') {
                $decoded = json_decode($files, true);
                $files = is_array($decoded) ? $decoded : array($files);
            }
            $files = array_values(array_filter((array)$files, 'is_string'));
            if ($files) {
                $item['file'] = array('ref' => trim($files[0]));
                $item['extra_files'] = count($files) - 1;
            }
            $item['version'] = (string)get_post_meta($version[0]->ID, '_version', true);
        }

        $item['downloads'] = (int)get_post_meta($post->ID, '_download_count', true);
        static $has_table = null;
        $table = $wpdb->prefix . 'dlm_downloads';
        if ($has_table === null) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $has_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        }
        if ($has_table) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $logged = (int)$wpdb->get_var($wpdb->prepare("SELECT download_count FROM {$table} WHERE download_id = %d", $post->ID));
            $item['downloads'] = max($item['downloads'], $logged);
        }

        if ((string)get_post_meta($post->ID, '_is_purchasable', true) === '1') {
            $item['locked'][] = __('paid', 'document-engine');
        }
        $item['locked'] = (array)apply_filters('document_engine_migrate_dlm_locks', $item['locked'], $post);

        if (get_post_meta($post->ID, '_members_only', true) === 'yes') {
            $item['access'] = array('mode' => 'logged_in', 'roles' => array());
        }
        return $item;
    }

    private static function read_wpdm($post, $item)
    {
        $files = maybe_unserialize(get_post_meta($post->ID, '__wpdm_files', true));
        $files = array_values(array_filter((array)$files, function ($f) {
            return is_string($f) && trim($f) !== '';
        }));
        if ($files) {
            $item['file'] = array('ref' => trim($files[0]), 'base' => self::wpdm_dirs((int)$post->post_author), 'roots' => self::wpdm_roots());
            $item['extra_files'] = count($files) - 1;
        }
        $item['downloads'] = (int)get_post_meta($post->ID, '__wpdm_download_count', true);
        $item['version'] = (string)get_post_meta($post->ID, '__wpdm_version', true);

        // Locks this plugin can't reproduce: import as a draft without the file, for the owner to review.
        $locks = array(
            '__wpdm_email_lock' => __('email', 'document-engine'),
            '__wpdm_captcha_lock' => __('captcha', 'document-engine'),
            '__wpdm_linkedin_lock' => __('social share', 'document-engine'),
            '__wpdm_twitter_lock' => __('social share', 'document-engine'),
            '__wpdm_facebook_lock' => __('social share', 'document-engine'),
            '__wpdm_tweet_lock' => __('social share', 'document-engine'),
            '__wpdm_gplusone_lock' => __('social share', 'document-engine'),
        );
        foreach ($locks as $meta_key => $label) {
            if (get_post_meta($post->ID, $meta_key, true)) {
                $item['locked'][] = $label;
            }
        }
        if ((float)get_post_meta($post->ID, '__wpdm_base_price', true) > 0) {
            $item['locked'][] = __('paid', 'document-engine');
        }
        $item['locked'] = array_values(array_unique($item['locked']));

        if (get_post_meta($post->ID, '__wpdm_password_lock', true) && $item['password'] === '') {
            $password = get_post_meta($post->ID, '__wpdm_password', true);
            // WPDM allows several passwords in "[a][b]" form; WordPress holds one, so take the first.
            if (is_string($password) && preg_match('/^\[([^\]]+)\]/', $password, $m)) {
                $password = $m[1];
            }
            $item['password'] = is_string($password) ? mb_substr(trim($password), 0, 255) : '';
        }

        // WPDM allows a package only for the roles on it and on its categories; "guest" means everyone,
        // and no roles at all means nobody but admins.
        $roles = (array)maybe_unserialize(get_post_meta($post->ID, '__wpdm_access', true));
        foreach ($item['categories'] as $term_id) {
            $roles = array_merge($roles, (array)maybe_unserialize(get_term_meta($term_id, '__wpdm_access', true)));
        }
        $roles = array_values(array_unique(array_filter($roles, function ($role) {
            return is_string($role) && $role !== '';
        })));
        if (!in_array('guest', $roles, true)) {
            $known = array_keys(wp_roles()->get_names());
            $item['access'] = array('mode' => 'roles', 'roles' => array_values(array_intersect($roles, $known)));
        }
        return $item;
    }

    /**
     * WPDM's own file folder, which files may be copied from even when set outside uploads.
     */
    private static function wpdm_roots()
    {
        $uploads = wp_upload_dir(null, false);
        return array(defined('UPLOAD_DIR') ? (string)UPLOAD_DIR : trailingslashit($uploads['basedir']) . 'download-manager-files/');
    }

    /**
     * Folders WPDM resolves relative file names against, in its own order.
     */
    private static function wpdm_dirs($author_id)
    {
        $uploads = wp_upload_dir(null, false);
        $base = defined('UPLOAD_DIR') ? (string)UPLOAD_DIR : trailingslashit($uploads['basedir']) . 'download-manager-files/';
        $dirs = array(trailingslashit($base), trailingslashit(ABSPATH));
        $root = get_option('_wpdm_file_browser_root');
        if (is_string($root) && $root !== '') {
            $dirs[] = trailingslashit($root);
        }
        $user = get_userdata($author_id);
        if ($user) {
            $dirs[] = trailingslashit($base) . $user->user_login . '/';
        }
        return $dirs;
    }
}
