<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;


use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class PDF extends Setting_Base
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'pdf_downloads';
        $this->label = __('PDF Download Settings', 'document-engine');

        parent::__construct();
    }

    /**
     * Get sections.
     *
     * @return array
     */
    public function get_sections()
    {
        $sections = array(
            '' => __('Button', 'document-engine'),
            'header_footer' => __('Header & Footer', 'document-engine'),
            'page' => __('Page', 'document-engine'),
            'style' => __('Style', 'document-engine'),
        );

        return apply_filters('document_engine_get_sections_' . $this->id, $sections);
    }

    /**
     * Output the settings.
     */
    public function output()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);

        Settings::output_fields($settings);
    }

    /**
     * Save settings.
     */
    public function save()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);
        Settings::save_fields($settings);

        if ($current_section) {
            do_action('document_engine_update_options_' . $this->id . '_' . $current_section);
        }
    }

    /**
     * Get settings array.
     *
     * @param string $current_section Current section name.
     * @return array
     */
    public function get_settings($current_section = '')
    {
        if ('page' === $current_section) {
            $settings = array(
                array(
                    'title' => __('PDF Page Setup', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_pdf_page_options',
                ),
                array(
                    'title' => __('Page orientation', 'document-engine'),
                    'id' => 'document_engine_pdf_page_orientation',
                    'type' => 'select',
                    'default' => 'horizontal',
                    'options' => array(
                        'vertical' => __('Vertical', 'document-engine'),
                        'horizontal' => __('Horizontal', 'document-engine'),
                    )
                ),
                array(
                    'title' => __('Font Size', 'document-engine'),
                    'desc' => __('Font Size in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_font_size',
                    'type' => 'number',
                    'default' => 12
                ), array(
                    'title' => __('Margin left', 'document-engine'),
                    'desc' => __('Margin left in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_margin_left',
                    'type' => 'number',
                    'default' => 15
                ),
                array(
                    'title' => __('Margin right', 'document-engine'),
                    'desc' => __('Margin right in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_margin_right',
                    'type' => 'number',
                    'default' => 15
                ),
                array(
                    'title' => __('Margin Top', 'document-engine'),
                    'desc' => __('Margin Top in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_margin_top',
                    'type' => 'number',
                    'default' => 50
                ),
                array(
                    'title' => __('Margin Bottom', 'document-engine'),
                    'desc' => __('Margin Bottom in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_margin_bottom',
                    'type' => 'number',
                    'default' => 50
                ),
                array(
                    'title' => __('Margin Header', 'document-engine'),
                    'desc' => __('Margin Header in (pt)', 'document-engine'),
                    'id' => 'document_engine_pdf_page_margin_header',
                    'type' => 'number',
                    'default' => 15
                ),
                array(
                    'title' => __('Enable PDF protection', 'document-engine'),
                    'desc' => __(' Encrypts PDF file and respects permissions given below', 'document-engine'),
                    'id' => 'document_engine_pdf_page_enable_protection',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'title' => __('Protected PDF permissions', 'document-engine'),
                    'id' => 'document_engine_pdf_page_protected_permissions',
                    'type' => 'multicheckbox',
                    'options' => document_engine_get_available_pdf_permissions()
                ),
                array(
                    'title' => __('Keep columns', 'document-engine'),
                    'desc' => __(' Columns will be written successively ([document_engine_pdf_columns] shortcode). i.e. there will be no balancing of the length of columns.', 'document-engine'),
                    'id' => 'document_engine_pdf_page_keep_columns',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_pdf_page_options',
                ),

            );

        } else if ('style' === $current_section) {
            $settings = array(
                array(
                    'title' => __('PDF Style', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_pdf_style',
                ),
                array(
                    'title' => __('Use current theme\'s CSS', 'document-engine'),
                    'desc' => __(' Includes the stylesheet from current theme, but is overridden by PDF Custom CSS and plugins adding its own stylesheets.', 'document-engine'),
                    'id' => 'document_engine_pdf_use_theme_style',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),

                array(
                    'title' => __('Custom CSS', 'document-engine'),
                    'id' => 'document_engine_pdf_custom_css',
                    'type' => 'textarea',
                    'class' => 'document-engine-pdf-custom-css',
                    'default' => ''
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_pdf_style',
                ),

            );

        } else if ('header_footer' === $current_section) {
            $settings = array(
                array(
                    'title' => __('PDF Header', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_pdf_header_options',
                ),
                array(
                    'title' => __('Header Logo', 'document-engine'),
                    'id' => 'document_engine_pdf_header_logo',
                    'type' => 'image',
                    'default' => '0',
                ),
                array(
                    'title' => __('Show Post Title', 'document-engine'),
                    'desc' => __('Show post title on pdf header', 'document-engine'),
                    'id' => 'document_engine_pdf_header_show_post_title',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'title' => __('Show Page Number', 'document-engine'),
                    'desc' => __('Show page number on pdf header', 'document-engine'),

                    'id' => 'document_engine_pdf_header_show_pagination',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'title' => __('Header font Size', 'document-engine'),
                    'desc' => __('Font Size in (pt). Leave it blank to use default page font size', 'document-engine'),
                    'id' => 'document_engine_pdf_header_font_size',
                    'type' => 'number',
                    'default' => ''
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_pdf_header_options',
                ),

                array(
                    'title' => __('PDF Page Footer', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_pdf_footer_options',
                ),

                array(
                    'title' => __('Footer text', 'document-engine'),
                    'id' => 'document_engine_pdf_footer_text',
                    'desc' => __('HTML tags supports: a, br, em, strong, hr, p, h1 to h4', 'document-engine'),
                    'type' => 'textarea',
                    'allowed_html' => array(
                        'a' => array(
                            'href' => array(),
                            'target' => array()
                        ),
                        'br' => array(),
                        'em' => array(),
                        'strong' => array(),
                        'hr' => array(),
                        'p' => array(),
                        'h1' => array(),
                        'h2' => array(),
                        'h3' => array(),
                        'h4' => array(),
                        'h5' => array(),
                        'h6' => array(),
                    )
                ),
                array(
                    'title' => __('Show Post Title', 'document-engine'),
                    'desc' => __('Show post title on pdf footer', 'document-engine'),
                    'id' => 'document_engine_pdf_footer_show_post_title',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'title' => __('Show Page Number', 'document-engine'),
                    'desc' => __('Show page number on pdf footer', 'document-engine'),
                    'id' => 'document_engine_pdf_footer_show_pagination',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'title' => __('Footer font Size', 'document-engine'),
                    'desc' => __('Font Size in (pt). Leave it blank to use default page font size', 'document-engine'),
                    'id' => 'document_engine_pdf_footer_font_size',
                    'type' => 'number',
                    'default' => ''
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_pdf_footer_options',
                ),

            );

        } else {
            $post_types_arr = document_engine_get_available_post_types();

            $settings = array(
                array(
                    'title' => __('PDF Button', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_pdf_button_options',
                ),
                array(
                    'title' => __('Button text', 'document-engine'),
                    'id' => 'document_engine_pdf_button_text',
                    'type' => 'text',
                    'default' => 'Download PDF'
                ),
                array(
                    'title' => __('Post types to apply', 'document-engine'),
                    'id' => 'document_engine_pdf_post_type',
                    'type' => 'multicheckbox',
                    'options' => $post_types_arr
                ),
                array(
                    'title' => __('Action', 'document-engine'),
                    'id' => 'document_engine_pdf_button_action',
                    'type' => 'select',
                    'options' => array(
                        'open' => __('Open PDF in new window', 'document-engine'),
                        'download' => __('Download PDF directly', 'document-engine')
                    ),
                    'default' => 'download'
                ),
                array(
                    'title' => __('Button Position', 'document-engine'),
                    'id' => 'document_engine_pdf_button_position',
                    'type' => 'select',
                    'options' => array(
                        'before' => __('Before Content', 'document-engine'),
                        'after' => __('After Content', 'document-engine')
                    ),
                    'default' => 'before'
                ),
                array(
                    'title' => __('Button Alignment', 'document-engine'),
                    'id' => 'document_engine_pdf_button_alignment',
                    'type' => 'select',
                    'options' => array(
                        'left' => __('Left', 'document-engine'),
                        'right' => __('Right', 'document-engine'),
                        'center' => __('Center', 'document-engine')
                    ),
                    'default' => 'right'
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_pdf_button_options',
                ),

            );
        }

        return apply_filters('document_engine_get_settings_' . $this->id, $settings, $current_section);
    }
}
