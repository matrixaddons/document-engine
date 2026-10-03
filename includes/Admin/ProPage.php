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
     * Pro plans (same as store.mantrabrain.com): EDD price IDs 1-3 yearly, 4-6 lifetime.
     * Every plan includes every Pro feature; plans differ in sites and support.
     */
    public static function plans()
    {
        return apply_filters('document_engine_pro_plans', array(
            array('name' => __('Personal', 'document-engine'), 'sites' => __('1 website', 'document-engine'), 'yearly' => 79, 'lifetime' => 199, 'yearly_id' => 1, 'lifetime_id' => 4, 'support' => __('Email support', 'document-engine'), 'featured' => false),
            array('name' => __('Plus', 'document-engine'), 'sites' => __('5 websites', 'document-engine'), 'yearly' => 149, 'lifetime' => 379, 'yearly_id' => 2, 'lifetime_id' => 5, 'support' => __('Email support', 'document-engine'), 'featured' => true),
            array('name' => __('Agency', 'document-engine'), 'sites' => __('25 websites', 'document-engine'), 'yearly' => 249, 'lifetime' => 599, 'yearly_id' => 3, 'lifetime_id' => 6, 'support' => __('Priority support', 'document-engine'), 'featured' => false),
        ));
    }

    /**
     * Straight to the store's checkout with a plan in the cart.
     */
    public static function checkout_url($price_id)
    {
        return add_query_arg(array(
            'edd_action' => 'add_to_cart',
            'download_id' => ProInstaller::item_id(),
            'edd_options[price_id]' => (int)$price_id,
            'utm_source' => 'wp-admin',
            'utm_medium' => 'plugin',
            'utm_campaign' => 'free-vs-pro',
        ), trailingslashit(ProInstaller::store_url()) . 'checkout/');
    }

    /**
     * Pro features, grouped. Also used by the docs/upsell cards.
     */
    public static function features()
    {
        return apply_filters('document_engine_pro_features', array(
            __('Control who sees what', 'document-engine') => array(
                __('Restrict documents and categories to logged-in users, roles or specific people', 'document-engine'),
                __('Protected storage: files move out of the public uploads folder and are only served to people allowed to open them', 'document-engine'),
                __('Secure viewer: hide download and print, block copying, and stamp each page with the viewer\'s name, email and time', 'document-engine'),
                __('Email gate: ask for name and email (with consent) before a download, with CSV export and webhooks', 'document-engine'),
                __('Accept terms: everyone agrees to your licence or disclaimer before opening a document, and each acceptance is logged', 'document-engine'),
            ),
            __('Know what happens', 'document-engine') => array(
                __('Audit log of every view, download and denied request, per user, with CSV export and retention settings', 'document-engine'),
                __('Analytics dashboard: trends, top documents, most active users', 'document-engine'),
            ),
            __('Keep documents current', 'document-engine') => array(
                __('Versions: upload a new file without changing the link, see history and restore', 'document-engine'),
                __('Expiry and review dates with reminder emails', 'document-engine'),
                __('Search inside PDF, Word (.docx), Excel (.xlsx), PowerPoint (.pptx) and OpenDocument files, and automatic PDF thumbnails', 'document-engine'),
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
                array(__('Sort by clicking column headings; page numbers or a "Load more" button', 'document-engine'), true, true, 'library'),
                array(__('Your own fields (reference, department, dates…) as columns, filters and sort options, including ACF fields', 'document-engine'), false, true, 'fields'),
                array(__('Search inside PDF, Word (.docx), Excel (.xlsx), PowerPoint (.pptx) and OpenDocument files', 'document-engine'), false, true, 'ai'),
                array(__('Download several documents from a table library as one ZIP', 'document-engine'), false, true, 'portal'),
                array(__('Bulk upload and CSV import, including updates by ID', 'document-engine'), false, true, 'import'),
                array(__('"My documents" page for clients or staff', 'document-engine'), false, true, 'portal'),
            ),
            __('Viewing', 'document-engine') => array(
                array(__('Built-in PDF viewer with search, thumbnails, outline and links', 'document-engine'), true, true, 'viewer'),
                array(__('Preview popup for PDFs, images, audio and video', 'document-engine'), true, true, 'preview'),
                array(__('Word, Excel and PowerPoint previews with Microsoft Office Online (opt-in)', 'document-engine'), false, true, 'office-preview'),
                array(__('QR code for every document', 'document-engine'), true, true, 'qr'),
                array(__('Secure viewer for PDFs: no download or print, a watermark with the reader\'s name on every page', 'document-engine'), false, true, 'secure'),
                array(__('Downloaded PDFs stamped with the reader\'s details', 'document-engine'), false, true, 'secure'),
            ),
            __('Access and sharing', 'document-engine') => array(
                array(__('Draft, private and password-protected documents', 'document-engine'), true, true, 'faq-private'),
                array(__('Members-only documents by role, person, whole category or membership level (Paid Memberships Pro, WooCommerce Memberships)', 'document-engine'), false, true, 'access'),
                array(__('Private file storage, so file addresses can\'t be shared', 'document-engine'), false, true, 'access'),
                array(__('Expiring share links, named per recipient, with open tracking and single-use or limited uses', 'document-engine'), false, true, 'share'),
                array(__('Email gate: name and email before download, with leads export', 'document-engine'), false, true, 'gate'),
                array(__('Accept terms (licence or disclaimer) before opening, with each acceptance logged', 'document-engine'), false, true, 'terms'),
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
                array(__('Post to PDF button with header, footer, paper sizes, your own fonts (including Chinese, Japanese, Korean), custom CSS and PDF permissions', 'document-engine'), true, true, 'post-to-pdf'),
                array(__('Text and image watermarks on Post to PDF', 'document-engine'), false, true, 'post-to-pdf'),
                array(__('Blocks, shortcodes and Elementor widgets', 'document-engine'), true, true, 'blocks'),
                array(__('Move from Download Monitor, WordPress Download Manager, Barn2, Simple Download Monitor or Simple File List', 'document-engine'), true, true, 'migrate'),
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
                    <ul class="dengine-a-checklist dengine-a-checklist--plain">
                        <li><?php echo UI::icon('check', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e('From $79 a year for one site, or pay once. Every plan includes every Pro feature.', 'document-engine'); ?></span></li>
                        <li><?php echo UI::icon('check', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e('If your licence expires, Pro keeps working; only updates and support stop.', 'document-engine'); ?></span></li>
                        <li><?php echo UI::icon('check', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e('14-day money-back guarantee.', 'document-engine'); ?></span></li>
                    </ul>
                </div>
                <div class="dengine-a-vs__actions">
                    <a class="dengine-a-btn dengine-a-btn--primary" href="#pricing"><?php esc_html_e('See plans', 'document-engine'); ?></a>
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
        <section class="dengine-a-plans" id="pricing" aria-labelledby="dengine-plans-title">
            <h3 class="dengine-a-plans__title" id="dengine-plans-title"><?php esc_html_e('Pro plans', 'document-engine'); ?></h3>
            <p class="dengine-a-page__desc"><?php esc_html_e('Every plan includes every Pro feature. Plans differ only in the number of websites and the support.', 'document-engine'); ?></p>
            <div class="dengine-a-plans__grid">
                <?php foreach (self::plans() as $plan) : ?>
                    <div class="dengine-a-plan<?php echo !empty($plan['featured']) ? ' is-featured' : ''; ?>">
                        <?php if (!empty($plan['featured'])) : ?><span class="dengine-a-plan__badge"><?php esc_html_e('Most popular', 'document-engine'); ?></span><?php endif; ?>
                        <h4 class="dengine-a-plan__name"><?php echo esc_html($plan['name']); ?></h4>
                        <p class="dengine-a-plan__sites"><?php echo esc_html($plan['sites']); ?> · <?php echo esc_html($plan['support']); ?></p>
                        <p class="dengine-a-plan__price"><strong>$<?php echo esc_html(number_format_i18n($plan['yearly'])); ?></strong> <span><?php esc_html_e('per year', 'document-engine'); ?></span></p>
                        <a class="dengine-a-btn <?php echo !empty($plan['featured']) ? 'dengine-a-btn--primary' : 'dengine-a-btn--secondary'; ?>" href="<?php echo esc_url(self::checkout_url($plan['yearly_id'])); ?>" target="_blank" rel="noopener"><?php
                            /* translators: %s: plan name */
                            echo esc_html(sprintf(__('Buy %s', 'document-engine'), $plan['name']));
                        ?></a>
                        <p class="dengine-a-plan__lifetime"><?php
                            /* translators: %s: lifetime price */
                            echo esc_html(sprintf(__('or $%s once, for life', 'document-engine'), number_format_i18n($plan['lifetime'])));
                        ?> <a href="<?php echo esc_url(self::checkout_url($plan['lifetime_id'])); ?>" target="_blank" rel="noopener"><?php esc_html_e('Buy lifetime', 'document-engine'); ?><span class="screen-reader-text"> <?php echo esc_html($plan['name']); ?></span></a></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="dengine-a-sub dengine-a-plans__note"><?php esc_html_e('Prices in US dollars. Secure checkout on store.mantrabrain.com.', 'document-engine'); ?> <a href="https://mantrabrain.com/refund-policy/" target="_blank" rel="noopener"><?php esc_html_e('Refund policy', 'document-engine'); ?></a></p>
        </section>
        <?php ProInstaller::form(); ?>
        <?php
        UI::page_end();
    }
}
