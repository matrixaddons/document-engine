<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('document_engine_pdf_is_valid_post_type')) {
    function document_engine_pdf_is_valid_post_type()
    {
        $pdf_post_id = sanitize_text_field(get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG));

        if (absint($pdf_post_id) < 1) {

            return false;
        }
        if (get_post_status($pdf_post_id) !== 'publish') {
            return false;
        }
        return true;

    }
}
if (!function_exists('document_engine_get_available_post_types')) {
    function document_engine_get_available_post_types()
    {
        $args = array(
            'public' => true,
            '_builtin' => false
        );

        $post_types = get_post_types($args);

        $post_types_updated = array(
            array('id' => 'post', 'title' => __('post', 'document-engine')),
            array('id' => 'page', 'title' => __('page', 'document-engine')),
            array('id' => 'attachment', 'title' => __('attachment', 'document-engine')),
        );

        foreach ($post_types as $post_type) {

            $post_types_updated[] = array('id' => $post_type, 'title' => $post_type);


        }

        return $post_types_updated;

    }
}

if (!function_exists('document_engine_get_available_pdf_permissions')) {
    function document_engine_get_available_pdf_permissions()
    {
        return array(
            array('id' => 'copy', 'title' => 'Copy'),
            array('id' => 'print', 'title' => 'Print'),
            array('id' => 'print-highres', 'title' => 'Print Highres'),
            array('id' => 'modify', 'title' => 'Modify'),
            array('id' => 'annot-forms', 'title' => 'Annot Forms'),
            array('id' => 'fill-forms', 'title' => 'Fill Forms'),
            array('id' => 'extract', 'title' => 'Extract'),
            array('id' => 'assemble', 'title' => 'Assemble')
        );

    }
}

if (!function_exists('document_engine_pdf_css')) {

    function document_engine_pdf_css()
    {
        include_once DOCUMENT_ENGINE_ABSPATH . 'includes/Helpers/css.php';

    }
}
if (!function_exists('document_engine_get_attachment_image_url')) {
    function document_engine_get_attachment_image_url($image_id)
    {
        $src = $image_id > 0 ? wp_get_attachment_image_url($image_id, 'full') : '';

        return apply_filters('document_engine_get_attachment_image_url', $src, $image_id);
    }
}