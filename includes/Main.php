<?php

namespace MatrixAddons\DocumentEngine;

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

    }

    public function init_plugin()
    {
        $this->load_textdomain();
    }

    public function dispatch_hook()
    {
        add_action('init', [$this, 'init_plugin']);

        Assets::init();
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

    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}
