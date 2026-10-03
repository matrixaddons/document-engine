<?php

namespace MatrixAddons\DocumentEngine\Documents;

defined('ABSPATH') || exit;

/**
 * Every document download and view goes through here: `/?dengine_download=<id>[&inline=1][&view=1]`.
 *
 * Checks access, records the event, then redirects to a public file or streams a local one
 * (with HTTP Range support for the PDF viewer). Add-ons hook in to protect storage, gate
 * downloads or log activity.
 */
class FileServer
{
    const QUERY_VAR = 'dengine_download';

    public static function init()
    {
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('template_redirect', array(__CLASS__, 'maybe_serve'), 1);
        add_filter('rest_attachment_query', array(__CLASS__, 'rest_hide_private_files'));
        add_filter('rest_request_before_callbacks', array(__CLASS__, 'rest_guard_private_file'), 10, 3);
    }

    /**
     * Files that belong to documents the current user may not read (drafts, private, scheduled).
     *
     * @return int[] attachment IDs
     */
    public static function hidden_file_ids()
    {
        global $wpdb;
        static $cache = array();
        $user = get_current_user_id();
        if (isset($cache[$user])) {
            return $cache[$user];
        }
        // People who can edit every document can read every file.
        if (current_user_can('edit_others_dengine_documents')) {
            return $cache[$user] = array();
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.meta_value AS file_id, p.ID AS doc FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_type = %s AND (p.post_status NOT IN ('publish', 'auto-draft') OR p.post_password <> '') LIMIT 50000",
            Document::META_FILE_ID,
            PostType::POST_TYPE
        ));
        $ids = array();
        foreach ((array)$rows as $row) {
            if ((int)$row->file_id > 0 && !current_user_can('read_post', (int)$row->doc)) {
                $ids[] = (int)$row->file_id;
            }
        }
        return $cache[$user] = array_values(array_unique($ids));
    }

    /**
     * Whether the current visitor may embed a Media Library file (viewer "file" source, version 1 PDF blocks):
     * its parent is public (or it has none) or they can read it, and it isn't the file of a document they can't open.
     */
    public static function can_embed_attachment($attachment_id)
    {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id || get_post_type($attachment_id) !== 'attachment' || in_array($attachment_id, self::hidden_file_ids(), true)) {
            return false;
        }
        $status = get_post_status_object((string)get_post_status($attachment_id));
        $allowed = ($status && $status->public) || current_user_can('read_post', $attachment_id);
        return (bool)apply_filters('document_engine_can_embed_attachment', $allowed, $attachment_id);
    }

    /**
     * Keeps files of non-public documents out of /wp/v2/media lists for people who can't read the document.
     */
    public static function rest_hide_private_files($args)
    {
        $hidden = self::hidden_file_ids();
        if ($hidden) {
            $args['post__not_in'] = array_merge(isset($args['post__not_in']) ? (array)$args['post__not_in'] : array(), $hidden);
        }
        return $args;
    }

    public static function rest_guard_private_file($response, $handler, $request)
    {
        if (!is_wp_error($response) && $request instanceof \WP_REST_Request && preg_match('#^/wp/v2/media/(\d+)#', $request->get_route(), $m) && in_array((int)$m[1], self::hidden_file_ids(), true)) {
            return new \WP_Error('rest_post_invalid_id', __('Invalid post ID.', 'document-engine'), array('status' => 404));
        }
        return $response;
    }

    public static function query_vars($vars)
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Whether a user may open a document. Pro adds roles, users, passwords and protected categories.
     *
     * @param Document $document
     * @param int|null $user_id Defaults to the current user.
     * @param string $context download|view|list
     */
    public static function can_access(Document $document, $user_id = null, $context = 'download')
    {
        $user_id = $user_id === null ? get_current_user_id() : (int)$user_id;
        $post = $document->get_post();

        if ($post->post_status === 'publish') {
            $allowed = !post_password_required($post);
        } else {
            $allowed = $user_id > 0 && user_can($user_id, 'read_post', $post->ID);
        }

        return (bool)apply_filters('document_engine_can_access_document', $allowed, $document, $user_id, $context);
    }

    public static function maybe_serve()
    {
        $id = absint(get_query_var(self::QUERY_VAR));
        if ($id < 1) {
            return;
        }

        $document = Document::get($id);
        if (!$document || !$document->has_file()) {
            self::fail(404, __('This document could not be found.', 'document-engine'));
        }

        $is_view = !empty($_GET['view']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $inline = $is_view || !empty($_GET['inline']) || $document->get_behavior() === 'inline'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $context = $is_view ? 'view' : 'download';

        if (!self::can_access($document, null, $context)) {
            $throttle = 'dengine_denied_' . md5($document->get_id() . '|' . (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '') . '|' . get_current_user_id());
            if (!get_transient($throttle)) {
                set_transient($throttle, 1, MINUTE_IN_SECONDS);
                do_action('document_engine_document_event', 'denied', $document, array('context' => $context));
            }
            do_action('document_engine_access_denied', $document, $context);
            self::deny($document);
        }

        /**
         * Last chance to take over (e.g. show an email gate). Exit to stop serving.
         */
        do_action('document_engine_before_serve_document', $document, $context);

        $path = $document->get_file_path();
        $url = $document->get_file_url();
        $local = $path !== '' && file_exists($path);
        if (!$local && ($url === '' || !wp_http_validate_url($url))) {
            // Nothing to serve: don't count a download or fire download hooks for a missing file.
            self::fail(404, __('The file for this document is missing.', 'document-engine'));
        }

        self::record($document, $context, array('inline' => $inline));

        if ($local) {
            // Public Media Library files are redirected to when nothing needs PHP in the middle.
            // Files of drafts, private or password-protected documents are always streamed, so their raw address is never handed out.
            $public = $document->get_post()->post_status === 'publish' && $document->get_post()->post_password === '';
            // Files kept in protected storage (Document Engine Pro, even after Pro is switched off) can't be linked to directly.
            $private_file = (bool)get_post_meta($document->get_file_id(), '_dengine_protected', true) || strpos(wp_normalize_path($path), '/document-engine-private') !== false;
            $redirect = $public && $inline && !$private_file && $document->get_file_url() !== '' && !apply_filters('document_engine_stream_file', false, $document, $context);
            if ($redirect) {
                self::redirect($document->get_file_url());
            }
            self::stream($path, $document, $inline);
        }

        self::redirect($url);
    }

    /**
     * Counts a download/view and fires the event. Add-ons that serve the bytes themselves call this first.
     */
    public static function record(Document $document, $context, $args = array(), $force = false)
    {
        if (!$force && !self::should_record()) {
            return;
        }
        if ($context === 'download' && get_option('document_engine_count_downloads', 'yes') === 'yes') {
            $document->increment_downloads();
        }
        do_action('document_engine_document_event', $context, $document, $args);
    }

    /**
     * Skips counting for bots, prefetches and PDF.js follow-up range requests.
     */
    private static function should_record()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : 'GET';
        if ($method !== 'GET') {
            return false;
        }
        if (!empty($_SERVER['HTTP_RANGE']) && !preg_match('/bytes=0-/', sanitize_text_field(wp_unslash($_SERVER['HTTP_RANGE'])))) {
            return false;
        }
        $purpose = isset($_SERVER['HTTP_SEC_PURPOSE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_PURPOSE'])) : (isset($_SERVER['HTTP_PURPOSE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_PURPOSE'])) : '');
        if (stripos($purpose, 'prefetch') !== false) {
            return false;
        }
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $is_bot = $agent === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|headless|curl|wget|python|scrapy/i', $agent);

        return !apply_filters('document_engine_is_bot', (bool)$is_bot, $agent);
    }

    private static function redirect($url)
    {
        nocache_headers();
        wp_redirect(esc_url_raw($url), 302, DOCUMENT_ENGINE_BRAND); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
        exit;
    }

    /**
     * Streams a local file with Range support.
     */
    public static function stream($path, Document $document, $inline)
    {
        $size = (int)filesize($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = wp_check_filetype($path);
        $mime = !empty($type['type']) ? $type['type'] : 'application/octet-stream';

        $name = sanitize_file_name($document->get_title());
        $name = ($name !== '' ? $name : 'document-' . $document->get_id()) . ($ext !== '' ? '.' . $ext : '');
        $name = (string)apply_filters('document_engine_download_filename', $name, $document);

        // Only types browsers can render safely are sent inline.
        $inline_types = apply_filters('document_engine_inline_mime_types', array('application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain', 'audio/mpeg', 'video/mp4'));
        $disposition = ($inline && in_array($mime, $inline_types, true)) ? 'inline' : 'attachment';

        /**
         * Hand the transfer to the web server (X-Sendfile / X-Accel-Redirect). Return true once headers are sent.
         */
        if (apply_filters('document_engine_offload_stream', false, $path, $mime, $disposition, $name, $document)) {
            exit;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $start = 0;
        $end = $size - 1;
        $status = 200;

        if ($size > 0 && !empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', sanitize_text_field(wp_unslash($_SERVER['HTTP_RANGE'])), $m)) {
            if ($m[1] === '' && $m[2] !== '') {
                $start = max(0, $size - (int)$m[2]);
            } else {
                $start = (int)$m[1];
                $end = $m[2] !== '' ? min((int)$m[2], $size - 1) : $end;
            }
            if ($start > $end || $start >= $size) {
                status_header(416);
                header('Content-Range: bytes */' . $size);
                exit;
            }
            $status = 206;
        }

        status_header($status);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . ($end - $start + 1));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Robots-Tag: noindex, nofollow');
        if ($status === 206) {
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        }

        $handle = fopen($path, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions -- streamed in chunks with HTTP Range support
        if ($handle) {
            fseek($handle, $start);
            $remaining = $end - $start + 1;
            while ($remaining > 0 && !feof($handle) && !connection_aborted()) {
                $chunk = fread($handle, min(1048576, $remaining)); // phpcs:ignore WordPress.WP.AlternativeFunctions
                if ($chunk === false) {
                    break;
                }
                echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $remaining -= strlen($chunk);
                flush();
            }
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions
        }
        exit;
    }

    /**
     * Sends visitors to the document's own page (which explains what to do), or shows a standalone notice.
     */
    private static function deny(Document $document)
    {
        nocache_headers();
        $post = $document->get_post();
        // Drafts, scheduled and private documents: don't confirm they exist or show their title.
        if (!in_array($post->post_status, array('publish'), true)) {
            self::fail(404, __('This document could not be found.', 'document-engine'), is_user_logged_in() ? '' : wp_login_url($document->get_download_url()));
        }
        // Password-protected: the WordPress password form is the way in, not a login.
        if ($post->post_password !== '' && post_password_required($post)) {
            if (get_option('document_engine_single_pages', 'yes') === 'yes') {
                wp_safe_redirect(get_permalink($post), 303);
                exit;
            }
            self::notice_page($document->get_title(), '<div class="dengine-access">' . get_the_password_form($post) . '</div>', 200);
        }
        if (get_option('document_engine_single_pages', 'yes') === 'yes' && $post->post_status === 'publish' && $post->post_password === '') {
            wp_safe_redirect(get_permalink($post), 303);
            exit;
        }
        ob_start();
        document_engine_get_template('document/access.php', array(
            'document' => $document,
            'reason' => is_user_logged_in() ? 'denied' : 'login',
            'return' => $document->get_download_url(),
        ));
        self::notice_page(is_user_logged_in() ? __('Access denied', 'document-engine') : __('Log in required', 'document-engine'), ob_get_clean(), is_user_logged_in() ? 403 : 401);
    }

    /**
     * Branded standalone page (site name + card) used when there is no document page to show.
     */
    public static function notice_page($title, $body_html, $status = 200)
    {
        nocache_headers();
        status_header((int)$status);
        header('X-Robots-Tag: noindex, nofollow');
        document_engine_get_template('notice-page.php', array('title' => $title, 'body' => $body_html));
        exit;
    }

    private static function fail($status, $message, $login_url = '')
    {
        $body = '<div class="dengine-access"><h2 class="dengine-access__title">' . esc_html($message) . '</h2><p class="dengine-access__actions">'
            . ($login_url !== '' ? '<a class="dengine-button" href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'document-engine') . '</a> ' : '')
            . '<a class="dengine-button' . ($login_url !== '' ? ' dengine-button--ghost' : '') . '" href="' . esc_url(home_url('/')) . '">' . esc_html__('Go to the home page', 'document-engine') . '</a></p></div>';
        self::notice_page(__('Document unavailable', 'document-engine'), $body, $status);
    }
}
