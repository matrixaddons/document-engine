<?php

namespace MatrixAddons\DocumentEngine\Diagnostics;

defined('ABSPATH') || exit;

/**
 * Site Health checks: PDF generation requirements and a writable work directory.
 */
class SiteHealth
{
    public static function init()
    {
        add_filter('site_status_tests', array(__CLASS__, 'tests'));
        add_filter('debug_information', array(__CLASS__, 'debug_information'));
    }

    public static function tests($tests)
    {
        $tests['direct']['document_engine_requirements'] = array(
            /* translators: %s: plugin name */
            'label' => sprintf(__('%s requirements', 'document-engine'), DOCUMENT_ENGINE_BRAND),
            'test' => array(__CLASS__, 'test_requirements'),
        );
        return $tests;
    }

    public static function test_requirements()
    {
        $problems = array();
        if (!extension_loaded('mbstring')) {
            $problems[] = __('The PHP mbstring extension is missing (needed to create PDFs).', 'document-engine');
        }
        if (!extension_loaded('gd')) {
            $problems[] = __('The PHP GD extension is missing (needed for images in PDFs).', 'document-engine');
        }
        $dir = document_engine()->get_log_dir(true);
        if (!wp_is_writable($dir)) {
            /* translators: %s: directory path */
            $problems[] = sprintf(__('The folder %s is not writable, so PDFs cannot be generated or cached.', 'document-engine'), $dir);
        }

        $result = array(
            /* translators: %s: plugin name */
            'label' => sprintf(__('%s can create PDFs and serve documents', 'document-engine'), DOCUMENT_ENGINE_BRAND),
            'status' => 'good',
            'badge' => array('label' => DOCUMENT_ENGINE_BRAND, 'color' => 'blue'),
            'description' => '<p>' . esc_html__('All requirements for generating PDFs and serving documents are met.', 'document-engine') . '</p>',
            'actions' => '',
            'test' => 'document_engine_requirements',
        );

        if ($problems) {
            $result['status'] = 'recommended';
            /* translators: %s: plugin name */
            $result['label'] = sprintf(__('%s needs attention', 'document-engine'), DOCUMENT_ENGINE_BRAND);
            $result['description'] = '<ul><li>' . implode('</li><li>', array_map('esc_html', $problems)) . '</li></ul>';
        }
        return $result;
    }

    public static function debug_information($info)
    {
        $counts = wp_count_posts('dengine_document');
        $info['document-engine'] = array(
            'label' => DOCUMENT_ENGINE_BRAND,
            'fields' => array(
                'version' => array('label' => __('Version', 'document-engine'), 'value' => DOCUMENT_ENGINE_VERSION),
                'installed_from' => array('label' => __('First installed version', 'document-engine'), 'value' => get_option('document_engine_installed_from', '')),
                'documents' => array('label' => __('Published documents', 'document-engine'), 'value' => $counts ? (int)$counts->publish : 0),
                'pdf_post_types' => array('label' => __('Post to PDF post types', 'document-engine'), 'value' => implode(', ', array_keys(document_engine_pdf_post_type())) ?: '—'),
                'legacy_viewer' => array('label' => __('Classic blocks use built-in viewer', 'document-engine'), 'value' => \MatrixAddons\DocumentEngine\Blocks::legacy_uses_builtin_viewer() ? 'yes' : 'no'),
                'pdf_cache' => array('label' => __('PDF cache', 'document-engine'), 'value' => get_option('document_engine_pdf_cache', 'yes')),
            ),
        );
        return $info;
    }
}
