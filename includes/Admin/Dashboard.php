<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Documents → Dashboard: the landing screen (numbers, checklist, quick actions, recent activity).
 */
class Dashboard
{
    const SLUG = 'dengine-dashboard';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'), 8);
        add_action('admin_init', array(__CLASS__, 'activation_redirect'));
        add_action('admin_post_dengine_create_library_page', array(__CLASS__, 'create_library_page'));
    }

    public static function url()
    {
        return admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . self::SLUG);
    }

    public static function menu()
    {
        add_submenu_page('edit.php?post_type=' . PostType::POST_TYPE, __('Dashboard', 'document-engine'), __('Dashboard', 'document-engine'), 'edit_dengine_documents', self::SLUG, array(__CLASS__, 'render'), 0);
    }

    /**
     * First activation (single plugin, not bulk) lands on the dashboard once.
     */
    public static function activation_redirect()
    {
        if (!get_transient('document_engine_activation_redirect')) {
            return;
        }
        delete_transient('document_engine_activation_redirect');
        if (wp_doing_ajax() || is_network_admin() || isset($_GET['activate-multi']) || !current_user_can('edit_dengine_documents')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        wp_safe_redirect(self::url());
        exit;
    }

    /**
     * Quick action: a draft page with a ready-made library, opened in the editor.
     */
    public static function create_library_page()
    {
        check_admin_referer('dengine_create_library_page');
        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('You cannot create pages.', 'document-engine'), 403);
        }
        $page = wp_insert_post(array(
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => __('Documents', 'document-engine'),
            'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html__('Find policies, reports and forms. Use the search box or filter by category.', 'document-engine') . "</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:document-engine/library {\"filters\":[\"category\",\"type\",\"sort\"]} /-->",
        ), true);
        if (is_wp_error($page)) {
            wp_die(esc_html($page->get_error_message()));
        }
        wp_safe_redirect(get_edit_post_link($page, 'raw'));
        exit;
    }

    private static function stat($icon, $label, $value, $hint = '', $url = '')
    {
        $inner = '<span class="dengine-a-kpi__label">' . UI::icon($icon, 16) . esc_html($label) . '</span><span class="dengine-a-kpi__value">' . esc_html($value) . '</span>' . ($hint !== '' ? '<span class="dengine-a-kpi__hint">' . esc_html($hint) . '</span>' : '');
        return $url ? '<a class="dengine-a-kpi dengine-a-kpi--link" href="' . esc_url($url) . '">' . $inner . '</a>' : '<div class="dengine-a-kpi">' . $inner . '</div>';
    }

    public static function render()
    {
        global $wpdb;
        $type = PostType::POST_TYPE;
        $counts = wp_count_posts($type);
        $published = $counts ? (int)$counts->publish : 0;
        $pending = $counts ? (int)$counts->pending : 0;
        $downloads = (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'", Document::META_DOWNLOADS, $type)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $categories = (int)wp_count_terms(array('taxonomy' => PostType::CATEGORY, 'hide_empty' => false));
        $user = wp_get_current_user();
        $create = wp_nonce_url(admin_url('admin-post.php?action=dengine_create_library_page'), 'dengine_create_library_page');

        UI::page_start(
            /* translators: %s: first name */
            sprintf(__('Welcome, %s', 'document-engine'), $user->first_name ?: $user->display_name),
            esc_html__('Your document library at a glance.', 'document-engine'),
            array(
                array(__('Add document', 'document-engine'), admin_url('post-new.php?post_type=' . $type), true, 'plus'),
            ),
            'dengine-a--dashboard'
        );

        $installed = get_transient('dengine_install_pro_' . get_current_user_id());
        if (is_array($installed) && !empty($installed['ok'])) {
            delete_transient('dengine_install_pro_' . get_current_user_id());
            echo '<div class="dengine-a-notice dengine-a-notice--success" role="status">' . UI::icon('check', 16) . esc_html($installed['message']) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // At most one: the review request, or a usage milestone (admins only, after the first week).
        echo Nudges::dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<div class="dengine-a-kpis">';
        echo self::stat('documents', __('Documents', 'document-engine'), number_format_i18n($published), __('Published', 'document-engine'), admin_url('edit.php?post_type=' . $type)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::stat('download', __('Downloads', 'document-engine'), number_format_i18n($downloads), __('All time', 'document-engine')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::stat('folder', __('Categories', 'document-engine'), number_format_i18n($categories), '', admin_url('edit-tags.php?taxonomy=' . PostType::CATEGORY . '&post_type=' . $type)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::stat('bell', __('Awaiting review', 'document-engine'), number_format_i18n($pending), '', admin_url('edit.php?post_status=pending&post_type=' . $type)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        do_action('document_engine_dashboard_stats');
        echo '</div>';

        echo '<div class="dengine-a-grid-2">';
        echo '<div>';
        self::checklist($create);

        $top = get_posts(array(
            'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 5, 'dengine_skip_access' => true,
            'meta_query' => array('dl' => array('key' => Document::META_DOWNLOADS, 'type' => 'NUMERIC', 'value' => 0, 'compare' => '>')), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'orderby' => array('dl' => 'DESC'),
        ));
        UI::card_start(__('Most downloaded', 'document-engine'), '', UI::button(__('All documents', 'document-engine'), admin_url('edit.php?post_type=' . $type), 'link'));
        self::doc_table($top, 'downloads');
        if ($top) {
            // T6: counts are free; who read what is Pro.
            echo Nudges::card('t6-downloads', __('These are download counts. Pro\'s activity log shows <strong>who</strong> opened each document, when, and for how long.', 'document-engine'), Upsell::url('activity')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        UI::card_end();

        // Library managers see everyone's drafts; others see published documents and their own drafts.
        $recent_args = array('post_type' => $type, 'post_status' => array('publish', 'pending', 'draft'), 'numberposts' => 5, 'orderby' => 'modified', 'dengine_skip_access' => true);
        if (!current_user_can('edit_others_dengine_documents')) {
            $own = get_posts(array_merge($recent_args, array('author' => get_current_user_id())));
            $published = get_posts(array_merge($recent_args, array('post_status' => 'publish')));
            $recent = array_values(array_reduce(array_merge($own, $published), function ($all, $p) {
                $all[$p->ID] = $p;
                return $all;
            }, array()));
            usort($recent, function ($a, $b) {
                return strcmp($b->post_modified_gmt, $a->post_modified_gmt);
            });
            $recent = array_slice($recent, 0, 5); // Newest first, then the top five.
        } else {
            $recent = get_posts($recent_args);
        }
        UI::card_start(__('Recently updated', 'document-engine'));
        self::doc_table($recent, 'modified');
        UI::card_end();
        echo '</div><div>';

        UI::card_start(__('Quick actions', 'document-engine'));
        echo '<div class="dengine-a-actions">';
        $actions = array(
            array('plus', __('Add a document', 'document-engine'), admin_url('post-new.php?post_type=' . $type)),
            array('upload', __('Turn Media Library files into documents', 'document-engine'), admin_url('upload.php?mode=list')),
            array('layout', __('Create a library page', 'document-engine'), $create),
            array('pdf', __('Set up Save as PDF', 'document-engine'), admin_url('admin.php?page=document-engine-settings&tab=pdf_downloads')),
        );
        foreach (apply_filters('document_engine_dashboard_actions', $actions) as $action) {
            echo '<a class="dengine-a-action" href="' . esc_url($action[2]) . '">' . UI::icon($action[0], 18) . '<span>' . esc_html($action[1]) . '</span>' . UI::icon('arrow-left', 14) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
        UI::card_end();


        do_action('document_engine_dashboard_sidebar');
        echo '</div></div>';
        UI::page_end();
    }

    private static function checklist($create)
    {
        $counts = wp_count_posts(PostType::POST_TYPE);
        $steps = array(
            array($counts && ((int)$counts->publish + (int)$counts->draft) > 0, __('Add your first documents', 'document-engine'), __('Upload files, or turn existing Media Library files into documents in bulk.', 'document-engine'), admin_url('post-new.php?post_type=' . PostType::POST_TYPE)),
            array((bool)get_posts(array('post_type' => array('page', 'post'), 'post_status' => 'any', 's' => 'document-engine/library', 'fields' => 'ids', 'numberposts' => 1)), __('Publish a document library', 'document-engine'), __('One click creates a page with a searchable library.', 'document-engine'), $create),
            array(count(document_engine_pdf_post_type()) > 0, __('Let visitors save posts as PDF', 'document-engine'), __('Optional: pick which content gets a Download PDF button.', 'document-engine'), admin_url('admin.php?page=document-engine-settings&tab=pdf_downloads')),
        );
        $done = count(array_filter(wp_list_pluck($steps, 0)));
        if ($done === count($steps)) {
            return;
        }
        UI::card_start(__('Get set up', 'document-engine'), '', '<span class="dengine-a-pill">' . esc_html(sprintf(
            /* translators: 1: done, 2: total */
            __('%1$d of %2$d done', 'document-engine'),
            $done,
            count($steps)
        )) . '</span>');
        echo '<ol class="dengine-a-checklist">';
        foreach ($steps as $step) {
            echo '<li class="' . ($step[0] ? 'is-done' : '') . '"><span class="dengine-a-checklist__mark">' . ($step[0] ? UI::icon('check', 14) : '') . '</span><div><a href="' . esc_url($step[3]) . '">' . esc_html($step[1]) . '</a><p>' . esc_html($step[2]) . '</p></div></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</ol>';
        // Free first: one optional line once the core steps are done (nothing pre-ticked, nothing installed).
        if ($steps[0][0] && $steps[1][0] && Nudges::can_show('onboarding-pro', false)) {
            echo '<p class="dengine-a-help">' . esc_html__('Need members-only documents or proof that staff read a policy?', 'document-engine') . ' <a href="' . esc_url(ProPage::url()) . '">' . esc_html__('Compare Free and Pro', 'document-engine') . '</a></p>';
        }
        UI::card_end();
    }

    private static function doc_table($posts, $mode)
    {
        if (!$posts) {
            $mode === 'downloads'
                ? UI::empty_state('download', __('No downloads yet', 'document-engine'), __('Your most downloaded documents will show up here.', 'document-engine'))
                : UI::empty_state('documents', __('Nothing here yet', 'document-engine'), __('Documents will show up here as soon as you add some.', 'document-engine'));
            return;
        }
        echo '<div class="dengine-a-table-wrap"><table class="dengine-a-table"><tbody>';
        foreach ($posts as $post) {
            $document = Document::get($post);
            if (!$document) {
                continue;
            }
            $right = $mode === 'downloads'
                ? sprintf(
                    /* translators: %s: number */
                    _n('%s download', '%s downloads', $document->get_download_count(), 'document-engine'),
                    number_format_i18n($document->get_download_count())
                )
                : sprintf(
                    /* translators: %s: time ago */
                    __('%s ago', 'document-engine'),
                    human_time_diff(get_post_modified_time('U', true, $post), time())
                );
            $edit = get_edit_post_link($post->ID);
            $status = $post->post_status !== 'publish' ? ' <span class="dengine-a-pill dengine-a-pill--warning">' . esc_html(get_post_status_object($post->post_status)->label) . '</span>' : '';
            echo '<tr><td><span class="dengine-admin-file">' . document_engine_file_icon($document->get_extension(), 'dengine-icon--inline') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                . ($edit ? '<a href="' . esc_url($edit) . '">' . esc_html($document->get_title()) . '</a>' : '<span>' . esc_html($document->get_title()) . '</span>') . $status . '</span></td><td class="num"><span class="dengine-a-sub">' . esc_html($right) . '</span></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</tbody></table></div>';
    }
}
