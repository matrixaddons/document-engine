<?php
/**
 * Plugin Name: Document Engine
 * Plugin URI: https://matrixaddons.com/downloads/document-engine-wordpress-to-pdf-plugin/
 * Description: Convert any post type to PDF document & PDF viewer block
 * Author: MatrixAddons
 * Author URI: https://profiles.wordpress.org/matrixaddons
 * Version: 1.2
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
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
    define('DOCUMENT_ENGINE_VERSION', '1.2');
}

// Define DOCUMENT_ENGINE_PLUGIN_URI.
if (!defined('DOCUMENT_ENGINE_PLUGIN_URI')) {
    define('DOCUMENT_ENGINE_PLUGIN_URI', plugins_url('/', DOCUMENT_ENGINE_FILE));
}

// Define DOCUMENT_ENGINE_PLUGIN_DIR.
if (!defined('DOCUMENT_ENGINE_PLUGIN_DIR')) {
    define('DOCUMENT_ENGINE_PLUGIN_DIR', plugin_dir_path(DOCUMENT_ENGINE_FILE));
}
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
