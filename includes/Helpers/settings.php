<?php
if (!function_exists('document_engine_pdf_button_text')) {
    function document_engine_pdf_button_text()
    {
        return sanitize_text_field(get_option('document_engine_pdf_button_text', __('Download PDF', 'document-engine')));
    }
}
if (!function_exists('document_engine_pdf_post_type')) {
    function document_engine_pdf_post_type()
    {

        $post_types = get_option('document_engine_pdf_post_type', array());

        $post_types = is_array($post_types) ? $post_types : array();

        return $post_types;
    }
}

if (!function_exists('document_engine_pdf_button_action')) {
    function document_engine_pdf_button_action()
    {
        return sanitize_text_field(get_option('document_engine_pdf_button_action', 'download'));
    }
}
if (!function_exists('document_engine_pdf_button_position')) {
    function document_engine_pdf_button_position()
    {
        return sanitize_text_field(get_option('document_engine_pdf_button_position', 'before'));
    }
}
if (!function_exists('document_engine_pdf_button_alignment')) {
    function document_engine_pdf_button_alignment()
    {
        return sanitize_text_field(get_option('document_engine_pdf_button_alignment', 'right'));
    }
}
if (!function_exists('document_engine_pdf_header_logo')) {
    function document_engine_pdf_header_logo($url = true)
    {
        $image_id = absint(get_option('document_engine_pdf_header_logo', 0));

        if ($image_id < 1) {
            return null;
        }
        if (!$url) {
            return $image_id;
        }
        $image_url = document_engine_get_attachment_image_url($image_id);
        
        if ($image_url !== '') {
            return $image_url;
        }
        return null;
    }
}

if (!function_exists('document_engine_pdf_header_show_post_title')) {
    function document_engine_pdf_header_show_post_title()
    {
        return 'yes' === (get_option('document_engine_pdf_header_show_post_title', 'no'));
    }
}
if (!function_exists('document_engine_pdf_header_show_pagination')) {
    function document_engine_pdf_header_show_pagination()
    {
        return 'yes' === (get_option('document_engine_pdf_header_show_pagination', 'no'));
    }
}
if (!function_exists('document_engine_pdf_header_show_pagination')) {
    function document_engine_pdf_header_show_pagination()
    {
        return 'yes' === (get_option('document_engine_pdf_header_show_pagination', 'no'));
    }
}
if (!function_exists('document_engine_pdf_footer_text')) {
    function document_engine_pdf_footer_text()
    {
        return wp_kses(get_option('document_engine_pdf_footer_text', ''), array(
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
        ));
    }
}
if (!function_exists('document_engine_pdf_footer_show_post_title')) {
    function document_engine_pdf_footer_show_post_title()
    {
        return 'yes' === (get_option('document_engine_pdf_footer_show_post_title', 'no'));
    }
}
if (!function_exists('document_engine_pdf_footer_show_pagination')) {
    function document_engine_pdf_footer_show_pagination()
    {
        return 'yes' === (get_option('document_engine_pdf_footer_show_pagination', 'no'));
    }
}

if (!function_exists('document_engine_pdf_page_orientation')) {
    function document_engine_pdf_page_orientation()
    {
        return sanitize_text_field(get_option('document_engine_pdf_page_orientation', 'vertical'));
    }
}
if (!function_exists('document_engine_pdf_page_font_size')) {
    function document_engine_pdf_page_font_size()
    {
        return absint(get_option('document_engine_pdf_page_font_size', '12'));
    }
}

if (!function_exists('document_engine_pdf_page_margin_left')) {
    function document_engine_pdf_page_margin_left()
    {
        return absint(get_option('document_engine_pdf_page_margin_left', '15'));
    }
}
if (!function_exists('document_engine_pdf_page_margin_right')) {
    function document_engine_pdf_page_margin_right()
    {
        return absint(get_option('document_engine_pdf_page_margin_right', '15'));
    }
}
if (!function_exists('document_engine_pdf_page_margin_top')) {
    function document_engine_pdf_page_margin_top()
    {
        return absint(get_option('document_engine_pdf_page_margin_top', '50'));
    }
}
if (!function_exists('document_engine_pdf_page_margin_bottom')) {
    function document_engine_pdf_page_margin_bottom()
    {
        return absint(get_option('document_engine_pdf_page_margin_bottom', '50'));
    }
}
if (!function_exists('document_engine_pdf_page_margin_header')) {
    function document_engine_pdf_page_margin_header()
    {
        return absint(get_option('document_engine_pdf_page_margin_header', '15'));
    }
}
if (!function_exists('document_engine_pdf_page_protected_permissions')) {
    function document_engine_pdf_page_protected_permissions()
    {
        $permissions = (get_option('document_engine_pdf_page_protected_permissions', array()));
        return is_array($permissions) ? $permissions : array();
    }
}

if (!function_exists('document_engine_pdf_page_keep_columns')) {
    function document_engine_pdf_page_keep_columns()
    {
        return 'yes' === (get_option('document_engine_pdf_page_keep_columns', 'no'));
    }
}
if (!function_exists('document_engine_pdf_page_enable_protection')) {
    function document_engine_pdf_page_enable_protection()
    {
        return 'yes' === (get_option('document_engine_pdf_page_enable_protection', 'no'));
    }
}
if (!function_exists('document_engine_pdf_use_theme_style')) {
    function document_engine_pdf_use_theme_style()
    {
        return 'yes' === (get_option('document_engine_pdf_use_theme_style', 'no'));
    }
}
if (!function_exists('document_engine_pdf_custom_css')) {
    function document_engine_pdf_custom_css()
    {
        return sanitize_text_field(get_option('document_engine_pdf_custom_css', ''));
    }
}
if (!function_exists('document_engine_pdf_header_font_size')) {
    function document_engine_pdf_header_font_size()
    {
        return absint(get_option('document_engine_pdf_header_font_size', 0));
    }
}
if (!function_exists('document_engine_pdf_footer_font_size')) {
    function document_engine_pdf_footer_font_size()
    {
        return absint(get_option('document_engine_pdf_footer_font_size', 0));
    }
}