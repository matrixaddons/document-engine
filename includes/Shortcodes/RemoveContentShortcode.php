<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class RemoveContentShortcode
{

    /**
     * Get the shortcode content.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function get($atts, $content = null)
    {
        return Shortcodes::shortcode_wrapper(array(__CLASS__, 'output'), $atts, $content);
    }

    /**
     * [document_engine_pdf_remove tag="gallery"]content to remove[/document_engine_pdf_remove]
     * This shortcode is used remove pieces of content in the generated PDF
     * @return string
     */
    public static function output($atts, $content = null)
    {
        $shortcode_atts = shortcode_atts(array(
            'tag' => ''
        ), $atts);

        $tag = sanitize_text_field($shortcode_atts['tag']);

        if ($tag !== '' && !document_engine_pdf_is_valid_post_type()) {
            remove_shortcode($tag);
            add_shortcode($tag, '__return_false');
            echo do_shortcode($content);

        } else if (document_engine_pdf_is_valid_post_type()) {

            echo '';
        } else {

            echo do_shortcode($content);
        }

    }
}
