<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;


use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class General extends Setting_Base
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'general';
        $this->label = __('General', 'document-engine');

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
            '' => __('CSS Classes', 'document-engine'),
            'layouts' => __('Layouts', 'document-engine'),
            'colors' => __('Colors', 'document-engine'),
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
        if ('layouts' === $current_section) {
            $settings = array(
                array(
                    'title' => __('Layout Settings', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_templates_options',
                ),
                array(
                    'title' => __('Tab Layout for tour page', 'document-engine'),
                    'desc' => __('Tab layout for single tour page', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_setting_layouts_single_tour_tab_layout',
                    'type' => 'select',
                    'options' => array(
                        '' => __('Tab Style Layout', 'document-engine'),
                        'heading_and_content' => __('Heading & Content Style Tab', 'document-engine')
                    ),
                    'default' => ''
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_templates_options',
                ),

            );

        } else if ('colors' === $current_section) {
            $settings = array(
                array(
                    'title' => __('Color Settings', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_design_color_options',
                ),
                array(
                    'title' => __('Primary Color', 'document-engine'),
                    'desc' => __('Primary Color of Yatra Plugin', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_design_primary_color',
                    'type' => 'color',
                    'default' => ''
                ),
                array(
                    'title' => __('Available For Booking Color', 'document-engine'),
                    'desc' => __('Background Color for Available for Booking', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_available_for_booking_color',
                    'type' => 'color',
                    'default' => '#2f582b'
                ),
                array(
                    'title' => __('Available For Enquiry Color', 'document-engine'),
                    'desc' => __('Background Color for Available for Enquiry Only', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_available_for_enquiry_only_color',
                    'type' => 'color',
                    'default' => '#008bb5'
                ),
                array(
                    'title' => __('Not Available Color', 'document-engine'),
                    'desc' => __('Background Color for Not available for booking & enquiry', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_not_available_for_booking_enquiry_color',
                    'type' => 'color',
                    'default' => '#aaa'
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_design_color_options',
                ),

            );

        } else {
            $settings = array(
                array(
                    'title' => __('CSS Classes Settings', 'document-engine'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'document_engine_css_classes_options',
                ),
                array(
                    'title' => __('Page Container Class', 'document-engine'),
                    'desc' => __('Container class for all page templates for yatra plugin.', 'document-engine'),
                    'desc_tip' => true,
                    'id' => 'document_engine_page_container_class',
                    'type' => 'text',
                ),
                array(
                    'type' => 'sectionend',
                    'id' => 'document_engine_css_classes_options',
                ),

            );
        }

        return apply_filters('document_engine_get_settings_' . $this->id, $settings, $current_section);
    }
}
