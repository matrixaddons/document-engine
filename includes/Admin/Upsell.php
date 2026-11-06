<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Pro previews for free users: shown where the features would live, never as nag notices.
 * Everything here switches off when Document Engine Pro is active.
 */
class Upsell
{
    const SLUG = 'dengine-pro-feature';

    public static function init()
    {
        if (defined('DOCUMENT_ENGINE_PRO_FILE')) {
            return;
        }
        add_action('admin_menu', array(__CLASS__, 'menu'), 30);
        add_action('admin_menu', array(__CLASS__, 'preview_tabs'), 31);
        add_filter('document_engine_menu_previews', array(__CLASS__, 'preview_slugs'));
        add_filter('document_engine_settings_tabs_array', array(__CLASS__, 'settings_tab'), 80);
        add_action('document_engine_settings_pro_preview', array(__CLASS__, 'settings_tab_body'));
        add_filter('document_engine_settings_tab_has_form', function ($has, $tab) {
            return $tab === 'pro_preview' ? false : $has;
        }, 10, 2);
        add_filter('document_engine_settings_tab_icons', function ($icons) {
            $icons['pro_preview'] = 'shield';
            return $icons;
        });
        add_filter('document_engine_settings_tab_descriptions', function ($d) {
            $d['pro_preview'] = __('Decide who can open each document and keep files private. Available in Pro.', 'document-engine');
            return $d;
        });
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'editor_data'), 20);
        add_action(PostType::CATEGORY . '_edit_form_fields', array(__CLASS__, 'category_row'));
    }

    /**
     * Feature previews (also used for the pages). Keyed by slug.
     */
    public static function features()
    {
        return apply_filters('document_engine_pro_previews', array(
            'access' => array(
                'icon' => 'shield',
                'title' => __('Access control & private files', 'document-engine'),
                'lead' => __('Decide who can open every document — by role, by person, or for a whole category — and keep the files out of public reach.', 'document-engine'),
                'points' => array(
                    __('Restrict documents or entire categories to logged-in users, roles, named people or one shared password', 'document-engine'),
                    __('Protected storage: files move out of the public uploads folder', 'document-engine'),
                    __('Expiring share links for people without an account', 'document-engine'),
                    __('Secure viewer with per-reader watermarks; no download, print or copy', 'document-engine'),
                ),
            ),
            'activity' => array(
                'icon' => 'activity',
                'title' => __('Activity & analytics', 'document-engine'),
                'lead' => __('See who opened and downloaded what, and when — with a full audit log you can export.', 'document-engine'),
                'points' => array(
                    __('Views, downloads, people and blocked requests over time', 'document-engine'),
                    __('Per-document history and top documents', 'document-engine'),
                    __('Filterable audit log with CSV export and retention settings', 'document-engine'),
                    __('Privacy-friendly: no IP addresses stored', 'document-engine'),
                ),
            ),
            'leads' => array(
                'icon' => 'users',
                'title' => __('Email gate & leads', 'document-engine'),
                'lead' => __('Ask for a name and email before a download, with consent — and send leads to your CRM.', 'document-engine'),
                'points' => array(
                    __('Per document or site-wide', 'document-engine'),
                    __('Consent checkbox and spam protection built in', 'document-engine'),
                    __('Webhook to Zapier, Make or your CRM; CSV export', 'document-engine'),
                    __('Returning visitors are remembered', 'document-engine'),
                ),
            ),
            'versions' => array(
                'icon' => 'clock',
                'title' => __('Versions and review dates', 'document-engine'),
                'lead' => __('Replace a file and keep the old one. Know which documents are out of date before someone complains.', 'document-engine'),
                'points' => array(
                    __('Every earlier file is kept and can be restored, with the link unchanged', 'document-engine'),
                    __('Review-by dates with a daily reminder email', 'document-engine'),
                    __('Unpublish on a date, automatically', 'document-engine'),
                    __('Upload a new version straight from the documents list', 'document-engine'),
                ),
            ),
            'acks' => array(
                'icon' => 'check',
                'title' => __('Read and confirm', 'document-engine'),
                'lead' => __('Ask staff or members to confirm they have read a policy, and have the evidence ready for an inspection.', 'document-engine'),
                'points' => array(
                    __('Choose roles, set a deadline, and reminder emails go out on their own', 'document-engine'),
                    __('A Required Reading block shows each person what they still owe', 'document-engine'),
                    __('Export who confirmed what and when, with a fingerprint of the file', 'document-engine'),
                    __('Start a new round after an important change', 'document-engine'),
                ),
            ),
            'fields' => array(
                'icon' => 'layout',
                'title' => __('Your own fields and more files', 'document-engine'),
                'lead' => __('Turn the library into a register: a reference number, a department, a meeting date, and every format of the document in one place.', 'document-engine'),
                'points' => array(
                    __('Text, number, date, choice, yes/no, link and email fields, or your ACF fields', 'document-engine'),
                    __('Show them as library columns, filters with counts and sort options', 'document-engine'),
                    __('More files on one document: PDF and Word versions, appendices', 'document-engine'),
                    __('Export the register as a spreadsheet, edit it, import it back', 'document-engine'),
                ),
            ),
            'governance' => array(
                'icon' => 'shield',
                'title' => __('Approvals, change history and legal hold', 'document-engine'),
                'lead' => __('For teams who answer to auditors: nothing goes live without sign-off, every change has a name on it, and records can\'t be deleted while they matter.', 'document-engine'),
                'points' => array(
                    __('Publishing approval with separation of duties and a fingerprint of the file approved', 'document-engine'),
                    __('A chained change history you can check, export, or copy to your log system', 'document-engine'),
                    __('Legal hold and retention rules per category; nothing is ever deleted for good by a rule', 'document-engine'),
                    __('Category managers, plus a read-only REST API for your reporting tools', 'document-engine'),
                ),
            ),
            'search' => array(
                'icon' => 'search',
                'title' => __('Search inside files', 'document-engine'),
                'lead' => __('Visitors find the words inside your files, not only titles and descriptions. And you see what they couldn\'t find.', 'document-engine'),
                'points' => array(
                    __('PDF, Word, Excel, PowerPoint, OpenDocument and plain text', 'document-engine'),
                    __('In libraries and in your site search', 'document-engine'),
                    __('Restricted documents never match for people who can\'t open them', 'document-engine'),
                    __('A report of the searches that found nothing', 'document-engine'),
                ),
            ),
            'import' => array(
                'icon' => 'upload',
                'title' => __('Bulk import, versions & more', 'document-engine'),
                'lead' => __('Move a whole archive in at once and keep every document current.', 'document-engine'),
                'points' => array(
                    __('Drag in hundreds of files or import a spreadsheet', 'document-engine'),
                    __('Versions: replace a file without changing its link, restore any time', 'document-engine'),
                    __('Review and expiry dates with reminder emails', 'document-engine'),
                    __('Search inside PDF, Word, Excel and PowerPoint files', 'document-engine'),
                ),
            ),
        ));
    }

    /**
     * @param string $feature Preview to open.
     * @param string $from    The prompt that led here; passed on to the store so sales can be attributed without tracking in the plugin.
     */
    public static function url($feature, $from = '')
    {
        return admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . self::SLUG . '&feature=' . $feature . ($from !== '' ? '&from=' . rawurlencode($from) : ''));
    }

    public static function menu()
    {
        // Opened from Pro previews in the editor and settings; not listed in the WordPress menu.
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        $hook = add_submenu_page($parent, __('Document Engine Pro', 'document-engine'), __('Pro feature', 'document-engine'), 'edit_dengine_documents', self::SLUG, array(__CLASS__, 'render'));
        remove_submenu_page($parent, self::SLUG);
        // Pages removed from the menu have no title; give the browser tab one.
        add_action('load-' . $hook, function () {
            $GLOBALS['title'] = __('Document Engine Pro', 'document-engine');
        });
    }

    /**
     * Pro screens a free site would look for inside Reports and Tools (Activity, Import): shown as a
     * "Pro" tab next to the free screens of the same group, never as menu items of their own.
     */
    public static function preview_slugs($slugs = array())
    {
        return array_merge((array)$slugs, array('dengine-activity' => 'activity', 'dengine-import' => 'import'));
    }

    public static function preview_tabs()
    {
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        $caps = array('dengine-activity' => 'edit_others_dengine_documents', 'dengine-import' => 'publish_dengine_documents');
        $features = self::features();
        foreach (self::preview_slugs() as $slug => $feature) {
            if (!isset($features[$feature])) {
                continue;
            }
            add_submenu_page($parent, $features[$feature]['title'], $feature === 'activity' ? __('Activity', 'document-engine') : __('Import', 'document-engine'), $caps[$slug], $slug, function () use ($feature) {
                $features = self::features();
                UI::page_start($features[$feature]['title'], esc_html__('Available in Document Engine Pro.', 'document-engine'));
                self::preview($features[$feature], $feature);
                echo self::sample($feature); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                UI::page_end();
            });
        }
    }

    public static function render()
    {
        $features = self::features();
        $key = isset($_GET['feature']) ? sanitize_key($_GET['feature']) : 'access'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $feature = isset($features[$key]) ? $features[$key] : $features['access'];
        UI::page_start($feature['title'], esc_html__('Available in Document Engine Pro.', 'document-engine'));
        self::preview($feature, $key);
        echo self::sample($key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        UI::page_end();
    }

    /**
     * The shared preview block: explanation, benefits, and both paths (buy / how to install after buying).
     */
    public static function preview($feature, $key = '')
    {
        $key = $key !== '' ? $key : sanitize_key($feature['icon']);
        $can_buy = current_user_can('manage_options');
        $store = add_query_arg('utm_content', ProPage::from() ?: $key, ProPage::store_url('preview-' . $key)) . '#pricing';
        ?>
        <div class="dengine-a-preview">
            <div class="dengine-a-preview__main">
                <span class="dengine-a-preview__icon"><?php echo UI::icon($feature['icon'], 26); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span class="dengine-a-pill dengine-a-pill--pro"><?php esc_html_e('Pro', 'document-engine'); ?></span>
                <h3><?php echo esc_html($feature['title']); ?></h3>
                <p class="dengine-a-preview__lead"><?php echo esc_html($feature['lead']); ?></p>
                <ul class="dengine-a-preview__points">
                    <?php foreach ($feature['points'] as $point) : ?>
                        <li><?php echo UI::icon('check', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html($point); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="dengine-a-preview__actions">
                    <?php if ($can_buy) : ?>
                        <a class="dengine-a-btn dengine-a-btn--primary" href="<?php echo esc_url($store); ?>" target="_blank" rel="noopener"><?php esc_html_e('See plans and pricing', 'document-engine'); ?><?php echo UI::icon('external', 14); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <?php else : ?>
                        <a class="dengine-a-btn dengine-a-btn--primary" href="<?php echo esc_url(self::ask_admin_link($feature)); ?>"><?php esc_html_e('Ask your administrator', 'document-engine'); ?></a>
                    <?php endif; ?>
                    <a class="dengine-a-btn dengine-a-btn--secondary" href="<?php echo esc_url(ProPage::url(ProPage::from())); ?>"><?php esc_html_e('Compare free and Pro', 'document-engine'); ?></a>
                </p>
                <p class="dengine-a-help"><?php echo esc_html(ProPage::reassurance()); ?></p>
            </div>
            <aside class="dengine-a-preview__side">
                <?php ProPage::install_card(); ?>
            </aside>
        </div>
        <?php
    }

    /**
     * The person who needs a feature often isn't the person who can buy it: a ready email to whoever is.
     */
    private static function ask_admin_link($feature)
    {
        $subject = sprintf(
            /* translators: %s: feature name */
            __('Could we add %s to our document library?', 'document-engine'),
            $feature['title']
        );
        $body = $feature['lead'] . "\n\n" . ProPage::store_url('ask-admin') . "\n\n" . ProPage::reassurance();
        return 'mailto:?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
    }

    /**
     * Free category screen: the one place people go to restrict a whole category. A disabled row, not a working-looking one.
     */
    public static function category_row()
    {
        if (!Nudges::can_show('t11-category', false)) {
            return;
        }
        ?>
        <tr class="form-field">
            <th scope="row"><?php esc_html_e('Who can open these documents', 'document-engine'); ?> <span class="dengine-a-pill dengine-a-pill--pro"><?php esc_html_e('Pro', 'document-engine'); ?></span></th>
            <td>
                <select disabled aria-label="<?php esc_attr_e('Who can open these documents (available in Pro)', 'document-engine'); ?>"><option><?php esc_html_e('Everyone', 'document-engine'); ?></option></select>
                <p class="description"><?php esc_html_e('Pro limits a whole category to logged-in users, roles, named people or one shared password, and keeps its files private.', 'document-engine'); ?> <a href="<?php echo esc_url(self::url('access', 't11-category')); ?>"><?php esc_html_e('See how Pro does this', 'document-engine'); ?></a></p>
            </td>
        </tr>
        <?php
    }

    public static function settings_tab($tabs)
    {
        $tabs['pro_preview'] = __('Access & security', 'document-engine');
        return $tabs;
    }

    public static function settings_tab_body()
    {
        $features = self::features();
        self::preview($features['access']);
        echo self::sample('access'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * What the Pro screen looks like, filled with clearly labelled example data (not the site's data).
     */
    public static function sample($key)
    {
        $kpi = function ($icon, $label, $value) {
            return '<div class="dengine-a-kpi"><span class="dengine-a-kpi__label">' . UI::icon($icon, 16) . esc_html($label) . '</span><span class="dengine-a-kpi__value">' . esc_html($value) . '</span></div>';
        };
        $rows = function ($head, $rows) {
            $html = '<div class="dengine-a-table-wrap"><table class="dengine-a-table"><thead><tr>';
            foreach ($head as $h) {
                $html .= '<th scope="col">' . esc_html($h) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . esc_html($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            return $html . '</tbody></table></div>';
        };
        ob_start();
        UI::card_start(__('What it looks like', 'document-engine') . ' ', '', '<span class="dengine-a-example">' . esc_html__('Example data', 'document-engine') . '</span>');
        echo '<div class="dengine-a-sample" aria-label="' . esc_attr__('Example of the Pro screen, not your data', 'document-engine') . '">';
        if ($key === 'activity') {
            $tiles = '<div class="dengine-a-kpis">' . $kpi('viewer', __('Views', 'document-engine'), '498') . $kpi('download', __('Downloads', 'document-engine'), '385') . $kpi('users', __('People', 'document-engine'), '61') . $kpi('lock', __('Blocked', 'document-engine'), '3') . '</div>';
            echo $tiles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $kpi()
            $table = $rows(
                array(__('When', 'document-engine'), __('Event', 'document-engine'), __('Document', 'document-engine'), __('Who', 'document-engine')),
                array(
                    array(__('2 minutes ago', 'document-engine'), __('Viewed', 'document-engine'), __('Code of Conduct', 'document-engine'), 'Sam Carter'),
                    array(__('1 hour ago', 'document-engine'), __('Downloaded', 'document-engine'), __('Budget 2026/27', 'document-engine'), 'Priya Shah'),
                    array(__('3 hours ago', 'document-engine'), __('Blocked', 'document-engine'), __('Staff Handbook', 'document-engine'), __('Visitor', 'document-engine')),
                    array(__('Yesterday', 'document-engine'), __('Share link', 'document-engine'), __('Annual Report', 'document-engine'), __('External auditor', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'import') {
            $table = $rows(
                array('title', 'file_url', 'category', 'cf:reference', __('Result', 'document-engine')),
                array(
                    array('Expenses Policy', '…/expenses-policy.pdf', 'policies', 'RC-COR-121', __('Created', 'document-engine')),
                    array('Planning Minutes, May', '…/minutes-may.pdf', 'meeting-minutes', 'RC-DEM-044', __('Created', 'document-engine')),
                    array('Budget 2026/27', '…/budget-v2.pdf', 'reports', 'RC-FIN-002', __('Updated (new version)', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'versions') {
            $table = $rows(
                array(__('Version', 'document-engine'), __('Uploaded', 'document-engine'), __('By', 'document-engine'), __('Note', 'document-engine')),
                array(
                    array('3', __('Today', 'document-engine'), 'Priya Shah', __('Updated for the 2026 rates', 'document-engine')),
                    array('2', __('12 March', 'document-engine'), 'Sam Carter', __('Corrected appendix B', 'document-engine')),
                    array('1', __('9 January', 'document-engine'), 'Sam Carter', __('First published', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'acks') {
            $table = $rows(
                array(__('Person', 'document-engine'), __('Status', 'document-engine'), __('When', 'document-engine'), __('File fingerprint', 'document-engine')),
                array(
                    array('Sam Carter', __('Confirmed', 'document-engine'), __('2 October', 'document-engine'), '9f3c…a1e4'),
                    array('Priya Shah', __('Confirmed', 'document-engine'), __('3 October', 'document-engine'), '9f3c…a1e4'),
                    array('Alex Morgan', __('Overdue, reminder sent', 'document-engine'), '–', '–'),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'fields') {
            $table = $rows(
                array(__('Document', 'document-engine'), __('Reference', 'document-engine'), __('Department', 'document-engine'), __('Files', 'document-engine')),
                array(
                    array(__('Expenses Policy', 'document-engine'), 'RC-COR-121', __('Finance', 'document-engine'), 'PDF · DOCX'),
                    array(__('Planning Minutes, May', 'document-engine'), 'RC-DEM-044', __('Planning', 'document-engine'), 'PDF'),
                    array(__('Budget 2026/27', 'document-engine'), 'RC-FIN-002', __('Finance', 'document-engine'), 'PDF · XLSX'),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'governance') {
            $table = $rows(
                array(__('When', 'document-engine'), __('Who', 'document-engine'), __('Change', 'document-engine'), __('Details', 'document-engine')),
                array(
                    array(__('Today 09:14', 'document-engine'), 'p.shah', __('Approved', 'document-engine'), __('Expenses Policy · sha256 9f3c…a1e4', 'document-engine')),
                    array(__('Today 09:02', 'document-engine'), 's.carter', __('Sent for approval', 'document-engine'), __('Expenses Policy (draft → pending)', 'document-engine')),
                    array(__('Yesterday', 'document-engine'), 'a.morgan', __('Access changed', 'document-engine'), __('Staff Handbook · who can open it: everyone → roles', 'document-engine')),
                    array(__('Yesterday', 'document-engine'), 'p.shah', __('Legal hold set', 'document-engine'), __('Contract 2024-17 · dispute with supplier', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'search') {
            $table = $rows(
                array(__('Search', 'document-engine'), __('Searches', 'document-engine'), __('Found', 'document-engine')),
                array(
                    array('parking permit', '41', __('3 documents (inside the files)', 'document-engine')),
                    array('council tax 2026', '28', __('1 document', 'document-engine')),
                    array('allotment waiting list', '17', __('Nothing: you may want to publish this', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } elseif ($key === 'leads') {
            $table = $rows(
                array(__('When', 'document-engine'), __('Name', 'document-engine'), __('Email', 'document-engine'), __('Document', 'document-engine')),
                array(
                    array(__('Today', 'document-engine'), 'Alex Morgan', 'alex@example.org', __('Park Masterplan', 'document-engine')),
                    array(__('Yesterday', 'document-engine'), 'Jo Lee', 'jo@example.com', __('Annual Report', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        } else {
            $table = $rows(
                array(__('Setting', 'document-engine'), __('This document', 'document-engine')),
                array(
                    array(__('Who can open it', 'document-engine'), __('Logged-in users', 'document-engine')),
                    array(__('Private file storage', 'document-engine'), __('On (the file is out of the public uploads folder)', 'document-engine')),
                    array(__('Secure viewer', 'document-engine'), __('On: read-only, "Sam Carter · sam@example.org" on every page', 'document-engine')),
                    array(__('Share link', 'document-engine'), __('External auditor · expires in 7 days · opened twice', 'document-engine')),
                    array(__('Read and confirm', 'document-engine'), __('5 of 6 staff confirmed · due 10 October', 'document-engine')),
                )
            );
            echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $rows()
        }
        echo '</div>';
        UI::card_end();
        return ob_get_clean();
    }

    /**
     * Lets the document editor show a small "Access & security (Pro)" panel.
     */
    public static function editor_data()
    {
        if (!wp_script_is('document-engine-document-editor', 'enqueued')) {
            return;
        }
        // Contextual only (T1: a private or password document's file is still public; T4: replacing a file).
        wp_add_inline_script('document-engine-document-editor', 'window.DocumentEngineEditor = Object.assign(window.DocumentEngineEditor || {}, ' . wp_json_encode(array(
            'nudges' => array(
                // These answer what the admin just did, so they don't wait for the first-week quiet period.
                'privateFile' => Nudges::can_show('t1-private-file', false) ? self::url('access', 't1-private-file') : '',
                'versions' => Nudges::can_show('t4-versions', false) ? self::url('versions', 't4-versions') : '',
                'moreFiles' => Nudges::can_show('t10-more-files') ? self::url('fields', 't10-more-files') : '',
                'ajax' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dengine_nudge'),
            ),
        )) . ');', 'before');
    }
}
