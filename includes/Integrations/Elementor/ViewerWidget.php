<?php

namespace MatrixAddons\DocumentEngine\Integrations\Elementor;

use MatrixAddons\DocumentEngine\Viewer\Viewer;

defined('ABSPATH') || exit;

class ViewerWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'dengine-viewer';
    }

    public function get_title()
    {
        return __('PDF Viewer', 'document-engine');
    }

    public function get_icon()
    {
        return 'eicon-document-file';
    }

    public function get_style_depends()
    {
        return array('document-engine-documents');
    }

    public function get_script_depends()
    {
        return array('document-engine-viewer');
    }

    public function get_categories()
    {
        return array('document-engine');
    }

    public function get_keywords()
    {
        return array('pdf', 'viewer', 'embed', 'document');
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', array('label' => __('PDF', 'document-engine')));
        $this->add_control('source', array('label' => __('Source', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'document', 'options' => array('document' => __('A document', 'document-engine'), 'media' => __('Media Library file', 'document-engine'), 'url' => __('Web address', 'document-engine'))));
        $this->add_control('document', array('label' => __('Document', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'options' => Widgets::document_options(), 'label_block' => true, 'condition' => array('source' => 'document')));
        $this->add_control('file', array('label' => __('PDF file', 'document-engine'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => array('application/pdf'), 'condition' => array('source' => 'media')));
        $this->add_control('url', array('label' => __('PDF address', 'document-engine'), 'type' => \Elementor\Controls_Manager::URL, 'options' => false, 'condition' => array('source' => 'url')));
        $this->add_control('height', array('label' => __('Height', 'document-engine'), 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => get_option('document_engine_viewer_height', '800px')));
        $this->add_control('download', array('label' => __('Download button', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => array('' => __('Default', 'document-engine'), 'yes' => __('Show', 'document-engine'), 'no' => __('Hide', 'document-engine'))));
        $this->add_control('print', array('label' => __('Print button', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => array('' => __('Default', 'document-engine'), 'yes' => __('Show', 'document-engine'), 'no' => __('Hide', 'document-engine'))));
        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        $tri = function ($value) {
            return $value === 'yes' ? true : ($value === 'no' ? false : null);
        };
        $html = Viewer::render(array(
            'documentId' => $s['source'] === 'document' ? absint($s['document']) : 0,
            'fileId' => $s['source'] === 'media' && !empty($s['file']['id']) ? absint($s['file']['id']) : 0,
            'url' => $s['source'] === 'url' && !empty($s['url']['url']) ? (string)$s['url']['url'] : '',
            'height' => (string)$s['height'],
            'download' => $tri($s['download']),
            'print' => $tri($s['print']),
        ));
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shared renderer.
    }
}
