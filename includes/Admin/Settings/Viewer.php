<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;

use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;
use MatrixAddons\DocumentEngine\Blocks;

defined('ABSPATH') || exit;

class Viewer extends Setting_Base
{
    public function __construct()
    {
        $this->id = 'viewer';
        $this->label = __('PDF Viewer', 'document-engine');

        parent::__construct();
    }

    public function output()
    {
        Settings::output_fields($this->get_settings());
    }

    public function save()
    {
        Settings::save_fields($this->get_settings());
    }

    public function get_settings()
    {
        $settings = array(
            array(
                'title' => __('Viewer defaults', 'document-engine'),
                'type' => 'title',
                'desc' => __('Each PDF Viewer block can change these.', 'document-engine'),
                'id' => 'document_engine_viewer_options',
            ),
            array(
                'title' => __('Default height', 'document-engine'),
                'desc' => __('For example 800px, or 80vh for 80% of the screen height.', 'document-engine'),
                'id' => 'document_engine_viewer_height',
                'type' => 'text',
                'default' => '800px',
            ),
            array(
                'title' => __('Initial zoom', 'document-engine'),
                'id' => 'document_engine_viewer_zoom',
                'type' => 'select',
                'default' => 'page-width',
                'options' => array(
                    'page-width' => __('Fit width', 'document-engine'),
                    'page-fit' => __('Fit page', 'document-engine'),
                    'auto' => __('Automatic', 'document-engine'),
                    '100' => '100%',
                ),
            ),
            array('title' => __('Toolbar', 'document-engine'), 'desc' => __('Page navigation and zoom', 'document-engine'), 'id' => 'document_engine_viewer_toolbar', 'type' => 'checkbox', 'default' => 'yes', 'checkboxgroup' => 'start'),
            array('desc' => __('Download button', 'document-engine'), 'id' => 'document_engine_viewer_download', 'type' => 'checkbox', 'default' => 'yes', 'checkboxgroup' => ''),
            array('desc' => __('Print button', 'document-engine'), 'id' => 'document_engine_viewer_print', 'type' => 'checkbox', 'default' => 'yes', 'checkboxgroup' => ''),
            array('desc' => __('Full screen button', 'document-engine'), 'id' => 'document_engine_viewer_fullscreen', 'type' => 'checkbox', 'default' => 'yes', 'checkboxgroup' => ''),
            array('desc' => __('Search inside the document', 'document-engine'), 'id' => 'document_engine_viewer_search', 'type' => 'checkbox', 'default' => 'yes', 'checkboxgroup' => ''),
            array(
                'desc' => __('Page thumbnails and outline sidebar', 'document-engine'),
                'id' => 'document_engine_viewer_sidebar',
                'type' => 'checkbox',
                'default' => 'yes',
                'checkboxgroup' => 'end',
                // T2 (static): hiding the buttons is not protection.
                'desc_tip' => __('Hiding the Download or Print button doesn\'t stop downloads: browsers can still save the file.', 'document-engine')
                    . (\MatrixAddons\DocumentEngine\Admin\Nudges::can_show('t2-viewer', false) ? ' ' . sprintf(
                        /* translators: %s: link to the Pro preview */
                        __('Document Engine Pro\'s secure viewer shows a PDF read-only, with the reader\'s name on every page. %s', 'document-engine'),
                        '<a href="' . esc_url(\MatrixAddons\DocumentEngine\Admin\Upsell::url('access')) . '">' . esc_html__('See how Pro does this', 'document-engine') . '</a>'
                    ) : ''),
            ),
            array(
                'title' => __('Version 1 blocks', 'document-engine'),
                'desc' => __('Use this viewer for PDF Viewer (classic) blocks', 'document-engine'),
                'desc_tip' => __('Off keeps the Google Docs viewer those blocks used in version 1. On is faster and sends nothing to Google.', 'document-engine'),
                'id' => 'document_engine_viewer_legacy_builtin',
                'type' => 'checkbox',
                'default' => Blocks::legacy_uses_builtin_viewer() ? 'yes' : 'no',
            ),
            array('type' => 'sectionend', 'id' => 'document_engine_viewer_options'),
        );

        return apply_filters('document_engine_get_settings_' . $this->id, $settings);
    }
}
