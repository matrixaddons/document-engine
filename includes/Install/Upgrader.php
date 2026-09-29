<?php

namespace MatrixAddons\DocumentEngine\Install;

defined('ABSPATH') || exit;

/**
 * Runs one-off data changes when the stored version is older than the code.
 *
 * `document_engine_installed_from` records whether the site started on 1.x. Legacy sites keep
 * 1.x front-end behaviour (e.g. the Google viewer in old PDF blocks) until the owner switches.
 */
class Upgrader
{
    const OPTION = 'document_engine_db_version';

    public static function init()
    {
        add_action('init', array(__CLASS__, 'maybe_upgrade'), 5);
    }

    public static function is_legacy_site()
    {
        $from = get_option('document_engine_installed_from', '');
        return $from !== '' && version_compare($from, '2.0.0', '<');
    }

    public static function maybe_upgrade()
    {
        $stored = get_option(self::OPTION, '');

        if ($stored === DOCUMENT_ENGINE_VERSION) {
            return;
        }

        if ($stored === '') {
            // 1.x never stored a version; any saved 1.x option means an existing install.
            $legacy = get_option('document_engine_pdf_button_text', null) !== null
                || get_option('document_engine_pdf_post_type', null) !== null
                || get_option('document_engine_queue_flush_rewrite_rules', null) !== null
                || self::has_legacy_blocks();

            add_option('document_engine_installed_from', $legacy ? '1.x' : DOCUMENT_ENGINE_VERSION);

            if (!$legacy) {
                // Fresh 2.0 installs use the built-in viewer everywhere.
                add_option('document_engine_viewer_legacy_builtin', 'yes');
            }
        }

        update_option(self::OPTION, DOCUMENT_ENGINE_VERSION);
        update_option('document_engine_flush_rewrite', 'yes');

        do_action('document_engine_upgraded', $stored, DOCUMENT_ENGINE_VERSION);
    }

    private static function has_legacy_blocks()
    {
        global $wpdb;

        return (bool)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%<!-- wp:document-engine/pdf%' LIMIT 1");
    }
}
