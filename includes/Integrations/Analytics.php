<?php

namespace MatrixAddons\DocumentEngine\Integrations;

defined('ABSPATH') || exit;

/**
 * Sends a GA4 "file_download" event (gtag or Google Tag Manager) when a visitor downloads a document.
 *
 * GA4's own download tracking looks for a file extension in the link; document links
 * (?dengine_download=ID) have none, so without this downloads never reach Analytics.
 * Nothing is sent unless the site already loads Google Analytics or Tag Manager.
 */
class Analytics
{
    public static function init()
    {
        add_action('wp_footer', array(__CLASS__, 'script'), 99);
    }

    public static function script()
    {
        if (!wp_style_is('document-engine-documents', 'done') && !wp_style_is('document-engine-documents', 'enqueued')) {
            return;
        }
        if (!apply_filters('document_engine_analytics_events', true)) {
            return;
        }
        ?>
        <script>
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a[data-dengine-download], a[href*="dengine_download="]');
            if (!a || /[?&]view=1/.test(a.href)) { return; }
            var label = (a.getAttribute('aria-label') || a.textContent || '').replace(/^\s*(Download|Get)\s+/i, '').trim();
            var data = { file_name: label, link_url: a.href, link_text: (a.textContent || '').trim(), document_id: a.getAttribute('data-dengine-download') || '' };
            if (typeof window.gtag === 'function') { window.gtag('event', 'file_download', data); }
            else if (Array.isArray(window.dataLayer)) { window.dataLayer.push(Object.assign({ event: 'file_download' }, data)); }
        }, true);
        </script>
        <?php
    }
}
