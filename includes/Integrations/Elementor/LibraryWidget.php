<?php

namespace MatrixAddons\DocumentEngine\Integrations\Elementor;

use MatrixAddons\DocumentEngine\Library\Library;
use MatrixAddons\DocumentEngine\Library\Query;

defined('ABSPATH') || exit;

class LibraryWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'dengine-library';
    }

    public function get_title()
    {
        return __('Document Library', 'document-engine');
    }

    public function get_icon()
    {
        return 'eicon-table';
    }

    public function get_style_depends()
    {
        return array('document-engine-documents');
    }

    public function get_script_depends()
    {
        return array('document-engine-library', 'document-engine-viewer');
    }

    public function get_categories()
    {
        return array('document-engine');
    }

    public function get_keywords()
    {
        return array('document', 'library', 'files', 'download', 'pdf');
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', array('label' => __('Library', 'document-engine')));
        $this->add_control('layout', array('label' => __('Layout', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => get_option('document_engine_library_layout', 'table'), 'options' => Query::layouts()));
        $this->add_control('categories', array('label' => __('Categories (empty = all)', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => Widgets::category_options(), 'label_block' => true));
        $this->add_control('columns', array('label' => __('Columns', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => Query::columns(), 'default' => array('title', 'category', 'type', 'size', 'date', 'actions'), 'label_block' => true, 'condition' => array('layout' => 'table')));
        $this->add_control('per_page', array('label' => __('Documents per page', 'document-engine'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 100, 'default' => absint(get_option('document_engine_library_per_page', 20)) ?: 20));
        $this->add_control('search', array('label' => __('Search box', 'document-engine'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes'));
        $this->add_control('filters', array('label' => __('Filters', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => array('category' => __('Category', 'document-engine'), 'tag' => __('Tag', 'document-engine'), 'type' => __('File type', 'document-engine'), 'year' => __('Year', 'document-engine'), 'author' => __('Author', 'document-engine'), 'sort' => __('Sort order', 'document-engine')), 'default' => array('category', 'type'), 'label_block' => true));
        $this->add_control('multi_filters', array('label' => __('Let visitors pick several', 'document-engine'), 'description' => __('Category, tag and file type become checkboxes.', 'document-engine'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => ''));
        $this->add_control('orderby', array('label' => __('Order by', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'date', 'options' => array('date' => __('Date', 'document-engine'), 'title' => __('Title', 'document-engine'), 'modified' => __('Last updated', 'document-engine'), 'downloads' => __('Downloads', 'document-engine'), 'menu_order' => __('Custom order', 'document-engine'))));
        $this->add_control('order', array('label' => __('Direction', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'desc', 'options' => array('desc' => __('Descending', 'document-engine'), 'asc' => __('Ascending', 'document-engine'))));
        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        $html = Library::render(array(
            'id' => 'el' . substr(preg_replace('/[^a-z0-9]/', '', strtolower((string)$this->get_id())), 0, 8),
            'layout' => $s['layout'],
            'categories' => (array)$s['categories'],
            'columns' => (array)($s['columns'] ?: array()),
            'per_page' => $s['per_page'],
            'search' => $s['search'] === 'yes',
            'filters' => (array)$s['filters'],
            'multi_filters' => isset($s['multi_filters']) && $s['multi_filters'] === 'yes',
            'orderby' => $s['orderby'],
            'order' => $s['order'],
        ));
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shared renderer.
    }
}
