<?php

namespace MatrixAddons\DocumentEngine\Integrations\Elementor;

use MatrixAddons\DocumentEngine\Library\Library;

defined('ABSPATH') || exit;

class DownloadWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'dengine-download';
    }

    public function get_title()
    {
        return __('Document Download', 'document-engine');
    }

    public function get_icon()
    {
        return 'eicon-download-button';
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
        return array('download', 'file', 'button', 'document');
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', array('label' => __('Download', 'document-engine')));
        $this->add_control('document', array('label' => __('Document', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT2, 'options' => Widgets::document_options(), 'label_block' => true));
        $this->add_control('style', array('label' => __('Style', 'document-engine'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'card', 'options' => array('card' => __('Card', 'document-engine'), 'button' => __('Button', 'document-engine'))));
        $this->add_control('label', array('label' => __('Button text', 'document-engine'), 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => __('Download', 'document-engine')));
        $this->add_control('show_meta', array('label' => __('Show file type and size', 'document-engine'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes'));
        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        $html = Library::render_download(array(
            'documentId' => absint($s['document']),
            'variant' => $s['style'],
            'label' => (string)$s['label'],
            'showMeta' => $s['show_meta'] === 'yes',
        ));
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shared renderer.
    }
}
