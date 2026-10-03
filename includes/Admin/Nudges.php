<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Contextual Pro suggestions and the review request, kept within WordPress.org guideline 11:
 * only for administrators, only on Document Engine's own screens, inline (never modals or
 * site-wide notices), at most one per screen, each dismissible for good, nothing in the first
 * 7 days, and nothing at all once Pro is active.
 */
class Nudges
{
    const META = 'dengine_nudges_dismissed';
    const QUIET_DAYS = 7;
    const REVIEW_URL = 'https://wordpress.org/support/plugin/document-engine/reviews/#new-post';

    private static $shown_on_screen = false;
    private static $script_printed = false;

    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'first_seen'));
        add_action('wp_ajax_dengine_dismiss_nudge', array(__CLASS__, 'dismiss'));
    }

    public static function first_seen()
    {
        if (!get_option('document_engine_first_seen')) {
            add_option('document_engine_first_seen', time(), '', false);
        }
    }

    public static function days_installed()
    {
        $first = (int)get_option('document_engine_first_seen', time());
        return (int)floor((time() - $first) / DAY_IN_SECONDS);
    }

    private static function dismissed()
    {
        $list = get_user_meta(get_current_user_id(), self::META, true);
        return is_array($list) ? $list : array();
    }

    public static function is_dismissed($id)
    {
        $list = self::dismissed();
        if (!isset($list[$id])) {
            return false;
        }
        // "Not now" stores a time to ask again; "Don't show again" stores true.
        return $list[$id] === true || (int)$list[$id] > time();
    }

    /**
     * Whether a suggestion may be shown to the current user now.
     *
     * @param string $id         Suggestion id (also the utm_content of its link).
     * @param bool   $contextual False for static "Pro" labels, which ignore the quiet period.
     */
    public static function can_show($id, $contextual = true)
    {
        if (UI::is_pro() || !current_user_can('manage_options') || self::is_dismissed($id)) {
            return false;
        }
        if ($contextual && self::days_installed() < self::QUIET_DAYS) {
            return false;
        }
        return (bool)apply_filters('document_engine_show_pro_suggestion', true, $id);
    }

    /**
     * A link to the Pro page on our site, attributed to the suggestion (no tracking in the plugin itself).
     */
    public static function store_link($id, $anchor = 'pro')
    {
        return ProPage::store_url('suggestion') . '&utm_content=' . rawurlencode($id) . '#' . $anchor;
    }

    /**
     * Inline card: a fact, what Pro does about it, one link, and "Don't show again".
     */
    public static function card($id, $text, $link_url = '', $link_label = '', $once_per_screen = true)
    {
        if (!self::can_show($id) || ($once_per_screen && self::$shown_on_screen)) {
            return '';
        }
        self::$shown_on_screen = true;
        $html = '<div class="dengine-nudge" data-dengine-nudge="' . esc_attr($id) . '">'
            . '<p class="dengine-nudge__text">' . wp_kses($text, array('strong' => array(), 'em' => array())) . '</p>'
            . '<p class="dengine-nudge__actions">';
        if ($link_url !== '') {
            $external = strpos($link_url, admin_url()) !== 0;
            $html .= '<a href="' . esc_url($link_url) . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . esc_html($link_label !== '' ? $link_label : __('See how Pro does this', 'document-engine')) . '</a>';
        }
        $html .= '<button type="button" class="button-link dengine-nudge__dismiss">' . esc_html__('Don\'t show again', 'document-engine') . '</button></p></div>';
        return $html . self::script();
    }

    /**
     * Shown once per user: marks the suggestion as seen right away (for one-off moments such as a migration result).
     */
    public static function once($id)
    {
        $list = self::dismissed();
        $list[$id] = true;
        update_user_meta(get_current_user_id(), self::META, $list);
    }

    public static function dismiss()
    {
        check_ajax_referer('dengine_nudge', 'nonce');
        $id = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';
        if ($id === '' || !current_user_can('edit_dengine_documents')) {
            wp_send_json_error(null, 403);
        }
        $list = self::dismissed();
        $later = isset($_POST['later']) ? absint($_POST['later']) : 0;
        $list[$id] = $later ? time() + min(90, $later) * DAY_IN_SECONDS : true;
        update_user_meta(get_current_user_id(), self::META, $list);
        wp_send_json_success();
    }

    private static function script()
    {
        if (self::$script_printed) {
            return '';
        }
        self::$script_printed = true;
        $data = wp_json_encode(array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('dengine_nudge')));
        return '<script>(function(){var c=' . $data . ';document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest(".dengine-nudge__dismiss,[data-dengine-later]");if(!b){return;}var n=b.closest("[data-dengine-nudge]");if(!n){return;}if(b.tagName!=="A"){e.preventDefault();}var f=new FormData();f.append("action","dengine_dismiss_nudge");f.append("nonce",c.nonce);f.append("id",n.getAttribute("data-dengine-nudge"));if(b.hasAttribute("data-dengine-later")){f.append("later",b.getAttribute("data-dengine-later"));}fetch(c.url,{method:"POST",body:f,credentials:"same-origin"});n.remove();});})();</script>';
    }

    /**
     * Documents → Dashboard only: the review request first (it isn't an upsell), then one usage milestone,
     * at most one every 30 days.
     */
    public static function dashboard()
    {
        if (UI::is_pro() || !current_user_can('manage_options') || self::days_installed() < self::QUIET_DAYS) {
            return '';
        }
        $published = (int)wp_count_posts(PostType::POST_TYPE)->publish;

        if (self::days_installed() >= 21 && $published >= 5 && !self::is_dismissed('review') && self::has_library_page()) {
            self::$shown_on_screen = true;
            return '<div class="dengine-nudge dengine-nudge--review" data-dengine-nudge="review">'
                . '<p class="dengine-nudge__text">' . esc_html__('Is Document Engine working well for you? A short review on WordPress.org helps other councils, schools and teams find it, and tells us what to improve.', 'document-engine') . '</p>'
                . '<p class="dengine-nudge__actions"><a class="button button-primary dengine-nudge__dismiss" href="' . esc_url(self::REVIEW_URL) . '" target="_blank" rel="noopener">' . esc_html__('Write a review', 'document-engine') . '</a>'
                . '<button type="button" class="button-link" data-dengine-later="30">' . esc_html__('Not now', 'document-engine') . '</button>'
                . '<button type="button" class="button-link dengine-nudge__dismiss">' . esc_html__('Don\'t ask again', 'document-engine') . '</button></p></div>' . self::script();
        }

        $last = (int)get_user_meta(get_current_user_id(), 'dengine_nudge_milestone_at', true);
        if ($last && $last > time() - 30 * DAY_IN_SECONDS) {
            $current = (string)get_user_meta(get_current_user_id(), 'dengine_nudge_milestone', true);
            // Keep showing the one already chosen this month until it's dismissed.
            if ($current === '' || self::is_dismissed($current)) {
                return '';
            }
            return self::milestone_card($current, $published);
        }

        $pick = '';
        if ($published >= 25 && !self::is_dismissed('m-25-documents')) {
            $pick = 'm-25-documents';
        } elseif (self::total_downloads() >= 1000 && !self::is_dismissed('m-1000-downloads')) {
            $pick = 'm-1000-downloads';
        }
        if ($pick === '') {
            return '';
        }
        update_user_meta(get_current_user_id(), 'dengine_nudge_milestone_at', time());
        update_user_meta(get_current_user_id(), 'dengine_nudge_milestone', $pick);
        return self::milestone_card($pick, $published);
    }

    private static function milestone_card($id, $published)
    {
        if ($id === 'm-25-documents') {
            /* translators: %s: number of published documents */
            $text = sprintf(__('Your library has %s documents. Pro adds review-by dates and versions, so it stays current as it grows.', 'document-engine'), number_format_i18n($published));
            return self::card($id, $text, Upsell::url('import'));
        }
        /* translators: %s: total downloads */
        $text = sprintf(__('Your documents have been downloaded %s times. Pro shows who opened what, and for how long.', 'document-engine'), number_format_i18n(self::total_downloads()));
        return self::card($id, $text, Upsell::url('activity'));
    }

    private static function total_downloads()
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_dengine_downloads'));
    }

    private static function has_library_page()
    {
        return (bool)\MatrixAddons\DocumentEngine\Library\SearchBox::default_page();
    }
}
