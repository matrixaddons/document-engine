<?php

namespace MatrixAddons\DocumentEngine\Accessibility;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * "Request an accessible format": visitors ask for a document in another format
 * (accessible PDF, Word, large print…). Public bodies must provide this on request
 * (e.g. ADA Title II, the UK PSBAR, the European Accessibility Act). Off by default.
 */
class Requests
{
    const POST_TYPE = 'dengine_request';
    const QUERY_VAR = 'dengine_request';

    public static function init()
    {
        add_action('init', array(__CLASS__, 'register'));
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('template_redirect', array(__CLASS__, 'maybe_render'), 1);
        add_filter('document_engine_single_content', array(__CLASS__, 'single_link'), 30, 4);
        add_filter('wp_privacy_personal_data_exporters', array(__CLASS__, 'exporters'));
        add_filter('wp_privacy_personal_data_erasers', array(__CLASS__, 'erasers'));

        if (is_admin()) {
            add_filter('manage_' . self::POST_TYPE . '_posts_columns', array(__CLASS__, 'columns'));
            add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array(__CLASS__, 'column'), 10, 2);
            add_filter('post_row_actions', array(__CLASS__, 'row_actions'), 10, 2);
            add_action('admin_post_dengine_request_status', array(__CLASS__, 'set_status'));
            add_action('admin_menu', array(__CLASS__, 'menu_badge'), 998); // Before AppShell groups the menu (999).
            add_filter('bulk_actions-edit-' . self::POST_TYPE, '__return_empty_array');
            add_action('admin_notices', array(__CLASS__, 'list_intro'));
            add_filter('display_post_states', array(__CLASS__, 'post_states'), 10, 2);
        }
    }

    public static function enabled()
    {
        return get_option('document_engine_a11y_requests', 'no') === 'yes';
    }

    public static function formats()
    {
        return apply_filters('document_engine_accessible_formats', array(
            'accessible-pdf' => __('Accessible (tagged) PDF', 'document-engine'),
            'word' => __('Word document', 'document-engine'),
            'large-print' => __('Large print', 'document-engine'),
            'plain-text' => __('Plain text or web page', 'document-engine'),
            'braille' => __('Braille', 'document-engine'),
            'audio' => __('Audio', 'document-engine'),
            'other' => __('Something else (tell us below)', 'document-engine'),
        ));
    }

    public static function register()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => __('Format requests', 'document-engine'),
                'singular_name' => __('Format request', 'document-engine'),
                'menu_name' => __('Format requests', 'document-engine'),
                'all_items' => __('Format requests', 'document-engine'),
                'edit_item' => __('Format request', 'document-engine'),
                'not_found' => __('No format requests yet.', 'document-engine'),
                'search_items' => __('Search requests', 'document-engine'),
            ),
            'public' => false,
            'show_ui' => self::enabled() || (is_admin() && self::has_requests()),
            'show_in_menu' => 'edit.php?post_type=' . PostType::POST_TYPE,
            'show_in_rest' => false,
            'supports' => array('title'),
            'capability_type' => array('dengine_document', 'dengine_documents'),
            'map_meta_cap' => true,
            // Requests hold names and email addresses: editors and above only.
            'capabilities' => array('create_posts' => 'do_not_allow', 'edit_posts' => 'edit_others_dengine_documents'),
            'exclude_from_search' => true,
        ));
    }

    private static function has_requests()
    {
        $counts = wp_count_posts(self::POST_TYPE);
        return isset($counts->private) && (int)$counts->private > 0;
    }

    public static function query_vars($vars)
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function url(Document $document)
    {
        return add_query_arg(self::QUERY_VAR, $document->get_id(), home_url('/'));
    }

    public static function single_link($html, $card, $content, $document)
    {
        if (!self::enabled() || !$document->has_file() || !FileServer::can_access($document, null, 'view')) {
            return $html;
        }
        $link = '<p class="dengine-a11y-link">' . document_engine_ui_icon('view') . '<span>' . esc_html__('Need this document in another format?', 'document-engine') . ' <a href="' . esc_url(self::url($document)) . '">' . esc_html__('Request an accessible version', 'document-engine') . '</a></span></p>';
        // Straight under the summary card, before the page text.
        return $card !== '' && strpos($html, $card) === 0 ? $card . $link . substr($html, strlen($card)) : $html . $link;
    }

    public static function maybe_render()
    {
        $id = absint(get_query_var(self::QUERY_VAR));
        if ($id < 1 || !self::enabled()) {
            return;
        }
        $document = Document::get($id);
        if (!$document || $document->get_post()->post_status !== 'publish' || !FileServer::can_access($document, null, 'view')) {
            FileServer::notice_page(__('Not available', 'document-engine'), '<div class="dengine-access"><h2 class="dengine-access__title">' . esc_html__('This request form is not available.', 'document-engine') . '</h2></div>', 404);
        }

        $errors = array();
        $values = array('name' => '', 'email' => '', 'format' => 'accessible-pdf', 'note' => '');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dengine_request_nonce'])) {
            $values = array(
                'name' => sanitize_text_field(wp_unslash($_POST['dengine_name'] ?? '')),
                'email' => sanitize_email(wp_unslash($_POST['dengine_email'] ?? '')),
                'format' => sanitize_key(wp_unslash($_POST['dengine_format'] ?? '')),
                'note' => sanitize_textarea_field(wp_unslash($_POST['dengine_note'] ?? '')),
            );
            $errors = self::handle($document, $values);
            if (!$errors) {
                FileServer::notice_page(
                    __('Request received', 'document-engine'),
                    '<div class="dengine-access"><h2 class="dengine-access__title">' . esc_html__('Thank you. We have your request.', 'document-engine') . '</h2><p>' . esc_html(sprintf(
                        /* translators: %s: email address */
                        __('We will send the document to %s as soon as it is ready.', 'document-engine'),
                        $values['email']
                    )) . '</p><p class="dengine-access__actions"><a class="dengine-button dengine-button--ghost" href="' . esc_url(get_permalink($document->get_post())) . '">' . esc_html__('Back to the document', 'document-engine') . '</a></p></div>'
                );
            }
        }
        FileServer::notice_page(__('Request an accessible version', 'document-engine'), self::form($document, $values, $errors));
    }

    private static function form(Document $document, $values, $errors)
    {
        ob_start();
        ?>
        <form class="dengine-form" method="post" action="<?php echo esc_url(self::url($document)); ?>">
            <div class="dengine-form__head">
                <span class="dengine-access__doc"><?php echo document_engine_file_icon($document->get_extension()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($document->get_title()); ?></span>
                <h1 class="dengine-form__title"><?php esc_html_e('Request an accessible version', 'document-engine'); ?></h1>
                <p class="dengine-form__intro"><?php esc_html_e('Tell us the format that works for you and we will send it to you by email.', 'document-engine'); ?></p>
            </div>
            <?php if ($errors) : ?>
                <div class="dengine-form__errors" role="alert"><?php foreach ($errors as $error) : ?><p><?php echo esc_html($error); ?></p><?php endforeach; ?></div>
            <?php endif; ?>
            <?php wp_nonce_field('dengine_request_' . $document->get_id(), 'dengine_request_nonce'); ?>
            <div class="dengine-form__row"><label for="dengine_name"><?php esc_html_e('Your name', 'document-engine'); ?></label><input type="text" id="dengine_name" name="dengine_name" value="<?php echo esc_attr($values['name']); ?>" autocomplete="name" required></div>
            <div class="dengine-form__row"><label for="dengine_email"><?php esc_html_e('Email address', 'document-engine'); ?></label><input type="email" id="dengine_email" name="dengine_email" value="<?php echo esc_attr($values['email']); ?>" autocomplete="email" required></div>
            <div class="dengine-form__row"><label for="dengine_format"><?php esc_html_e('Format you need', 'document-engine'); ?></label>
                <select id="dengine_format" name="dengine_format">
                    <?php foreach (self::formats() as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($values['format'], $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="dengine-form__row"><label for="dengine_note"><?php esc_html_e('Anything else we should know?', 'document-engine'); ?> <span class="dengine-form__hint" style="display:inline"><?php esc_html_e('(optional)', 'document-engine'); ?></span></label><textarea id="dengine_note" name="dengine_note" rows="3"><?php echo esc_textarea($values['note']); ?></textarea></div>
            <p class="dengine-form__hp" aria-hidden="true"><label>Website<input type="text" name="dengine_website" tabindex="-1" autocomplete="off"></label></p>
            <input type="hidden" name="dengine_ts" value="<?php echo esc_attr(self::stamp()); ?>">
            <?php do_action('document_engine_form_fields', 'request'); ?>
            <div class="dengine-form__actions"><button type="submit" class="dengine-button"><?php esc_html_e('Send request', 'document-engine'); ?></button></div>
            <p class="dengine-form__foot"><?php esc_html_e('We only use your details to send you this document.', 'document-engine'); ?></p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Signed time the form was shown; bots that post instantly (or replay old forms) are refused.
     */
    private static function stamp($time = null)
    {
        $time = $time === null ? time() : (int)$time;
        return $time . '.' . substr(hash_hmac('sha256', 'dengine-request|' . $time, wp_salt('nonce')), 0, 16);
    }

    private static function stamp_ok($value)
    {
        $parts = explode('.', (string)$value);
        if (count($parts) !== 2 || !hash_equals(self::stamp($parts[0]), (string)$value)) {
            return false;
        }
        $age = time() - (int)$parts[0];
        return $age >= (int)apply_filters('document_engine_request_min_seconds', 3) && $age <= DAY_IN_SECONDS;
    }

    public static function client_ip()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        // Sites behind a proxy/CDN can supply the real client IP.
        return (string)apply_filters('document_engine_client_ip', $ip);
    }

    /**
     * @return string[] errors; empty when the request was stored
     */
    private static function handle(Document $document, $values)
    {
        $errors = array();
        // Logged-out visitors can't hold a stable nonce behind page caches; honeypot + rate limit protect the form.
        if (is_user_logged_in() && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dengine_request_nonce'])), 'dengine_request_' . $document->get_id())) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
            $errors[] = __('Your session expired. Please try again.', 'document-engine');
        }
        if (!empty($_POST['dengine_website'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $errors[] = __('Your request could not be sent.', 'document-engine');
        }
        if (!self::stamp_ok(isset($_POST['dengine_ts']) ? sanitize_text_field(wp_unslash($_POST['dengine_ts'])) : '')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $errors[] = __('Please wait a moment and send the form again.', 'document-engine');
        }
        // Limits are per visitor (one person can't use up a document's quota for everyone), plus a site-wide ceiling against floods.
        $limits = wp_parse_args(apply_filters('document_engine_request_limits', array()), array('visitor_hour' => 5, 'visitor_day' => 10, 'document_day' => 3, 'site_day' => 300));
        $visitor = substr(hash('sha256', self::client_ip() . wp_salt('nonce')), 0, 16);
        $counters = array(
            'dengine_req_v_' . $visitor => array($limits['visitor_hour'], HOUR_IN_SECONDS),
            'dengine_req_vd_' . $visitor . '_' . gmdate('Ymd') => array($limits['visitor_day'], DAY_IN_SECONDS),
            'dengine_req_d_' . $document->get_id() . '_' . $visitor . '_' . gmdate('Ymd') => array($limits['document_day'], DAY_IN_SECONDS),
            'dengine_req_s_' . gmdate('Ymd') => array($limits['site_day'], DAY_IN_SECONDS),
        );
        foreach ($counters as $key => $limit) {
            if ((int)get_transient($key) >= (int)$limit[0]) {
                $errors[] = __('Too many requests right now. Please try again later or contact us directly.', 'document-engine');
                break;
            }
        }
        if ($values['name'] === '') {
            $errors[] = __('Please enter your name.', 'document-engine');
        }
        if (!is_email($values['email'])) {
            $errors[] = __('Please enter a valid email address.', 'document-engine');
        }
        if (!array_key_exists($values['format'], self::formats())) {
            $errors[] = __('Please choose a format.', 'document-engine');
        }
        if (!$errors && apply_filters('document_engine_request_spam_check', false, $values, $document)) {
            $errors[] = __('Your request could not be sent.', 'document-engine');
        }
        if ($errors) {
            return $errors;
        }
        foreach ($counters as $key => $limit) {
            set_transient($key, (int)get_transient($key) + 1, $limit[1]);
        }

        $formats = self::formats();
        $request_id = wp_insert_post(wp_slash(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'post_title' => $document->get_title() . ' — ' . $formats[$values['format']],
            'post_parent' => $document->get_id(),
            'post_author' => 0,
            'meta_input' => array(
                '_dengine_name' => mb_substr($values['name'], 0, 190),
                '_dengine_email' => $values['email'],
                '_dengine_format' => $values['format'],
                '_dengine_note' => mb_substr($values['note'], 0, 2000),
                '_dengine_state' => 'open',
            ),
        )), true);
        if (is_wp_error($request_id)) {
            return array(__('Your request could not be saved. Please try again.', 'document-engine'));
        }

        $to = get_option('document_engine_a11y_email', '') ?: get_option('admin_email');
        wp_mail(
            $to,
            /* translators: %s: document title */
            sprintf(__('Accessible format requested: %s', 'document-engine'), $document->get_title()),
            implode("\n", array(
                /* translators: 1: name, 2: email */
                sprintf(__('%1$s (%2$s) asked for this document in another format.', 'document-engine'), $values['name'], $values['email']),
                '',
                /* translators: %s: document title */
                sprintf(__('Document: %s', 'document-engine'), $document->get_title()),
                /* translators: %s: format */
                sprintf(__('Format: %s', 'document-engine'), $formats[$values['format']]),
                $values['note'] !== '' ? sprintf(
                    /* translators: %s: note */
                    __('Note: %s', 'document-engine'),
                    $values['note']
                ) : '',
                '',
                admin_url('edit.php?post_type=' . self::POST_TYPE),
            )),
            array('Reply-To: ' . trim(str_replace(array(',', ';', '"', '<', '>', "\r", "\n"), ' ', $values['name'])) . ' <' . $values['email'] . '>')
        );
        do_action('document_engine_accessible_format_requested', $request_id, $document, $values);
        return array();
    }

    /* ---------- Admin list ---------- */

    /**
     * Count bubble on Format requests (or the Reports group) for requests still open.
     */
    public static function menu_badge()
    {
        global $submenu, $wpdb;
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        if (!self::enabled() || empty($submenu[$parent]) || !current_user_can('edit_others_dengine_documents')) {
            return;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $open = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_dengine_state' WHERE p.post_type = %s AND p.post_status = 'private' AND m.meta_value = 'open'", self::POST_TYPE));
        if (!$open) {
            return;
        }
        foreach ($submenu[$parent] as $i => $item) {
            if (isset($item[2]) && $item[2] === 'edit.php?post_type=' . self::POST_TYPE) {
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $submenu[$parent][$i][0] .= ' <span class="awaiting-mod count-' . $open . '"><span class="pending-count">' . esc_html(number_format_i18n($open)) . '</span></span>';
            }
        }
    }

    public static function list_intro()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-' . self::POST_TYPE) {
            return;
        }
        echo '<div class="notice notice-info"><p>' . esc_html__('Visitors ask for documents in formats they can use. Email the requester the alternative version (reply to the notification email), then mark the request done.', 'document-engine') . '</p></div>';
    }

    public static function columns($columns)
    {
        return array(
            'title' => __('Request', 'document-engine'),
            'dengine_requester' => __('Requested by', 'document-engine'),
            'dengine_state' => __('Status', 'document-engine'),
            'dengine_received' => __('Received', 'document-engine'),
        );
    }

    public static function column($column, $post_id)
    {
        if ($column === 'dengine_requester') {
            $email = (string)get_post_meta($post_id, '_dengine_email', true);
            echo esc_html((string)get_post_meta($post_id, '_dengine_name', true)) . '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            $note = (string)get_post_meta($post_id, '_dengine_note', true);
            if ($note !== '') {
                echo '<br><em>' . esc_html(wp_trim_words($note, 30)) . '</em>';
            }
        } elseif ($column === 'dengine_received') {
            echo esc_html(sprintf(
                /* translators: %s: time ago */
                __('%s ago', 'document-engine'),
                human_time_diff(get_post_time('U', true, $post_id), time())
            ));
        } elseif ($column === 'dengine_state') {
            $done = get_post_meta($post_id, '_dengine_state', true) === 'done';
            echo '<span class="dengine-a-pill ' . ($done ? 'dengine-a-pill--success' : 'dengine-a-pill--warning') . '">' . esc_html($done ? __('Done', 'document-engine') : __('Open', 'document-engine')) . '</span>';
        }
    }

    public static function post_states($states, $post)
    {
        if ($post->post_type === self::POST_TYPE) {
            unset($states['private']);
        }
        return $states;
    }

    public static function row_actions($actions, $post)
    {
        if ($post->post_type !== self::POST_TYPE) {
            return $actions;
        }
        $done = get_post_meta($post->ID, '_dengine_state', true) === 'done';
        $out = array();
        if ($post->post_parent) {
            $out['document'] = '<a href="' . esc_url((string)get_edit_post_link($post->post_parent)) . '">' . esc_html__('Open document', 'document-engine') . '</a>';
        }
        $out['state'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=dengine_request_status&id=' . $post->ID . '&state=' . ($done ? 'open' : 'done')), 'dengine_request_' . $post->ID)) . '">' . esc_html($done ? __('Reopen', 'document-engine') : __('Mark done', 'document-engine')) . '</a>';
        if (isset($actions['trash'])) {
            $out['trash'] = $actions['trash'];
        }
        return $out;
    }

    public static function set_status()
    {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer('dengine_request_' . $id);
        if (get_post_type($id) !== self::POST_TYPE || !current_user_can('edit_post', $id)) {
            wp_die(esc_html__('Sorry, you are not allowed to do that.', 'document-engine'), 403);
        }
        update_post_meta($id, '_dengine_state', isset($_GET['state']) && $_GET['state'] === 'done' ? 'done' : 'open');
        wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=' . self::POST_TYPE));
        exit;
    }

    /* ---------- Privacy ---------- */

    private static function by_email($email, $page = 1)
    {
        return get_posts(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 100,
            'paged' => max(1, (int)$page),
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_key' => '_dengine_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        ));
    }

    public static function exporters($exporters)
    {
        $exporters['document-engine-requests'] = array(
            'exporter_friendly_name' => __('Accessible format requests', 'document-engine'),
            'callback' => function ($email, $page = 1) {
                $items = array();
                $posts = self::by_email($email, $page);
                foreach ($posts as $post) {
                    $items[] = array(
                        'group_id' => 'document-engine-requests',
                        'group_label' => __('Accessible format requests', 'document-engine'),
                        'item_id' => 'dengine-request-' . $post->ID,
                        'data' => array(
                            array('name' => __('Request', 'document-engine'), 'value' => $post->post_title),
                            array('name' => __('Name', 'document-engine'), 'value' => get_post_meta($post->ID, '_dengine_name', true)),
                            array('name' => __('Note', 'document-engine'), 'value' => get_post_meta($post->ID, '_dengine_note', true)),
                            array('name' => __('Date', 'document-engine'), 'value' => $post->post_date),
                        ),
                    );
                }
                return array('data' => $items, 'done' => count($posts) < 100);
            },
        );
        return $exporters;
    }

    public static function erasers($erasers)
    {
        $erasers['document-engine-requests'] = array(
            'eraser_friendly_name' => __('Accessible format requests', 'document-engine'),
            'callback' => function ($email) {
                // Always the first page: deleted posts drop out, so the next call gets the next batch.
                $posts = self::by_email($email, 1);
                $removed = 0;
                foreach ($posts as $post) {
                    $removed += wp_delete_post($post->ID, true) ? 1 : 0;
                }
                return array('items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => count($posts) < 100);
            },
        );
        return $erasers;
    }
}
