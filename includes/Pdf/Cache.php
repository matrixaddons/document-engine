<?php

namespace MatrixAddons\DocumentEngine\Pdf;

defined('ABSPATH') || exit;

/**
 * Stores generated post PDFs so repeat downloads don't re-run mPDF.
 *
 * Only anonymous visitors are served from cache (logged-in content can differ per user).
 * Entries are keyed by post modification time and a settings salt, so edits and setting
 * changes take effect immediately; stale files are pruned daily.
 */
class Cache
{
    const SALT_OPTION = 'document_engine_pdf_cache_salt';

    public static function init()
    {
        add_action('save_post', array(__CLASS__, 'purge_post'));
        add_action('deleted_post', array(__CLASS__, 'purge_post'));
        add_action('document_engine_settings_saved', array(__CLASS__, 'flush'));
        add_action('document_engine_daily', array(__CLASS__, 'prune'));
        add_action('switch_theme', array(__CLASS__, 'flush'));
    }

    public static function enabled($post_id)
    {
        $enabled = get_option('document_engine_pdf_cache', 'yes') === 'yes' && !is_user_logged_in();
        return (bool)apply_filters('document_engine_pdf_cache_enabled', $enabled, $post_id);
    }

    public static function dir()
    {
        $dir = document_engine()->get_log_dir(true) . 'pdf-cache/';
        if (!file_exists($dir . 'index.html')) {
            document_engine()->protect_dir($dir);
        }
        return $dir;
    }

    public static function key($post_id)
    {
        $post = get_post($post_id);
        $parts = array(
            $post_id,
            $post ? $post->post_modified_gmt : '',
            get_option(self::SALT_OPTION, '') . wp_salt('auth'),
            DOCUMENT_ENGINE_VERSION,
            determine_locale(),
            apply_filters('document_engine_pdf_cache_key', '', $post_id),
        );
        return md5(implode('|', $parts));
    }

    public static function path($post_id)
    {
        return self::dir() . absint($post_id) . '-' . self::key($post_id) . '.pdf';
    }

    public static function get($post_id)
    {
        if (!self::enabled($post_id)) {
            return '';
        }
        $path = self::path($post_id);
        return file_exists($path) ? $path : '';
    }

    public static function put($post_id, $content)
    {
        if (!self::enabled($post_id) || $content === '') {
            return;
        }
        self::purge_post($post_id);
        $path = self::path($post_id);
        $tmp = $path . '.' . wp_generate_password(6, false) . '.tmp';
        if (@file_put_contents($tmp, $content) !== false) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            @rename($tmp, $path); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- atomic replace
        }
    }

    public static function purge_post($post_id)
    {
        $dir = document_engine()->get_log_dir(false) . 'pdf-cache/';
        foreach ((array)glob($dir . absint($post_id) . '-*.pdf') as $file) {
            if ($file) {
                wp_delete_file($file);
            }
        }
    }

    /**
     * Invalidates everything (settings changed).
     */
    public static function flush()
    {
        update_option(self::SALT_OPTION, wp_generate_password(8, false), false);
        self::prune(0);
    }

    public static function prune($max_age = null)
    {
        $max_age = $max_age === null || $max_age === '' ? 7 * DAY_IN_SECONDS : (int)$max_age;
        $dir = document_engine()->get_log_dir(false) . 'pdf-cache/';
        foreach ((array)glob($dir . '*.{pdf,tmp}', GLOB_BRACE) as $file) {
            if ($file && (time() - (int)@filemtime($file)) >= $max_age) {
                wp_delete_file($file);
            }
        }
    }

    /**
     * Simple per-IP limit on uncached generations (mPDF is CPU heavy).
     *
     * @return bool True when the request may proceed.
     */
    public static function allow_generation()
    {
        if (current_user_can('edit_posts')) {
            return true;
        }
        $limit = (int)apply_filters('document_engine_pdf_rate_limit', absint(get_option('document_engine_pdf_rate_limit', 20)));
        if ($limit < 1) {
            return true;
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        // Sites behind a proxy/CDN can supply the real client IP.
        $ip = (string)apply_filters('document_engine_client_ip', $ip);
        $who = is_user_logged_in() ? 'u' . get_current_user_id() : $ip;
        $key = 'dengine_pdf_rl_' . md5($who . wp_salt('nonce'));
        $count = (int)get_transient($key);
        if ($count >= $limit) {
            return false;
        }
        set_transient($key, $count + 1, MINUTE_IN_SECONDS);
        return true;
    }
}
