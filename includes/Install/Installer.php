<?php

namespace MatrixAddons\DocumentEngine\Install;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Activation, deactivation and first-install defaults.
 */
class Installer
{
    public static function activate()
    {
        Upgrader::maybe_upgrade();

        PostType::register();
        \MatrixAddons\DocumentEngine\Documents\Capabilities::sync();
        flush_rewrite_rules(false);

        if (function_exists('document_engine')) {
            document_engine()->get_log_dir(true);
        }

        set_transient('document_engine_activation_redirect', 1, 60);

        if (!wp_next_scheduled('document_engine_daily')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'document_engine_daily');
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('document_engine_daily');
        flush_rewrite_rules(false);
    }
}
