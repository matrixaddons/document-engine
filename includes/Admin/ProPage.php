<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Documents → Free vs Pro: what each edition includes, with one quiet way to upgrade.
 * Shown on free sites only.
 */
class ProPage
{
    const SLUG = 'document-engine-pro';

    public static function url()
    {
        return admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . self::SLUG);
    }

    public static function store_url($campaign = 'pro-page')
    {
        return add_query_arg(array(
            'utm_source' => 'wp-admin',
            'utm_medium' => 'plugin',
            'utm_campaign' => $campaign,
        ), apply_filters('document_engine_pro_url', 'https://matrixaddons.com/plugins/document-engine/'));
    }

    /**
     * Pro features, grouped. Also used by the docs/upsell cards.
     */
    public static function features()
    {
        return apply_filters('document_engine_pro_features', array(
            __('Control who sees what', 'document-engine') => array(
                __('Restrict documents and categories to logged-in users, roles, specific people or a password', 'document-engine'),
                __('Protected storage: files move out of the public uploads folder and are only served through signed, expiring links', 'document-engine'),
                __('Secure viewer: hide download and print, block copying, and stamp each page with the viewer\'s name, email and time', 'document-engine'),
                __('Email gate: ask for name and email (with consent) before a download, with CSV export and webhooks', 'document-engine'),
            ),
            __('Know what happens', 'document-engine') => array(
                __('Audit log of every view, download and denied request, per user, with CSV export and retention settings', 'document-engine'),
                __('Analytics dashboard: trends, top documents, most active users', 'document-engine'),
            ),
            __('Keep documents current', 'document-engine') => array(
                __('Versions: upload a new file without changing the link, see history and restore', 'document-engine'),
                __('Expiry and review dates with reminder emails', 'document-engine'),
                __('Search inside PDF, Word, Excel and PowerPoint files and automatic PDF thumbnails', 'document-engine'),
            ),
            __('Work faster', 'document-engine') => array(
                __('Bulk import by drag and drop or CSV', 'document-engine'),
                __('Download several documents as one ZIP', 'document-engine'),
                __('Front-end document submission with moderation', 'document-engine'),
                __('Handbook PDF: combine many posts into one PDF with a cover and contents', 'document-engine'),
                __('Watermarks (text or image) on generated PDFs', 'document-engine'),
                __('WP-CLI commands', 'document-engine'),
            ),
        ));
    }

    /**
     * Feature comparison: group => rows of array(feature, in free, in pro, docs article).
     * Every row describes a feature that exists in this version.
     */
    public static function comparison()
    {
        return apply_filters('document_engine_pro_comparison', array(
            __('Documents and libraries', 'document-engine') => array(
                array(__('Documents with their own pages, file details and a download counter', 'document-engine'), true, true, 'documents'),
                array(__('Libraries as a table, grid or folders, with instant search', 'document-engine'), true, true, 'library'),
                array(__('Filters by category, tag, file type, year and author, with multi-select and counts', 'document-engine'), true, true, 'library'),
                array(__('Your own fields (reference, department, dates…) as columns, filters and sort options, including ACF fields', 'document-engine'), false, true, 'fields'),
                array(__('Search inside PDF, Word, Excel and PowerPoint files', 'document-engine'), false, true, 'ai'),
                array(__('Download several documents as one ZIP', 'document-engine'), false, true, 'portal'),
                array(__('Bulk upload and CSV import, including updates by ID', 'document-engine'), false, true, 'import'),
                array(__('"My documents" page for clients or staff', 'document-engine'), false, true, 'portal'),
            ),
            __('Viewing', 'document-engine') => array(
                array(__('Built-in PDF viewer with search, thumbnails, outline and links', 'document-engine'), true, true, 'viewer'),
                array(__('Preview popup for PDFs, images, audio and video', 'document-engine'), true, true, 'preview'),
                array(__('QR code for every document', 'document-engine'), true, true, 'qr'),
                array(__('Secure viewer: no download or print, a watermark with the reader\'s name on every page', 'document-engine'), false, true, 'secure'),
                array(__('Downloaded PDFs stamped with the reader\'s details', 'document-engine'), false, true, 'secure'),
            ),
            __('Access and sharing', 'document-engine') => array(
                array(__('Draft, private and password-protected documents', 'document-engine'), true, true, 'faq-private'),
                array(__('Members-only documents by role, person or whole category', 'document-engine'), false, true, 'access'),
                array(__('Private file storage, so file addresses can\'t be shared', 'document-engine'), false, true, 'access'),
                array(__('Expiring share links, named per recipient, with open tracking', 'document-engine'), false, true, 'share'),
                array(__('Email gate: name and email before download, with leads export', 'document-engine'), false, true, 'gate'),
                array(__('Daily download limits and Cloudflare Turnstile on forms', 'document-engine'), false, true, 'protection'),
            ),
            __('Compliance and insight', 'document-engine') => array(
                array(__('Accessible format requests from visitors', 'document-engine'), true, true, 'format-requests'),
                array(__('Activity log: views, downloads and blocked attempts, with CSV export', 'document-engine'), false, true, 'analytics'),
                array(__('Reading analytics: time per page and how far people read', 'document-engine'), false, true, 'analytics'),
                array(__('Search insights: what visitors look for and don\'t find', 'document-engine'), false, true, 'analytics'),
                array(__('Read and confirm, with reminders and evidence export', 'document-engine'), false, true, 'acks'),
                array(__('Version history, review dates and automatic expiry', 'document-engine'), false, true, 'versions'),
            ),
            __('Publishing and integrations', 'document-engine') => array(
                array(__('Post to PDF button with header, footer and watermarks', 'document-engine'), true, true, 'post-to-pdf'),
                array(__('Blocks, shortcodes and Elementor widgets', 'document-engine'), true, true, 'blocks'),
                array(__('Move from Download Monitor, WordPress Download Manager or Barn2', 'document-engine'), true, true, 'migrate'),
                array(__('Command palette and read-only abilities for AI agents', 'document-engine'), true, true, 'palette'),
                array(__('Handbook PDF: many posts in one PDF with cover and contents', 'document-engine'), false, true, 'portal'),
                array(__('AI assistant that suggests titles, summaries and tags', 'document-engine'), false, true, 'ai'),
                array(__('Webhooks, Slack and Teams posts, email subscriptions', 'document-engine'), false, true, 'notify'),
                array(__('Front-end document submissions', 'document-engine'), false, true, 'submit'),
                array(__('WP-CLI commands', 'document-engine'), false, true, 'cli'),
            ),
        ));
    }

    public static function render()
    {
        $yes = '<span class="dengine-a-vs__yes">' . UI::icon('check', 18) . '<span class="screen-reader-text">' . esc_html__('Included', 'document-engine') . '</span></span>';
        $no = '<span class="dengine-a-vs__no" aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__('Not included', 'document-engine') . '</span>';
        UI::page_start(__('Free vs Pro', 'document-engine'), esc_html__('Everything in the free plugin stays free, with no limits on documents or time. Pro is an add-on for sites that need to control access, prove who read what, and manage documents at scale.', 'document-engine'));
        ?>
        <div class="dengine-a-card dengine-a-vs__intro">
            <div class="dengine-a-card__body">
                <div>
                    <h3 class="dengine-a-card__title"><?php esc_html_e('Add Pro when you need it', 'document-engine'); ?></h3>
                    <p class="dengine-a-card__desc"><?php esc_html_e('Pro installs next to this plugin. Your documents, pages and settings stay exactly as they are.', 'document-engine'); ?></p>
                </div>
                <div class="dengine-a-vs__actions">
                    <a class="dengine-a-btn dengine-a-btn--primary" href="<?php echo esc_url(self::store_url()); ?>" target="_blank" rel="noopener"><?php esc_html_e('Upgrade to Pro', 'document-engine'); ?><?php echo UI::icon('external', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="dengine-a-btn dengine-a-btn--secondary" href="#install-pro"><?php esc_html_e('I have a licence key', 'document-engine'); ?></a>
                </div>
            </div>
        </div>
        <?php foreach (self::comparison() as $group => $rows) : ?>
            <section class="dengine-a-card">
                <div class="dengine-a-card__body">
                    <div class="dengine-a-table-wrap">
                        <table class="dengine-a-table dengine-a-vs">
                            <caption class="screen-reader-text"><?php echo esc_html($group); ?></caption>
                            <thead><tr><th scope="col"><?php echo esc_html($group); ?></th><th scope="col" class="dengine-a-vs__col"><?php esc_html_e('Free', 'document-engine'); ?></th><th scope="col" class="dengine-a-vs__col"><?php esc_html_e('Pro', 'document-engine'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($rows as $row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($row[0]); ?><?php if (!empty($row[3])) : ?> <a class="dengine-a-vs__more" href="<?php echo esc_url(Docs::url($row[3])); ?>"><?php esc_html_e('Learn more', 'document-engine'); ?><span class="screen-reader-text">: <?php echo esc_html($row[0]); ?></span></a><?php endif; ?></td>
                                        <td class="dengine-a-vs__col"><?php echo $row[1] ? $yes : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                                        <td class="dengine-a-vs__col"><?php echo $row[2] ? $yes : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
        <?php ProInstaller::form(); ?>
        <?php
        UI::page_end();
    }
}
