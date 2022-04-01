<?php

namespace MatrixAddons\DocumentEngine;

use MatrixAddons\DocumentEngine\Hooks\Template;

final class Main
{
    private static $instances = [];

    protected function __construct()
    {
        $this->define_constant();
        register_activation_hook(__FILE__, [$this, 'activate']);
        $this->load_helpers();
        $this->dispatch_hook();
    }

    public function define_constant()
    {
        define('DOCUMENT_ENGINE_ABSPATH', dirname(DOCUMENT_ENGINE_FILE) . '/');
        define('DOCUMENT_ENGINE_PLUGIN_BASENAME', plugin_basename(DOCUMENT_ENGINE_FILE));
        define('DOCUMENT_ENGINE_PLUGIN_SLUG', 'document-engine');
        define('DOCUMENT_ENGINE_ASSETS_DIR_PATH', DOCUMENT_ENGINE_PLUGIN_DIR . 'assets/');
        define('DOCUMENT_ENGINE_ASSETS_URI', DOCUMENT_ENGINE_PLUGIN_URI . 'assets/');
    }

    public function load_helpers()
    {
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/main.php';
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/template.php';
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/settings.php';

    }

    public function init_plugin()
    {
        $this->load_textdomain();
    }

    public function dispatch_hook()
    {
        add_action('init', [$this, 'init_plugin']);

        Assets::init();
        new Template();
        /* Block::init();
         Migration::init();
         PostTypes\Maps::init();
         Meta\Maps::init();
         Api::init();*/

        if (is_admin()) {
            new \MatrixAddons\DocumentEngine\Admin\Main();
        }
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('document-engine', false, dirname(DOCUMENT_ENGINE_PLUGIN_BASENAME) . '/languages');
    }

    public function activate()
    {
        //Installer::init();
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

    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}
