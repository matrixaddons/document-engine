<?php
/**
 * Public helpers for documents, libraries and the viewer.
 */

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;

defined('ABSPATH') || exit;

if (!function_exists('document_engine_get_document')) {
    /**
     * @param int|WP_Post $post
     * @return Document|null
     */
    function document_engine_get_document($post)
    {
        return Document::get($post);
    }
}

if (!function_exists('document_engine_user_can_access')) {
    function document_engine_user_can_access($document, $user_id = null, $context = 'download')
    {
        $document = $document instanceof Document ? $document : Document::get($document);
        return $document ? FileServer::can_access($document, $user_id, $context) : false;
    }
}

if (!function_exists('document_engine_file_type_group')) {
    /**
     * Groups extensions for filters and icon colors.
     */
    function document_engine_file_type_group($ext)
    {
        $groups = apply_filters('document_engine_file_type_groups', array(
            'pdf' => array('pdf'),
            'word' => array('doc', 'docx', 'odt', 'rtf', 'pages', 'txt', 'md'),
            'sheet' => array('xls', 'xlsx', 'ods', 'csv', 'numbers', 'tsv'),
            'slides' => array('ppt', 'pptx', 'odp', 'key'),
            'image' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'tif', 'tiff', 'psd', 'ai', 'eps'),
            'audio' => array('mp3', 'wav', 'm4a', 'ogg', 'flac'),
            'video' => array('mp4', 'mov', 'webm', 'avi', 'mkv'),
            'archive' => array('zip', 'rar', '7z', 'gz', 'tar'),
        ));
        foreach ($groups as $group => $extensions) {
            if (in_array($ext, $extensions, true)) {
                return $group;
            }
        }
        return 'other';
    }
}

if (!function_exists('document_engine_file_type_groups_labels')) {
    function document_engine_file_type_groups_labels()
    {
        return apply_filters('document_engine_file_type_group_labels', array(
            'pdf' => __('PDF', 'document-engine'),
            'word' => __('Documents', 'document-engine'),
            'sheet' => __('Spreadsheets', 'document-engine'),
            'slides' => __('Presentations', 'document-engine'),
            'image' => __('Images', 'document-engine'),
            'audio' => __('Audio', 'document-engine'),
            'video' => __('Video', 'document-engine'),
            'archive' => __('Archives', 'document-engine'),
            'other' => __('Other', 'document-engine'),
        ));
    }
}

if (!function_exists('document_engine_file_icon')) {
    /**
     * Inline SVG file icon labelled with the extension.
     */
    function document_engine_file_icon($ext, $class = '')
    {
        $ext = substr(strtoupper(preg_replace('/[^a-z0-9]/i', '', (string)$ext)), 0, 4);
        $group = document_engine_file_type_group(strtolower($ext));
        $label = $ext !== '' ? $ext : 'FILE';

        $svg = '<svg class="dengine-icon dengine-icon--' . esc_attr($group) . ' ' . esc_attr($class) . '" width="32" height="38" viewBox="0 0 40 48" aria-hidden="true" focusable="false">'
            . '<path class="dengine-icon__page" d="M4 0h22l14 14v30a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"/>'
            . '<path class="dengine-icon__fold" d="M26 0l14 14H30a4 4 0 0 1-4-4z"/>'
            . '<text x="20" y="36" text-anchor="middle">' . esc_html($label) . '</text></svg>';

        return apply_filters('document_engine_file_icon', $svg, $ext, $group);
    }
}

if (!function_exists('document_engine_ui_icon')) {
    /**
     * Small UI icons (download, view, search…) as inline SVG.
     */
    function document_engine_ui_icon($name)
    {
        $paths = array(
            'download' => 'M12 3v11m0 0l-4-4m4 4l4-4M5 19h14',
            'view' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
            'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm10 2l-4.35-4.35',
            'folder' => 'M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
            'lock' => 'M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4',
            'external' => 'M14 4h6v6m0-6L10 14M18 14v6H4V6h6',
        );
        if (!isset($paths[$name])) {
            return '';
        }
        return '<svg class="dengine-ui-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="' . esc_attr($paths[$name]) . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}

if (!function_exists('document_engine_enqueue_frontend')) {
    /**
     * Loads the document styles (and optionally scripts) only on pages that render our output.
     */
    function document_engine_enqueue_frontend($scripts = array())
    {
        wp_enqueue_style('document-engine-documents');
        foreach ((array)$scripts as $handle) {
            wp_enqueue_script($handle);
        }
    }
}
