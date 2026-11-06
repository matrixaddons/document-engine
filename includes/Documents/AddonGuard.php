<?php

namespace MatrixAddons\DocumentEngine\Documents;

use MatrixAddons\DocumentEngine\Admin\UI;

defined('ABSPATH') || exit;

/**
 * Document Engine Pro's rules live in Pro, so switching Pro off would open every restricted or
 * view-only document to everyone without warning. While Pro is off those documents stay closed
 * (people who can edit them still get in), and administrators can choose to open them.
 *
 * Does nothing on sites that never ran Pro.
 */
class AddonGuard
{
    const RELEASED = 'document_engine_pro_rules_released';

    private static $restricted = array();

    public static function init()
    {
        add_filter('document_engine_can_access_document', array(__CLASS__, 'filter_access'), 5, 4);
        add_filter('document_engine_single_content', array(__CLASS__, 'single_content'), 10, 4);
        add_filter('the_content', array(__CLASS__, 'blank'), 5);
        add_filter('get_the_excerpt', array(__CLASS__, 'blank'), 999);
        add_filter('wp_robots', array(__CLASS__, 'robots'));
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('admin_post_dengine_release_pro_rules', array(__CLASS__, 'release'));
        add_filter('pre_trash_post', array(__CLASS__, 'keep_held'), 10, 2);
        add_filter('pre_delete_post', array(__CLASS__, 'keep_held'), 10, 2);
    }

    /**
     * A legal hold set with Pro still stands while Pro is off.
     */
    public static function keep_held($check, $post)
    {
        if ($check === null && $post && $post->post_type === PostType::POST_TYPE && !did_action('document_engine_pro_loaded') && get_post_meta($post->ID, '_dengine_legal_hold', true)) {
            return false;
        }
        return $check;
    }

    public static function active()
    {
        return !did_action('document_engine_pro_loaded') && get_option('document_engine_pro_db_version', '') !== '' && get_option(self::RELEASED, '') !== 'yes';
    }

    /**
     * Reads the rule Pro stored: the document's own, otherwise its categories' (and their parents'),
     * and its view-only setting.
     */
    public static function is_closed($document_id)
    {
        $document_id = (int)$document_id;
        if (isset(self::$restricted[$document_id])) {
            return self::$restricted[$document_id];
        }
        $modes = array('logged_in', 'roles', 'users', 'levels', 'password');
        $mode = (string)get_post_meta($document_id, '_dengine_access', true);
        $closed = in_array($mode, $modes, true);
        if (!$closed && ($mode === '' || $mode === 'inherit')) {
            $terms = get_the_terms($document_id, PostType::CATEGORY);
            foreach (is_array($terms) ? $terms : array() as $term) {
                foreach (array_merge(array($term->term_id), get_ancestors($term->term_id, PostType::CATEGORY, 'taxonomy')) as $id) {
                    if (in_array((string)get_term_meta($id, 'dengine_access', true), $modes, true)) {
                        $closed = true;
                        break 2;
                    }
                }
            }
        }
        if (!$closed) {
            $secure = (string)get_post_meta($document_id, '_dengine_secure', true);
            $default = (string)get_option('document_engine_pro_secure_default', 'off');
            $closed = $secure === 'on' || ($secure !== 'off' && $default === 'all');
        }
        return self::$restricted[$document_id] = $closed;
    }

    public static function filter_access($allowed, $document, $user_id, $context)
    {
        if (!$allowed || !self::active() || !self::is_closed($document->get_id())) {
            return $allowed;
        }
        return $user_id > 0 && user_can($user_id, 'edit_post', $document->get_id());
    }

    private static function denied_here($post = null)
    {
        $post = get_post($post);
        return $post && $post->post_type === PostType::POST_TYPE && self::active() && self::is_closed($post->ID) && !current_user_can('edit_post', $post->ID);
    }

    public static function single_content($html, $card, $content, $document)
    {
        return self::denied_here($document->get_post()) ? $card : $html;
    }

    public static function blank($text)
    {
        return self::denied_here() ? '' : $text;
    }

    public static function robots($robots)
    {
        if (is_singular(PostType::POST_TYPE) && self::denied_here(get_queried_object_id())) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }
        return $robots;
    }

    private static function count_closed()
    {
        $ids = get_posts(array('post_type' => PostType::POST_TYPE, 'post_status' => 'publish', 'fields' => 'ids', 'numberposts' => 2000));
        return count(array_filter($ids, array(__CLASS__, 'is_closed')));
    }

    public static function notice()
    {
        $screen = get_current_screen();
        if (!self::active() || !current_user_can('manage_options') || !$screen || !($screen->id === 'plugins' || $screen->post_type === PostType::POST_TYPE)) {
            return;
        }
        $count = self::count_closed();
        if (!$count) {
            return;
        }
        $release = wp_nonce_url(admin_url('admin-post.php?action=dengine_release_pro_rules'), 'dengine_release_pro_rules');
        echo '<div class="notice notice-warning"><p>' . esc_html(sprintf(
            /* translators: %s: number of documents */
            _n('Document Engine Pro is off, so %s restricted or view-only document stays closed to visitors. Turn Pro back on to apply its rules again.', 'Document Engine Pro is off, so %s restricted or view-only documents stay closed to visitors. Turn Pro back on to apply its rules again.', $count, 'document-engine'),
            number_format_i18n($count)
        )) . ' <a href="' . esc_url(admin_url('plugins.php')) . '">' . esc_html__('Go to Plugins', 'document-engine') . '</a> · <a href="' . esc_url($release) . '" onclick="return confirm(' . esc_attr(wp_json_encode(__('Open these documents to everyone? Anyone will be able to read them.', 'document-engine'))) . ')">' . esc_html__('Open them to everyone', 'document-engine') . '</a></p></div>';
    }

    public static function release()
    {
        check_admin_referer('dengine_release_pro_rules');
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'document-engine'), 403);
        }
        update_option(self::RELEASED, 'yes', false);
        wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=' . PostType::POST_TYPE));
        exit;
    }
}
