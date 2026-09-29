<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;

use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;
use MatrixAddons\DocumentEngine\Library\Query;

defined('ABSPATH') || exit;

class General extends Setting_Base
{
    public function __construct()
    {
        $this->id = 'general';
        $this->label = __('Documents', 'document-engine');

        parent::__construct();

        add_filter('document_engine_admin_settings_sanitize_option_document_engine_documents_slug', function ($value) {
            $value = sanitize_title($value);
            return $value !== '' ? $value : 'documents';
        });
    }

    public function get_sections()
    {
        return apply_filters('document_engine_get_sections_' . $this->id, array(
            '' => __('Documents', 'document-engine'),
            'library' => __('Library', 'document-engine'),
        ));
    }

    public function output()
    {
        global $current_section;
        Settings::output_fields($this->get_settings($current_section));
    }

    public function save()
    {
        global $current_section;
        Settings::save_fields($this->get_settings($current_section));
        do_action('document_engine_update_options_' . $this->id . '_' . $current_section);
    }

    public function get_settings($current_section = '')
    {
        if ($current_section === 'library') {
            $settings = array(
                array('title' => __('Library defaults', 'document-engine'), 'type' => 'title', 'desc' => __('Starting values for new Document Library blocks and the [document_engine_library] shortcode. Each library can change them.', 'document-engine'), 'id' => 'document_engine_library_options'),
                array(
                    'title' => __('Layout', 'document-engine'),
                    'id' => 'document_engine_library_layout',
                    'type' => 'select',
                    'default' => 'table',
                    'options' => Query::layouts(),
                ),
                array(
                    'title' => __('Documents per page', 'document-engine'),
                    'id' => 'document_engine_library_per_page',
                    'type' => 'number',
                    'suffix' => __('per page', 'document-engine'),
                    'default' => 20,
                    'custom_attributes' => array('min' => 1, 'max' => 100),
                ),
                array(
                    'title' => __('View button', 'document-engine'),
                    'desc' => __('Show a "View" button next to Download for PDFs', 'document-engine'),
                    'id' => 'document_engine_library_view_button',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                array(
                    'title' => __('View opens', 'document-engine'),
                    'id' => 'document_engine_library_preview',
                    'type' => 'select',
                    'default' => 'page',
                    'desc' => __('A popup lets visitors look at PDFs, images, audio and video without leaving the library.', 'document-engine'),
                    'options' => array(
                        'page' => __('The document page', 'document-engine'),
                        'popup' => __('A preview popup', 'document-engine'),
                    ),
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_library_options'),
            );
        } else {
            $settings = array(
                array('title' => __('Document pages', 'document-engine'), 'type' => 'title', 'desc' => __('Each document can have its own page with its description, the PDF viewer and a download button.', 'document-engine'), 'id' => 'document_engine_documents_options'),
                array(
                    'title' => __('Pages', 'document-engine'),
                    'desc' => __('Give each document its own page', 'document-engine'),
                    'id' => 'document_engine_single_pages',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                array(
                    'title' => __('URL prefix', 'document-engine'),
                    'desc' => sprintf(
                        /* translators: %s: example URL */
                        __('Document pages live at %s', 'document-engine'),
                        '<code>' . esc_html(home_url('/' . get_option('document_engine_documents_slug', 'documents') . '/annual-report/')) . '</code>'
                    ),
                    'id' => 'document_engine_documents_slug',
                    'type' => 'text',
                    'default' => 'documents',
                ),
                array(
                    'title' => __('PDF preview', 'document-engine'),
                    'desc' => __('Show PDFs in the viewer on their document page', 'document-engine'),
                    'id' => 'document_engine_single_viewer',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                array(
                    'title' => __('Related documents', 'document-engine'),
                    'desc' => __('List related documents (same category or tags) under each document page', 'document-engine'),
                    'id' => 'document_engine_single_related',
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                array(
                    'title' => __('Site search', 'document-engine'),
                    'desc' => __('Include documents in site search results', 'document-engine'),
                    'id' => 'document_engine_documents_in_search',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                array(
                    'title' => __('Download button', 'document-engine'),
                    'id' => 'document_engine_link_behavior',
                    'type' => 'select',
                    'default' => 'download',
                    'desc' => __('Each document can change this in its sidebar.', 'document-engine'),
                    'options' => array(
                        'download' => __('Downloads the file', 'document-engine'),
                        'inline' => __('Opens the file in the browser (PDFs, images, text)', 'document-engine'),
                    ),
                ),
                array(
                    'title' => __('Download counter', 'document-engine'),
                    'desc' => __('Count downloads', 'document-engine'),
                    'desc_tip' => __('Search engines, link previews and repeated range requests are not counted.', 'document-engine'),
                    'id' => 'document_engine_count_downloads',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_documents_options'),

                array('title' => __('Accessibility', 'document-engine'), 'type' => 'title', 'desc' => __('Laws such as ADA Title II, the UK accessibility regulations and the European Accessibility Act expect public bodies and many businesses to provide documents in an accessible format when someone asks.', 'document-engine'), 'id' => 'document_engine_a11y_options'),
                array(
                    'title' => __('Format requests', 'document-engine'),
                    'desc' => __('Show "Request an accessible version" on document pages', 'document-engine'),
                    'desc_tip' => __('Visitors choose a format (accessible PDF, Word, large print…) and leave their email. Requests are listed in the Documents menu under Format requests (Reports → Format requests with Pro).', 'document-engine'),
                    'id' => 'document_engine_a11y_requests',
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                array(
                    'title' => __('Send requests to', 'document-engine'),
                    'desc' => __('Empty uses the site admin email.', 'document-engine'),
                    'id' => 'document_engine_a11y_email',
                    'type' => 'email',
                    'placeholder' => get_option('admin_email'),
                    'default' => '',
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_a11y_options'),
            );
        }

        return apply_filters('document_engine_get_settings_' . $this->id, $settings, $current_section);
    }
}
