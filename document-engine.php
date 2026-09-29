<?php
/**
 * Plugin Name: Document Engine
 * Plugin URI: https://matrixaddons.com/plugins/document-engine/
 * Description: Document library, self-hosted PDF viewer and post to PDF. Organise documents with search and filters, show PDFs without Google, and let visitors download any post as a branded PDF.
 * Author: MatrixAddons
 * Author URI: https://matrixaddons.com/
 * Version: 2.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: document-engine
 * Domain Path: /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>' . esc_html__('Document Engine needs PHP 7.4 or newer. Please ask your host to upgrade PHP.', 'document-engine') . '</p></div>';
    });
    return;
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Define DOCUMENT_ENGINE_PLUGIN_FILE.
if (!defined('DOCUMENT_ENGINE_FILE')) {
    define('DOCUMENT_ENGINE_FILE', __FILE__);
}

// Define DOCUMENT_ENGINE_VERSION.
if (!defined('DOCUMENT_ENGINE_VERSION')) {
    define('DOCUMENT_ENGINE_VERSION', '2.0.0');
}

// Product name shown to users. Everything user-facing reads it from here.
if (!defined('DOCUMENT_ENGINE_BRAND')) {
    define('DOCUMENT_ENGINE_BRAND', 'Document Engine');
}

// Define DOCUMENT_ENGINE_PLUGIN_URI.
if (!defined('DOCUMENT_ENGINE_PLUGIN_URI')) {
    define('DOCUMENT_ENGINE_PLUGIN_URI', plugins_url('/', DOCUMENT_ENGINE_FILE));
}

// Define DOCUMENT_ENGINE_PLUGIN_DIR.
if (!defined('DOCUMENT_ENGINE_PLUGIN_DIR')) {
    define('DOCUMENT_ENGINE_PLUGIN_DIR', plugin_dir_path(DOCUMENT_ENGINE_FILE));
}

register_activation_hook(__FILE__, array('\MatrixAddons\DocumentEngine\Install\Installer', 'activate'));
register_deactivation_hook(__FILE__, array('\MatrixAddons\DocumentEngine\Install\Installer', 'deactivate'));

/**
 * Initializes the main plugin
 *
 * @return \MatrixAddons\DocumentEngine\Main
 */
if (!function_exists('document_engine')) {
    function document_engine()
    {
        return \MatrixAddons\DocumentEngine\Main::getInstance();
    }
}

document_engine();
