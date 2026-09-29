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
        // Only publicly viewable post types can be exported, and only when the visitor could read the post itself.
        $post_type = get_post_type($pdf_post_id);
        // Documents have their own files; their descriptions are never exported (access rules live on the file).
        $can_generate = $post_type && !in_array($post_type, array('dengine_document', 'attachment'), true) && is_post_type_viewable($post_type) && !post_password_required($pdf_post_id);
        // Only where the site offers PDFs: a post type ticked in Settings → Post to PDF, or a post with a Save as PDF block/shortcode.
        if ($can_generate) {
            // Same reading as the button itself (1.x and 2.x): the ticked post types are the option's keys.
            $enabled = array_map('strval', array_keys(document_engine_pdf_post_type()));
            $post = get_post($pdf_post_id);
            $offered = in_array($post_type, $enabled, true)
                || has_block('document-engine/pdf-button', $post)
                || has_shortcode((string)$post->post_content, apply_filters('document_engine_pdf_button_shortcode_tag', 'document_engine_pdf_button'));
            $can_generate = (bool)$offered;
        }

        return (bool)apply_filters('document_engine_pdf_can_generate', $can_generate, absint($pdf_post_id));

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
            array('id' => 'copy', 'title' => __('Copy text', 'document-engine')),
            array('id' => 'print', 'title' => __('Print', 'document-engine')),
            array('id' => 'print-highres', 'title' => __('Print in high quality', 'document-engine')),
            array('id' => 'modify', 'title' => __('Edit', 'document-engine')),
            array('id' => 'annot-forms', 'title' => __('Add comments', 'document-engine')),
            array('id' => 'fill-forms', 'title' => __('Fill in forms', 'document-engine')),
            array('id' => 'extract', 'title' => __('Extract for accessibility', 'document-engine')),
            array('id' => 'assemble', 'title' => __('Rearrange pages', 'document-engine'))
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