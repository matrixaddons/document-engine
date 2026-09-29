<?php

namespace MatrixAddons\DocumentEngine;

use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Documents\PostType;
use MatrixAddons\DocumentEngine\Hooks\Template;
use MatrixAddons\DocumentEngine\Install\Upgrader;
use MatrixAddons\DocumentEngine\Library\Library;
use MatrixAddons\DocumentEngine\Library\Lists;
use MatrixAddons\DocumentEngine\Library\Rest;
use MatrixAddons\DocumentEngine\Pdf\Cache;
use MatrixAddons\DocumentEngine\Viewer\Viewer;

final class Main
{
    private static $instances = [];

    protected function __construct()
    {
        $this->define_constant();
        $this->load_helpers();
        $this->dispatch_hook();
    }

    public function define_constant()
    {
        define('DOCUMENT_ENGINE_ABSPATH', dirname(DOCUMENT_ENGINE_FILE) . '/');
        define('DOCUMENT_ENGINE_PLUGIN_BASENAME', plugin_basename(DOCUMENT_ENGINE_FILE));
        define('DOCUMENT_ENGINE_QUERY_VAR_SLUG', 'generate_pdf');
        define('DOCUMENT_ENGINE_ASSETS_DIR_PATH', DOCUMENT_ENGINE_PLUGIN_DIR . 'assets/');
        define('DOCUMENT_ENGINE_ASSETS_URI', DOCUMENT_ENGINE_PLUGIN_URI . 'assets/');
    }

    public function load_helpers()
    {
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/main.php';
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/template.php';
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/settings.php';
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/documents.php';
    }

    public function init_plugin()
    {
        $this->load_textdomain();
    }

    public function dispatch_hook()
    {
        add_action('init', [$this, 'init_plugin']);
        add_action('init', array('\MatrixAddons\DocumentEngine\Shortcodes', 'init'));

        Upgrader::init();
        PostType::init();
        Documents\Capabilities::init();
        FileServer::init();
        Assets::init();
        Blocks::init();
        Library::init();
        Lists::init();
        \MatrixAddons\DocumentEngine\Library\SearchBox::init();
        \MatrixAddons\DocumentEngine\Integrations\Abilities::init();
        \MatrixAddons\DocumentEngine\Migrate\Compat::init();
        \MatrixAddons\DocumentEngine\Accessibility\Requests::init();
        \MatrixAddons\DocumentEngine\Integrations\Elementor\Widgets::init();
        Rest::init();
        Viewer::init();
        Cache::init();
        Diagnostics\SiteHealth::init();
        new Template();

        if (is_admin()) {
            new \MatrixAddons\DocumentEngine\Admin\Main();
        }

        /**
         * Fires once the free plugin has registered its modules. Add-ons boot from here.
         */
        do_action('document_engine_loaded');
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('document-engine', false, dirname(DOCUMENT_ENGINE_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Kept for add-ons that called it in 1.x; activation now lives in Install\Installer.
     */
    public function activate()
    {
        Install\Installer::activate();
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(DOCUMENT_ENGINE_FILE));
    }

    public function template_path()
    {
        return apply_filters('document_engine_template_path', 'document_engine/');
    }

    /**
     * Get the template path.
     *
     * @return string
     */
    public function plugin_template_path()
    {
        return apply_filters('document_engine_plugin_template_path', $this->plugin_path() . '/templates/');
    }

    public function get_log_dir($create_if_not_exists = true)
    {
        $wp_upload_dir = wp_upload_dir();

        $log_dir = $wp_upload_dir['basedir'] . '/document-engine/';

        if (!file_exists(trailingslashit($log_dir) . 'index.html') && $create_if_not_exists) {
            $this->protect_dir($log_dir);
        }
        return $log_dir;
    }

    private function clear_dir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        $this->clear_dir($dir . '/' . $object);
                    } else {
                        wp_delete_file($dir . '/' . $object);
                    }
                }
            }

            @rmdir($dir); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
        }
    }

    /**
     * mPDF temp directory. Shared by concurrent requests, so it is only cleared when asked for explicitly.
     */
    public function get_tmp_pdf_dir($create_if_not_exists = true, $force_clear = false)
    {
        $log_dir = $this->get_log_dir(true);

        $tmp_pdf_dir = $log_dir . 'pdf/';

        if ($force_clear) {
            $this->clear_dir($tmp_pdf_dir);
        }

        if (!file_exists(trailingslashit($tmp_pdf_dir) . 'index.html') && $create_if_not_exists) {
            $this->protect_dir($tmp_pdf_dir);
        }
        return $tmp_pdf_dir;
    }

    /**
     * Creates a directory with an empty index and a deny-all .htaccess.
     *
     * @param string $dir Absolute path.
     * @return bool
     */
    public function protect_dir($dir)
    {
        return $this->create_files(array(
            array(
                'base' => $dir,
                'file' => 'index.html',
                'content' => '',
            ),
            array(
                'base' => $dir,
                'file' => '.htaccess',
                'content' => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
            )
        ), $dir);
    }

    private function create_files($files, $base_dir)
    {
        // Bypass if filesystem is read-only and/or non-standard upload system is used.
        if (apply_filters('document_engine_install_skip_create_files', false)) {
            return false;
        }

        if (file_exists(trailingslashit($base_dir) . 'index.html')) {
            return true;
        }
        $has_created_dir = false;

        foreach ($files as $file) {
            if (wp_mkdir_p($file['base']) && !file_exists(trailingslashit($file['base']) . $file['file'])) {
                $file_handle = @fopen(trailingslashit($file['base']) . $file['file'], 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions
                if ($file_handle) {
                    fwrite($file_handle, $file['content']); // phpcs:ignore WordPress.WP.AlternativeFunctions
                    fclose($file_handle); // phpcs:ignore WordPress.WP.AlternativeFunctions
                    $has_created_dir = true;
                }
            }
        }
        return $has_created_dir;
    }

    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}
