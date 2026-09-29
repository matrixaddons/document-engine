<?php

namespace MatrixAddons\DocumentEngine\Integrations\Elementor;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Elementor widgets: Document Library, PDF Viewer, Document Download and Document List.
 * Each widget renders through the same code as its block, so output is identical everywhere.
 */
class Widgets
{
    const CACHE = 'dengine_el_documents';

    public static function init()
    {
        add_action('save_post_' . PostType::POST_TYPE, array(__CLASS__, 'flush'));
        add_action('deleted_post', array(__CLASS__, 'flush'));
        add_action('elementor/elements/categories_registered', array(__CLASS__, 'category'));
        add_action('elementor/widgets/register', array(__CLASS__, 'register'));
        add_action('elementor/frontend/after_enqueue_scripts', array(__CLASS__, 'editor_boot'));
    }

    /**
     * In the Elementor editor, widgets are re-rendered without a page load: start the viewer
     * and library scripts on the new markup.
     */
    public static function editor_boot()
    {
        if (!wp_script_is('elementor-frontend', 'enqueued')) {
            return;
        }
        wp_add_inline_script('elementor-frontend', "window.addEventListener('elementor/frontend/init',function(){if(!window.elementorFrontend||!elementorFrontend.hooks){return;}['dengine-library','dengine-viewer','dengine-list','dengine-download'].forEach(function(n){elementorFrontend.hooks.addAction('frontend/element_ready/'+n+'.default',function(\$el){var el=\$el&&\$el[0]?\$el[0]:null;if(!el){return;}if(window.DocumentEngineLibraryBoot){window.DocumentEngineLibraryBoot(el);}if(window.DocumentEngineViewerBoot){window.DocumentEngineViewerBoot(el);}});});});");
    }

    public static function category($manager)
    {
        $manager->add_category('document-engine', array('title' => DOCUMENT_ENGINE_BRAND, 'icon' => 'eicon-document-file'));
    }

    public static function register($manager)
    {
        $manager->register(new LibraryWidget());
        $manager->register(new ViewerWidget());
        $manager->register(new DownloadWidget());
        $manager->register(new ListWidget());
    }

    /**
     * Published documents for a select control (id => title).
     */
    public static function flush()
    {
        delete_transient(self::CACHE);
    }

    /**
     * Published documents for a select control (id => title). Elementor builds controls on
     * front-end requests too, so the list is one light query, cached until a document changes.
     */
    public static function document_options()
    {
        $rows = get_transient(self::CACHE);
        if (!is_array($rows)) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $rows = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY post_title ASC LIMIT 2000", PostType::POST_TYPE), ARRAY_A);
            set_transient(self::CACHE, $rows, DAY_IN_SECONDS);
        }
        $options = array('' => __('— Choose a document —', 'document-engine'));
        foreach ($rows as $row) {
            $options[(string)$row['ID']] = $row['post_title'] !== '' ? $row['post_title'] : '#' . $row['ID'];
        }
        return $options;
    }

    public static function category_options()
    {
        $options = array();
        $terms = get_terms(array('taxonomy' => PostType::CATEGORY, 'hide_empty' => false));
        foreach (is_array($terms) ? $terms : array() as $term) {
            $options[$term->slug] = $term->name;
        }
        return $options;
    }
}
