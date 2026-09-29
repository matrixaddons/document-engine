<?php

namespace MatrixAddons\DocumentEngine\Integrations\Elementor;

use MatrixAddons\DocumentEngine\Library\Lists;

defined('ABSPATH') || exit;

class ListWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'dengine-list';
    }

    public function get_title()
    {
        return __('Document List', 'document-engine');
    }

    public function get_icon()
    {
        return 'eicon-bullet-list';
    }

    public function get_style_depends()
    {
        return array('document-engine-documents');
    }

    public function get_script_depends()
    {
        return array();
    }

    public function get_categories()
    {
        return array('document-engine');
    }

    public function get_keywords()
    {
        return array('popular', 'recent', 'related', 'latest', 'documents');
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', array('label' => __('List', 'document-engine')));
        $this->add_control('mode', array('label' => __('Show', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'recent', 'options' => Lists::modes()));
        $this->add_control('count', array('label' => __('Number of documents', 'document-engine'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 20, 'default' => 5));
        $this->add_control('title', array('label' => __('Heading', 'document-engine'), 'type' => \Elementor\Controls_Manager::TEXT));
        $this->add_control('categories', array('label' => __('Only these categories', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => Widgets::category_options(), 'label_block' => true));
        $this->add_control('document', array('label' => __('Related to', 'document-engine'), 'description' => __('Leave empty on document pages.', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'options' => Widgets::document_options(), 'label_block' => true, 'condition' => array('mode' => 'related')));
        $this->add_control('show_meta', array('label' => __('Show file type, size and date', 'document-engine'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes'));
        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        $html = Lists::render(array(
            'mode' => $s['mode'],
            'count' => $s['count'],
            'title' => (string)$s['title'],
            'categories' => (array)$s['categories'],
            'documentId' => absint($s['document'] ?? 0),
            'showMeta' => $s['show_meta'] === 'yes',
        ));
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shared renderer.
    }
}
